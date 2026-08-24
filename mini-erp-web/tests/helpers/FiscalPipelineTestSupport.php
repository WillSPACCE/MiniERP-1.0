<?php

declare(strict_types=1);

use MiniErp\Fiscal\A1CertificateInspector;
use MiniErp\Fiscal\FiscalArtifactStorage;
use MiniErp\Fiscal\FiscalNfeXmlBuilder;
use MiniErp\Fiscal\FiscalXmlSigner;
use MiniErp\Fiscal\LocalEncryptedSecretStorage;
use MiniErp\Fiscal\NfeAccessKeyGenerator;
use MiniErp\Fiscal\OfficialNfeXsdValidator;
use MiniErp\Fiscal\OperationalCertificateProvider;
use MiniErp\Fiscal\PrivateCertificateStorage;
use MiniErp\Repositories\FiscalConfigurationRepository;
use MiniErp\Repositories\FiscalOperationRepository;
use MiniErp\Services\EstablishmentFiscalConfigurationService;
use MiniErp\Services\FiscalDocumentDTOFactory;
use MiniErp\Services\OfflineFiscalDocumentPipelineService;

function fiscal_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function fiscal_test_db(): array
{
    $cfg = require __DIR__ . '/../../config.php';
    $dbCfg = $cfg['db'];
    $server = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port']), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $database = 'mini_erp_test_fiscal_' . bin2hex(random_bytes(4));
    $server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbCfg['host'], $dbCfg['port'], $database), $dbCfg['username'], $dbCfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $schema = (string) file_get_contents(__DIR__ . '/../../database/tenant-template/v1/schema.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $schema) ?: [])) as $statement) {
        $pdo->exec($statement);
    }
    $migration = (string) file_get_contents(__DIR__ . '/../../migrations/20260822_complete_fiscal_artifact_runtime.sql');
    foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\R|$)/', $migration) ?: [])) as $statement) {
        $pdo->exec($statement);
    }

    return [$server, $pdo, $database];
}

