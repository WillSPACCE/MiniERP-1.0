<?php
declare(strict_types=1);
$root=dirname(__DIR__);$index=file_get_contents($root.'/public/index.php');$notes=file_get_contents($root.'/public/fiscal_notes.php');$mirror=file_get_contents($root.'/public/fiscal_mirror.php');$repo=file_get_contents($root.'/src/Repositories/FiscalIssuedNotesRepository.php');$detail=file_get_contents($root.'/public/fiscal_note_detail.php');
function f05(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
f05(str_contains($index,"case 'fiscal_notes'")&&str_contains($index,"define('FISCAL_NOTES_EMBEDDED'")&&str_contains($index,'fiscal-notes.css'),'ERP shell integration');
f05(substr_count($index,'?page=fiscal_notes')>=2&&str_contains($index,"\$currentPage === 'fiscal_notes'"),'menu and active state');
f05(str_contains($notes,'notes-breadcrumb')&&str_contains($notes,'Início'),'breadcrumb');
f05(str_contains($notes,"header('Location: /?"),'legacy route redirect');
f05(str_contains($notes,'/fiscal_mirror.php?mirror_id=')&&str_contains($notes,'target="_blank"'),'mirror URL');
f05(str_contains($mirror,'class="page-ready"')&&str_contains($mirror,'Content-Type: text/html; charset=UTF-8'),'mirror visible response');
f05(str_contains($mirror,'id="fiscal-print-root"')&&str_contains($mirror,'window.print()'),'mirror print');
f05(str_contains($repo,'r.access_key')&&!str_contains($repo,'a.access_key'),'reservation access key');
f05(str_contains($repo,"columnExists('fiscal_number_reservations','number')"),'number fallback');
f05(str_contains($notes,"preg_match('/^\\d{44}$/',\$key)")&&str_contains($notes,'data-copy-key'),'valid copy key');
foreach(['Ver DANFE','Imprimir','PDF','Ver XML','Baixar XML','Tentar novamente']as$action)f05(str_contains($notes,$action),'action '.$action);
f05(str_contains($notes,'action-menu')&&str_contains($notes,'data-detail-id'),'professional action UI');
f05(str_contains($detail,'data-app-tabs')&&str_contains($detail,'detail-frame'),'detail modal');
f05(str_contains($notes,'class="highlight"'),'recent highlight');
echo "FiscalNotes05ErpShell PASS\n";
