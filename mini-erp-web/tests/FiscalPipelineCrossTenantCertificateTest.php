<?php

declare(strict_types=1);
if (getenv('RUN_FISCAL_MARIADB_TESTS') !== '1') { echo "FiscalPipelineCrossTenantCertificateTest SKIPPED\n"; exit; }

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

use MiniErp\Fiscal\OperationalCertificateProvider;
use MiniErp\Fiscal\A1CertificateInspector;
use MiniErp\Fiscal\PrivateCertificateStorage;
use MiniErp\Fiscal\LocalEncryptedSecretStorage;
use MiniErp\Repositories\FiscalConfigurationRepository;

[$server, $pdo, $database] = fiscal_test_db();
$tenantA = 995010;
$tenantB = 995011;
$establishmentId = 995110;
$taxId = '12345678000195';

try {
    // Seed certificate under tenant B but for establishmentId of A
    fiscal_seed_certificate($pdo, $tenantB, $establishmentId, $taxId, 'TENANT B CERT');

    // Provider for tenant A should NOT see tenant B certificate
    $certRoot = sys_get_temp_dir() . '/minierp-fiscal-certificates-' . bin2hex(random_bytes(4));
    $secretRoot = sys_get_temp_dir() . '/minierp-fiscal-secrets-' . bin2hex(random_bytes(4));
    $provider = new OperationalCertificateProvider(new A1CertificateInspector(), new PrivateCertificateStorage($certRoot), new LocalEncryptedSecretStorage($secretRoot, str_repeat('S', 32)), new FiscalConfigurationRepository($pdo, $tenantA));

    $ready = $provider->certificateReady($establishmentId, $taxId);
    fiscal_assert($ready === false, 'cross-tenant certificate should not be visible');

    echo "FiscalPipelineCrossTenantCertificateTest OK\n";
} finally {
    fiscal_drop_database($server, $database);
}