function fiscal_seed_document(PDO $pdo, int $tenantId, int $establishmentId, string $model = '55', int $documentVersion = 1, int $documentId = 0): array
{
    $productId = 0;
    $stmt = $pdo->prepare('SELECT id FROM produtos WHERE codigo = ? LIMIT 1');
    $stmt->execute(['PFT-001']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $productId = (int) $row['id'];
    } else {
        $pdo->exec("INSERT INTO produtos(nome,codigo,ncm,unidade,preco,status) VALUES('Produto Fiscal Teste','PFT-001','01012100','UN',100.00,'ativo')");
        $productId = (int) $pdo->lastInsertId();
    }

    $orderId = 1;
    $pdo->exec("INSERT INTO fiscal_orders(tenant_id,operation_type,establishment_id,person_id,internal_code,operation_date,commercial_status,fiscal_status,operation_nature,fiscal_model,purpose,final_consumer,presence_indicator,payment_condition,payment_method,first_due_date,notes,seller_id,carrier_id,driver_id,freight_mode,discount_amount,freight_amount,insurance_amount,other_amount,products_total,grand_total,created_by) VALUES($tenantId,'VENDA',$establishmentId,1,'ORDER-FISCAL-TEST', '2024-01-01','SAVED','NOT_CREATED','VENDA','$model','NORMAL',1,'1','','','2024-01-10','TEST_ONLY',NULL,NULL,NULL,'9',0,0,0,0,100.00,100.00,1)");
    $orderId = (int) $pdo->lastInsertId();

    $pdo->exec("INSERT INTO fiscal_order_items(order_id,product_id,quantity,unit_price,discount_amount,freight_amount,insurance_amount,other_amount,gross_total,net_total) VALUES($orderId,$productId,1,100.00,0,0,0,0,100.00,100.00)");
    $sstmt = $pdo->prepare('SELECT id FROM fiscal_series WHERE tenant_id=? AND establishment_id=? AND model=? AND series=? LIMIT 1');
    $sstmt->execute([$tenantId, $establishmentId, $model, 1]);
    if (!$sstmt->fetch(PDO::FETCH_ASSOC)) {
        $pdo->exec("INSERT INTO fiscal_series(tenant_id,establishment_id,model,series,next_number,environment,emission_type,process_version,active) VALUES($tenantId,$establishmentId,'$model',1,1,2,1,'TEST_ONLY',1)");
    }

    $documentKey = 'fiscal-doc-' . $documentVersion . '-' . bin2hex(random_bytes(4));
    $issuer = [
        'tax_id' => '12345678000195',
        'legal_name' => 'Emitente Fiscal TESTE',
        'trade_name' => 'Emitente Fiscal TESTE',
        'state' => 'SP',
        'state_code' => '35',
        'state_registration' => '123456789',
        'tax_regime_code' => '1',
        'street' => 'Rua A',
        'number' => '100',
        'district' => 'Centro',
        'city_name' => 'Sao Paulo',
        'city_ibge_code' => '3550308',
        'postal_code' => '01001000',
        'country_code' => '1058',
        'country_name' => 'BRASIL',
        'cnpj' => '12345678000195',
    ];
    $recipient = [
        'tax_id' => '98765432000110',
        'legal_name' => 'Cliente Fiscal TESTE',
        'state' => 'SP',
        'state_code' => '35',
        'street' => 'Rua B',
        'number' => '20',
        'district' => 'Centro',
        'city_name' => 'Sao Paulo',
        'city_ibge_code' => '3550308',
        'postal_code' => '01001000',
        'country_code' => '1058',
        'country_name' => 'BRASIL',
    ];
    $totals = ['model' => $model, 'grand' => '100.00', 'products' => '100.00', 'purpose' => 1, 'final_consumer' => 1, 'presence_indicator' => 1, 'operation_nature' => 'VENDA', 'operation_type' => 'EXIT'];

    $taxJson = json_encode([
        'cfop' => '5102',
        'icms' => ['cst' => '00', 'modBC' => '3', 'base' => '100.00', 'rate' => '18.00', 'amount' => '18.00'],
        'ipi' => [],
        'pis' => ['cst' => '01', 'base' => '100.00', 'rate' => '0.65', 'amount' => '0.65'],
        'cofins' => ['cst' => '01', 'base' => '100.00', 'rate' => '3.00', 'amount' => '3.00'],
    ], JSON_THROW_ON_ERROR);

    $pdo->prepare("INSERT INTO fiscal_documents(tenant_id,source_order_id,document_version,idempotency_key,status,pending_json,issuer_snapshot_json,recipient_snapshot_json,payment_snapshot_json,transport_snapshot_json,totals_json,created_by) VALUES(?,?, ?, ?, 'FISCAL_READY', ?, ?, ?, ?, ?, ?, 1)")->execute([
        $tenantId,
        $orderId,
        $documentVersion,
        $documentKey,
        json_encode([], JSON_THROW_ON_ERROR),
        json_encode($issuer, JSON_THROW_ON_ERROR),
        json_encode($recipient, JSON_THROW_ON_ERROR),
        json_encode(['method' => '01', 'amount' => '100.00'], JSON_THROW_ON_ERROR),
        json_encode(['freight_mode' => 9], JSON_THROW_ON_ERROR),
        json_encode($totals, JSON_THROW_ON_ERROR),
    ]);
    $documentId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO fiscal_document_items(fiscal_document_id,source_order_item_id,product_id,product_snapshot_json,quantity_commercial,quantity_taxable,unit_value_commercial,unit_value_taxable,gross_total,discount_amount,freight_amount,insurance_amount,other_amount,net_total,included_in_total,fiscal_status,tax_context_json,tax_resolution_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
        $documentId,
        1,
        $productId,
        json_encode(['codigo' => 'PFT-001', 'nome' => 'Produto Fiscal Teste', 'ncm' => '01012100', 'unidade' => 'UN', 'taxable_unit' => 'UN', 'merchandise_origin' => '0'], JSON_THROW_ON_ERROR),
        '1.0000',
        '1.0000',
        '100.00',
        '100.00',
        '100.00',
        '0.00',
        '0.00',
        '0.00',
        '0.00',
        '100.00',
        1,
        'READY',
        json_encode(['base' => '100.00'], JSON_THROW_ON_ERROR),
        $taxJson,
    ]);

    return ['tenant_id' => $tenantId, 'establishment_id' => $establishmentId, 'order_id' => $orderId, 'document_id' => $documentId, 'product_id' => $productId, 'idempotency_key' => $documentKey];
}

