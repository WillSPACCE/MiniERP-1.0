<?php
declare(strict_types=1);
$root=dirname(__DIR__);$page=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');$action=(string)file_get_contents($root.'/public/master_data_action.php');$directory=(string)file_get_contents($root.'/src/Repositories/MasterDataDirectoryRepository.php');$index=(string)file_get_contents($root.'/public/index.php');
function md46(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['Pessoas','Produtos','CFOPs','people_type','per_page','md-row','md-modal','Salvar e fechar','tipo_pessoa[]','Fiscal / Financeiro','Estoque / Preços'] as $needle)md46(str_contains($page,$needle),'UI: '.$needle);
foreach(['dirty','Fechar sem salvar','master_data_action.php','ajax_cnpj.php','city_ibge_code','Usar dado consultado','event.target.closest(\'button\')'] as $needle)md46(str_contains($js,$needle),'JS: '.$needle);
foreach(['erp_tenant_id','erp_user_id','hash_equals','HTTP_SEC_FETCH_SITE','RECORD_NOT_FOUND','status=\'inativo\''] as $needle)md46(str_contains($action,$needle),'security: '.$needle);
foreach(['UNION ALL','role_customer','role_supplier','role_seller','role_carrier','LIMIT ','OFFSET ','tenant_id'] as $needle)md46(str_contains($directory,$needle),'directory: '.$needle);
md46(str_contains($index,"'fornecedores' => 'fornecedor'")&&str_contains($index,"'motoristas' => 'motorista'")&&str_contains($index,"'transportadoras' => 'transportadora'"),'legacy deep links');
md46(!preg_match('/SEFAZ|sefazEnviaLote|sefazConsultaRecibo/',$action.$directory),'master data must not alter SEFAZ');
echo "MasterDataUiTest OK\n";
