<?php
declare(strict_types=1);require __DIR__.'/../vendor/autoload.php';use MiniErp\Services\CepLookupService;
function cepAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$service=new CepLookupService(null,static fn(string $url):array=>['status'=>200,'body'=>json_encode(['cep'=>'01001000','state'=>'SP','city'=>'São Paulo','neighborhood'=>'Sé','street'=>'Praça da Sé','ibge'=>['city'=>'3550308']]),'error'=>'']);$data=$service->lookup('01001-000');
cepAssert($data['postal_code']==='01001000','normalize');cepAssert($data['street']==='Praça da Sé','street');cepAssert($data['city_ibge_code']==='3550308','IBGE city');
try{$service->lookup('123');throw new RuntimeException('invalid accepted');}catch(InvalidArgumentException){}
$root=dirname(__DIR__);$ui=(string)file_get_contents($root.'/public/includes/master_data_configuration.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');$action=(string)file_get_contents($root.'/public/master_data_action.php');$css=(string)file_get_contents($root.'/public/assets/master-data-fixes.css');
foreach(['md-cep','Consultar CEP'] as $needle)cepAssert(str_contains($ui,$needle),'CEP UI');foreach(['ajax_cep.php','check_document','md-duplicate'] as $needle)cepAssert(str_contains($js,$needle),'JS '.$needle);foreach(['fornecedores','transportadoras','motoristas','Este CPF/CNPJ já está cadastrado'] as $needle)cepAssert(str_contains($action,$needle),'duplicate '.$needle);cepAssert(str_contains($css,'.md-form>footer .btn')&&str_contains($css,'font-size:14px!important'),'footer typography');
echo "CepLookupAndDuplicateTest OK\n";
