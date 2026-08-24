<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$detail=(string)file_get_contents($root.'/public/fiscal_note_detail.php');
$action=(string)file_get_contents($root.'/public/fiscal_action.php');
$create=(string)file_get_contents($root.'/src/Services/CreateInternalFiscalDocumentService.php');
function masterUiAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
foreach(['Corrigir cliente','Corrigir produto','Abrir configuração fiscal','FiscalDocumentPreflightService']as$needle)masterUiAssert(str_contains($detail,$needle),$needle);
masterUiAssert(str_contains($create,'contextualCfop'),'contextual CFOP must feed document creation');
masterUiAssert(str_contains($action,"'source_order'=>")&&!str_contains($action,"'source_document'=>"),'retry fingerprint must be stable for unchanged master data');
masterUiAssert(str_contains($action,'CreateInternalFiscalDocumentService'),'retry must create a fresh snapshot after corrections');
echo "FiscalMasterDataUiStatic PASS\n";
