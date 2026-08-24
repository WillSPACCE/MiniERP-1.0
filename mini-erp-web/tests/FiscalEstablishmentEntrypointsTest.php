<?php
declare(strict_types=1);
$root=dirname(__DIR__); $platform=file_get_contents($root.'/public/plataforma/empresa-fiscal.php'); $erp=file_get_contents($root.'/public/index.php'); $migration=file_get_contents($root.'/migrations/20260821_create_tenant_establishments.sql'); $template=file_get_contents($root.'/database/tenant-template/v1/schema.sql');
foreach ([$platform,$erp,$migration,$template] as $content) if ($content===false) exit(1);
function entryAssert(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"ASSERTION FAILED: {$message}\n");exit(1);}}
entryAssert(str_contains($platform,'resolveAdministrative($administrativeContext)'),'platform uses explicit administrative tenant'); entryAssert(!str_contains($platform,'$_SESSION' . "['tenant_id']"),'platform does not mutate tenant session'); entryAssert(str_contains($erp,'getEffectiveTenantId()'),'ERP uses authenticated effective tenant'); entryAssert(str_contains($migration,'CREATE TABLE IF NOT EXISTS establishments'),'migration is additive/idempotent'); entryAssert(str_contains($template,'CREATE TABLE IF NOT EXISTS establishments'),'official template provisions establishments'); entryAssert(!preg_match('/\b(DROP|ALTER|DELETE)\b/i',$migration),'migration is non-destructive');
echo "FiscalEstablishmentEntrypoints OK\n";
