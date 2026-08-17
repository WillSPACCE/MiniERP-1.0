<?php

// Classe responsável por criar e reutilizar a conexão com o banco.
// Ela faz cache da conexão em uma propriedade estática para evitar abrir várias vezes.
class Database
{
    // Guarda a conexão ativa para reutilizar em toda a aplicação.
    private static ?PDO $connection = null;
    // Quando definido, força a conexão a usar este database name (tenant-specific)
    private static ?string $tenantDbName = null;

    // Retorna a conexão pronta para uso.
    public static function getConnection(): PDO
    {
        // Se a conexão já foi criada, retorna a mesma.
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // Lê as configurações globais do projeto.
        $config = require __DIR__ . '/../config.php';
        $driver = $config['db']['driver'] ?? 'sqlite';

        // Se o projeto estiver configurado para usar MySQL.
        if ($driver === 'mysql') {
            $dbToUse = self::$tenantDbName ?? $config['db']['database'];
            // Garante que o database exista
            self::ensureDatabaseExistsByName($config, $dbToUse);

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $config['db']['host'],
                $config['db']['port'],
                $dbToUse
            );

            self::$connection = new PDO($dsn, $config['db']['username'], $config['db']['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            self::initializeSchema();

            return self::$connection;
        }

        // Configuração padrão: SQLite local em arquivo .sqlite.
        $sqlitePath = $config['db']['sqlite_path'];
        $directory = dirname($sqlitePath);

        // Garante que a pasta do banco exista.
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        // Cria a conexão com SQLite usando o arquivo de banco.
        self::$connection = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Executa o schema e os dados iniciais quando a base for criada.
        self::initializeSchema();

        return self::$connection;
    }

    // Garante que o banco exista antes de tentar conectar ao schema.
    private static function ensureDatabaseExists(array $config): void
    {
        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config['db']['host'],
            $config['db']['port']
        );

        $adminConnection = new PDO($serverDsn, $config['db']['username'], $config['db']['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $database = $config['db']['database'];
        $adminConnection->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Garante que um database com nome específico exista
    private static function ensureDatabaseExistsByName(array $config, string $dbName): void
    {
        $serverDsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $config['db']['host'],
            $config['db']['port']
        );

        $adminConnection = new PDO($serverDsn, $config['db']['username'], $config['db']['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $adminConnection->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    // Define explicitamente o nome do database do tenant para as próximas conexões.
    public static function setTenantDbName(?string $dbName): void
    {
        self::$tenantDbName = $dbName;
        // força recriação da conexão na próxima chamada
        self::$connection = null;
    }

    // Cria as tabelas e insere dados iniciais do sistema.
    private static function initializeSchema(): void
    {
        $schemaPath = __DIR__ . '/../database/schema.sql';
        $seedPath = __DIR__ . '/../database/seeds.sql';

        if (!file_exists($schemaPath)) {
            throw new RuntimeException('Arquivo de schema não encontrado: ' . $schemaPath);
        }

        // Lê o SQL do schema e executa instruções uma por uma.
        $schemaSql = file_get_contents($schemaPath);
        foreach (self::splitSqlStatements($schemaSql) as $statement) {
            if (trim($statement) !== '') {
                self::$connection->exec($statement);
            }
        }

        // Se existir um arquivo de dados iniciais, também executa.
        if (file_exists($seedPath)) {
            $seedSql = file_get_contents($seedPath);
            foreach (self::splitSqlStatements($seedSql) as $statement) {
                if (trim($statement) !== '') {
                    self::$connection->exec($statement);
                }
            }
        }

        // Garante um usuário administrador inicial (senha padrão: 'admin')
        try {
                $row = self::$connection->query("SELECT COUNT(*) as c FROM usuarios")->fetch();
            $count = (int) ($row['c'] ?? 0);
                if ($count === 0) {
                    $hash = password_hash('admin', PASSWORD_DEFAULT);
                $stmt = self::$connection->prepare('INSERT INTO usuarios (nome, email, senha, role, status) VALUES (:nome, :email, :senha, :role, :status)');
                $stmt->execute([
                    'nome' => 'Administrador',
                    'email' => 'admin@localhost',
                        'senha' => password_hash('admin', PASSWORD_DEFAULT),
                    'role' => 'admin',
                    'status' => 'ativo',
                ]);
            }
        } catch (Throwable $e) {
            // Se a tabela não existir (por algum motivo), ignora.
        }

        // Tenta aplicar migrações simples para adicionar colunas company_id quando necessário.
        try {
            // Produtos
            self::$connection->exec('ALTER TABLE produtos ADD COLUMN company_id INT NULL');
        } catch (Throwable $e) {
            // ignora se já existe
        }

        try {
            // Usuários
            self::$connection->exec('ALTER TABLE usuarios ADD COLUMN company_id INT NULL');
        } catch (Throwable $e) {
            // ignora se já existe
        }

        // Importa empresas do antigo arquivo JSON (data/empresas.json) para a tabela companies,
        // caso existam registros e a tabela tenants esteja vazia.
        try {
            $tenantsCount = 0;
            $row = self::$connection->query('SELECT COUNT(*) AS c FROM tenants')->fetch();
            $tenantsCount = (int) ($row['c'] ?? 0);
            if ($tenantsCount === 0) {
                // Try migrate from data/empresas.json
                $dataFile = __DIR__ . '/../data/empresas.json';
                if (file_exists($dataFile)) {
                    $json = json_decode(file_get_contents($dataFile), true) ?: [];
                    $insert = self::$connection->prepare('INSERT INTO tenants (uuid, nome_fantasia, razao_social, cnpj, slug, municipio, regime, data) VALUES (:uuid, :nome_fantasia, :razao_social, :cnpj, :slug, :municipio, :regime, :data)');
                    foreach ($json as $item) {
                        $nome = trim((string)($item['nome_fantasia'] ?? $item['apelido'] ?? ''));
                        $slug = preg_replace('/[^a-z0-9]+/','-', strtolower(trim($nome)));
                        if ($slug === '') $slug = 'tenant';
                        // ensure uniqueness by appending timestamp if necessary
                        $slug = $slug . '-' . substr(bin2hex(random_bytes(4)),0,6);
                        $insert->execute([
                            'uuid' => bin2hex(random_bytes(8)),
                            'nome_fantasia' => $nome,
                            'razao_social' => $item['razao_social'] ?? '',
                            'cnpj' => preg_replace('/\D/','', $item['cnpj'] ?? ''),
                            'slug' => $slug,
                            'municipio' => $item['municipio'] ?? '',
                            'regime' => $item['regime'] ?? '',
                            'data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
                // If companies table exists, try migrate those as well
                try {
                    $row = self::$connection->query("SHOW TABLES LIKE 'companies'")->fetch();
                    if ($row) {
                        $rows = self::$connection->query('SELECT * FROM companies')->fetchAll();
                        $insert = self::$connection->prepare('INSERT INTO tenants (uuid, nome_fantasia, razao_social, cnpj, slug, municipio, regime, data) VALUES (:uuid, :nome_fantasia, :razao_social, :cnpj, :slug, :municipio, :regime, :data)');
                        foreach ($rows as $item) {
                            $nome = trim((string)($item['apelido'] ?? $item['razao_social'] ?? ''));
                            $slug = preg_replace('/[^a-z0-9]+/','-', strtolower(trim($nome)));
                            if ($slug === '') $slug = 'tenant';
                            $slug = $slug . '-' . substr(bin2hex(random_bytes(4)),0,6);
                            $insert->execute([
                                'uuid' => bin2hex(random_bytes(8)),
                                'nome_fantasia' => $nome,
                                'razao_social' => $item['razao_social'] ?? '',
                                'cnpj' => preg_replace('/\D/','', $item['cnpj'] ?? ''),
                                'slug' => $slug,
                                'municipio' => $item['municipio'] ?? '',
                                'regime' => $item['regime'] ?? '',
                                'data' => json_encode($item, JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
            }
        } catch (Throwable $e) {
            // não interrompe inicialização se falhar
        }
    }

    // Separa um arquivo SQL em comandos independentes, usando ';' como delimitador.
    private static function splitSqlStatements(string $sql): array
    {
        return preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    }
}
