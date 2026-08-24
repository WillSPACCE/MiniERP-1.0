<?php
declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalNumberReservationRepositoryTest SKIPPED\n";
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

use MiniErp\Repositories\FiscalNumberReservationRepository;

$cfg = require __DIR__ . '/../config.php';
$dbCfg = $cfg['db'];
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port']), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'mini_erp_test_fiscal_reservation_' . bin2hex(random_bytes(4));
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

    $pdo->exec("INSERT INTO fiscal_orders(tenant_id,operation_type,establishment_id,person_id,internal_code,operation_date,commercial_status,fiscal_status,operation_nature,fiscal_model,purpose,final_consumer,presence_indicator,payment_condition,payment_method,first_due_date,notes,seller_id,carrier_id,driver_id,freight_mode,discount_amount,freight_amount,insurance_amount,other_amount,products_total,grand_total,created_by) VALUES(1,'VENDA',7,1,'ORDER-001','2024-01-01','SAVED','NOT_CREATED','VENDA','55','NORMAL',1,'1','','',NULL,'',NULL,NULL,NULL,'9',0,0,0,0,0,0,1)");
    $pdo->exec("INSERT INTO fiscal_series(tenant_id,establishment_id,model,series,next_number,environment,emission_type,process_version,active) VALUES(1,7,'55',1,1,2,1,'TEST_ONLY',1)");
    $pdo->exec("INSERT INTO fiscal_documents(tenant_id,source_order_id,document_version,idempotency_key,status,pending_json,issuer_snapshot_json,recipient_snapshot_json,payment_snapshot_json,transport_snapshot_json,totals_json,created_by) VALUES(1,1,1,'doc-key-1','FISCAL_READY','[]','{}','{}','{}','{}','{}',1)");

    $repo = new FiscalNumberReservationRepository($pdo, 1);
    $created = $repo->create([
        'establishment_id' => 7,
        'fiscal_document_id' => 1,
        'fiscal_document_version' => 1,
        'fiscal_series_id' => 1,
        'model' => '55',
        'environment' => 2,
        'series' => 1,
        'number' => 1,
        'cnf' => '12345678',
        'access_key' => '44444444444444444444444444444444444444444444',
        'status' => 'RESERVED',
        'idempotency_key' => 'reserve-k1',
        'created_by' => 1,
    ]);

    $found = $repo->findById((int) $created['id']);
    if ($found['number'] !== 1 || $found['access_key'] !== '44444444444444444444444444444444444444444444') {
        throw new RuntimeException('reservation metadata mismatch');
    }

    $byDoc = $repo->findByDocumentVersion(1, 1);
    if ((int) $byDoc['id'] !== (int) $created['id']) {
        throw new RuntimeException('document version lookup failed');
    }

    $repo->updateStatus((int) $created['id'], 'FAILED');
    $updated = $repo->findById((int) $created['id']);
    if ($updated['status'] !== 'FAILED') {
        throw new RuntimeException('status update failed');
    }

    try {
        $repo->create([
            'establishment_id' => 7,
            'fiscal_document_id' => 1,
            'fiscal_document_version' => 1,
            'fiscal_series_id' => 1,
            'model' => '55',
            'environment' => 2,
            'series' => 1,
            'number' => 1,
            'cnf' => '87654321',
            'access_key' => '11111111111111111111111111111111111111111111',
            'status' => 'RESERVED',
            'idempotency_key' => 'reserve-k2',
            'created_by' => 1,
        ]);
        throw new RuntimeException('duplicate reservation accepted');
    } catch (RuntimeException $e) {
        if (!str_contains($e->getMessage(), 'duplicate') && !str_contains($e->getMessage(), 'UNIQUE')) {
            throw $e;
        }
    }

    echo "FiscalNumberReservationRepositoryTest OK\n";
} finally {
    $pdo = null;
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
