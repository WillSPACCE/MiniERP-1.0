<?php
declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalArtifactTemplateSchemaTest SKIPPED\n";
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

$cfg = require __DIR__ . '/../config.php';
$dbCfg = $cfg['db'];
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port']), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'mini_erp_test_template_schema_' . bin2hex(random_bytes(4));
$server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port'], $database), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $schema = (string) file_get_contents(__DIR__ . '/../database/tenant-template/v1/schema.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $schema) ?: [])) as $statement) {
        $pdo->exec($statement);
    }

    foreach (['fiscal_number_reservations', 'fiscal_artifacts'] as $table) {
        $cols = array_column($pdo->query("DESCRIBE `{$table}`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        if ($table === 'fiscal_number_reservations' && !in_array('idempotency_key', $cols, true)) {
            throw new RuntimeException("template missing idempotency_key in {$table}");
        }
        if ($table === 'fiscal_artifacts' && !in_array('schema_package', $cols, true)) {
            throw new RuntimeException("template missing schema_package in {$table}");
        }
    }

    echo "FiscalArtifactTemplateSchemaTest OK\n";
} finally {
    $pdo = null;
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
