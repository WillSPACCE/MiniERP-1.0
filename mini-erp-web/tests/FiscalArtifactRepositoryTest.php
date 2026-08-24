<?php
declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') {
    echo "FiscalArtifactRepositoryTest SKIPPED\n";
    exit;
}
require __DIR__ . '/../vendor/autoload.php';

use MiniErp\Repositories\FiscalArtifactRepository;
use MiniErp\Repositories\FiscalNumberReservationRepository;

$cfg = require __DIR__ . '/../config.php';
$dbCfg = $cfg['db'];
$server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port']), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$database = 'mini_erp_test_fiscal_artifact_' . bin2hex(random_bytes(4));
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

    $pdo->exec("INSERT INTO fiscal_orders(tenant_id,operation_type,establishment_id,person_id,internal_code,operation_date,commercial_status,fiscal_status,operation_nature,fiscal_model,purpose,final_consumer,presence_indicator,payment_condition,payment_method,first_due_date,notes,seller_id,carrier_id,driver_id,freight_mode,discount_amount,freight_amount,insurance_amount,other_amount,products_total,grand_total,created_by) VALUES(1,'VENDA',7,1,'ORDER-003','2024-01-01','SAVED','NOT_CREATED','VENDA','55','NORMAL',1,'1','','',NULL,'',NULL,NULL,NULL,'9',0,0,0,0,0,0,1)");
    $pdo->exec("INSERT INTO fiscal_series(tenant_id,establishment_id,model,series,next_number,environment,emission_type,process_version,active) VALUES(1,7,'55',1,1,2,1,'TEST_ONLY',1)");
    $pdo->exec("INSERT INTO fiscal_documents(tenant_id,source_order_id,document_version,idempotency_key,status,pending_json,issuer_snapshot_json,recipient_snapshot_json,payment_snapshot_json,transport_snapshot_json,totals_json,created_by) VALUES(1,1,1,'doc-key-3','FISCAL_READY','[]','{}','{}','{}','{}','{}',1)");
    $pdo->exec("INSERT INTO fiscal_certificates(tenant_id,establishment_id,storage_reference,file_name,sha256,fingerprint_sha256,subject,issuer,serial_number,tax_id,valid_from,valid_until,status,secret_reference,uploaded_by,active) VALUES(1,7,'/tmp/cert.p12','cert.p12','abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789','fingerprint','CN=TEST','CN=TEST','SER123','12345678000195','2024-01-01 00:00:00','2035-01-01 00:00:00','VALID','secret-ref',1,1)");

    $reservationRepo = new FiscalNumberReservationRepository($pdo, 1);
    $reservation = $reservationRepo->create([
        'establishment_id' => 7,
        'fiscal_document_id' => 1,
        'fiscal_document_version' => 1,
        'fiscal_series_id' => 1,
        'model' => '55',
        'environment' => 2,
        'series' => 1,
        'number' => 1,
        'cnf' => 'A1B2C3D4',
        'access_key' => '55555555555555555555555555555555555555555555',
        'status' => 'RESERVED',
        'idempotency_key' => 'reserve-k3',
        'created_by' => 1,
    ]);

    $artifactRepo = new FiscalArtifactRepository($pdo, 1);
    $artifact = $artifactRepo->create([
        'establishment_id' => 7,
        'fiscal_document_id' => 1,
        'fiscal_document_version' => 1,
        'certificate_id' => 1,
        'number_reservation_id' => (int) $reservation['id'],
        'model' => '55',
        'environment' => 2,
        'series' => 1,
        'number' => 1,
        'access_key' => '55555555555555555555555555555555555555555555',
        'artifact_type' => 'NFE',
        'status' => 'XSD_VALID_OFFLINE',
        'schema_package' => 'nfe',
        'schema_version' => '010e-v1.02',
        'schema_checksum' => hash('sha256', 'nfe010e'),
        'storage_reference' => 'storage/fiscal/tenant-1/establishment-7/documents/document-1/test.xml',
        'sha256' => hash('sha256', '<xml />'),
        'size_bytes' => 12,
        'created_by' => 1,
    ]);

    $fromRepo = $artifactRepo->findById((int) $artifact['id']);
    if ($fromRepo['sha256'] !== hash('sha256', '<xml />')) {
        throw new RuntimeException('artifact hash mismatch');
    }

    $forDocument = $artifactRepo->findByDocumentVersion(1, 1);
    if ((int) $forDocument['id'] !== (int) $artifact['id']) {
        throw new RuntimeException('artifact document lookup failed');
    }

    $artifactRepo->updateStatus((int) $artifact['id'], 'XSD_VALID_OFFLINE');
    $valid = $artifactRepo->findValidArtifact(1, 1, 'NFE');
    if ((int) $valid['id'] !== (int) $artifact['id']) {
        throw new RuntimeException('valid artifact lookup failed');
    }

    $otherTenant = new FiscalArtifactRepository($pdo, 2);
    $cross = $otherTenant->findById((int) $artifact['id']);
    if ($cross !== null) {
        throw new RuntimeException('cross tenant artifact leak');
    }

    echo "FiscalArtifactRepositoryTest OK\n";
} finally {
    $pdo = null;
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
