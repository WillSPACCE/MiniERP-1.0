<?php
declare(strict_types=1);
if(getenv('RUN_REAL_SEFAZ_HOMOLOGATION_TESTS')!=='1'){echo"SefazStatusRealHomologationTest SKIPPED (requires explicit user action through UI)\n";exit;}throw new RuntimeException('Use a Central Fiscal autenticada apos selecionar explicitamente o tenant 5 ou 14; este comando nunca escolhe empresa real automaticamente.');
