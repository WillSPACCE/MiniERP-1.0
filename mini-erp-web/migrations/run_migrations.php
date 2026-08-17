<?php
// Simple migration runner: executa todos os arquivos .sql em migrations/ na ordem alfabética.
// Usa as credenciais de config.php na raiz do projeto.

require_once __DIR__ . '/../config.php';
$config = require __DIR__ . '/../config.php';
$db = $config['db'] ?? null;
if (!$db) {
    echo "Falha: config.php não contém array 'db'.\n";
    exit(2);
}

$driver = $db['driver'] ?? 'mysql';
if ($driver !== 'mysql') {
    echo "Atenção: driver definido em config.php não é 'mysql' (foi: $driver). O runner suporta apenas MySQL.\n";
}

$host = $db['host'] ?? '127.0.0.1';
$port = $db['port'] ?? '3306';
$name = $db['database'] ?? null;
$user = $db['username'] ?? null;
$pass = $db['password'] ?? null;

if (!$name || !$user) {
    echo "Faltam credenciais em config.php (database/username).\n";
    exit(2);
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Throwable $e) {
    echo "Erro ao conectar ao MySQL: " . $e->getMessage() . "\n";
    exit(3);
}

$migs = glob(__DIR__ . '/*.sql');
sort($migs);
if (empty($migs)) {
    echo "Nenhuma migration encontrada em migrations/.\n";
    exit(0);
}

foreach ($migs as $file) {
    echo "Aplicando: " . basename($file) . "...\n";
    $sql = file_get_contents($file);
    // Remove comentários SQL iniciados com -- e linhas em branco
    $lines = preg_split('/\R/', $sql);
    $clean = [];
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '') continue;
        if (strpos($t, '--') === 0) continue;
        $clean[] = $line;
    }
    $sql = implode("\n", $clean);
    // Divide por ponto-e-vírgula para executar instruções separadas
    $stmts = preg_split('/;\s*\n/', $sql);
    foreach ($stmts as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        try {
            $pdo->exec($stmt);
        } catch (Throwable $e) {
            echo "ERRO ao executar statement: " . $e->getMessage() . "\n";
            echo "Statement:\n" . $stmt . "\n";
            // Não aborta: continua com próximas statements
        }
    }
    echo "OK\n";
}

echo "Todas migrations processadas. Verifique mensagens acima para erros.\n";
return 0;
