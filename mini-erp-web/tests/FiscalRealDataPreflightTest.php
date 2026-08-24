<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
use MiniErp\Services\FiscalDocumentPreflightService;
function pf(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
$valid=['issuer_snapshot'=>['tax_id'=>'12345678000195','legal_name'=>'EMPRESA TEST_ONLY','state_registration'=>'123','tax_regime_code'=>'1','street'=>'Rua A','number'=>'1','district'=>'Centro','city_ibge_code'=>'3550308','city_name'=>'São Paulo','state'=>'SP','postal_code'=>'01001000'],'recipient_snapshot'=>['cpf_cnpj'=>'12345678901','nome'=>'CLIENTE TEST_ONLY','logradouro'=>'Rua B','numero'=>'2','bairro'=>'Centro','codigo_ibge'=>'3550308','cidade'=>'São Paulo','uf'=>'SP','cep'=>'01001000'],'payment_snapshot'=>['method'=>'01'],'totals'=>['model'=>'55'],'items'=>[['product_snapshot_json'=>json_encode(['nome'=>'PRODUTO TEST_ONLY','ncm'=>'84713012']),'tax_resolution_json'=>json_encode(['cfop'=>'5102','icms'=>['cst'=>'00'],'pis'=>['cst'=>'01'],'cofins'=>['cst'=>'01']])]]];
$service=new FiscalDocumentPreflightService();pf($service->inspect($valid)['ready'],'valid preflight');
foreach([['recipient_snapshot','cpf_cnpj','CUSTOMER_DOCUMENT_MISSING'],['recipient_snapshot','logradouro','CUSTOMER_STREET_MISSING'],['recipient_snapshot','codigo_ibge','CUSTOMER_CITY_CODE_MISSING']]as[$group,$field,$code]){$bad=$valid;$bad[$group][$field]='';$r=$service->inspect($bad);pf(!$r['ready']&&in_array($code,array_column($r['errors'],'code'),true),$code);}
$bad=$valid;$bad['items'][0]['product_snapshot_json']=json_encode(['nome'=>'PRODUTO','ncm'=>'']);pf(in_array('PRODUCT_NCM_MISSING',array_column($service->inspect($bad)['errors'],'code'),true),'PRODUCT_NCM_MISSING');
$bad=$valid;$bad['items'][0]['tax_resolution_json']='null';$codes=array_column($service->inspect($bad)['errors'],'code');pf(in_array('CFOP_NOT_RESOLVED',$codes,true)&&in_array('FISCAL_RULE_NOT_FOUND',$codes,true),'tax pending');
$builder=file_get_contents(__DIR__.'/../src/Fiscal/FiscalNfeXmlBuilder.php');foreach(["??'SN'","??'01000000'","??'00'","??'01'"]as$fallback)pf(!str_contains($builder,$fallback),'fabricated fallback '.$fallback);
echo"FiscalRealDataPreflight PASS\n";
