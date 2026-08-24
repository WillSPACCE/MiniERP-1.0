<?php

declare(strict_types=1);

use MiniErp\Repositories\PlatformTenantRepository;
use MiniErp\Repositories\PlatformTenantUserRepository;
use MiniErp\Services\PlatformTenantUserService;

require_once __DIR__ . '/_context.php';
require_once __DIR__ . '/_layout.php';

function requireTenantUserContext(): array
{
    [$connection, , $authorizer, $identity] = requireAuthorizedPlatformContext();
    require_once __DIR__ . '/../../src/Context/SelectedTenant.php';
    require_once __DIR__ . '/../../src/Context/AdministrativeContext.php';
    require_once __DIR__ . '/../../src/Contracts/PlatformTenantRepositoryContract.php';
    require_once __DIR__ . '/../../src/Contracts/PlatformTenantUserRepositoryContract.php';
    require_once __DIR__ . '/../../src/Repositories/PlatformTenantRepository.php';
    require_once __DIR__ . '/../../src/Repositories/PlatformTenantUserRepository.php';
    require_once __DIR__ . '/../../src/Services/PlatformTenantUserService.php';

    $tenantId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $tenantRepository = new PlatformTenantRepository($connection);
    $tenant = $tenantId > 0 ? $tenantRepository->findById($tenantId) : null;
    if ($tenant === null) { http_response_code(404); echo 'Empresa não encontrada.'; exit; }
    $service = new PlatformTenantUserService(new PlatformTenantUserRepository($connection), $authorizer);
    try { $administrativeContext = $service->context($identity, $tenant); }
    catch (DomainException $exception) { http_response_code(409); echo platformEscape($exception->getMessage()); exit; }
    if (empty($_SESSION['platform_user_csrf'])) $_SESSION['platform_user_csrf'] = bin2hex(random_bytes(32));
    return [$identity, $tenant, $service, $administrativeContext];
}

function requirePlatformUserCsrf(): void
{
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals((string) ($_SESSION['platform_user_csrf'] ?? ''), $token)) throw new DomainException('Sessão do formulário expirada.');
}
