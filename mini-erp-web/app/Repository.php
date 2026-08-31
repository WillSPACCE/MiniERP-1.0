<?php
require_once __DIR__ . '/../src/Services/ProductFiscalData.php';
require_once __DIR__ . '/../src/Contracts/CnpjLookupProviderContract.php';
require_once __DIR__ . '/../src/Infrastructure/BrasilApiCnpjProvider.php';
require_once __DIR__ . '/../src/Services/CnpjLookupService.php';

// Repositório central da aplicação.
// É o ponto de contato entre a interface web e o banco de dados.
// Aqui ficam as regras de leitura, gravação e consulta dos dados do ERP.
class Repository {
    /**
     * PDO connection instance (declared to avoid dynamic property deprecation)
     */
    private $pdo;
    /** cache de colunas por tabela (por conexão) */
    private $columnCache = [];

    public function __construct(?PDO $connection = null, bool $initializeLegacyCompatibility = true)
    {
        // inicializa conexão e garante colunas essenciais
        $this->pdo = $connection ?? Database::getConnection();
        if ($initializeLegacyCompatibility) {
            $this->ensureClienteColumns();
            $this->ensureFornecedorColumns();
            $this->ensureUsuarioColumns();
            $this->ensureTenantsColumns();
        }
    }

    // métodos começam abaixo

