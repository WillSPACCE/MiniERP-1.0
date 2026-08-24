<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
use MiniErp\Contracts\CnpjLookupProviderContract;use MiniErp\Services\{CnpjLookupException,CnpjLookupResult,CnpjLookupService};
function ca(bool $v,string $m):void{if(!$v)throw new RuntimeException($m);}
final class CnpjProviderFake implements CnpjLookupProviderContract{public string $received='';public function __construct(private CnpjLookupResult $result){}public function lookup(string $cnpj):?CnpjLookupResult{$this->received=$cnpj;return $this->result;}}
$payload=json_decode((string)file_get_contents(__DIR__.'/fixtures/brasilapi-open-knowledge.json'),true,512,JSON_THROW_ON_ERROR);$dto=CnpjLookupResult::fromBrasilApi($payload);$data=$dto->toArray();
ca($data['legal_name']==='OPEN KNOWLEDGE BRASIL','legal name');ca($data['city_ibge_code']==='3550308','IBGE uses codigo_municipio_ibge');ca($data['street']==='AVENIDA PAULISTA','street normalization');ca($data['email']===null,'null email');ca($data['main_cnae']===9430800,'CNAE');
$provider=new CnpjProviderFake($dto);$service=new CnpjLookupService($provider);$result=$service->lookup('19.131.243/0001-97');ca($provider->received==='19131243000197','numeric normalized');ca($result?->toArray()['phone_1']==='1123851939','DTO returned');
ca(CnpjLookupService::isValid('12.ABC.345/01DE-35'),'alphanumeric accepted');ca(CnpjLookupService::normalize('12.Abc.345/01de-35')==='12ABC34501DE35','letters preserved');ca(!CnpjLookupService::isValid('11.111.111/1111-11'),'invalid rejected');
try{$service->lookup('123');throw new RuntimeException('invalid accepted');}catch(CnpjLookupException $e){ca($e->reason==='CNPJ_INVALID','invalid reason');}
echo "CnpjLookupService OK\n";
