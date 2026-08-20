<?php

declare(strict_types=1);

namespace MiniErp\Adapters;

use InvalidArgumentException;

final class LegacyContextAdapter
{
    public static function fromLegacyState(array $legacyState): LegacyTenantContextInput
    {
        $userId = self::readRequiredPositiveInt($legacyState, ['user_id', 'userId', 'authenticatedUserId']);
        $legacyTenantId = self::readOptionalPositiveInt($legacyState, ['tenant_id', 'tenantId', 'legacyTenantId']);
        $legacyCompanyId = self::readOptionalPositiveInt($legacyState, ['company_id', 'companyId', 'legacyCompanyId']);
        $currentCompanyId = self::readOptionalPositiveInt($legacyState, ['current_company_id', 'currentCompanyId']);
        $selectedTenantId = self::readOptionalPositiveInt($legacyState, ['selected_tenant_id', 'selectedTenantId']);
        $slug = self::readOptionalString($legacyState, ['slug']);
        $isGlobalAdmin = self::readOptionalBool($legacyState, ['is_global_admin', 'isGlobalAdmin']);

        return new LegacyTenantContextInput(
            authenticatedUserId: $userId,
            legacyTenantId: $legacyTenantId,
            legacyCompanyId: $legacyCompanyId,
            currentCompanyId: $currentCompanyId,
            selectedTenantId: $selectedTenantId,
            slug: $slug,
            isGlobalAdmin: $isGlobalAdmin
        );
    }

    private static function readRequiredPositiveInt(array $state, array $candidateKeys): int
    {
        $value = self::readScalar($state, $candidateKeys);

        if ($value === null) {
            throw new InvalidArgumentException('Legacy authenticated user id is required.');
        }

        if (!is_int($value) && !ctype_digit((string) $value)) {
            throw new InvalidArgumentException('Legacy user id must be a positive integer.');
        }

        $normalized = (int) $value;
        if ($normalized < 1) {
            throw new InvalidArgumentException('Legacy user id must be greater than zero.');
        }

        return $normalized;
    }

    private static function readOptionalPositiveInt(array $state, array $candidateKeys): ?int
    {
        $value = self::readScalar($state, $candidateKeys);

        if ($value === null || $value === '') {
            return null;
        }

        if (!is_int($value) && !ctype_digit((string) $value)) {
            throw new InvalidArgumentException(sprintf('Legacy field %s must be a positive integer or null.', implode(', ', $candidateKeys)));
        }

        $normalized = (int) $value;
        if ($normalized < 1) {
            throw new InvalidArgumentException(sprintf('Legacy field %s must be greater than zero when provided.', implode(', ', $candidateKeys)));
        }

        return $normalized;
    }

    private static function readOptionalString(array $state, array $candidateKeys): ?string
    {
        $value = self::readScalar($state, $candidateKeys);

        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            throw new InvalidArgumentException(sprintf('Legacy field %s cannot be blank when provided.', implode(', ', $candidateKeys)));
        }

        return $stringValue;
    }

    private static function readOptionalBool(array $state, array $candidateKeys): ?bool
    {
        $value = self::readScalar($state, $candidateKeys);

        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '1' || $normalized === 'true' || $normalized === 'yes') {
            return true;
        }

        if ($normalized === '0' || $normalized === 'false' || $normalized === 'no') {
            return false;
        }

        throw new InvalidArgumentException(sprintf('Legacy field %s must be a boolean-like value when provided.', implode(', ', $candidateKeys)));
    }

    private static function readScalar(array $state, array $candidateKeys)
    {
        foreach ($candidateKeys as $key) {
            if (array_key_exists($key, $state)) {
                return $state[$key];
            }
        }

        return null;
    }
}
