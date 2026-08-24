<?php
declare(strict_types=1);
namespace MiniErp\Services;

use RuntimeException;

final class FiscalDocumentPreflightService
{
    public function inspect(array $document):array
    {
        $checks=[];$errors=[];$issuer=$document['issuer_snapshot']??[];$recipient=$document['recipient_snapshot']??[];$totals=$document['totals']??[];
        $this->check($checks,$errors,'Empresa',!empty($issuer),'ISSUER_MISSING','Estabelecimento emitente não encontrado.');
        $this->required($checks,$errors,'CNPJ emitente',$issuer,['tax_id'],'ISSUER_DOCUMENT_MISSING','Informe o CNPJ do estabelecimento.');
        $this->required($checks,$errors,'IE emitente',$issuer,['state_registration'],'ISSUER_IE_MISSING','Informe a inscrição estadual do estabelecimento.');
        $this->required($checks,$errors,'CRT',$issuer,['tax_regime_code'],'ISSUER_CRT_MISSING','Informe o regime tributário do estabelecimento.');
        $this->address($checks,$errors,'emitente',$issuer,'ISSUER_ADDRESS_MISSING');
        $this->required($checks,$errors,'Cliente',$recipient,['nome','legal_name'],'CUSTOMER_NAME_MISSING','Informe o nome ou razão social do cliente.');
        $foreign=trim((string)($recipient['foreign_id']??''))!=='';
        $taxId=preg_replace('/\D/','',(string)($recipient['cpf_cnpj']??$recipient['tax_id']??''));
        $this->check($checks,$errors,'Documento cliente',$foreign||in_array(strlen((string)$taxId),[11,14],true),'CUSTOMER_DOCUMENT_MISSING','Informe CPF ou CNPJ válido no cadastro do cliente.',['entity'=>'customer','entity_id'=>(int)($recipient['id']??0),'field'=>'cpf_cnpj','action'=>'edit_customer']);
        $this->address($checks,$errors,'cliente',$recipient,'CUSTOMER_ADDRESS_MISSING');
        $items=$document['items']??[];$this->check($checks,$errors,'Produtos',$items!==[],'DOCUMENT_ITEMS_MISSING','O pedido não possui itens fiscais.');
        foreach($items as$index=>$item){$n=$index+1;$product=json_decode((string)($item['product_snapshot_json']??'{}'),true)?:[];$tax=json_decode((string)($item['tax_resolution_json']??'null'),true);
            $meta=['entity'=>'product','entity_id'=>(int)($item['product_id']??$product['id']??0),'item'=>$n,'product_name'=>(string)($product['nome']??$n),'action'=>'edit_product'];
            $this->check($checks,$errors,"NCM item {$n}",preg_match('/^\d{8}$/',(string)($product['ncm']??''))===1,'PRODUCT_NCM_MISSING',"O produto “".($product['nome']??$n).'” não possui NCM válido.',$meta+['field'=>'ncm']);
            $this->check($checks,$errors,"CFOP item {$n}",is_array($tax)&&preg_match('/^\d{4}$/',(string)($tax['cfop']??''))===1,'CFOP_NOT_RESOLVED',"CFOP não resolvido para o item {$n}.",$meta+['field'=>'cfop','action'=>'open_fiscal_settings']);
            $this->check($checks,$errors,"Tributação item {$n}",$this->validTaxes($tax),'FISCAL_RULE_NOT_FOUND',"Tributação completa não resolvida para o item {$n}.",$meta+['field'=>'tax_rule','action'=>'open_fiscal_settings']);
        }
        $this->check($checks,$errors,'Modelo',in_array((string)($totals['model']??''),['55','65'],true),'MODEL_NOT_SUPPORTED','Modelo fiscal não suportado.');
        $method=$document['payment_snapshot']['method']??'';$this->check($checks,$errors,'Pagamento',trim((string)$method)!=='','PAYMENT_METHOD_MISSING','Informe a forma de pagamento do pedido.');
        return['ready'=>$errors===[],'checks'=>$checks,'errors'=>$errors];
    }

    public function assertReady(array $document):array
    {
        $result=$this->inspect($document);if(!$result['ready']){$first=$result['errors'][0];throw new RuntimeException($first['code'].': '.$first['message']);}return$result;
    }

    private function address(array&$checks,array&$errors,string$owner,array$data,string$code):void
    {
        $fields=[[['street','logradouro','endereco'],'STREET','logradouro'],[['number','numero'],'NUMBER','número'],[['district','bairro'],'DISTRICT','bairro'],[['city_ibge_code','codigo_ibge','municipio_ibge'],'CITY_CODE','código IBGE'],[['city_name','municipio','cidade'],'CITY','município'],[['state','uf','estado'],'STATE','UF'],[['postal_code','cep'],'ZIP','CEP']];$ok=true;foreach($fields as[$names,$suffix,$label])if(!$this->value($data,$names)){$ok=false;$prefix=$owner==='cliente'?'CUSTOMER':'ISSUER';$this->check($checks,$errors,$label.' '.$owner,false,$prefix.'_'.$suffix.'_MISSING',ucfirst($owner)." sem {$label}.",['entity'=>$owner==='cliente'?'customer':'establishment','entity_id'=>(int)($data['id']??0),'field'=>$names[0],'action'=>$owner==='cliente'?'edit_customer':'edit_establishment']);}$checks[]=['name'=>'Endereço '.$owner,'ok'=>$ok];
    }
    private function required(array&$checks,array&$errors,string$name,array$data,array$aliases,string$code,string$message):void{$this->check($checks,$errors,$name,$this->value($data,$aliases),$code,$message);}
    private function value(array$data,array$aliases):bool{foreach($aliases as$key)if(trim((string)($data[$key]??''))!=='')return true;return false;}
    private function validTaxes(mixed$tax):bool{if(!is_array($tax)||$tax===[])return false;foreach(['icms','pis','cofins']as$group){if(!is_array($tax[$group]??null)||$tax[$group]===[])return false;$cst=(string)($tax[$group]['cst']??$tax[$group]['CST']??$tax[$group]['csosn']??$tax[$group]['CSOSN']??'');if($cst==='')return false;}return true;}
    private function check(array&$checks,array&$errors,string$name,bool$ok,string$code,string$message,array$meta=[]):void{$checks[]=['name'=>$name,'ok'=>$ok];if(!$ok)$errors[]=['code'=>$code,'message'=>$message]+$meta;}
}
