<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/PlatformTenantLifecycle.php';

use MiniErp\Services\PlatformTenantLifecycle;

function lifecycleAssert(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

$lifecycle = new PlatformTenantLifecycle();
$registered = $lifecycle->actions('cadastrada');
lifecycleAssert($registered['edit'] && $registered['provision'], 'cadastrada allows edit and provision');
lifecycleAssert(!$registered['users'] && !$registered['erp'] && !$registered['block'], 'cadastrada blocks operational actions');

$running = $lifecycle->actions('provisionando');
lifecycleAssert(!in_array(true, $running, true), 'provisionando closes all exposed actions');

$active = $lifecycle->actions('ativa', 'mini_erp_tenant_14');
lifecycleAssert($active['edit'] && $active['users'] && $active['erp'] && $active['block'], 'ativa with database enables expected visual actions');
lifecycleAssert(!$lifecycle->actions('ativa', null)['erp'], 'ERP requires a non-empty db_name');
lifecycleAssert($lifecycle->interpret('ativo') === 'ativa', 'legacy ativo is interpreted without persistence changes');

$blocked = $lifecycle->actions('bloqueada', 'mini_erp_tenant_14');
lifecycleAssert(!$blocked['erp'] && $blocked['unblock'], 'bloqueada denies ERP and exposes future unblock action');
lifecycleAssert($lifecycle->interpret('ativo', true) === 'bloqueada', 'legacy blocked flag is interpreted conservatively');

$archived = $lifecycle->actions('arquivada', 'mini_erp_tenant_14');
lifecycleAssert(!in_array(true, $archived, true), 'arquivada permits no operation');

lifecycleAssert($lifecycle->canTransition('cadastrada', 'provisionando'), 'documented transition is accepted');
lifecycleAssert($lifecycle->canTransition('provisionando', 'ativa'), 'activation transition is accepted');
lifecycleAssert(!$lifecycle->canTransition('arquivada', 'ativa'), 'archived tenant cannot be reactivated');
lifecycleAssert(!$lifecycle->canTransition('cadastrada', 'bloqueada'), 'invalid transition is rejected');
lifecycleAssert(!in_array(true, $lifecycle->actions('status_inventado'), true), 'unknown status is fail-closed');

echo "PlatformTenantLifecycle OK\n";
