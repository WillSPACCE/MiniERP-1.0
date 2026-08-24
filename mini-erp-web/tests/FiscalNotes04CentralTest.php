<?php
declare(strict_types=1);
$root=dirname(__DIR__);$repo=file_get_contents($root.'/src/Repositories/FiscalIssuedNotesRepository.php');$page=file_get_contents($root.'/public/fiscal_notes.php');$detail=file_get_contents($root.'/public/fiscal_note_detail.php');$css=file_get_contents($root.'/public/assets/fiscal-notes.css');$js=file_get_contents($root.'/public/assets/fiscal-notes.js');
function notes04(bool $ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['type','status','series','model','environment','from','to','q']as$filter)notes04(str_contains($page,"'$filter'"),'filter '.$filter);
foreach([10,25,50,100]as$size)notes04(str_contains($repo,(string)$size),'page size '.$size);
notes04(str_contains($repo,'UNION ALL')&&str_contains($repo,'LIMIT ')&&str_contains($repo,'OFFSET '),'server pagination');
notes04(str_contains($repo,'r.access_key')&&!str_contains($repo,'a.access_key'),'canonical access key');
notes04(str_contains($repo,"columnExists('fiscal_number_reservations','number')"),'legacy number compatibility');
notes04(str_contains($repo,"tableExists('fiscal_document_events')"),'optional events');
notes04(str_contains($page,'data-copy-key')&&str_contains($js,'navigator.clipboard'),'copy key');
notes04(str_contains($page,'SEM VALOR FISCAL'),'mirror semantics');
notes04(str_contains($detail,'Visualização NFC-e ainda não disponível'),'NFC-e isolation');
notes04(str_contains($css,'@media(max-width:760px)')&&str_contains($css,'content:attr(data-label)'),'responsive cards');
notes04(str_contains($page,'Não foi possível carregar a Central de Notas.')&&!str_contains($page,'SQLSTATE'),'safe error boundary');
echo "FiscalNotes04Central PASS\n";
