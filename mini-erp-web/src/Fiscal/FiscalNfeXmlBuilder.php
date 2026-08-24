<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use NFePHP\NFe\Make;
use RuntimeException;

final class FiscalNfeXmlBuilder
{
    public function build(FiscalDocumentDTO $dto, array $identity): string
    {
        foreach (['access_key','uf_code','numeric_code','series','number','issued_at','environment','emission_type'] as $field) if (empty($identity[$field])) throw new RuntimeException("Identificação fiscal incompleta: {$field}.");
        if (!in_array($dto->model, ['55','65'], true)) throw new RuntimeException('Modelo fiscal inválido no snapshot.');
        $issuer=$dto->issuer;$recipient=$dto->recipient;$totals=$dto->totals;
        foreach (['tax_id','legal_name','state_registration','tax_regime_code','street','number','district','city_ibge_code','city_name','state','postal_code'] as $field) if (trim((string)($issuer[$field]??''))==='') throw new RuntimeException("ISSUER_FIELD_MISSING: Cadastro do emitente incompleto: {$field}.");
        $recipientName=trim((string)($recipient['nome']??$recipient['legal_name']??''));if($recipientName==='')throw new RuntimeException('CUSTOMER_NAME_MISSING: Destinatário sem nome ou razão social.');
        $recipientTaxId=preg_replace('/\D/','',(string)($recipient['cpf_cnpj']??$recipient['tax_id']??''));if(!in_array(strlen((string)$recipientTaxId),[11,14],true)&&trim((string)($recipient['foreign_id']??''))==='')throw new RuntimeException('CUSTOMER_DOCUMENT_MISSING: Destinatário sem CPF/CNPJ válido.');
        foreach([['street','logradouro','endereco'],['number','numero'],['district','bairro'],['city_ibge_code','codigo_ibge','municipio_ibge'],['city_name','municipio','cidade'],['state','uf','estado'],['postal_code','cep']]as$aliases){$present=false;foreach($aliases as$key)if(trim((string)($recipient[$key]??''))!==''){$present=true;break;}if(!$present)throw new RuntimeException('CUSTOMER_ADDRESS_MISSING: Destinatário sem endereço completo.');}
        $processVersion = trim((string) ($identity['process_version'] ?? 'MiniERP-1.0'));
        if ($processVersion === '') $processVersion = 'MiniERP-1.0';
        if (strlen($processVersion) > 20) $processVersion = substr($processVersion, 0, 20);
        $make=new Make('PL_010_V1.30');$make->setOnlyAscii(false);$make->setCheckGtin(false);
        $make->taginfNFe((object)['Id'=>'NFe'.$identity['access_key'],'versao'=>'4.00']);
        $make->tagide((object)['cUF'=>$identity['uf_code'],'cNF'=>$identity['numeric_code'],'natOp'=>$totals['operation_nature']??'','mod'=>$dto->model,'serie'=>$identity['series'],'nNF'=>$identity['number'],'dhEmi'=>$identity['issued_at'],'dhSaiEnt'=>null,'tpNF'=>($totals['operation_type']??'EXIT')==='ENTRY'?0:1,'idDest'=>$identity['destination_scope']??1,'cMunFG'=>$issuer['city_ibge_code'],'cMunFGIBS'=>$issuer['city_ibge_code'],'tpImp'=>$dto->model==='65'?4:1,'tpEmis'=>$identity['emission_type'],'tpNFDebito'=>null,'tpNFCredito'=>null,'cDV'=>substr($identity['access_key'],-1),'tpAmb'=>$identity['environment'],'finNFe'=>$totals['purpose']??1,'indFinal'=>$totals['final_consumer']??1,'indPres'=>$totals['presence_indicator']??1,'indIntermed'=>null,'cIndOp'=>null,'procEmi'=>0,'verProc'=>$processVersion,'dhCont'=>null,'xJust'=>null]);
        $make->tagEmit((object)['CNPJ'=>$issuer['tax_id'],'CPF'=>null,'xNome'=>$issuer['legal_name'],'xFant'=>$issuer['trade_name']??null,'IE'=>$issuer['state_registration'],'IEST'=>$issuer['st_registration']??null,'IM'=>$issuer['municipal_registration']??null,'CNAE'=>$issuer['cnae']??null,'CRT'=>$issuer['tax_regime_code']]);
        $make->tagenderEmit($this->address($issuer));
        $taxId=strtoupper(preg_replace('/[^A-Z0-9]/i','',(string)($recipient['cpf_cnpj']??$recipient['tax_id']??''))??'');
        $make->tagdest((object)['CNPJ'=>strlen($taxId)===14?$taxId:null,'CPF'=>strlen($taxId)===11?$taxId:null,'idEstrangeiro'=>$recipient['foreign_id']??null,'xNome'=>$recipient['nome']??$recipient['legal_name']??'','indIEDest'=>$recipient['state_registration_indicator']??9,'IE'=>$recipient['inscricao_estadual']??null,'ISUF'=>$recipient['isuf']??null,'IM'=>$recipient['municipal_registration']??null,'email'=>$recipient['email']??null]);
        $make->tagenderDest($this->address($recipient));
        $sum='0.00';$itemNumber=0;
        foreach($dto->items as $row){$itemNumber++;$p=$row['product'];$tax=$row['tax'];$v=$row['values'];$cfop=(string)($tax['cfop']??'');if(!preg_match('/^\d{4}$/',$cfop))throw new RuntimeException("CFOP_NOT_RESOLVED: CFOP fiscal ainda não resolvido para o item {$itemNumber}.");if(!preg_match('/^\d{8}$/',(string)($p['ncm']??'')))throw new RuntimeException("PRODUCT_NCM_MISSING: NCM inválido no item {$itemNumber}.");foreach(['codigo','nome','unidade','taxable_unit']as$field)if(trim((string)($p[$field]??''))==='')throw new RuntimeException("PRODUCT_FIELD_MISSING: {$field} ausente no item {$itemNumber}.");
            $q=(string)($v['quantity_commercial']??'0');$unit=(string)($v['unit_value_commercial']??'0');$prod=(string)($v['gross_total']??bcmul($q,$unit,2));$sum=bcadd($sum,(string)($v['net_total']??$prod),2);
            $prodObj = ['item'=>$itemNumber,'cProd'=>$p['codigo']??(string)$itemNumber,'cEAN'=>$p['gtin']??'SEM GTIN','cBarra'=>null,'xProd'=>$p['nome']??'','NCM'=>$p['ncm']??'','cBenef'=>$p['tax_benefit_code']??null,'tpCredPresIBSZFM'=>null,'EXTIPI'=>$p['ex_tipi']??null,'CFOP'=>$cfop,'uCom'=>$p['unidade']??'UN','qCom'=>$q,'vUnCom'=>$unit,'vProd'=>$prod,'cEANTrib'=>$p['taxable_gtin']??'SEM GTIN','uTrib'=>$p['taxable_unit']??$p['unidade']??'UN','qTrib'=>$v['quantity_taxable']??$q,'vUnTrib'=>$v['unit_value_taxable']??$unit,'indTot'=>1,'CEST'=>$p['cest']??null];
            $ivFrete = $this->normalizeZeroAmountNullable($v['freight_amount'] ?? 0);
            $ivSeg = $this->normalizeZeroAmountNullable($v['insurance_amount'] ?? 0);
            $ivDesc = $this->normalizeZeroAmountNullable($v['discount_amount'] ?? 0);
            $ivOutro = $this->normalizeZeroAmountNullable($v['other_amount'] ?? 0);
            if ($ivFrete !== null) $prodObj['vFrete'] = $ivFrete;
            if ($ivSeg !== null) $prodObj['vSeg'] = $ivSeg;
            if ($ivDesc !== null) $prodObj['vDesc'] = $ivDesc;
            if ($ivOutro !== null) $prodObj['vOutro'] = $ivOutro;
            $make->tagprod((object)$prodObj);
            $make->tagimposto((object)['item'=>$itemNumber,'vTotTrib'=>null]);
            $icms=$this->requiredTax($tax,'icms',$itemNumber);$icmsTag=['item'=>$itemNumber,'orig'=>$p['merchandise_origin']??null];foreach(['cst'=>'CST','CST'=>'CST','csosn'=>'CSOSN','CSOSN'=>'CSOSN','modBC'=>'modBC','base'=>'vBC','vBC'=>'vBC','rate'=>'pICMS','pICMS'=>'pICMS','amount'=>'vICMS','vICMS'=>'vICMS']as$from=>$to)if(array_key_exists($from,$icms))$icmsTag[$to]=$icms[$from];if(trim((string)$icmsTag['orig'])===''||(!isset($icmsTag['CST'])&&!isset($icmsTag['CSOSN'])))throw new RuntimeException("FISCAL_RULE_NOT_FOUND: ICMS incompleto no item {$itemNumber}.");if(isset($icmsTag['CSOSN']))$make->tagICMSSN((object)$icmsTag);else$make->tagICMS((object)$icmsTag);
            $ipi=$tax['ipi']??[];if($ipi!==[]){$ipiTag=['item'=>$itemNumber];foreach(['legal_code'=>'cEnq','cEnq'=>'cEnq','cst'=>'CST','CST'=>'CST','base'=>'vBC','vBC'=>'vBC','rate'=>'pIPI','pIPI'=>'pIPI','amount'=>'vIPI','vIPI'=>'vIPI']as$from=>$to)if(array_key_exists($from,$ipi))$ipiTag[$to]=$ipi[$from];if(!isset($ipiTag['CST'],$ipiTag['cEnq']))throw new RuntimeException("FISCAL_RULE_NOT_FOUND: IPI incompleto no item {$itemNumber}.");$make->tagIPI((object)$ipiTag);}
            $pis=$this->requiredTax($tax,'pis',$itemNumber);$make->tagPIS((object)$this->contributionTag($pis,$itemNumber,'PIS'));
            $cofins=$this->requiredTax($tax,'cofins',$itemNumber);$make->tagCOFINS((object)$this->contributionTag($cofins,$itemNumber,'COFINS'));
            if(!empty($tax['ibsCbs']))$make->tagIBSCBS((object)array_merge(['item'=>$itemNumber],$tax['ibsCbs']));if(!empty($tax['selectiveTax']))$make->tagIS((object)array_merge(['item'=>$itemNumber],$tax['selectiveTax']));
        }
        if(bccomp($sum,(string)($totals['grand']??''),2)!==0)throw new RuntimeException('Totais do snapshot divergem da soma dos itens.');
        $icms = ['vNF' => $totals['grand'], 'vProd' => $totals['products'] ?? $sum];
        $vFrete = $this->normalizeZeroAmountNullable($totals['freight'] ?? 0);
        $vSeg = $this->normalizeZeroAmountNullable($totals['insurance'] ?? 0);
        $vDesc = $this->normalizeZeroAmountNullable($totals['discount'] ?? 0);
        $vOutro = $this->normalizeZeroAmountNullable($totals['other'] ?? 0);
        if ($vFrete !== null) $icms['vFrete'] = $vFrete;
        if ($vSeg !== null) $icms['vSeg'] = $vSeg;
        if ($vDesc !== null) $icms['vDesc'] = $vDesc;
        if ($vOutro !== null) $icms['vOutro'] = $vOutro;
        $make->tagICMSTot((object)$icms);
        $make->tagtransp((object)['modFrete'=>$dto->transport['freight_mode']??9]);$make->tagpag((object)['vTroco'=>$dto->payment['change']??null]);
        $method=(string)($dto->payment['method']??'');if($method==='')throw new RuntimeException('Forma de pagamento ausente no snapshot.');$make->tagdetPag((object)['indPag'=>0,'tPag'=>$method,'xPag'=>null,'vPag'=>$dto->payment['amount']??$totals['grand']]);
        if(!empty($totals['notes']))$make->taginfAdic((object)['infAdFisco'=>null,'infCpl'=>$totals['notes']]);
        return $make->getXML();
    }
    private function address(array $a): object{return(object)['xLgr'=>$a['street']??$a['logradouro']??$a['endereco']??'','nro'=>$a['number']??$a['numero']??'','xCpl'=>$a['complement']??$a['complemento']??null,'xBairro'=>$a['district']??$a['bairro']??'','cMun'=>$a['city_ibge_code']??$a['codigo_ibge']??$a['municipio_ibge']??'','xMun'=>$a['city_name']??$a['municipio']??$a['cidade']??'','UF'=>$a['state']??$a['uf']??$a['estado']??'','CEP'=>$a['postal_code']??$a['cep']??'','cPais'=>$a['country_code']??null,'xPais'=>$a['country_name']??null,'fone'=>$a['phone']??$a['telefone']??null];}

