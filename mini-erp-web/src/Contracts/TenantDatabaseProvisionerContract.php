<?php

declare(strict_types=1);

namespace MiniErp\Contracts;

interface TenantDatabaseProvisionerContract
{
    public function databaseExists(string $databaseName): bool;

    public function createDatabase(string $databaseName): void;

    public function installSchema(string $databaseName, string $schemaVersion): void;

    public function validateSchema(string $databaseName, string $schemaVersion): bool;
}
