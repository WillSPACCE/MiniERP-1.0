<?php
declare(strict_types=1);
namespace MiniErp\Repositories;

use DateTimeImmutable;
use MiniErp\Contracts\TaxRuleRepositoryContract;
use MiniErp\Fiscal\{FiscalTaxContext,FiscalTaxRule};
use PDO;

final readonly class MariaDbTaxRuleRepository implements TaxRuleRepositoryContract
{
    public function __construct(private PDO$pdo,private int$tenantId){}
    public function findCandidates(FiscalTaxContext$context):array
    {
        if($context->tenantId!==$this->tenantId)return[];
        $stmt=$this->pdo->prepare("SELECT * FROM tax_rule_versions WHERE tenant_id=:tenant_id AND status='ACTIVE' AND valid_from<=:operation_date AND (valid_to IS NULL OR valid_to>=:operation_date)");
        $stmt->execute(['tenant_id'=>$this->tenantId,'operation_date'=>$context->operationDate->format('Y-m-d')]);
        $rules=array_map([$this,'hydrate'],$stmt->fetchAll(PDO::FETCH_ASSOC));
        $fallback=$this->configurationFallback($context);if($fallback)$rules[]=$fallback;
        return$rules;
    }
    public function addVersion(FiscalTaxRule$r):int
    {
        if($r->tenantId!==$this->tenantId)throw new \RuntimeException('Tenant da regra diverge do repositório.');
        $sql='INSERT INTO tax_rule_versions (tenant_id,rule_code,rule_version,priority,valid_from,valid_to,source_document,source_version,source_date,conditions_json,cfop,icms_json,ipi_json,pis_json,cofins_json,ibs_cbs_json,selective_tax_json,status,fixture_kind) VALUES (:tenant_id,:rule_code,:rule_version,:priority,:valid_from,:valid_to,:source_document,:source_version,:source_date,:conditions_json,:cfop,:icms_json,:ipi_json,:pis_json,:cofins_json,:ibs_cbs_json,:selective_tax_json,:status,:fixture_kind)';
        $stmt=$this->pdo->prepare($sql);$stmt->execute(['tenant_id'=>$r->tenantId,'rule_code'=>$r->code,'rule_version'=>$r->version,'priority'=>$r->priority,'valid_from'=>$r->validFrom->format('Y-m-d'),'valid_to'=>$r->validTo?->format('Y-m-d'),'source_document'=>$r->sourceDocument,'source_version'=>$r->sourceVersion,'source_date'=>$r->sourceDate,'conditions_json'=>json_encode($r->conditions,JSON_THROW_ON_ERROR),'cfop'=>$r->cfop,'icms_json'=>json_encode($r->icms,JSON_THROW_ON_ERROR),'ipi_json'=>json_encode($r->ipi,JSON_THROW_ON_ERROR),'pis_json'=>json_encode($r->pis,JSON_THROW_ON_ERROR),'cofins_json'=>json_encode($r->cofins,JSON_THROW_ON_ERROR),'ibs_cbs_json'=>json_encode($r->ibsCbs,JSON_THROW_ON_ERROR),'selective_tax_json'=>json_encode($r->selectiveTax,JSON_THROW_ON_ERROR),'status'=>$r->status,'fixture_kind'=>$r->fixtureKind]);return(int)$this->pdo->lastInsertId();
    }
    private function configurationFallback(FiscalTaxContext$c):?FiscalTaxRule
    {
        try{
            $originState=strtoupper(trim($c->originState));$destinationState=strtoupper(trim($c->destinationState));if($originState===''||$destinationState==='')return null;$scope=$originState===$destinationState?'INTERNAL':'INTERSTATE';$context=$c->direction.'_'.$scope;
            $s=$this->pdo->prepare("SELECT d.cfop FROM establishment_cfop_defaults d JOIN cfops f ON CONVERT(f.codigo USING utf8mb4) COLLATE utf8mb4_unicode_ci=d.cfop COLLATE utf8mb4_unicode_ci AND f.status='ativo' WHERE d.tenant_id=? AND d.establishment_id=? AND d.operation_context=? LIMIT 1");$s->execute([$this->tenantId,$c->establishmentId,$context]);$cfop=(string)$s->fetchColumn();if(!preg_match('/^\d{4}$/',$cfop))return null;
            $g=$this->one('SELECT * FROM establishment_fiscal_settings WHERE tenant_id=? AND establishment_id=?',[$this->tenantId,$c->establishmentId]);
            $ic=$this->one('SELECT * FROM establishment_icms_defaults WHERE tenant_id=? AND establishment_id=? AND uf=? AND active=1 AND valid_from<=? AND (valid_to IS NULL OR valid_to>=?) ORDER BY valid_from DESC LIMIT 1',[$this->tenantId,$c->establishmentId,$c->destinationState,$c->operationDate->format('Y-m-d'),$c->operationDate->format('Y-m-d')]);
            $legacy=$this->one('SELECT * FROM establishment_legacy_tax_defaults WHERE tenant_id=? AND establishment_id=?',[$this->tenantId,$c->establishmentId]);
            $profile=$c->productId?($this->one('SELECT * FROM product_taxes WHERE product_id=?',[$c->productId])):[];
            $productCst=$c->finalConsumer?($profile['icms_consumer_cst']??null):($c->crt==='1'?($profile['icms_csosn']??null):($profile['icms_cst']??null));$cst=(string)($productCst??($c->finalConsumer?($g['final_consumer_cst_csosn']??null):null)??$ic['cst_csosn']??$g['default_cst_csosn']??'');$icmsRate=(string)($profile['icms_rate']??($c->finalConsumer?($ic['final_consumer_rate']??null):null)??$ic['juridica_rate']??$ic['internal_rate']??'');
            $prefix=$c->direction==='ENTRY'?'input':'output';$pisCst=(string)($profile['pis_'.$prefix.'_cst']??$legacy['pis_'.$prefix.'_cst']??'');$pisRate=(string)($profile['pis_'.$prefix.'_rate']??$legacy['pis_'.$prefix.'_rate']??'');$cofinsCst=(string)($profile['cofins_'.$prefix.'_cst']??$legacy['cofins_'.$prefix.'_cst']??'');$cofinsRate=(string)($profile['cofins_'.$prefix.'_rate']??$legacy['cofins_'.$prefix.'_rate']??'');
            if($cst===''||$icmsRate===''||$pisCst===''||$pisRate===''||$cofinsCst===''||$cofinsRate==='')return null;
            $base=bcmul($c->quantity,$c->unitPrice,2);$amount=static fn(string$rate):string=>bcdiv(bcmul($base,$rate,6),'100',2);
            $icms=['base'=>$base,'rate'=>$icmsRate,'amount'=>$amount($icmsRate),'modBC'=>'3'];if(strlen($cst)===3)$icms['csosn']=$cst;else$icms['cst']=$cst;
            $pis=['cst'=>$pisCst,'base'=>$base,'rate'=>$pisRate,'amount'=>$amount($pisRate)];$cofins=['cst'=>$cofinsCst,'base'=>$base,'rate'=>$cofinsRate,'amount'=>$amount($cofinsRate)];$ipi=[];
            $profileIpiCst=(string)($profile['ipi_'.$prefix.'_cst']??'');$profileIpiRate=(string)($profile['ipi_output_rate']??'');if($profileIpiCst!==''&&$profileIpiRate!==''){$ipi=['cst'=>$profileIpiCst,'legal_code'=>(string)($legacy['ipi_cenq']??'999'),'base'=>$base,'rate'=>$profileIpiRate,'amount'=>$amount($profileIpiRate)];}elseif(($legacy['ipi_applicability']??'PENDING')==='APPLICABLE'){$ipiCst=(string)($legacy['ipi_'.$prefix.'_cst']??'');$ipiRate=(string)($legacy['ipi_'.$prefix.'_rate']??'');$cenq=(string)($legacy['ipi_cenq']??'');if($ipiCst===''||$ipiRate===''||$cenq==='')return null;$ipi=['cst'=>$ipiCst,'legal_code'=>$cenq,'base'=>$base,'rate'=>$ipiRate,'amount'=>$amount($ipiRate)];}elseif(($legacy['ipi_applicability']??'PENDING')!=='NOT_APPLICABLE')return null;
            return new FiscalTaxRule(null,$this->tenantId,'ESTABLISHMENT_DEFAULT_'.$context,1,-1000000,$c->operationDate,null,'CENTRAL_FISCAL','1',$c->operationDate->format('Y-m-d'),[],$cfop,$icms,$ipi,$pis,$cofins,[],[],'ACTIVE','CONFIGURATION_FALLBACK');
        }catch(\Throwable){return null;}
    }
    private function one(string$sql,array$params):array{$s=$this->pdo->prepare($sql);$s->execute($params);return$s->fetch(PDO::FETCH_ASSOC)?:[];}
    private function hydrate(array$r):FiscalTaxRule{$j=fn(string$k):array=>json_decode((string)$r[$k],true,512,JSON_THROW_ON_ERROR);return new FiscalTaxRule((int)$r['id'],(int)$r['tenant_id'],$r['rule_code'],(int)$r['rule_version'],(int)$r['priority'],new DateTimeImmutable($r['valid_from']),$r['valid_to']?new DateTimeImmutable($r['valid_to']):null,$r['source_document'],$r['source_version'],$r['source_date'],$j('conditions_json'),$r['cfop'],$j('icms_json'),$j('ipi_json'),$j('pis_json'),$j('cofins_json'),$j('ibs_cbs_json'),$j('selective_tax_json'),$r['status'],$r['fixture_kind']);}
}