    private function ensureTenantsColumns(): void
    {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM tenants LIKE 'blocked'");
            if ($stmt->fetch() === false) {
                $this->pdo->exec("ALTER TABLE tenants ADD COLUMN blocked TINYINT(1) DEFAULT 0");
            }
        } catch (Throwable $e) {
            // se a tabela tenants não existir ainda, ignora
        }
    }

    public function setCompanyBlocked(int $companyId, bool $blocked): void
    {
        // atualiza flag no tenants (DB principal)
        try {
            $config = require __DIR__ . '/../config.php';
            $dbConf = $config['db'];
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
            $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $stmt = $pdoMain->prepare('UPDATE tenants SET blocked = :b WHERE id = :id');
            $stmt->execute(['b' => $blocked ? 1 : 0, 'id' => $companyId]);

            // atualiza status dos usuários centrais
            if ($blocked) {
                $pdoMain->prepare("UPDATE usuarios SET status = 'bloqueado' WHERE tenant_id = :tid")->execute(['tid' => $companyId]);
            } else {
                $pdoMain->prepare("UPDATE usuarios SET status = 'ativo' WHERE tenant_id = :tid AND role <> 'suspendido'")->execute(['tid' => $companyId]);
            }

            // se tenant possui DB próprio, atualiza usuários no DB do tenant também
            $row = $pdoMain->prepare('SELECT db_name FROM tenants WHERE id = :id LIMIT 1');
            $row->execute(['id' => $companyId]);
            $t = $row->fetch();
            if ($t && !empty($t['db_name'])) {
                $dbName = $t['db_name'];
                Database::withTenantDbName($dbName, function () use ($blocked): void {
                    $tenantPdo = Database::getConnection();
                    if ($blocked) {
                        try { $tenantPdo->prepare("UPDATE usuarios SET status = 'bloqueado'")->execute(); } catch (Throwable $e) {}
                    } else {
                        try { $tenantPdo->prepare("UPDATE usuarios SET status = 'ativo'")->execute(); } catch (Throwable $e) {}
                    }
                });
            }
        } catch (Throwable $e) {
            throw $e;
        }
    }

    // Retorna o tenant_id atual da sessão ou lança exceção se não existir.
    private function requireTenantId(): int
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $erpUserId = (int) ($_SESSION['erp_user_id'] ?? 0);
        $erpTenantId = (int) ($_SESSION['erp_tenant_id'] ?? 0);
        $legacyUserProvided = array_key_exists('user_id', $_SESSION);
        $legacyTenantProvided = array_key_exists('tenant_id', $_SESSION);

        if ($erpUserId > 0 || $erpTenantId > 0) {
            if ($erpUserId < 1 || $erpTenantId < 1) {
                throw new RuntimeException('Acesso negado: sessão ERP incompleta.');
            }

            if ($legacyUserProvided || $legacyTenantProvided) {
                $legacyUserId = (int) ($_SESSION['user_id'] ?? $erpUserId);
                $legacyTenantId = (int) ($_SESSION['tenant_id'] ?? $erpTenantId);
                if ($legacyUserId !== $erpUserId || $legacyTenantId !== $erpTenantId) {
                    throw new RuntimeException('Acesso negado: contexto ERP e compatibilidade legada divergentes.');
                }
            }

            $_SESSION['user_id'] = $erpUserId;
            $_SESSION['tenant_id'] = $erpTenantId;
            return $erpTenantId;
        }
        $tid = $_SESSION['tenant_id'] ?? null;
        if (empty($tid)) {
            throw new RuntimeException('Acesso negado: tenant não selecionado na sessão.');
        }

        // Verifica existência do tenant na tabela tenants quando possível
        try {
            $stmt = $this->pdo->prepare('SELECT id FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => (int)$tid]);
            if ($stmt->fetch()) {
                // encontrado usando a conexão atual
            } else {
                // tentativa de fallback para conexão principal (caso a conexão atual seja tenant-specific)
                $config = require __DIR__ . '/../config.php';
                $serverDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['database']);
                $pdoMain = new PDO($serverDsn, $config['db']['username'], $config['db']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                $stmt2 = $pdoMain->prepare('SELECT id FROM tenants WHERE id = :id LIMIT 1');
                $stmt2->execute(['id' => (int)$tid]);
                if (!$stmt2->fetch()) {
                    throw new RuntimeException('Acesso negado: tenant inválido.');
                }
            }
        } catch (Throwable $e) {
            // última tentativa: se o usuário atual é o admin principal (admin@localhost), permita fallback para tenant 1
            try {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $uid = $_SESSION['user_id'] ?? null;
                if ($uid) {
                    // tentar buscar usuário no DB principal
                    $config = require __DIR__ . '/../config.php';
                    $serverDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['database']);
                    $pdoMain = new PDO($serverDsn, $config['db']['username'], $config['db']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                    $u = $pdoMain->prepare('SELECT email, role FROM usuarios WHERE id = :id LIMIT 1');
                    $u->execute(['id' => (int)$uid]);
                    $row = $u->fetch();
                    $emailLower = strtolower((string)($row['email'] ?? ''));
                    $role = strtolower((string)($row['role'] ?? ''));
                    if ($emailLower === 'admin@localhost' || $role === 'admin') {
                        // garante tenant default para ambiente principal
                        return 1;
                    }
                }
            } catch (Throwable $e2) {
                // ignore
            }

            throw new RuntimeException('Acesso negado: falha na validação do tenant.');
        }

        // Se houver usuário autenticado na sessão, garanta que ele pertença ao tenant.
        // Para evitar executar uma query com `tenant_id` em um DB que não tem a coluna
        // (por exemplo quando $this->pdo apunta para o DB do tenant), usamos a conexão
        // do DB principal para esta validação.
        $userId = $_SESSION['user_id'] ?? null;
        if (!empty($userId)) {
            try {
                $config = require __DIR__ . '/../config.php';
                $dbConf = $config['db'];
                $mainDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
                $pdoMain = new PDO($mainDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                $col = $pdoMain->query("SHOW COLUMNS FROM usuarios LIKE 'tenant_id'")->fetch();
                if ($col) {
                    $stmt = $pdoMain->prepare('SELECT id FROM usuarios WHERE id = :id AND tenant_id = :tid');
                    $stmt->execute(['id' => (int)$userId, 'tid' => (int)$tid]);
                    if (!$stmt->fetch()) {
                        throw new RuntimeException('Acesso negado: usuário não pertence ao tenant selecionado.');
                    }
                }
            } catch (Throwable $e) {
                // Em caso de erro na verificação (ex: permissões), considera compatível para compatibilidade de desenvolvimento.
            }
        }

        return (int)$tid;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->columnCache)) return $this->columnCache[$key];
        try {
            $res = (bool) $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'")->fetch();
            $this->columnCache[$key] = $res;
            return $res;
        } catch (Throwable $e) {
            $this->columnCache[$key] = false;
            return false;
        }
    }

    private function ensureUsuarioColumns(): void
    {
        $cols = [
            "email_verified" => "TINYINT(1) DEFAULT 0",
            "email_verification_token" => "VARCHAR(255) DEFAULT NULL",
            "permissions" => "TEXT DEFAULT NULL",
            "cargo" => "VARCHAR(50) DEFAULT 'funcionario'",
            "pessoa_id" => "INT NULL",
        ];
        foreach ($cols as $col => $def) {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE '$col'");
            if ($stmt->fetch() !== false) continue;
            $this->pdo->exec("ALTER TABLE usuarios ADD COLUMN $col $def");
        }
    }

    public function ensureDefaultAdmin(): void
    {
        $email = 'admin@localhost';
        $hash = password_hash('admin', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $this->pdo->prepare('UPDATE usuarios SET senha = :senha, nome = :nome, role = "admin", status = "ativo", tenant_id = 1 WHERE email = :email')
                ->execute(['senha' => $hash, 'nome' => 'Administrador', 'email' => $email]);
            return;
        }

        $this->pdo->prepare('INSERT INTO usuarios (email, senha, nome, role, status, tenant_id) VALUES (:email, :senha, :nome, "admin", "ativo", 1)')
            ->execute(['email' => $email, 'senha' => $hash, 'nome' => 'Administrador']);
    }

    

    private function ensureClienteColumns(): void
    {
        $columns = [
            'cpf_cnpj' => 'VARCHAR(20) DEFAULT ""',
            'inscricao_estadual' => 'VARCHAR(50) DEFAULT ""',
            'logradouro' => 'VARCHAR(150) DEFAULT ""',
            'numero' => 'VARCHAR(20) DEFAULT ""',
            'complemento' => 'VARCHAR(100) DEFAULT ""',
            'bairro' => 'VARCHAR(100) DEFAULT ""',
            'municipio' => 'VARCHAR(100) DEFAULT ""',
            'codigo_municipal' => 'VARCHAR(20) DEFAULT ""',
            'uf' => 'VARCHAR(2) DEFAULT ""',
            'cep' => 'VARCHAR(20) DEFAULT ""',
            'cidade' => 'VARCHAR(100) DEFAULT ""',
            'nome_fantasia' => 'VARCHAR(150) DEFAULT ""',
            'tipo_pessoa' => 'VARCHAR(50) DEFAULT "cliente"',
            'pessoa_fisica' => 'VARCHAR(10) DEFAULT "sim"',
            'aniversario' => 'DATE NULL',
            'genero' => 'VARCHAR(30) DEFAULT ""',
            'data_cadastro' => 'DATE NULL',
            'nome_contato' => 'VARCHAR(150) DEFAULT ""',
            'fone_principal' => 'VARCHAR(50) DEFAULT ""',
            'fone_2' => 'VARCHAR(50) DEFAULT ""',
            'fone_3' => 'VARCHAR(50) DEFAULT ""',
            'estado' => 'VARCHAR(100) DEFAULT ""',
            'ponto_referencia' => 'VARCHAR(150) DEFAULT ""',
            'codigo_ibge' => 'VARCHAR(20) DEFAULT ""',
            'suprama' => 'VARCHAR(50) DEFAULT ""',
            'im' => 'VARCHAR(50) DEFAULT ""',
            'vendedor' => 'VARCHAR(150) DEFAULT ""',
            'status_pagamento' => 'VARCHAR(50) DEFAULT ""',
            'pagamento' => 'VARCHAR(50) DEFAULT ""',
            'anvisa_data_venc' => 'DATE NULL',
            'anvisa_codigo' => 'VARCHAR(50) DEFAULT ""',
            'comissao_percentual' => 'VARCHAR(20) DEFAULT ""',
            'comissao_volume' => 'VARCHAR(20) DEFAULT ""',
            'forma_pagamento' => 'VARCHAR(50) DEFAULT ""',
            'limite_credito' => 'DECIMAL(10,2) DEFAULT 0',
            'desconto' => 'DECIMAL(10,2) DEFAULT 0',
            'funeral' => 'VARCHAR(20) DEFAULT ""',
            'transportadora' => 'VARCHAR(150) DEFAULT ""',
            'placa' => 'VARCHAR(20) DEFAULT ""',
            'placa_uf' => 'VARCHAR(10) DEFAULT ""',
            'antt' => 'VARCHAR(50) DEFAULT ""',
            'frete' => 'DECIMAL(10,2) DEFAULT 0',
            'valor_frete' => 'DECIMAL(10,2) DEFAULT 0',
            'data' => 'JSON DEFAULT NULL',
        ];

        foreach ($columns as $column => $definition) {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM clientes LIKE '$column'");
            if ($stmt->fetch() !== false) {
                continue;
            }

            $this->pdo->exec("ALTER TABLE clientes ADD COLUMN $column $definition");
        }
    }

    private function ensureFornecedorColumns(): void
    {
        $cols = [
            'data' => 'JSON DEFAULT NULL',
        ];
        foreach ($cols as $col => $def) {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM fornecedores LIKE '$col'");
            if ($stmt->fetch() !== false) continue;
            $this->pdo->exec("ALTER TABLE fornecedores ADD COLUMN $col $def");
        }
    }

    private function normalizeTipoPessoa(array $data): string
    {
        $raw = $data['tipo_pessoa'] ?? ($data['tipo_pessoa[]'] ?? 'cliente');

        if (is_array($raw)) {
            $raw = array_filter(array_map('trim', $raw));
            return implode(',', $raw) ?: 'cliente';
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return 'cliente';
        }

        return $raw;
    }

    private function normalizeOptionalDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function normalizeOptionalNumber(mixed $value, float $default = 0.0): string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? (string) $default : $value;
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }

    private function isValidCPF(string $cpf): bool
    {
        $d = $this->onlyDigits($cpf);
        return strlen($d) === 11;
    }

    private function isValidCNPJ(string $cnpj): bool
    {
        $d = $this->onlyDigits($cnpj);
        return strlen($d) === 14;
    }

    private function isValidPhone(string $phone): bool
    {
        $d = $this->onlyDigits($phone);
        return $d === '' || in_array(strlen($d), [10, 11], true);
    }

    // Salva impostos para um produto (inserir ou atualizar)
    public function saveProductTaxes(int $productId, array $taxes): void
    {
        $tenantId = $this->requireTenantId();
        $productHasTenant = $this->hasColumn('produtos', 'tenant_id');
        $stmt = $this->pdo->prepare('SELECT id FROM produtos WHERE id = :id' . ($productHasTenant ? ' AND tenant_id = :tid' : ''));
        $params = ['id' => $productId]; if ($productHasTenant) $params['tid'] = $tenantId;
        $stmt->execute($params);
        if (!$stmt->fetch()) throw new RuntimeException('Produto não encontrado ou não pertence ao tenant.');

        $payload = [
            'product_id' => $productId,
            'ipi' => (string)($taxes['ipi'] ?? ''),
            'icms' => (string)($taxes['icms'] ?? ''),
            'pis' => (string)($taxes['pis'] ?? ''),
            'cofins' => (string)($taxes['cofins'] ?? ''),
        ];
        $taxHasTenant = $this->hasColumn('product_taxes', 'tenant_id');
        if ($taxHasTenant) $payload['tenant_id'] = $tenantId;
        $fields = array_keys($payload);
        $updates = array_map(fn(string $field): string => "$field = VALUES($field)", array_filter($fields, fn(string $field): bool => $field !== 'product_id'));
        $stmt = $this->pdo->prepare('INSERT INTO product_taxes (`' . implode('`,`', $fields) . '`) VALUES (:' . implode(',:', $fields) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates));
        $stmt->execute($payload);
    }

    // Retorna impostos de um produto ou null se não existir
    public function getProductTaxes(int $productId): ?array
    {
        $tenantId = $this->requireTenantId();
        $hasTenant = $this->hasColumn('produtos', 'tenant_id');
        $stmt = $this->pdo->prepare('SELECT pt.* FROM product_taxes pt JOIN produtos p ON p.id = pt.product_id WHERE pt.product_id = :product_id' . ($hasTenant ? ' AND p.tenant_id = :tid' : ''));
        $params = ['product_id' => $productId]; if ($hasTenant) $params['tid'] = $tenantId;
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Busca os números principais para mostrar no dashboard.
    public function getDashboardData(): array
    {
        try {
            $tenantId = $this->requireTenantId();
        } catch (RuntimeException $e) {
            // se for o admin principal, permitir visão global (sem tenant)
            $tenantId = null;
        }
        // Ajusta queries conforme presença da coluna tenant_id no schema (compatibilidade)
        $clientes = 0;
        $produtos = 0;
        $vendas = 0;
        $faturamento = 0.0;
        $estoqueBaixo = 0;

        $hasClientesTenant = (bool) $this->pdo->query("SHOW COLUMNS FROM clientes LIKE 'tenant_id'")->fetch();
        $hasProdutosTenant = (bool) $this->pdo->query("SHOW COLUMNS FROM produtos LIKE 'tenant_id'")->fetch();

        if ($hasClientesTenant && $tenantId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM clientes WHERE tenant_id = :tid');
            $stmt->execute(['tid' => $tenantId]);
            $clientes = (int) $stmt->fetch()['total'];
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM clientes');
            $clientes = (int) $stmt->fetch()['total'];
        }

        if ($hasProdutosTenant && $tenantId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM produtos WHERE tenant_id = :tid');
            $stmt->execute(['tid' => $tenantId]);
            $produtos = (int) $stmt->fetch()['total'];
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM produtos');
            $produtos = (int) $stmt->fetch()['total'];
        }

        // Vendas e faturamento: se produtos têm tenant, filtra por produto. Caso contrário, conta tudo.
        if ($hasProdutosTenant && $tenantId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM vendas v JOIN itens_venda iv ON iv.venda_id = v.id JOIN produtos p ON p.id = iv.produto_id WHERE p.tenant_id = :tid');
            $stmt->execute(['tid' => $tenantId]);
            $vendas = (int) $stmt->fetch()['total'];

            $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(v.total), 0) as total FROM vendas v JOIN itens_venda iv ON iv.venda_id = v.id JOIN produtos p ON p.id = iv.produto_id WHERE p.tenant_id = :tid');
            $stmt->execute(['tid' => $tenantId]);
            $faturamento = (float) $stmt->fetch()['total'];
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM vendas');
            $vendas = (int) $stmt->fetch()['total'];

            $stmt = $this->pdo->query('SELECT COALESCE(SUM(total), 0) as total FROM vendas');
            $faturamento = (float) $stmt->fetch()['total'];
        }

        if ($hasProdutosTenant && $tenantId !== null) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) as total FROM produtos WHERE estoque_atual <= 5 AND tenant_id = :tid');
            $stmt->execute(['tid' => $tenantId]);
            $estoqueBaixo = $stmt->fetch()['total'];
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) as total FROM produtos WHERE estoque_atual <= 5');
            $estoqueBaixo = $stmt->fetch()['total'];
        }

        return [
            'clientes' => $clientes,
            'produtos' => $produtos,
            'vendas' => $vendas,
            'faturamento' => $faturamento,
            'estoque_baixo' => (int) $estoqueBaixo,
        ];
    }

    // Lista todos os clientes em ordem decrescente de ID para o tenant.
    public function listClientes(string $search = ''): array
    {
        $tenantId = $this->requireTenantId();
        $hasTenantColumn = $this->hasColumn('clientes', 'tenant_id');
        $where = [];
        $params = [];
        if ($hasTenantColumn) { $where[] = 'tenant_id = :tid'; $params['tid'] = $tenantId; }
        $search = trim($search);
        if ($search !== '') {
            $searchFields = array_values(array_filter(
                ['nome', 'nome_fantasia', 'cpf_cnpj', 'email'],
                fn (string $field): bool => $this->hasColumn('clientes', $field)
            ));
            if ($searchFields !== []) {
                $where[] = '(' . implode(' OR ', array_map(fn (string $field): string => $field . ' LIKE :search', $searchFields)) . ')';
                $params['search'] = '%' . $search . '%';
            }
        }
        $sql = 'SELECT * FROM clientes' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Busca um tenant pelo ID (usa o DB principal onde a tabela tenants existe)
    public function findTenantById(int $id): ?array
    {
        // tenta usar a conexão atual; se for tenant-specific, cria uma conexão temporária para o DB principal
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            // fallback: abrir conexão direta ao DB principal
            $config = require __DIR__ . '/../config.php';
            $serverDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['database']);
            $pdo = new PDO($serverDsn, $config['db']['username'], $config['db']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();
            return $row ?: null;
        }
    }

    // Busca um cliente por ID (apenas do tenant).
    public function findCliente(int $id): ?array
    {
        $tenantId = $this->requireTenantId();
        $hasTenantColumn = $this->hasColumn('clientes', 'tenant_id');
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id' . ($hasTenantColumn ? ' AND tenant_id = :tid' : ''));
        $params = ['id' => $id];
        if ($hasTenantColumn) $params['tid'] = $tenantId;
        $stmt->execute($params);
        $cliente = $stmt->fetch();
        return $cliente ?: null;
    }

    // Cria ou atualiza um cliente.
    public function saveCliente(array $data): int
    {
        require_once __DIR__ . '/../src/Services/PersonFiscalData.php';
        $fiscalData = (new \MiniErp\Services\PersonFiscalData($data))->toArray();
        if (empty($_SESSION['erp_user_id'])) $this->ensureClienteColumns();

        $tenantId = $this->requireTenantId();

        $nome = trim((string) ($data['nome'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $cpfCnpj = $fiscalData['cpf_cnpj'];

        if ($nome === '' || ($cpfCnpj === '' && $fiscalData['person_type'] !== 'FOREIGN')) {
            throw new InvalidArgumentException('Nome e documento nacional são obrigatórios para PF/PJ.');
        }
        if ($cpfCnpj !== '') {
            $duplicateSql = 'SELECT id FROM clientes WHERE cpf_cnpj = :document' . (!empty($data['id']) ? ' AND id <> :id' : '') . ' LIMIT 1';
            $duplicate = $this->pdo->prepare($duplicateSql); $duplicateParams = ['document' => $cpfCnpj];
            if (!empty($data['id'])) $duplicateParams['id'] = (int) $data['id'];
            $duplicate->execute($duplicateParams);
            if ($duplicate->fetch()) throw new InvalidArgumentException('CPF/CNPJ já cadastrado para outra pessoa neste tenant.');
        }

        $fone1 = trim((string) ($data['fone_principal'] ?? $data['telefone'] ?? ''));
        $fone2 = trim((string) ($data['fone_2'] ?? ''));
        $fone3 = trim((string) ($data['fone_3'] ?? ''));
        if ($fone1 !== '' && !$this->isValidPhone($fone1)) {
            throw new InvalidArgumentException('Telefone principal inválido. Informe 10 ou 11 dígitos.');
        }
        if ($fone2 !== '' && !$this->isValidPhone($fone2)) {
            throw new InvalidArgumentException('Telefone 2 inválido. Informe 10 ou 11 dígitos.');
        }
        if ($fone3 !== '' && !$this->isValidPhone($fone3)) {
            throw new InvalidArgumentException('Telefone 3 inválido. Informe 10 ou 11 dígitos.');
        }

        $isMinimal = !empty($data['minimal']);
        if (!$isMinimal) {
            $cep = preg_replace('/\D/', '', (string) ($data['cep'] ?? ''));
            $logradouro = trim((string) ($data['logradouro'] ?? ''));
            $telefonePrincipal = $fone1;
            $requiredAddress=['logradouro'=>'logradouro','numero'=>'número','bairro'=>'bairro','cidade'=>'município','codigo_ibge'=>'código IBGE','estado'=>'UF','cep'=>'CEP'];$missing=[];
            foreach($requiredAddress as$field=>$label){$value=match($field){'cidade'=>trim((string)($data['cidade']??$data['municipio']??'')),'codigo_ibge'=>trim((string)($data['codigo_ibge']??$data['codigo_municipal']??'')),'estado'=>trim((string)($data['estado']??$data['uf']??'')),'cep'=>$cep,default=>trim((string)($data[$field]??''))};if($value==='')$missing[]=$label;}
            if($missing)throw new InvalidArgumentException('Complete o endereço fiscal do cliente: '.implode(', ',$missing).'.');
            if($telefonePrincipal==='')throw new InvalidArgumentException('Informe o telefone principal do cliente.');
        }

        $payload = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => trim((string) ($data['telefone'] ?? $fone1)),
            'cpf_cnpj' => $cpfCnpj,
            'inscricao_estadual' => trim((string) ($data['inscricao_estadual'] ?? '')),
            'logradouro' => trim((string) ($data['logradouro'] ?? '')),
            'numero' => trim((string) ($data['numero'] ?? '')),
            'complemento' => trim((string) ($data['complemento'] ?? '')),
            'bairro' => trim((string) ($data['bairro'] ?? '')),
            'municipio' => trim((string) ($data['municipio'] ?? '')),
            'codigo_municipal' => trim((string) ($data['codigo_municipal'] ?? $data['codigo_ibge'] ?? '')),
            'uf' => trim((string) ($data['uf'] ?? $data['estado'] ?? '')),
            'cep' => preg_replace('/\D/', '', (string) ($data['cep'] ?? '')),
            'cidade' => trim((string) ($data['cidade'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
            'nome_fantasia' => trim((string) ($data['nome_fantasia'] ?? '')),
            'tipo_pessoa' => $this->normalizeTipoPessoa($data),
            'pessoa_fisica' => trim((string) ($data['pessoa_fisica'] ?? 'sim')),
            'aniversario' => $this->normalizeOptionalDate($data['aniversario'] ?? ''),
            'genero' => trim((string) ($data['genero'] ?? '')),
            'data_cadastro' => $this->normalizeOptionalDate($data['data_cadastro'] ?? date('Y-m-d')),
            'nome_contato' => trim((string) ($data['nome_contato'] ?? '')),
            'fone_principal' => $fone1,
            'fone_2' => trim((string) ($data['fone_2'] ?? '')),
            'fone_3' => trim((string) ($data['fone_3'] ?? '')),
            'estado' => trim((string) ($data['estado'] ?? '')),
            'ponto_referencia' => trim((string) ($data['ponto_referencia'] ?? '')),
            'codigo_ibge' => trim((string) ($data['codigo_ibge'] ?? '')),
            'suprama' => trim((string) ($data['suprama'] ?? '')),
            'im' => trim((string) ($data['im'] ?? '')),
            'vendedor' => trim((string) ($data['vendedor'] ?? '')),
            'status_pagamento' => trim((string) ($data['status_pagamento'] ?? '')),
            'pagamento' => trim((string) ($data['pagamento'] ?? '')),
            'anvisa_data_venc' => $this->normalizeOptionalDate($data['anvisa_data_venc'] ?? ''),
            'anvisa_codigo' => trim((string) ($data['anvisa_codigo'] ?? '')),
            'comissao_percentual' => trim((string) ($data['comissao_percentual'] ?? '')),
            'comissao_volume' => trim((string) ($data['comissao_volume'] ?? '')),
            'forma_pagamento' => trim((string) ($data['forma_pagamento'] ?? '')),
            'limite_credito' => $this->normalizeOptionalNumber($data['limite_credito'] ?? '0', 0.0),
            'desconto' => $this->normalizeOptionalNumber($data['desconto'] ?? '0', 0.0),
            'funeral' => trim((string) ($data['funeral'] ?? '')),
            'transportadora' => trim((string) ($data['transportadora'] ?? '')),
            'placa' => trim((string) ($data['placa'] ?? '')),
            'placa_uf' => trim((string) ($data['placa_uf'] ?? '')),
            'antt' => trim((string) ($data['antt'] ?? '')),
            'frete' => $this->normalizeOptionalNumber($data['frete'] ?? '0', 0.0),
            'valor_frete' => $this->normalizeOptionalNumber($data['valor_frete'] ?? '0', 0.0),
            'data' => $data['data'] ?? null,
        ];
        $payload = array_merge($payload, $fiscalData);

        if ($this->hasColumn('clientes', 'tenant_id')) $payload['tenant_id'] = $tenantId;
        $payload = array_filter($payload, fn (string $field): bool => $this->hasColumn('clientes', $field), ARRAY_FILTER_USE_KEY);

        if (!empty($data['id'])) {
            $set = [];
            foreach (array_keys($payload) as $field) {
                $set[] = "$field = :$field";
            }
            $payload['id'] = (int) $data['id'];
            $hasTenantColumn = array_key_exists('tenant_id', $payload);
            $stmt = $this->pdo->prepare('UPDATE clientes SET ' . implode(', ', $set) . ' WHERE id = :id' . ($hasTenantColumn ? ' AND tenant_id = :tenant_id' : ''));
            $stmt->execute($payload);
            return $stmt->rowCount();
        }

        $fields = array_keys($payload);
        $placeholders = array_map(fn($field) => ':' . $field, $fields);
        $stmt = $this->pdo->prepare('INSERT INTO clientes (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($payload);
        return $stmt->rowCount();
    }

    // Remove um cliente pelo ID.
    public function deleteCliente(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $hasTenantColumn = $this->hasColumn('clientes', 'tenant_id');
        $stmt = $this->pdo->prepare("UPDATE clientes SET status = 'inativo' WHERE id = :id" . ($hasTenantColumn ? ' AND tenant_id = :tid' : ''));
        $params = ['id' => $id];
        if ($hasTenantColumn) $params['tid'] = $tenantId;
        $stmt->execute($params);
    }

    // Lista todos os produtos cadastrados.
    public function listProdutos(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $hasCol = (bool) $this->pdo->query("SHOW COLUMNS FROM produtos LIKE 'tenant_id'")->fetch();
            if ($hasCol) {
                $stmt = $this->pdo->prepare('SELECT * FROM produtos WHERE tenant_id = :tid ORDER BY id DESC');
                $stmt->execute(['tid' => $tenantId]);
                return $stmt->fetchAll();
            }
            return $this->pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll();
        } catch (Throwable $e) {
            try { return $this->pdo->query('SELECT * FROM produtos ORDER BY id DESC')->fetchAll(); } catch (Throwable $e2) { return []; }
        }
    }

    // Lista CFOPs se a tabela existir (opcional).
    public function listCfops(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $hasCol = (bool) $this->pdo->query("SHOW COLUMNS FROM cfops LIKE 'tenant_id'")->fetch();
            if ($hasCol) {
                return $this->pdo->query('SELECT * FROM cfops WHERE tenant_id = ' . (int)$tenantId . ' ORDER BY codigo ASC')->fetchAll();
            }
            return $this->pdo->query('SELECT * FROM cfops ORDER BY codigo ASC')->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // Busca um CFOP por ID.
    public function findCfop(int $id): ?array
    {
        $tenantId = $this->requireTenantId();
        $hasTenant = $this->hasColumn('cfops', 'tenant_id');
        $stmt = $this->pdo->prepare('SELECT * FROM cfops WHERE id = :id' . ($hasTenant ? ' AND tenant_id = :tid' : ''));
        $params=['id'=>$id];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
        $cfop = $stmt->fetch();
        return $cfop ?: null;
    }

    public function saveCfop(array $data): void
    {
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $descricao = trim((string) ($data['descricao'] ?? ''));

        if ($codigo === '' || $descricao === '') {
            throw new InvalidArgumentException('Código e descrição do CFOP são obrigatórios.');
        }

        $tenantId = $this->requireTenantId();
        $hasTenant = $this->hasColumn('cfops', 'tenant_id');
        if (!empty($data['id'])) {
            $stmt = $this->pdo->prepare('UPDATE cfops SET codigo = :codigo, descricao = :descricao, natureza = :natureza, aplicacao = :aplicacao, status = :status WHERE id = :id' . ($hasTenant ? ' AND tenant_id = :tid' : ''));
            $params=[
                'id' => (int) $data['id'],
                'codigo' => $codigo,
                'descricao' => $descricao,
                'natureza' => trim((string) ($data['natureza'] ?? '')),
                'aplicacao' => trim((string) ($data['aplicacao'] ?? '')),
                'status' => trim((string) ($data['status'] ?? 'ativo')),
            ];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO cfops (codigo, descricao, natureza, aplicacao, status' . ($hasTenant ? ', tenant_id' : '') . ') VALUES (:codigo, :descricao, :natureza, :aplicacao, :status' . ($hasTenant ? ', :tid' : '') . ')');
        $params=[
            'codigo' => $codigo,
            'descricao' => $descricao,
            'natureza' => trim((string) ($data['natureza'] ?? '')),
            'aplicacao' => trim((string) ($data['aplicacao'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
        ];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
    }

    public function deleteCfop(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $hasTenant=$this->hasColumn('cfops','tenant_id');$stmt=$this->pdo->prepare("UPDATE cfops SET status='inativo' WHERE id=:id".($hasTenant?' AND tenant_id=:tid':''));$params=['id'=>$id];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
    }

    public function listFornecedores(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $hasCol = (bool) $this->pdo->query("SHOW COLUMNS FROM fornecedores LIKE 'tenant_id'")->fetch();
            if ($hasCol) {
                return $this->pdo->query('SELECT * FROM fornecedores WHERE tenant_id = ' . (int)$tenantId . ' ORDER BY id DESC')->fetchAll();
            }
            return $this->pdo->query('SELECT * FROM fornecedores ORDER BY id DESC')->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findFornecedor(int $id): ?array
    {
        $tenantId = $this->requireTenantId();
        $stmt = $this->pdo->prepare('SELECT * FROM fornecedores WHERE id = :id AND tenant_id = :tid');
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        return $stmt->fetch() ?: null;
    }

    public function saveFornecedor(array $data): void
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $cnpj = preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? ''));
        if ($nome === '' || $cnpj === '') {
            throw new InvalidArgumentException('Nome e CPF/CNPJ do fornecedor são obrigatórios.');
        }

        $tenantId = $this->requireTenantId();
        $stmt = !empty($data['id'])
            ? $this->pdo->prepare('UPDATE fornecedores SET nome = :nome, nome_fantasia = :nome_fantasia, cpf_cnpj = :cpf_cnpj, inscricao_estadual = :inscricao_estadual, email = :email, telefone = :telefone, cep = :cep, logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro, municipio = :municipio, uf = :uf, cidade = :cidade, status = :status, data = :data WHERE id = :id AND tenant_id = :tid')
            : $this->pdo->prepare('INSERT INTO fornecedores (nome, nome_fantasia, cpf_cnpj, inscricao_estadual, email, telefone, cep, logradouro, numero, complemento, bairro, municipio, uf, cidade, status, tenant_id, data) VALUES (:nome, :nome_fantasia, :cpf_cnpj, :inscricao_estadual, :email, :telefone, :cep, :logradouro, :numero, :complemento, :bairro, :municipio, :uf, :cidade, :status, :tid, :data)');

        $params = [
            'nome' => $nome,
            'nome_fantasia' => trim((string) ($data['nome_fantasia'] ?? '')),
            'cpf_cnpj' => $cnpj,
            'inscricao_estadual' => trim((string) ($data['inscricao_estadual'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'telefone' => trim((string) ($data['telefone'] ?? '')),
            'cep' => preg_replace('/\D/', '', (string) ($data['cep'] ?? '')),
            'logradouro' => trim((string) ($data['logradouro'] ?? '')),
            'numero' => trim((string) ($data['numero'] ?? '')),
            'complemento' => trim((string) ($data['complemento'] ?? '')),
            'bairro' => trim((string) ($data['bairro'] ?? '')),
            'municipio' => trim((string) ($data['municipio'] ?? '')),
            'uf' => trim((string) ($data['uf'] ?? '')),
            'cidade' => trim((string) ($data['cidade'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
            'data' => $data['data'] ?? null,
        ];

        if (!empty($data['id'])) {
            $params['id'] = (int) $data['id'];
            $params['tid'] = $tenantId;
        } else {
            $params['tid'] = $tenantId;
        }

        $stmt->execute($params);
    }

    public function deleteFornecedor(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE id = :id AND tenant_id = :tid');
        $stmt->execute(['id' => $id, 'tid' => $tenantId]);
    }

    public function listMotoristas(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $hasCol = (bool) $this->pdo->query("SHOW COLUMNS FROM motoristas LIKE 'tenant_id'")->fetch();
            if ($hasCol) {
                return $this->pdo->query('SELECT * FROM motoristas WHERE tenant_id = ' . (int)$tenantId . ' ORDER BY id DESC')->fetchAll();
            }
            return $this->pdo->query('SELECT * FROM motoristas ORDER BY id DESC')->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findMotorista(int $id): ?array
    {
        $tenantId = $this->requireTenantId();
        $hasTenant = (bool)$this->pdo->query("SHOW COLUMNS FROM motoristas LIKE 'tenant_id'")->fetch();
        $stmt = $this->pdo->prepare('SELECT * FROM motoristas WHERE id = :id'.($hasTenant?' AND tenant_id = :tid':''));
        $params=['id'=>$id];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function saveMotorista(array $data): void
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string) ($data['cpf'] ?? ''));
        if ($nome === '' || $cpf === '') {
            throw new InvalidArgumentException('Nome e CPF do motorista são obrigatórios.');
        }

        if (strlen($cpf) !== 11) {
            throw new InvalidArgumentException('CPF do motorista inválido. Deve conter 11 dígitos.');
        }

        $telefone = trim((string) ($data['telefone'] ?? ''));
        if ($telefone !== '' && !$this->isValidPhone($telefone)) {
            throw new InvalidArgumentException('Telefone do motorista inválido. Informe 10 ou 11 dígitos.');
        }

        // Se não for criação minimal (vinda do fluxo de criação rápida de usuário), exigir dados da CNH/veículo
        $isMinimal = !empty($data['minimal']);
        if (!$isMinimal) {
            $cnh = trim((string) ($data['cnh'] ?? ''));
            $categoria = trim((string) ($data['categoria_cnh'] ?? ''));
            $venc = trim((string) ($data['vencimento_cnh'] ?? ''));
            if ($cnh === '' || $categoria === '' || $venc === '') {
                throw new InvalidArgumentException('Para cadastro completo do motorista, preencha CNH, categoria e data de vencimento.');
            }
        }

        $tenantId = $this->requireTenantId();
        $hasTenant = (bool)$this->pdo->query("SHOW COLUMNS FROM motoristas LIKE 'tenant_id'")->fetch();
        $stmt = !empty($data['id'])
            ? $this->pdo->prepare('UPDATE motoristas SET nome = :nome, cpf = :cpf, cnh = :cnh, categoria_cnh = :categoria_cnh, vencimento_cnh = :vencimento_cnh, telefone = :telefone, status = :status WHERE id = :id'.($hasTenant?' AND tenant_id = :tid':''))
            : $this->pdo->prepare('INSERT INTO motoristas (nome, cpf, cnh, categoria_cnh, vencimento_cnh, telefone, status'.($hasTenant?', tenant_id':'').') VALUES (:nome, :cpf, :cnh, :categoria_cnh, :vencimento_cnh, :telefone, :status'.($hasTenant?', :tid':'').')');

        $params = [
            'nome' => $nome,
            'cpf' => $cpf,
            'cnh' => trim((string) ($data['cnh'] ?? '')),
            'categoria_cnh' => trim((string) ($data['categoria_cnh'] ?? '')),
            'vencimento_cnh' => $data['vencimento_cnh'] ?? null,
            'telefone' => trim((string) ($data['telefone'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
        ];

        if (!empty($data['id'])) {
            $params['id'] = (int) $data['id'];
            if($hasTenant)$params['tid'] = $tenantId;
        } elseif($hasTenant) $params['tid'] = $tenantId;

        $stmt->execute($params);
    }

    public function deleteMotorista(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $hasTenant=(bool)$this->pdo->query("SHOW COLUMNS FROM motoristas LIKE 'tenant_id'")->fetch();$stmt=$this->pdo->prepare('DELETE FROM motoristas WHERE id=:id'.($hasTenant?' AND tenant_id=:tid':''));$params=['id'=>$id];if($hasTenant)$params['tid']=$tenantId;$stmt->execute($params);
    }

    public function listTransportadoras(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $rows = [];

            $legacyTableExists = (bool) $this->pdo->query("SHOW TABLES LIKE 'transportadoras'")->fetch();
            if ($legacyTableExists) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM transportadoras LIKE 'tenant_id'");
                $hasTenantCol = (bool) $stmt->fetch();
                $legacySql = $hasTenantCol
                    ? 'SELECT * FROM transportadoras WHERE tenant_id = ' . (int)$tenantId . ' ORDER BY id DESC'
                    : 'SELECT * FROM transportadoras ORDER BY id DESC';
                $legacyRows = $this->pdo->query($legacySql)->fetchAll();
                foreach ($legacyRows as $row) {
                    $rows[(string) ($row['cpf_cnpj'] ?? $row['document'] ?? $row['id'])] = $row;
                }
            }

            $clientesTableExists = (bool) $this->pdo->query("SHOW TABLES LIKE 'clientes'")->fetch();
            if ($clientesTableExists) {
                $stmt = $this->pdo->query("SHOW COLUMNS FROM clientes LIKE 'tenant_id'");
                $hasTenantCol = (bool) $stmt->fetch();
                $clientWhere = "(role_carrier = 1 OR FIND_IN_SET('transportadora', REPLACE(COALESCE(tipo_pessoa, ''), ' ', '')) > 0)";
                $sql = "SELECT id, nome, nome_fantasia, cpf_cnpj, email, telefone, fone_principal, cidade, uf, status FROM clientes WHERE " . $clientWhere;
                if ($hasTenantCol) {
                    $sql .= ' AND tenant_id = ' . (int)$tenantId;
                }
                $sql .= ' ORDER BY id DESC';
                foreach ($this->pdo->query($sql)->fetchAll() as $row) {
                    $key = (string) ($row['cpf_cnpj'] ?? $row['document'] ?? $row['id']);
                    $rows[$key] = $row;
                }
            }

            $result = array_values($rows);
            usort($result, static function (array $a, array $b): int {
                $left = trim((string) ($a['nome'] ?? $a['nome_fantasia'] ?? ''));
                $right = trim((string) ($b['nome'] ?? $b['nome_fantasia'] ?? ''));
                return strcasecmp($left, $right);
            });
            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findTransportadora(int $id): ?array
    {
        try {
            $tenantId = $this->requireTenantId();
            $stmt = $this->pdo->prepare('SELECT * FROM transportadoras WHERE id = :id AND tenant_id = :tid');
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }

            $stmt = $this->pdo->query("SHOW TABLES LIKE 'clientes'");
            if (!$stmt->fetch()) {
                return null;
            }

            $tenantCheck = $this->pdo->query("SHOW COLUMNS FROM clientes LIKE 'tenant_id'")->fetch();
            $sql = "SELECT * FROM clientes WHERE id = :id AND (role_carrier = 1 OR FIND_IN_SET('transportadora', REPLACE(COALESCE(tipo_pessoa, ''), ' ', '')) > 0)";
            if ($tenantCheck) {
                $sql .= ' AND tenant_id = :tid';
            }
            $sql .= ' LIMIT 1';

            $stmt = $this->pdo->prepare($sql);
            $params = ['id' => $id];
            if ($tenantCheck) {
                $params['tid'] = $tenantId;
            }
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function saveTransportadora(array $data): void
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $cnpj = preg_replace('/\D/', '', (string) ($data['cpf_cnpj'] ?? ''));
        if ($nome === '' || $cnpj === '') {
            throw new InvalidArgumentException('Nome e CPF/CNPJ da transportadora são obrigatórios.');
        }

        $tenantId = $this->requireTenantId();
        $stmt = !empty($data['id'])
            ? $this->pdo->prepare('UPDATE transportadoras SET nome = :nome, nome_fantasia = :nome_fantasia, cpf_cnpj = :cpf_cnpj, inscricao_estadual = :inscricao_estadual, email = :email, telefone = :telefone, cep = :cep, logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro, municipio = :municipio, uf = :uf, cidade = :cidade, status = :status WHERE id = :id AND tenant_id = :tid')
            : $this->pdo->prepare('INSERT INTO transportadoras (nome, nome_fantasia, cpf_cnpj, inscricao_estadual, email, telefone, cep, logradouro, numero, complemento, bairro, municipio, uf, cidade, status, tenant_id) VALUES (:nome, :nome_fantasia, :cpf_cnpj, :inscricao_estadual, :email, :telefone, :cep, :logradouro, :numero, :complemento, :bairro, :municipio, :uf, :cidade, :status, :tid)');

        $params = [
            'nome' => $nome,
            'nome_fantasia' => trim((string) ($data['nome_fantasia'] ?? '')),
            'cpf_cnpj' => $cnpj,
            'inscricao_estadual' => trim((string) ($data['inscricao_estadual'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'telefone' => trim((string) ($data['telefone'] ?? '')),
            'cep' => preg_replace('/\D/', '', (string) ($data['cep'] ?? '')),
            'logradouro' => trim((string) ($data['logradouro'] ?? '')),
            'numero' => trim((string) ($data['numero'] ?? '')),
            'complemento' => trim((string) ($data['complemento'] ?? '')),
            'bairro' => trim((string) ($data['bairro'] ?? '')),
            'municipio' => trim((string) ($data['municipio'] ?? '')),
            'uf' => trim((string) ($data['uf'] ?? '')),
            'cidade' => trim((string) ($data['cidade'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
        ];

        if (!empty($data['id'])) {
            $params['id'] = (int) $data['id'];
            $params['tid'] = $tenantId;
        } else {
            $params['tid'] = $tenantId;
        }

        $stmt->execute($params);
    }

    // --- usuários / funcionários ---
    private function ensureUsuarioPessoaLinkColumn(): bool
    {
        if ($this->hasColumn('usuarios', 'pessoa_id')) return true;
        try {
            $this->pdo->exec('ALTER TABLE usuarios ADD COLUMN pessoa_id INT NULL AFTER company_id');
            $this->columnCache['usuarios.pessoa_id'] = true;
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function listUsuarios(): array
    {
        try {
            $tenantId = $this->requireTenantId();
            $hasPersonLink = $this->ensureUsuarioPessoaLinkColumn();
            $personSelect = $hasPersonLink ? ', pessoa_id, (SELECT c.nome FROM clientes c WHERE c.id = usuarios.pessoa_id LIMIT 1) AS pessoa_nome' : ', NULL AS pessoa_id, NULL AS pessoa_nome';
            if ($this->hasColumn('usuarios', 'tenant_id')) {
                $stmt = $this->pdo->prepare('SELECT id, nome, email, role, avatar, status, cargo, permissions' . $personSelect . ' FROM usuarios WHERE tenant_id = :tid ORDER BY id DESC');
                $stmt->execute(['tid' => $tenantId]);
            } else {
                $stmt = $this->pdo->query('SELECT id, nome, email, role, avatar, status, cargo, permissions' . $personSelect . ' FROM usuarios ORDER BY id DESC');
            }
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    // Lista empresas cadastradas
    public function listCompanies(): array
    {
        // Sempre consultar a tabela `tenants` no DB principal (evita depender da conexão tenant-specific)
        try {
            $config = require __DIR__ . '/../config.php';
            $dbConf = $config['db'];
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
            $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            return $pdoMain->query('SELECT * FROM tenants ORDER BY id DESC')->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function findCompany(int $id): ?array
    {
        // Reaproveita função que já implementa fallback para o DB principal
        return $this->findTenantById($id);
    }

    public function saveCompany(array $data): void
    {
        // Save into tenants table. Generate uuid and slug if needed.
        $nome = trim((string)($data['nome_fantasia'] ?? $data['apelido'] ?? ''));
        $razao = trim((string)($data['razao_social'] ?? ''));
        $cnpj = preg_replace('/\D/', '', (string)($data['cnpj'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));

        if ($slug === '') {
            $slug = $this->generateUniqueSlug($nome);
        }

        $uuid = $data['uuid'] ?? bin2hex(random_bytes(8));

        $cep = preg_replace('/\D/', '', (string)($data['cep'] ?? ''));
        $uf = trim((string)($data['uf'] ?? ''));
        $logradouro = trim((string)($data['logradouro'] ?? ''));
        $numero = trim((string)($data['numero'] ?? ''));
        $complemento = trim((string)($data['complemento'] ?? ''));
        $bairro = trim((string)($data['bairro'] ?? ''));
        $telefone = trim((string)($data['telefone'] ?? ''));
        $codigo_ibge = trim((string)($data['codigo_ibge'] ?? $data['codigo_municipal'] ?? ''));

        if (!empty($data['id'])) {
            $stmt = $this->pdo->prepare('UPDATE tenants SET uuid = :uuid, nome_fantasia = :nome, razao_social = :razao, cnpj = :cnpj, slug = :slug, municipio = :municipio, regime = :regime, cep = :cep, uf = :uf, logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro, telefone = :telefone, codigo_ibge = :codigo_ibge, data = :data WHERE id = :id');
            $stmt->execute([
                'id' => (int)$data['id'],
                'uuid' => $uuid,
                'nome' => $nome,
                'razao' => $razao,
                'cnpj' => $cnpj,
                'slug' => $slug,
                'municipio' => trim((string)($data['municipio'] ?? '')),
                'regime' => trim((string)($data['regime'] ?? '')),
                'cep' => $cep,
                'uf' => $uf,
                'logradouro' => $logradouro,
                'numero' => $numero,
                'complemento' => $complemento,
                'bairro' => $bairro,
                'telefone' => $telefone,
                'codigo_ibge' => $codigo_ibge,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            // garantir admin para tenant editado
            try {
                $this->ensureTenantAdminExists((int)$data['id'], $slug, $nome);
            } catch (Throwable $e) {
                // ignore
            }
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO tenants (uuid, nome_fantasia, razao_social, cnpj, slug, municipio, regime, cep, uf, logradouro, numero, complemento, bairro, telefone, codigo_ibge, data) VALUES (:uuid, :nome, :razao, :cnpj, :slug, :municipio, :regime, :cep, :uf, :logradouro, :numero, :complemento, :bairro, :telefone, :codigo_ibge, :data)');
        try {
            $stmt->execute([
                'uuid' => $uuid,
                'nome' => $nome,
                'razao' => $razao,
                'cnpj' => $cnpj,
                'slug' => $slug,
                'municipio' => trim((string)($data['municipio'] ?? '')),
                'regime' => trim((string) ($data['regime'] ?? '')),
                'cep' => $cep,
                'uf' => $uf,
                'logradouro' => $logradouro,
                'numero' => $numero,
                'complemento' => $complemento,
                'bairro' => $bairro,
                'telefone' => $telefone,
                'codigo_ibge' => $codigo_ibge,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
            // se inseriu com sucesso, obter id, provisionar DB do tenant e garantir admin
            try {
                $insertedId = (int)$this->pdo->lastInsertId();
                if ($insertedId > 0) {
                    $this->provisionTenantDatabase($insertedId, $data['db_name'] ?? null);
                    $this->ensureTenantAdminExists($insertedId, $slug, $nome);
                }
            } catch (Throwable $e) {
                // ignore
            }
        } catch (PDOException $e) {
            // Se for duplicate key no índice de CNPJ, tenta atualizar o registro existente por cnpj
            if ($e->getCode() === '23000' && stripos($e->getMessage(), 'Duplicate') !== false) {
                $upd = $this->pdo->prepare('UPDATE tenants SET uuid = :uuid, nome_fantasia = :nome, razao_social = :razao, slug = :slug, municipio = :municipio, regime = :regime, cep = :cep, uf = :uf, logradouro = :logradouro, numero = :numero, complemento = :complemento, bairro = :bairro, telefone = :telefone, codigo_ibge = :codigo_ibge, data = :data WHERE cnpj = :cnpj');
                $upd->execute([
                    'uuid' => $uuid,
                    'nome' => $nome,
                    'razao' => $razao,
                    'slug' => $slug,
                    'municipio' => trim((string)($data['municipio'] ?? '')),
                    'regime' => trim((string) ($data['regime'] ?? '')),
                    'cep' => $cep,
                    'uf' => $uf,
                    'logradouro' => $logradouro,
                    'numero' => $numero,
                    'complemento' => $complemento,
                    'bairro' => $bairro,
                    'telefone' => $telefone,
                    'codigo_ibge' => $codigo_ibge,
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'cnpj' => $cnpj,
                ]);
                // após atualizar por CNPJ, localizar id e garantir admin
                try {
                    $config = require __DIR__ . '/../config.php';
                    $dbConf = $config['db'];
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
                    $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                    $row = $pdoMain->prepare('SELECT id FROM tenants WHERE cnpj = :cnpj LIMIT 1');
                    $row->execute(['cnpj' => $cnpj]);
                    $found = $row->fetch();
                    if ($found && !empty($found['id'])) {
                        $this->provisionTenantDatabase((int)$found['id'], $data['db_name'] ?? null);
                        $this->ensureTenantAdminExists((int)$found['id'], $slug, $nome);
                    }
                } catch (Throwable $e2) {
                    // ignore
                }
            } else {
                throw $e;
            }
        }
    }

    private function ensureTenantAdminExists(int $tenantId, string $slug, string $nome): void
    {
        // normaliza email admin@{slug}
        $slugNorm = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($slug)));
        $slugNorm = trim($slugNorm, '-');
        if ($slugNorm === '') $slugNorm = 'tenant' . $tenantId;
        $email = 'admin@' . $slugNorm;

        // conecta ao DB principal para gerenciar usuários centrais
        $config = require __DIR__ . '/../config.php';
        $dbConf = $config['db'];
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
        $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

        // verifica se já existe no DB principal
        $chk = $pdoMain->prepare('SELECT id FROM usuarios WHERE email = :email AND tenant_id = :tid LIMIT 1');
        $chk->execute(['email' => $email, 'tid' => $tenantId]);
        $exists = $chk->fetch();
        if (!$exists) {
            $senha = password_hash('admin', PASSWORD_DEFAULT);
            $nomeUser = $nome ?: 'Administrador';
            $ins = $pdoMain->prepare('INSERT INTO usuarios (nome, email, senha, role, email_verified, tenant_id, status) VALUES (:nome, :email, :senha, :role, :verified, :tid, :status)');
            $ins->execute([
                'nome' => $nomeUser,
                'email' => $email,
                'senha' => $senha,
                'role' => 'admin',
                'verified' => 1,
                'tid' => $tenantId,
                'status' => 'ativo',
            ]);
        }

        // também garante usuário admin dentro do DB do tenant (para portabilidade)
        try {
            $stmt = $pdoMain->prepare('SELECT db_name FROM tenants WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $tenantId]);
            $t = $stmt->fetch();
            $dbName = $t['db_name'] ?? null;
            if ($dbName) {
                // inicializa conexão para o tenant e insere admin local se necessário
                Database::withTenantDbName($dbName, function () use ($email, $nome): void {
                    $tenantPdo = Database::getConnection();
                    try {
                        $chk2 = $tenantPdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
                        $chk2->execute(['email' => $email]);
                        $exists2 = $chk2->fetch();
                        if (!$exists2) {
                            $senha2 = password_hash('admin', PASSWORD_DEFAULT);
                            $nomeUser2 = $nome ?: 'Administrador';
                            $ins2 = $tenantPdo->prepare('INSERT INTO usuarios (nome, email, senha, role, email_verified, status) VALUES (:nome, :email, :senha, :role, :verified, :status)');
                            $ins2->execute([
                                'nome' => $nomeUser2,
                                'email' => $email,
                                'senha' => $senha2,
                                'role' => 'admin',
                                'verified' => 1,
                                'status' => 'ativo',
                            ]);
                        }
                        // Também garante um usuário administrativo padrão local ao tenant: admin@local / senha 'admin'
                        try {
                            $localEmail = 'admin@local';
                            $chkLocal = $tenantPdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
                            $chkLocal->execute(['email' => $localEmail]);
                            $existsLocal = $chkLocal->fetch();
                            if (!$existsLocal) {
                                $senhaLocal = password_hash('admin', PASSWORD_DEFAULT);
                                $insLocal = $tenantPdo->prepare('INSERT INTO usuarios (nome, email, senha, role, email_verified, status) VALUES (:nome, :email, :senha, :role, :verified, :status)');
                                $insLocal->execute([
                                    'nome' => $nomeUser2 ?? ($nome ?: 'Administrador'),
                                    'email' => $localEmail,
                                    'senha' => $senhaLocal,
                                    'role' => 'admin',
                                    'verified' => 1,
                                    'status' => 'ativo',
                                ]);
                            }
                        } catch (Throwable $e) {
                            // ignore failures for local admin insertion
                        }
                    } catch (Throwable $e) {
                        // ignore tenant-level insertion errors
                    }
                });
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    private function provisionTenantDatabase(int $tenantId, ?string $dbNameProvided = null): string
    {
        $config = require __DIR__ . '/../config.php';
        $dbConf = $config['db'];

        // determina nome do DB: permite nome fornecido ou usa padrão baseado no DB principal
        if (!empty($dbNameProvided)) {
            $dbName = preg_replace('/[^0-9a-zA-Z_]/', '_', $dbNameProvided);
        } else {
            $base = preg_replace('/[^0-9a-zA-Z_]/', '_', $dbConf['database']);
            $dbName = sprintf('%s_tenant_%d', $base, $tenantId);
        }

        // cria o database via conexão administrativa
        $serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port']);
        $adminPdo = new PDO($serverDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $adminPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // atualiza campo db_name na tabela tenants no DB principal
        $mainDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
        $mainPdo = new PDO($mainDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $upd = $mainPdo->prepare('UPDATE tenants SET db_name = :db WHERE id = :id');
        $upd->execute(['db' => $dbName, 'id' => $tenantId]);

        // inicializa schema no novo DB usando a rotina existente em Database
        try {
            Database::withTenantDbName($dbName, function () {
                Database::getConnection(); // isso executa initializeSchema()
            });
        } catch (Throwable $e) {
            // se falhar, limpa tenant setting e rethrow
            Database::setTenantDbName(null);
            throw $e;
        }

        return $dbName;
    }

    // Public wrapper to provision tenant DB (used by maintenance scripts)
    public function provisionTenant(int $tenantId, ?string $dbNameProvided = null): string
    {
        return $this->provisionTenantDatabase($tenantId, $dbNameProvided);
    }

    // Cria (ou garante) a conta administrativa para um tenant específico.
    public function createTenantAdmin(int $tenantId): void
    {
        $t = $this->findTenantById($tenantId);
        if (!$t) throw new InvalidArgumentException('Tenant não encontrado: ' . $tenantId);
        $slug = $t['slug'] ?? (string)$tenantId;
        $nome = $t['nome_fantasia'] ?? ($t['razao_social'] ?? 'Administrador');
        $this->ensureTenantAdminExists($tenantId, $slug, $nome);
    }

    public function deleteCompany(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tenants WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Wrapper legado; novas entradas devem depender de CnpjLookupService.
    public function fetchCnpjData(string $cnpj): ?array
    {
        try {
            $result = (new \MiniErp\Services\CnpjLookupService(
                new \MiniErp\Infrastructure\BrasilApiCnpjProvider()
            ))->lookup($cnpj);
            return $result?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }

    // Atribui um usuário a uma empresa (compatibilidade)
    public function assignUserToCompany(int $userId, ?int $companyId): void
    {
        // somente atribui dentro do tenant da sessão
        $tenantId = $this->requireTenantId();
        $currentCompany = $_SESSION['current_company_id'] ?? null;
        if ($this->hasColumn('usuarios', 'tenant_id')) {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET company_id = :cid, tenant_id = :tid WHERE id = :id');
            $stmt->execute(['cid' => $currentCompany, 'tid' => $tenantId, 'id' => (int)$userId]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET company_id = :cid WHERE id = :id');
            $stmt->execute(['cid' => $currentCompany, 'id' => (int)$userId]);
        }
    }

    // find tenant by slug
    public function findTenantBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch() ?: null;
    }

    // Busca um tenant pela razão/ CNPJ diretamente no DB principal
    public function findTenantByCnpj(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if ($cnpj === '') return null;
        // Tenta na conexão atual e fallback para DB principal
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM tenants WHERE cnpj = :cnpj LIMIT 1');
            $stmt->execute(['cnpj' => $cnpj]);
            $row = $stmt->fetch();
            if ($row) return $row;
        } catch (Throwable $e) {
            // ignore and fallback
        }
        try {
            $config = require __DIR__ . '/../config.php';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db']['host'], $config['db']['port'], $config['db']['database']);
            $pdoMain = new PDO($dsn, $config['db']['username'], $config['db']['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            $stmt = $pdoMain->prepare('SELECT * FROM tenants WHERE cnpj = :cnpj LIMIT 1');
            $stmt->execute(['cnpj' => $cnpj]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = preg_replace('/[^a-z0-9]+/','-', strtolower(trim($name)));
        $base = trim($base, '-');
        if ($base === '') $base = 'tenant';
        $slug = $base;
        $i = 1;
        while (true) {
            $stmt = $this->pdo->prepare('SELECT id FROM tenants WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch();
            if (!$row) return $slug;
            $i++;
            $slug = $base . '-' . $i;
        }
    }

    public function findUsuarioById(int $id): ?array
    {
        // Recupera usuário por ID sem exigir tenant na sessão.
        // Necessário para popular a sessão após login quando o tenant ainda não foi definido.
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function findUsuarioByEmail(string $email, ?int $tenantId = null): ?array
    {
        // Se tenantId for fornecido, filtra por tenant. Caso contrário, busca global (útil para login/reset).
        if ($tenantId === null) {
            // Tenta na conexão atual (pode ser tenant-specific). Se não encontrar, tenta no DB principal.
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            if ($row) return $row;

            // fallback: consultar DB principal diretamente
            try {
                $config = require __DIR__ . '/../config.php';
                $dbConf = $config['db'];
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
                $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                $stmt = $pdoMain->prepare('SELECT * FROM usuarios WHERE email = :email');
                $stmt->execute(['email' => $email]);
                return $stmt->fetch() ?: null;
            } catch (Throwable $e) {
                return null;
            }
        }

        if ($this->hasColumn('usuarios', 'tenant_id')) {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email AND tenant_id = :tid');
            $stmt->execute(['email' => $email, 'tid' => $tenantId]);
            return $stmt->fetch() ?: null;
        }
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function saveUsuario(array $data): void
    {
        $nome = trim((string) ($data['nome'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        if ($nome === '' || $email === '') {
            throw new InvalidArgumentException('Nome e e-mail são obrigatórios para funcionário.');
        }

        $tenantId = $this->requireTenantId();
        $currentCompany = $_SESSION['current_company_id'] ?? null;

        $params = [
            'nome' => $nome,
            'email' => $email,
            'role' => trim((string) ($data['role'] ?? 'user')),
            'avatar' => trim((string) ($data['avatar'] ?? '')),
            'status' => trim((string) ($data['status'] ?? 'ativo')),
            'permissions' => trim((string) ($data['permissions'] ?? '')),
            'cargo' => trim((string) ($data['cargo'] ?? 'funcionario')),
            'company_id' => $currentCompany,
            'tenant_id' => $tenantId,
        ];
        if ($this->ensureUsuarioPessoaLinkColumn()) {
            $personId = (int)($data['pessoa_id'] ?? 0);
            if ($personId > 0) {
                $hasClientTenant = $this->hasColumn('clientes', 'tenant_id');
                $person = $this->pdo->prepare('SELECT id FROM clientes WHERE id = :id' . ($hasClientTenant ? ' AND tenant_id = :tid' : '') . ' LIMIT 1');
                $personParams = ['id' => $personId];
                if ($hasClientTenant) $personParams['tid'] = $tenantId;
                $person->execute($personParams);
                if (!$person->fetchColumn()) throw new InvalidArgumentException('A pessoa selecionada não pertence a esta empresa.');
            }
            $params['pessoa_id'] = $personId ?: null;
        }

        // trata senha se informado
        if (!empty($data['senha'])) {
            $params['senha'] = password_hash((string)$data['senha'], PASSWORD_DEFAULT);
        }

        // normaliza permissões quando vindas como array (checkboxes)
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $params['permissions'] = implode(',', array_map('trim', $data['permissions']));
        }

        if (!empty($data['id'])) {
            $set = [];
            foreach (array_keys($params) as $k) {
                $set[] = "$k = :$k";
            }
            $params['id'] = (int)$data['id'];
            if ($this->hasColumn('usuarios', 'tenant_id')) {
                $stmt = $this->pdo->prepare('UPDATE usuarios SET ' . implode(', ', $set) . ' WHERE id = :id AND tenant_id = :tenant_id');
                $params['tenant_id'] = $tenantId;
            } else {
                $stmt = $this->pdo->prepare('UPDATE usuarios SET ' . implode(', ', $set) . ' WHERE id = :id');
            }
            $stmt->execute($params);
            $this->syncTenantUserToMain((int)$data['id'], $tenantId);
            return;
        }

        // Novo usuário: adiciona campos de verificação de e-mail
        $params['email_verified'] = 0;
        $params['email_verification_token'] = bin2hex(random_bytes(12));

        // Se a tabela não possui tenant_id, remova do payload
        if (!$this->hasColumn('usuarios', 'tenant_id')) {
            unset($params['tenant_id']);
        }

        $fields = array_keys($params);
        $placeholders = array_map(fn($f) => ':' . $f, $fields);
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($params);
        $this->syncTenantUserToMain((int)$this->pdo->lastInsertId(), $tenantId);

        // envia e-mail de verificação (se configurado)
        try {
            require_once __DIR__ . '/Mailer.php';
            $mailer = new Mailer();
            $mailer->sendVerification($params['email'], $params['email_verification_token']);
        } catch (Throwable $e) {
            // não quebra a requisição se envio falhar
        }

        // criação de registros associados opcional (cliente / motorista)
        try {
            if (!empty($data['create_cliente']) && !empty($data['cpf_cnpj_cliente'])) {
                $clientePayload = [
                    'nome' => $nome,
                    'cpf_cnpj' => $data['cpf_cnpj_cliente'],
                    'email' => $email,
                    'telefone' => trim((string)($data['telefone'] ?? '')),
                    'status' => 'ativo',
                    'minimal' => true,
                ];
                $this->saveCliente($clientePayload);
            }

            if (!empty($data['create_motorista']) && !empty($data['cpf_motorista'])) {
                $motoristaPayload = [
                    'nome' => $nome,
                    'cpf' => preg_replace('/\D/', '', (string)$data['cpf_motorista']),
                    'telefone' => trim((string)($data['telefone'] ?? '')),
                    'status' => 'ativo',
                    'minimal' => true,
                ];
                $this->saveMotorista($motoristaPayload);
            }
        } catch (Throwable $e) {
            // não interrompe o fluxo se os cadastros associados falharem
        }
    }

    // Cria ou atualiza uma conta administrativa diretamente.
    public function createOrUpdateAdmin(string $email, string $password, string $nome = 'Administrador'): void
    {
        $existing = $this->findUsuarioByEmail($email);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        if ($existing) {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET senha = :senha, role = :role, nome = :nome, email_verified = 1 WHERE id = :id');
            $stmt->execute([
                'senha' => $hash,
                'role' => 'admin',
                'nome' => $nome,
                'id' => (int)$existing['id'],
            ]);
            return;
        }

        // Insere novo administrador e marca e-mail como verificado.
        $stmt = $this->pdo->prepare('INSERT INTO usuarios (nome, email, senha, role, email_verified) VALUES (:nome, :email, :senha, :role, 1)');
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $hash,
            'role' => 'admin',
        ]);
    }

    public function deleteUsuario(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $local=$this->findUsuarioById($id);
        if ($this->hasColumn('usuarios', 'tenant_id')) {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id AND tenant_id = :tid');
            $stmt->execute(['id' => $id, 'tid' => $tenantId]);
        } else {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->execute(['id' => $id]);
        }
        if($local){$main=$this->mainConnection();$main->prepare('DELETE FROM usuarios WHERE tenant_id=:tenant AND LOWER(email)=LOWER(:email)')->execute(['tenant'=>$tenantId,'email'=>$local['email']]);}
    }

    // Aprova um usuário pendente (ativa conta e marca e-mail como verificado)
    public function approveUsuario(int $id, string $role = 'user'): void
    {
        $role=in_array($role,['user','admin'],true)?$role:'user';
        $tenantId = $this->requireTenantId();
        if ($this->hasColumn('usuarios', 'tenant_id')) {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET status = :status, role=:role, cargo=IF(:role="admin","admin",cargo), email_verified = 1 WHERE id = :id AND tenant_id = :tid');
            $stmt->execute(['status' => 'ativo','role'=>$role, 'id' => $id, 'tid' => $tenantId]);
        } else {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET status = :status, role=:role, cargo=IF(:role="admin","admin",cargo), email_verified = 1 WHERE id = :id');
            $stmt->execute(['status' => 'ativo','role'=>$role, 'id' => $id]);
        }
        $this->syncTenantUserToMain($id, $tenantId);
    }

    private function mainConnection(): PDO
    {
        $config=require __DIR__.'/../config.php';$db=$config['db'];
        return new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$db['host'],$db['port'],$db['database']),$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    }

    private function syncTenantUserToMain(int $localUserId,int $tenantId): void
    {
        $local=$this->findUsuarioById($localUserId);if(!$local)return;
        $main=$this->mainConnection();$find=$main->prepare('SELECT id FROM usuarios WHERE tenant_id=:tenant AND LOWER(email)=LOWER(:email) LIMIT 1');$find->execute(['tenant'=>$tenantId,'email'=>$local['email']]);$mainId=(int)($find->fetchColumn()?:0);
        $values=['nome'=>$local['nome'],'email'=>strtolower((string)$local['email']),'senha'=>$local['senha'],'role'=>$local['role']?:'user','avatar'=>$local['avatar']??'','status'=>$local['status']?:'inativo','verified'=>(int)($local['email_verified']??0),'permissions'=>$local['permissions']??'','cargo'=>$local['cargo']??'funcionario','tenant'=>$tenantId,'company'=>$tenantId,'pessoa'=>$local['pessoa_id']??null];
        if($mainId>0){$values['id']=$mainId;$main->prepare('UPDATE usuarios SET nome=:nome,email=:email,senha=:senha,role=:role,avatar=:avatar,status=:status,email_verified=:verified,permissions=:permissions,cargo=:cargo,company_id=:company,pessoa_id=:pessoa WHERE id=:id AND tenant_id=:tenant')->execute($values);return;}
        $main->prepare('INSERT INTO usuarios(nome,email,senha,role,avatar,status,email_verified,permissions,cargo,tenant_id,company_id,pessoa_id) VALUES(:nome,:email,:senha,:role,:avatar,:status,:verified,:permissions,:cargo,:tenant,:company,:pessoa)')->execute($values);
    }

    // Cria um token de redefinição e o persiste em password_resets
    public function createPasswordReset(string $email, ?string $token = null): string
    {
        if ($token === null) {
            $token = bin2hex(random_bytes(16));
        }
        $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        // Tenta associar o reset ao tenant do usuário, quando existir.
        $user = $this->findUsuarioByEmail($email);
        $tid = $user['tenant_id'] ?? null;
        if ($tid === null) {
            $stmt = $this->pdo->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)');
            $stmt->execute(['email' => $email, 'token' => $token, 'expires_at' => $expires]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO password_resets (email, token, expires_at, tenant_id) VALUES (:email, :token, :expires_at, :tid)');
            $stmt->execute(['email' => $email, 'token' => $token, 'expires_at' => $expires, 'tid' => (int)$tid]);
        }
        return $token;
    }

    public function findPasswordResetByToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND expires_at > :now');
        $stmt->execute(['token' => $token, 'now' => (new DateTime())->format('Y-m-d H:i:s')]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findPasswordResetByEmailAndToken(string $email, string $token): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM password_resets WHERE token = :token AND email = :email AND expires_at > :now');
        $stmt->execute(['token' => $token, 'email' => $email, 'now' => (new DateTime())->format('Y-m-d H:i:s')]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function consumePasswordReset(string $token, string $newPassword): bool
    {
        $row = $this->findPasswordResetByToken($token);
        if (!$row) return false;
        $email = $row['email'];
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Se o token estiver associado a um tenant, atualiza apenas o usuário naquele tenant.
        $tid = $row['tenant_id'] ?? null;
        if ($tid !== null) {
            $upd = $this->pdo->prepare('UPDATE usuarios SET senha = :senha WHERE email = :email AND tenant_id = :tid');
            $upd->execute(['senha' => $hash, 'email' => $email, 'tid' => (int)$tid]);
        } else {
            $upd = $this->pdo->prepare('UPDATE usuarios SET senha = :senha WHERE email = :email');
            $upd->execute(['senha' => $hash, 'email' => $email]);
        }

        $del = $this->pdo->prepare('DELETE FROM password_resets WHERE token = :token');
        $del->execute(['token' => $token]);
        return true;
    }

    // Lista vendas com nome do cliente e quantidade de itens (filtradas por tenant)
    public function listVendas(): array
    {
        try {
            $tenantId = $this->requireTenantId();

            // Detecta se as tabelas possuem a coluna tenant_id para montar a cláusula WHERE com segurança
            $hasP = false;
            $hasV = false;
            try {
                $hasP = (bool) $this->pdo->query("SHOW COLUMNS FROM produtos LIKE 'tenant_id'")->fetch();
            } catch (Throwable $e) { $hasP = false; }
            try {
                $hasV = (bool) $this->pdo->query("SHOW COLUMNS FROM vendas LIKE 'tenant_id'")->fetch();
            } catch (Throwable $e) { $hasV = false; }

            $sql = 'SELECT v.*, c.nome AS cliente_nome, COUNT(iv.id) AS qtd_itens
                    FROM vendas v
                    LEFT JOIN clientes c ON c.id = v.cliente_id
                    LEFT JOIN itens_venda iv ON iv.venda_id = v.id
                    LEFT JOIN produtos p ON p.id = iv.produto_id';

            $where = [];
            if ($hasP) {
                $where[] = 'p.tenant_id = :tid';
            }
            if ($hasV) {
                $where[] = '(v.tenant_id IS NULL OR v.tenant_id = :tid)';
            }

            if (!empty($where)) {
                $sql .= '\n WHERE ' . implode(' AND ', $where);
            }

            $sql .= '\n GROUP BY v.id, c.nome\n ORDER BY v.id DESC';

            $stmt = $this->pdo->prepare($sql);
            if (!empty($where)) {
                $stmt->execute(['tid' => (int)$tenantId]);
            } else {
                $stmt->execute();
            }
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            // fallback permissivo
            try { return $this->pdo->query('SELECT v.*, c.nome AS cliente_nome, COUNT(iv.id) AS qtd_itens FROM vendas v LEFT JOIN clientes c ON c.id = v.cliente_id LEFT JOIN itens_venda iv ON iv.venda_id = v.id GROUP BY v.id, c.nome ORDER BY v.id DESC')->fetchAll(); } catch (Throwable $e2) { return []; }
        }
    }

    // Registra uma venda completa, validando cliente, quantidade e estoque.
    public function createSale(array $data, array $itens): int
    {
        $tenantId = $this->requireTenantId();

        $clienteId = (int) ($data['cliente_id'] ?? 0);
        $itensValidos = [];

        // Venda precisa ter cliente selecionado.
        if ($clienteId <= 0) {
            throw new InvalidArgumentException('Selecione um cliente para registrar a venda.');
        }

        // Percorre os itens do formulário para montar a venda.
        foreach ($itens as $item) {
            $produtoId = (int) ($item['produto_id'] ?? 0);
            $quantidade = (int) ($item['quantidade'] ?? 0);
            $produto = $this->findProduto($produtoId);

            // Ignora itens vazios ou inválidos.
            if ($produto === null || $quantidade <= 0) {
                continue;
            }

            // Evita vender mais do que há em estoque.
            if ($produto['estoque_atual'] < $quantidade) {
                throw new InvalidArgumentException('Estoque insuficiente para o produto: ' . $produto['nome']);
            }

            // Guarda os dados do item válido para a venda.
            $itensValidos[] = [
                'produto_id' => $produtoId,
                'quantidade' => $quantidade,
                'preco_unitario' => (float) $produto['preco'],
                'subtotal' => (float) $produto['preco'] * $quantidade,
            ];
        }

        // Se não houver nenhum item válido, devolve erro.
        if (count($itensValidos) === 0) {
            throw new InvalidArgumentException('Adicione ao menos um produto com quantidade válida.');
        }

        // Soma dos valores de todos os itens para calcular o total da venda.
        $total = array_sum(array_map(static fn ($item) => $item['subtotal'], $itensValidos));

        // Inicia uma transação para garantir consistência no banco.
        $this->pdo->beginTransaction();

        try {
            // Cria um registro principal de venda. Pode incluir CNPJ da empresa se fornecido.
            $empresaCnpj = isset($data['empresa_cnpj']) ? preg_replace('/\D/', '', (string)$data['empresa_cnpj']) : null;
            if ($empresaCnpj) {
                $stmt = $this->pdo->prepare('INSERT INTO vendas (cliente_id, data_venda, empresa_cnpj, total, status, tenant_id) VALUES (:cliente_id, :data_venda, :empresa_cnpj, :total, :status, :tid)');
                $stmt->execute([
                    'cliente_id' => $clienteId,
                    'data_venda' => date('Y-m-d'),
                    'empresa_cnpj' => $empresaCnpj,
                    'total' => $total,
                    'status' => 'finalizada',
                    'tid' => $tenantId,
                ]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO vendas (cliente_id, data_venda, total, status, tenant_id) VALUES (:cliente_id, :data_venda, :total, :status, :tid)');
                $stmt->execute([
                    'cliente_id' => $clienteId,
                    'data_venda' => date('Y-m-d'),
                    'total' => $total,
                    'status' => 'finalizada',
                    'tid' => $tenantId,
                ]);
            }

            $vendaId = (int) $this->pdo->lastInsertId();

            foreach ($itensValidos as $item) {
                $itemStmt = $this->pdo->prepare('INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal, tenant_id) VALUES (:venda_id, :produto_id, :quantidade, :preco_unitario, :subtotal, :tid)');
                $itemStmt->execute([
                    'venda_id' => $vendaId,
                    'produto_id' => $item['produto_id'],
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'subtotal' => $item['subtotal'],
                    'tid' => $tenantId,
                ]);

                // Atualiza o estoque do produto vendido.
                $stockStmt = $this->pdo->prepare('UPDATE produtos SET estoque_atual = estoque_atual - :quantidade WHERE id = :id AND tenant_id = :tid');
                $stockStmt->execute([
                    'quantidade' => $item['quantidade'],
                    'id' => $item['produto_id'],
                    'tid' => $tenantId,
                ]);
            }

            $this->pdo->commit();
            return $vendaId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Busca um produto por ID (filtrado por tenant)
    public function findProduto(int $id): ?array
    {
        $tenantId = $this->requireTenantId();
        $hasTenant = $this->hasColumn('produtos', 'tenant_id');
        $stmt = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id' . ($hasTenant ? ' AND tenant_id = :tid' : ''));
        $params = ['id' => $id];
        if ($hasTenant) $params['tid'] = $tenantId;
        $stmt->execute($params);
        $produto = $stmt->fetch();
        return $produto ?: null;
    }

    // Cria ou atualiza um produto (tenant obrigatório)
    public function saveProduto(array $data): void
    {
        $tenantId = $this->requireTenantId();
        // Ignora tenant_id/company_id vindos do payload para evitar manipulação pelo frontend
        unset($data['tenant_id'], $data['company_id']);
        $currentCompany = $_SESSION['current_company_id'] ?? null;

        $nome = trim((string) ($data['nome'] ?? ''));
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $preco = (float) ($data['preco'] ?? 0.0);

        if ($nome === '' || $codigo === '') {
            throw new InvalidArgumentException('Nome e código do produto são obrigatórios.');
        }

        $payload = array_merge([
            'nome' => $nome,
            'codigo' => $codigo,
            'categoria' => trim((string)($data['categoria'] ?? '')),
            'preco' => max(0, $preco),
            'estoque_atual' => max(0, (float)($data['estoque_atual'] ?? 0)),
            'status' => in_array(($data['status'] ?? 'ativo'), ['ativo', 'inativo'], true) ? $data['status'] : 'ativo',
            'company_id' => $currentCompany,
        ], (new \MiniErp\Services\ProductFiscalData($data))->toArray());
        if ($this->hasColumn('produtos', 'tenant_id')) $payload['tenant_id'] = $tenantId;
        $payload = array_filter($payload, fn(string $field): bool => $this->hasColumn('produtos', $field), ARRAY_FILTER_USE_KEY);

        if (!empty($data['id'])) {
            $set = array_map(fn(string $field): string => "$field = :$field", array_keys($payload));
            $payload['id'] = (int)$data['id'];
            $hasTenant = array_key_exists('tenant_id', $payload);
            $stmt = $this->pdo->prepare('UPDATE produtos SET ' . implode(', ', $set) . ' WHERE id = :id' . ($hasTenant ? ' AND tenant_id = :tenant_id' : ''));
            $stmt->execute($payload);
            return;
        }

        $fields = array_keys($payload);
        $stmt = $this->pdo->prepare('INSERT INTO produtos (`' . implode('`,`', $fields) . '`) VALUES (:' . implode(',:', $fields) . ')');
        $stmt->execute($payload);
    }

    // Remove um produto pelo ID (verifica tenant)
    public function deleteProduto(int $id): void
    {
        $tenantId = $this->requireTenantId();
        $hasTenant = $this->hasColumn('produtos', 'tenant_id');
        $stmt = $this->pdo->prepare("UPDATE produtos SET status = 'inativo' WHERE id = :id" . ($hasTenant ? ' AND tenant_id = :tid' : ''));
        $params = ['id' => $id];
        if ($hasTenant) $params['tid'] = $tenantId;
        $stmt->execute($params);
    }

}
