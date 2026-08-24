<?php

declare(strict_types=1);

use MiniErp\Infrastructure\MariaDbTenantProvisioner;
use MiniErp\Repositories\PlatformTenantRepository;
use MiniErp\Services\PlatformTenantDatabaseName;
use MiniErp\Services\ProvisionPlatformTenantService;

require_once __DIR__ . '/_context.php';
[$connection, , $authorizer, $identity] = requireAuthorizedPlatformContext();
require_once __DIR__ . '/../../src/Contracts/PlatformTenantRepositoryContract.php';
require_once __DIR__ . '/../../src/Contracts/TenantDatabaseProvisionerContract.php';
require_once __DIR__ . '/../../src/Repositories/PlatformTenantRepository.php';
require_once __DIR__ . '/../../src/Services/PlatformTenantDatabaseName.php';
require_once __DIR__ . '/../../src/Services/TenantSchemaTemplate.php';
require_once __DIR__ . '/../../src/Infrastructure/MariaDbTenantProvisioner.php';
require_once __DIR__ . '/../../src/Services/ProvisionPlatformTenantService.php';

$tenantId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$repository = new PlatformTenantRepository($connection);
$tenant = $tenantId > 0 ? $repository->findById($tenantId) : null;
if ($tenant === null) { http_response_code(404); echo 'Empresa não encontrada.'; exit; }

$config = require __DIR__ . '/../../config.php';
$schemaTemplate = new \MiniErp\Services\TenantSchemaTemplate(__DIR__ . '/../../database/tenant-template');
$provisioner = new MariaDbTenantProvisioner($connection, $config['db'], $schemaTemplate);
$service = new ProvisionPlatformTenantService($repository, $provisioner, $authorizer, $schemaTemplate);
$databaseName = PlatformTenantDatabaseName::fromTenantId($tenantId);
$canProvision = (string) ($tenant['status'] ?? '') === 'cadastrada'
    && empty($tenant['blocked'])
    && trim((string) ($tenant['db_name'] ?? '')) === ''
    && $repository->supportsSchemaVersion()
    && !$provisioner->databaseExists($databaseName);

if (empty($_SESSION['platform_provision_csrf'])) {
    $_SESSION['platform_provision_csrf'] = bin2hex(random_bytes(32));
}
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
    if (!hash_equals((string) $_SESSION['platform_provision_csrf'], $token)) {
        $error = 'Sessão de confirmação expirada. Recarregue a página.';
    } else {
        try {
            $service->provision($identity, $tenantId);
            $_SESSION['platform_provision_csrf'] = bin2hex(random_bytes(32));
            header('Location: /plataforma/empresa.php?id=' . $tenantId . '&provisioned=1&next=fiscal');
            exit;
        } catch (DomainException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable) {
            $error = 'O provisionamento falhou com segurança. A empresa não foi ativada.';
        }
    }
}

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Provisionar empresa</title><link rel="stylesheet" href="/assets/platform.css"></head><body><main class="content"><p><a href="/plataforma/empresa.php?id=<?= $tenantId ?>">← Detalhes da empresa</a></p><section class="panel"><p class="eyebrow">Ambiente dedicado</p><h1>Provisionar <?= $escape($tenant['nome_fantasia'] ?? '') ?></h1>
<?php if ($error !== ''): ?><p class="message error" role="alert"><?= $escape($error) ?></p><?php endif; ?>
<div class="detail-grid"><div class="detail"><small>Empresa</small><strong><?= $escape($tenant['razao_social'] ?? '') ?></strong></div><div class="detail"><small>Tenant ID</small><strong><?= $tenantId ?></strong></div><div class="detail"><small>Slug</small><strong><?= $escape($tenant['slug'] ?? '') ?></strong></div><div class="detail"><small>Status</small><strong><?= $escape($tenant['status'] ?? '') ?></strong></div><div class="detail"><small>Ambiente</small><strong>Novo banco dedicado</strong></div><div class="detail"><small>Banco derivado pelo servidor</small><strong><?= $escape($databaseName) ?></strong></div><div class="detail"><small>Template oficial</small><strong><?= $escape($schemaTemplate->currentVersion()) ?></strong></div></div>
<div class="confirm-box"><strong>Atenção</strong><p>O provisionamento criará o ambiente dedicado desta empresa e aplicará somente a estrutura segura, sem seeds ou dados de outra empresa.</p></div>
<?php if ($canProvision): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['platform_provision_csrf']) ?>"><div class="form-actions"><button class="btn" type="submit">Confirmar provisionamento</button><a class="btn secondary" href="/plataforma/empresa.php?id=<?= $tenantId ?>">Cancelar</a></div></form><?php else: ?><p class="message warning">As pré-condições não permitem provisionar esta empresa. Nenhuma alteração será executada.</p><a class="btn secondary" href="/plataforma/empresa.php?id=<?= $tenantId ?>">Voltar</a><?php endif; ?>
</section></main></body></html>
