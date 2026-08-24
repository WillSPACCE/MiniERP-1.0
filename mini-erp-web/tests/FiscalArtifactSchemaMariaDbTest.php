<?php
declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalArtifactSchemaMariaDbTest SKIPPED\n";
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

$cfg = require __DIR__ . '/../config.php';
$dbCfg = $cfg['db'];
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port']), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'mini_erp_test_fiscal_schema_' . bin2hex(random_bytes(4));
$server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port'], $database), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

try {
    $schema = (string) file_get_contents(__DIR__ . '/../database/tenant-template/v1/schema.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $schema) ?: [])) as $statement) {
        $pdo->exec($statement);
    }

    $migration = (string) file_get_contents(__DIR__ . '/../migrations/20260822_complete_fiscal_artifact_runtime.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $migration) ?: [])) as $statement) {
        $pdo->exec($statement);
    }

    $reservationCols = array_column($pdo->query('DESCRIBE fiscal_number_reservations')->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $artifactCols = array_column($pdo->query('DESCRIBE fiscal_artifacts')->fetchAll(PDO::FETCH_ASSOC), 'Field');

    foreach (['fiscal_document_version', 'fiscal_series_id', 'cnf', 'access_key', 'idempotency_key', 'updated_at'] as $col) {
        if (!in_array($col, $reservationCols, true)) {
            throw new RuntimeException("missing reservation column {$col}");
        }
    }

    foreach (['fiscal_document_version', 'certificate_id', 'number_reservation_id', 'model', 'environment', 'series', 'number', 'access_key', 'schema_package', 'schema_version', 'schema_checksum', 'sha256', 'size_bytes', 'updated_at'] as $col) {
        if (!in_array($col, $artifactCols, true)) {
            throw new RuntimeException("missing artifact column {$col}");
        }
    }

    $uniq = $pdo->query("SHOW INDEX FROM fiscal_number_reservations WHERE Key_name='uq_reservation_number'")->fetchAll(PDO::FETCH_ASSOC);
    if (count($uniq) < 1) {
        throw new RuntimeException('missing reservation unique number index');
    }

    $artifactIndex = $pdo->query("SHOW INDEX FROM fiscal_artifacts WHERE Key_name='idx_artifact_document'")->fetchAll(PDO::FETCH_ASSOC);
    if (count($artifactIndex) < 1) {
        throw new RuntimeException('missing artifact document index');
    }

    echo "FiscalArtifactSchemaMariaDbTest OK\n";
} finally {
    $pdo = null;
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
