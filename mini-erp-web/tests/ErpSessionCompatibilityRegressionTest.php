<?php
declare(strict_types=1);
require_once __DIR__.'/../app/Database.php';
require_once __DIR__.'/../app/Repository.php';
require_once __DIR__.'/../src/Context/TenantContext.php';
require_once __DIR__.'/../src/Infrastructure/TenantConnectionResolver.php';

use MiniErp\Context\TenantContext;
use MiniErp\Infrastructure\TenantConnectionResolver;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = ['erp_user_id' => 1, 'erp_tenant_id' => 14];

$pdo = (new TenantConnectionResolver(__DIR__.'/../config.php'))->resolve(new TenantContext(1, 14, 14));
$repo = new Repository($pdo, false);

try {
    $repo->listClientes('');
    echo "ERP_ONLY_SESSION_ACCEPTED\n";
} catch (Throwable $error) {
    echo 'ERP_ONLY_SESSION_REJECTED: ' . $error->getMessage() . "\n";
    exit(1);
}
