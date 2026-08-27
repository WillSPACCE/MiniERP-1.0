<?php

declare(strict_types=1);

$login = file_get_contents(__DIR__ . '/../public/plataforma/login.php');
$dashboard = file_get_contents(__DIR__ . '/../public/plataforma/index.php');
$contextBootstrap = file_get_contents(__DIR__ . '/../public/plataforma/_context.php');
$logout = file_get_contents(__DIR__ . '/../public/plataforma/logout.php');
$reader = file_get_contents(__DIR__ . '/../src/Repositories/ControlPlaneReader.php');

if ($login === false || $dashboard === false || $contextBootstrap === false || $logout === false || $reader === false) {
    fwrite(STDERR, "ASSERTION FAILED: control-plane source files must exist\n");
    exit(1);
}

function sourceAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

foreach ([
    "\$_SESSION['tenant_id'] =",
    "\$_SESSION['current_company_id'] =",
    'Database::setTenantDbName',
    'TenantContext',
    'SelectedTenant',
    'TenantConnectionResolver',
] as $forbidden) {
    sourceAssert(strpos($login . $dashboard . $contextBootstrap . $logout, $forbidden) === false, "platform entrypoints must not contain {$forbidden}");
}

sourceAssert(strpos($login, 'password_verify') === false, 'password verification is delegated to the authentication service');
sourceAssert(strpos($login, "\$_SESSION['platform_admin']") !== false && strpos($login, "'admin_id' =>") !== false, 'login stores a dedicated structured platform identity');
sourceAssert(strpos($login, 'session_regenerate_id(true)') !== false, 'login regenerates the session id');
sourceAssert(strpos($login, 'csrf_token') !== false && strpos($login, 'hash_equals') !== false, 'login has CSRF protection');
sourceAssert(strpos($contextBootstrap, "\$_SESSION['platform_admin']['admin_id']") !== false, 'dashboard bootstrap requires the dedicated platform session');
sourceAssert(strpos($contextBootstrap, 'PersistedPlatformAdminAuthorizer') !== false, 'dashboard bootstrap reauthorizes against persisted roles');
sourceAssert(strpos($contextBootstrap, 'ControlPlaneConnectionFactory') !== false, 'dashboard bootstrap uses the dedicated MAIN connection factory');
sourceAssert(strpos($logout, "unset(\$_SESSION['platform_admin']") !== false, 'logout removes only the platform identity');
sourceAssert(strpos($logout, 'hash_equals') !== false, 'logout requires a valid CSRF token');
sourceAssert(strpos($logout, "session_destroy") === false, 'platform logout does not destroy the ERP session');

$normalizedReader = strtoupper($reader);
sourceAssert(strpos($normalizedReader, 'SELECT ') !== false, 'reader contains SELECT operations');
foreach (['INSERT ', 'UPDATE ', 'DELETE ', 'CREATE ', 'ALTER ', 'DROP '] as $writeVerb) {
    sourceAssert(strpos($normalizedReader, $writeVerb) === false, "reader must not contain {$writeVerb}");
}
sourceAssert(strpos($reader, 'FROM tenants') !== false, 'tenant listing reads the control-plane tenant registry');
sourceAssert(strpos($reader, 'db_name') !== false, 'reader obtains db_name canonically from MAIN for lifecycle presentation');
sourceAssert(strpos($dashboard, "\$_GET['db_name']") === false, 'dashboard never accepts db_name from GET');
sourceAssert(strpos($dashboard, "\$_POST['db_name']") === false, 'dashboard never accepts db_name from POST');
$tableStart = strpos($dashboard, '<table>');
$tableEnd = strpos($dashboard, '</table>', $tableStart === false ? 0 : $tableStart);
sourceAssert($tableStart !== false && $tableEnd !== false, 'dashboard tenant table must exist');
$tenantTable = substr($dashboard, $tableStart, $tableEnd - $tableStart);
foreach (['senha', 'password', 'token', 'dsn'] as $sensitiveField) {
    sourceAssert(strpos(strtolower($tenantTable), $sensitiveField) === false, "tenant listing must not expose {$sensitiveField}");
}

echo "PlatformEntrypointIsolation OK\n";
