<?php

declare(strict_types=1);

namespace MiniErp\Adapters;

use DomainException;
use MiniErp\Contracts\ErpAuthenticationReaderContract;
use MiniErp\Infrastructure\TenantConnectionResolver;
use MiniErp\Services\ErpAuthenticationService;

final class ErpLegacyBootstrap
{
    public function __construct(
        private ErpAuthenticationReaderContract $reader,
        private ErpAuthenticationService $authentication,
        private TenantConnectionResolver $connections
    ) {}

    /** @return array{result: object, user: array, connection: \PDO, database: string} */
    public function bootstrap(array &$session): array
    {
        $userId = (int) ($session['erp_user_id'] ?? 0);
        $tenantId = (int) ($session['erp_tenant_id'] ?? 0);
        $result = $this->authentication->restore($userId, $tenantId);
        $user = $this->reader->findUserById($userId);
        if ($user === null || (int) ($user['tenant_id'] ?? 0) !== $result->tenantContext->getEffectiveTenantId()) {
            throw new DomainException('The authenticated ERP identity cannot be restored safely.');
        }

        $connection = $this->connections->resolve($result->tenantContext);
        $database = (string) $connection->query('SELECT DATABASE()')->fetchColumn();
        $expected = 'mini_erp_tenant_' . $result->tenantContext->getEffectiveTenantId();
        if (!hash_equals($expected, $database)) {
            throw new DomainException('The resolved connection does not match the authenticated tenant.');
        }

        // Explicit legacy boundary. Values are derived only from the revalidated identity.
        $session['user_id'] = $result->identity->getUserId();
        $session['tenant_id'] = $result->tenantContext->getEffectiveTenantId();
        unset($session['current_company_id']);

        return ['result' => $result, 'user' => $user, 'connection' => $connection, 'database' => $database];
    }
}
