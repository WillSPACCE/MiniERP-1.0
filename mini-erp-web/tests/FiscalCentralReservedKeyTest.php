<?php
declare(strict_types=1);
$ui=(string)file_get_contents(__DIR__.'/../public/fiscal_notes.php');
if(!str_contains($ui,'Chave reservada:')||!str_contains($ui,'reservation_access_key')&&!str_contains((string)file_get_contents(__DIR__.'/../src/Repositories/FiscalIssuedNotesRepository.php'),'r.access_key'))throw new RuntimeException('reserved access key is not exposed safely in Central de Notas');
if(str_contains($ui,'Chave autorizada:'))throw new RuntimeException('reserved key must not be called authorized');
echo"FiscalCentralReservedKey PASS\n";
