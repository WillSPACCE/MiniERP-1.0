<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Services/TenantSchemaTemplate.php';

use MiniErp\Services\TenantSchemaTemplate;

function templateAssert(bool $condition, string $message): void
{
    if (!$condition) { fwrite(STDERR, "ASSERTION FAILED: {$message}\n"); exit(1); }
}

$template = new TenantSchemaTemplate(__DIR__ . '/../database/tenant-template');
templateAssert($template->currentVersion() === 'v1', 'current template version is explicit');
templateAssert(realpath($template->currentSchemaPath()) === realpath(__DIR__ . '/../database/tenant-template/v1/schema.sql'), 'current version resolves canonical path');

try { $template->schemaPathFor('v999'); templateAssert(false, 'arbitrary version rejected'); } catch (InvalidArgumentException) {}
try { (new TenantSchemaTemplate(__DIR__ . '/missing-template-root'))->currentSchemaPath(); templateAssert(false, 'missing template rejected'); } catch (RuntimeException) {}

$temporaryRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mini-erp-empty-template-' . bin2hex(random_bytes(5));
mkdir($temporaryRoot . DIRECTORY_SEPARATOR . 'v1', 0777, true);
file_put_contents($temporaryRoot . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'schema.sql', '   ');
try {
    (new TenantSchemaTemplate($temporaryRoot))->currentSchemaPath();
    templateAssert(false, 'empty template rejected');
} catch (RuntimeException) {
    // expected
} finally {
    unlink($temporaryRoot . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'schema.sql');
    rmdir($temporaryRoot . DIRECTORY_SEPARATOR . 'v1');
    rmdir($temporaryRoot);
}

$schema = file_get_contents($template->currentSchemaPath());
templateAssert($schema !== false && stripos($schema, 'INSERT INTO') === false, 'template has no seeds or operational rows');
foreach (['tenants', 'usuarios', 'password_resets'] as $controlPlaneTable) {
    templateAssert(!preg_match('/CREATE\s+TABLE\s+`?' . $controlPlaneTable . '`?/i', (string) $schema), "{$controlPlaneTable} is excluded from tenant template");
}

echo "TenantSchemaTemplate OK\n";