    private function requiredTax(array $tax,string $group,int $item):array{$value=$tax[$group]??null;if(!is_array($value)||$value===[])throw new RuntimeException("FISCAL_RULE_NOT_FOUND: {$group} ausente no item {$item}.");return$value;}
    private function contributionTag(array $tax,int $item,string $name):array{$tag=['item'=>$item];$map=['cst'=>'CST','CST'=>'CST','base'=>'vBC','vBC'=>'vBC','rate'=>'p'.$name,'p'.$name=>'p'.$name,'amount'=>'v'.$name,'v'.$name=>'v'.$name,'quantity'=>'qBCProd','unit_rate'=>'vAliqProd'];foreach($map as$from=>$to)if(array_key_exists($from,$tax))$tag[$to]=$tax[$from];if(!isset($tag['CST']))throw new RuntimeException("FISCAL_RULE_NOT_FOUND: CST {$name} ausente no item {$item}.");return$tag;}

    private function normalizeZeroAmount(mixed $value): string
    {
        $candidate = trim((string) $value);
        if ($candidate === '') return '0';
        if (is_numeric($candidate) && abs((float) $candidate) < 0.000001) return '0';
        return $candidate;
    }

    private function normalizeZeroAmountNullable(mixed $value): ?string
    {
        $candidate = trim((string) $value);
        if ($candidate === '') return null;
        if (is_numeric($candidate) && abs((float) $candidate) < 0.000001) return null;
        return $candidate;
    }
}
