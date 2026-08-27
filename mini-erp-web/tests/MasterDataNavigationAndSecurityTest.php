<?php
declare(strict_types=1);
$root=dirname(__DIR__);$index=(string)file_get_contents($root.'/public/index.php');$view=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$action=(string)file_get_contents($root.'/public/master_data_action.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');
function md48(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
foreach(["'clientes'=>'cliente'","'fornecedores'=>'fornecedor'","'motoristas'=>'motorista'","'transportadoras'=>'transportadora'","\$_GET['person']","\$_GET['source_type']","\$_GET['tab']='pessoas'"] as $needle)md48(str_contains($index,$needle),'legacy route: '.$needle);
foreach(['data-open-id','data-open-type','data-open-pane','Pessoas</a>','Produtos</a>','CFOPs</a>','master-list','list-toolbar','data-table','table-actions','status-badge','pagination','Nenhum registro encontrado.'] as $needle)md48(str_contains($view,$needle),'central UI: '.$needle);
foreach(['master-data-return','md-row-highlight','syncConditionalTabs','initialPane'] as $needle)md48(str_contains($js,$needle),'context/deep link: '.$needle);
md48(str_contains($action,"\$_SESSION['erp_tenant_id']")&&!str_contains($action,"\$_REQUEST['tenant_id']"),'tenant comes only from session');
md48(str_contains($action,'hash_equals')&&str_contains($action,'HTTP_SEC_FETCH_SITE'),'CSRF and same-origin protection');
md48(str_contains($action,"'cliente'=>'clientes'")&&str_contains($action,"'produto'=>'produtos'")&&str_contains($action,"'cfop'=>'cfops'"),'record type whitelist');
md48(str_contains($action,'$directory->record($type,$id)')&&str_contains($action,'RECORD_NOT_FOUND'),'IDOR preflight');
md48(!preg_match('/sefazEnvia|sefazConsulta|certificate|certificado/i',$action.$view),'master data remains outside fiscal transmission/certificate scope');
echo "MasterDataNavigationAndSecurityTest OK\n";
