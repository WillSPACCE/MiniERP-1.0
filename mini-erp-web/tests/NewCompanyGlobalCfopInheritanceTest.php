<?php
declare(strict_types=1);
$source=(string)file_get_contents(__DIR__.'/../src/Repositories/TenantEstablishmentRepository.php');foreach(['mini_erp.platform_fiscal_defaults','ENTRY_INTERNAL','ENTRY_INTERSTATE','EXIT_INTERNAL','EXIT_INTERSTATE','INSERT IGNORE INTO establishment_cfop_defaults','ON DUPLICATE KEY UPDATE']as$needle)if(!str_contains($source,$needle))throw new RuntimeException($needle);if(strpos($source,'$stmt->execute($data)')>strpos($source,'inheritGlobalCfops'))throw new RuntimeException('inheritance order');echo"NewCompanyGlobalCfopInheritance OK\n";
