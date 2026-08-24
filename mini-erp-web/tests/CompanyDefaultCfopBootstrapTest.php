<?php
declare(strict_types=1);
$repo=(string)file_get_contents(__DIR__.'/../src/Repositories/CompanyFiscalSettingsRepository.php');foreach(['ENTRY_INTERNAL\'=>\'1102','ENTRY_INTERSTATE\'=>\'2102','EXIT_INTERNAL\'=>\'5102','EXIT_INTERSTATE\'=>\'6102']as$needle)if(!str_contains($repo,$needle))throw new RuntimeException($needle);if(!str_contains($repo,'if(isset($before[$context]))continue'))throw new RuntimeException('custom CFOP overwrite guard');if(!str_contains($repo,"status='ativo'"))throw new RuntimeException('official CFOP guard');echo"CompanyDefaultCfopBootstrap OK\n";
