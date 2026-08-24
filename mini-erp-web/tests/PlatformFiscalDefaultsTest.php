<?php
declare(strict_types=1);
function pg(bool$v,string$m):void{if(!$v)throw new RuntimeException($m);}$sql=(string)file_get_contents(__DIR__.'/../migrations/20260824_create_platform_fiscal_defaults.sql');foreach(['1102','2102','5102','6102','platform_fiscal_defaults']as$v)pg(str_contains($sql,$v),$v);$repo=(string)file_get_contents(__DIR__.'/../src/Repositories/PlatformFiscalDefaultsRepository.php');foreach(["entry_internal_cfop'=>'1'","entry_interstate_cfop'=>'2'","exit_internal_cfop'=>'5'","exit_interstate_cfop'=>'6'"]as$v)pg(str_contains($repo,$v),$v);echo"PlatformFiscalDefaults OK\n";
