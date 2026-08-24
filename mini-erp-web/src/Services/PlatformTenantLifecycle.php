<?php

declare(strict_types=1);

namespace MiniErp\Services;

final class PlatformTenantLifecycle
{
    private const TRANSITIONS = [
        'cadastrada' => ['provisionando'],
        'provisionando' => ['ativa', 'cadastrada'],
        'ativa' => ['parcialmente_bloqueada', 'bloqueada', 'arquivada'],
        'parcialmente_bloqueada' => ['ativa', 'bloqueada'],
        'bloqueada' => ['ativa', 'arquivada'],
        'arquivada' => [],
    ];

    public function interpret(string $status, bool $legacyBlocked = false): string
    {
        $normalized = strtolower(trim($status));

        // Compatibility with existing MAIN records. No persisted value is changed.
        if ($normalized === 'ativo' || $normalized === 'active') {
            $normalized = 'ativa';
        }
        if ($normalized === 'partially_blocked') {
            $normalized = 'parcialmente_bloqueada';
        }
        if ($normalized === 'blocked') {
            $normalized = 'bloqueada';
        }
        if ($normalized === 'archived') {
            $normalized = 'arquivada';
        }

        if ($legacyBlocked && $normalized === 'ativa') {
            return 'bloqueada';
        }

        return array_key_exists($normalized, self::TRANSITIONS) ? $normalized : 'desconhecida';
    }

    /** @return array{edit: bool, provision: bool, users: bool, erp: bool, block: bool, unblock: bool} */
    public function actions(string $status, ?string $dbName = null, bool $legacyBlocked = false): array
    {
        $state = $this->interpret($status, $legacyBlocked);
        $closed = ['edit' => false, 'provision' => false, 'users' => false, 'erp' => false, 'block' => false, 'unblock' => false];

        return match ($state) {
            'cadastrada' => array_merge($closed, ['edit' => true, 'provision' => true]),
            'provisionando' => $closed,
            'ativa' => array_merge($closed, [
                'edit' => true,
                'users' => true,
                'erp' => trim((string) $dbName) !== '',
                'block' => true,
            ]),
            'parcialmente_bloqueada' => array_merge($closed, ['edit' => true, 'users' => true, 'block' => true, 'unblock' => true]),
            'bloqueada' => array_merge($closed, ['edit' => true, 'users' => true, 'unblock' => true]),
            default => $closed,
        };
    }

    public function canTransition(string $from, string $to): bool
    {
        $from = $this->interpret($from);
        $to = $this->interpret($to);

        return $from !== 'desconhecida'
            && $to !== 'desconhecida'
            && in_array($to, self::TRANSITIONS[$from], true);
    }
}
