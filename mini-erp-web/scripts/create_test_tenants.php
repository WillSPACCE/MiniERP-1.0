<?php
/*
 Script de conveniência para criar 3 tenants de teste.
 - Cria 3 bancos separados: <main_db>_tenant_1..3
 - Executa o schema.sql em cada DB
 - Insere registro em `tenants` com `db_name`
 - Cria usuários admin no `usuarios` da base principal apontando para tenant (se coluna tenant_id existir) ou usando company_id

 USO (CLI):
 C:\xampp\php\php.exe scripts\create_test_tenants.php
*/

$baseDir = __DIR__ . '/..';
$config = require $baseDir . '/config.php';
$schemaPath = $baseDir . '/database/schema.sql';

if (!file_exists($schemaPath)) {
    echo "schema.sql não encontrado em: $schemaPath\n";
    exit(1);
}

$dbConf = $config['db'];
$driver = $dbConf['driver'] ?? 'mysql';
if ($driver !== 'mysql') {
    echo "Este script só suporta MySQL no momento. driver={$driver}\n";
    exit(1);
}

$serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port']);
try {
    $admin = new PDO($serverDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    echo "Falha ao conectar ao servidor MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

$mainDb = $dbConf['database'];

function splitSql(string $sql): array {
    return preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
}

// Garante coluna db_name em tenants
try {
    $admin->exec("USE `{$mainDb}`");
    $col = $admin->query("SHOW COLUMNS FROM tenants LIKE 'db_name'")->fetch();
    if (!$col) {
        $admin->exec("ALTER TABLE tenants ADD COLUMN db_name VARCHAR(255) DEFAULT NULL");
        echo "Coluna tenants.db_name criada.\n";
    }
} catch (Throwable $e) {
    echo "Aviso: não foi possível garantir coluna tenants.db_name: " . $e->getMessage() . "\n";
}

// Garante coluna tenant_id em usuarios
try {
    $col = $admin->query("SHOW COLUMNS FROM usuarios LIKE 'tenant_id'")->fetch();
    if (!$col) {
        $admin->exec("ALTER TABLE usuarios ADD COLUMN tenant_id INT NULL");
        echo "Coluna usuarios.tenant_id criada.\n";
    }
} catch (Throwable $e) {
    echo "Aviso: não foi possível garantir coluna usuarios.tenant_id: " . $e->getMessage() . "\n";
}

$schemaSql = file_get_contents($schemaPath);
$statements = splitSql($schemaSql);

for ($i = 1; $i <= 3; $i++) {
    $tenantDb = $mainDb . '_tenant_' . $i;
    echo "Criando banco: $tenantDb\n";
    try {
        $admin->exec("CREATE DATABASE IF NOT EXISTS `{$tenantDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {
        echo "Erro criando database {$tenantDb}: " . $e->getMessage() . "\n";
        continue;
    }

    // Conecta na DB do tenant
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $tenantDb);
    try {
        $pdoTenant = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (Throwable $e) {
        echo "Erro conectando em {$tenantDb}: " . $e->getMessage() . "\n";
        continue;
    }

    // Executa schema
    echo "Importando schema em {$tenantDb}...\n";
    foreach ($statements as $stmt) {
        $s = trim($stmt);
        if ($s === '') continue;
        try {
            $pdoTenant->exec($s);
        } catch (Throwable $e) {
            // ignora erros que já estejam aplicados
        }
    }

    // Insere tenant na base principal
    try {
        $slug = 'tenant-' . $i;
        $uuid = bin2hex(random_bytes(8));
        $nome = 'Empresa Teste ' . $i;
        $cnpj = sprintf('%014d', 10000000000000 + $i);
        $stmt = $admin->prepare('INSERT INTO tenants (uuid, nome_fantasia, razao_social, cnpj, slug, db_name, data) VALUES (:uuid, :nome, :razao, :cnpj, :slug, :db_name, :data)');
        $stmt->execute([
            'uuid' => $uuid,
            'nome' => $nome,
            'razao' => $nome,
            'cnpj' => $cnpj,
            'slug' => $slug,
            'db_name' => $tenantDb,
            'data' => json_encode(['created_by' => 'script', 'index' => $i], JSON_UNESCAPED_UNICODE),
        ]);
        $tenantId = (int)$admin->lastInsertId();
        echo "Tenant inserido (id={$tenantId})\n";

        // Insere usuário admin na tabela usuarios apontando para tenant
        $email = "admin{$i}@localhost";
        $senhaPlain = 'admin';
        $hash = password_hash($senhaPlain, PASSWORD_DEFAULT);
        $uStmt = $admin->prepare('INSERT INTO usuarios (nome, email, senha, role, status, tenant_id) VALUES (:nome, :email, :senha, :role, :status, :tid)');
        $uStmt->execute([
            'nome' => 'Administrador ' . $i,
            'email' => $email,
            'senha' => $hash,
            'role' => 'admin',
            'status' => 'ativo',
            'tid' => $tenantId,
        ]);
        echo "Usuário admin criado: {$email} / senha: {$senhaPlain}\n";
    } catch (Throwable $e) {
        echo "Erro inserindo tenant/usuario: " . $e->getMessage() . "\n";
    }
}

echo "Pronto. 3 tenants de teste (e usuários) foram criados na base principal.\n";

return 0;