function fiscal_seed_certificate(PDO $pdo, int $tenantId, int $establishmentId, string $taxId, string $subject = 'TEST ONLY'): array
{
    $tmp = sys_get_temp_dir() . '/minierp-fiscal-pipeline-' . bin2hex(random_bytes(4));
    $certs = new PrivateCertificateStorage($tmp . '/certs');
    $vault = new LocalEncryptedSecretStorage($tmp . '/secrets', str_repeat('S', 32));
    $repo = new FiscalConfigurationRepository($pdo, $tenantId);
    $service = new EstablishmentFiscalConfigurationService($repo, new A1CertificateInspector(), $certs, $vault, $tenantId);

    $make = function (string $cn) use ($taxId): array {
        $o = ['config' => 'C:/xampp/php/extras/openssl/openssl.cnf', 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256'];
        $k = openssl_pkey_new($o);
        $c = openssl_csr_new(['commonName' => $cn, 'organizationName' => 'TEST ONLY', 'serialNumber' => $taxId, 'countryName' => 'BR'], $k, $o);
        $x = openssl_csr_sign($c, null, $k, 2, $o);
        $p = bin2hex(random_bytes(14));
        $pfx = '';
        openssl_pkcs12_export($x, $pfx, $k, $p);
        return [$pfx, $p];
    };

    [$pfx, $password] = $make($subject);
    $meta = $service->upload($establishmentId, 'test.pfx', $pfx, $password, $taxId, 999);
    $certificate = $repo->certificate($establishmentId);
    fiscal_assert($certificate !== null, 'certificate not uploaded');
    return ['tenant_id' => $tenantId, 'establishment_id' => $establishmentId, 'certificate_id' => (int) $certificate['id'], 'storage_root' => $tmp, 'certs' => $certs, 'vault' => $vault, 'meta' => $meta];
}

function fiscal_pipeline_service(PDO $pdo, int $tenantId, ?string $xsdRoot = null, ?string $artifactRoot = null, ?string $certificateRoot = null, ?string $secretRoot = null): OfflineFiscalDocumentPipelineService
{
    $storageRoot = $artifactRoot ?? sys_get_temp_dir() . '/minierp-fiscal-artifacts-' . bin2hex(random_bytes(4));
    $validatorRoot = $xsdRoot ?? __DIR__ . '/../../resources/fiscal/xsd/nfe/010e-v1.02/NFe';
    $configRepo = new FiscalConfigurationRepository($pdo, $tenantId);
    $certificateRoot = $certificateRoot ?: sys_get_temp_dir() . '/minierp-fiscal-certificates';
    $secretRoot = $secretRoot ?: sys_get_temp_dir() . '/minierp-fiscal-secrets';
    $provider = new OperationalCertificateProvider(
        new A1CertificateInspector(),
        new PrivateCertificateStorage($certificateRoot),
        new LocalEncryptedSecretStorage($secretRoot, str_repeat('S', 32)),
        $configRepo,
    );

    return new OfflineFiscalDocumentPipelineService(
        $pdo,
        new FiscalOperationRepository($pdo, $tenantId),
        $configRepo,
        $provider,
        null,
        new FiscalArtifactStorage($storageRoot),
        new OfficialNfeXsdValidator($validatorRoot),
        new FiscalXmlSigner(),
        new NfeAccessKeyGenerator(),
        new FiscalDocumentDTOFactory(),
        new FiscalNfeXmlBuilder(),
    );
}

function fiscal_drop_database(PDO $server, string $database): void
{
    $server->exec("DROP DATABASE IF EXISTS `{$database}`");
}
