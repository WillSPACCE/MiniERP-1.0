<?php

declare(strict_types=1);

// Regressão estática: o fluxo create_tenant_user deve usar associação explícita
// sem trocar temporariamente o tenant/empresa da sessão administrativa.

$index = file_get_contents(__DIR__ . '/../public/index.php');
if ($index === false) {
    fwrite(STDERR, "Cannot read public/index.php\n");
    exit(1);
}

$start = strpos($index, "case 'create_tenant_user':");
$end = strpos($index, "case 'create_tenant_admin':", $start === false ? 0 : $start);

if ($start === false || $end === false || $end <= $start) {
    fwrite(STDERR, "Regression FAILED: create_tenant_user block was not found.\n");
    exit(1);
}

$flow = substr($index, $start, $end - $start);

$forbiddenPatterns = [
    "\$_SESSION['tenant_id'] = \$id;",
    "\$_SESSION['current_company_id'] = \$id;",
    '$prevTenant',
    '$prevCompany',
];

foreach ($forbiddenPatterns as $pattern) {
    if (strpos($flow, $pattern) !== false) {
        fwrite(STDERR, "Regression FAILED: forbidden legacy session mutation/restoration remains: {$pattern}\n");
        exit(1);
    }
}

$requiredPatterns = [
    'MainDbUserRepository.php',
    'new \\MiniErp\\Repositories\\MainDbUserRepository()',
    'assignUserToCompanyExplicit((int)$user[\'id\'], $id, $id)',
];

foreach ($requiredPatterns as $pattern) {
    if (strpos($flow, $pattern) === false) {
        fwrite(STDERR, "Regression FAILED: explicit tenant/company assignment is missing: {$pattern}\n");
        exit(1);
    }
}

echo "CreateTenantUserSessionRegression OK\n";
