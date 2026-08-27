<?php
declare(strict_types=1);

namespace MiniErp\Services;

use DateTimeImmutable;
use MiniErp\Fiscal\DecimalTaxCalculator;
use MiniErp\Fiscal\FiscalTaxContext;
use MiniErp\Fiscal\TaxRuleResolver;
use MiniErp\Repositories\FiscalOperationRepository;
use PDO;
use RuntimeException;

final readonly class CreateInternalFiscalDocumentService
{
    public function __construct(private FiscalOperationRepository $operations,private TaxRuleResolver $resolver) {}

    public function create(int $orderId,string $idempotencyKey,int $userId):array
    {
        if(!preg_match('/^[a-f0-9]{64}$/',$idempotencyKey))throw new RuntimeException('Token de idempotência inválido.');
        if($existing=$this->operations->findDocumentByKey($idempotencyKey))return$existing;
        return$this->operations->transaction(function()use($orderId,$idempotencyKey,$userId):array{
            if($existing=$this->operations->findDocumentByKey($idempotencyKey))return$existing;
            $pdo=$this->operations->pdo();$order=$this->operations->orderWithTransport($orderId);
            $issuer=$this->row('SELECT * FROM establishments WHERE tenant_id=? AND id=?',[$this->operations->tenantId(),$order['establishment_id']]);
            $recipient=$this->partyRow($order['operation_type']==='ENTRY'?'fornecedores':'clientes',(int)$order['person_id']);
            $carrier=$this->carrierSnapshot((int)($order['carrier_id']??0));
            $driver=$this->partyRow('motoristas',(int)($order['driver_id']??0))??[];
            $pending=[];
            if(!$issuer)$pending[]='Emitente não encontrado.';else foreach(['tax_id'=>'CNPJ','state_registration'=>'IE','tax_regime_code'=>'CRT','state'=>'UF','city_ibge_code'=>'IBGE']as$field=>$label)if(trim((string)($issuer[$field]??''))==='')$pending[]="Emitente sem {$label}.";
            if(!$recipient)$pending[]='Destinatário/fornecedor não encontrado.';
            $resolved=[];
            foreach($order['items']as$item){
                $itemPending=[];foreach(['ncm'=>'NCM','merchandise_origin'=>'origem','unidade'=>'unidade comercial','taxable_unit'=>'unidade tributável']as$field=>$label)if(trim((string)($item[$field]??''))==='')$itemPending[]="Produto {$item['nome']} sem {$label}.";
                $context=null;$resolution=null;
                if($issuer&&$recipient&&!$itemPending){
                    $destinationState=(string)($recipient['uf']??$recipient['estado']??'');$cfopHint=trim((string)($item['cfop_padrao']??''));
                    if($cfopHint==='')$cfopHint=(string)($this->operations->contextualCfop((int)$issuer['id'],$order['operation_type'],(string)$issuer['state'],$destinationState)??'');
                    $context=new FiscalTaxContext($this->operations->tenantId(),(int)$issuer['id'],(string)$issuer['tax_regime_code'],(string)$issuer['state'],$destinationState,(string)($recipient['person_type']??'PJ'),(string)($recipient['state_registration_indicator']??'9'),(string)($recipient['country_code']??'1058'),(string)$item['ncm'],(string)$item['cest'],(string)$item['merchandise_origin'],(string)$item['tax_benefit_code'],$order['operation_type'],(string)$order['fiscal_model'],(string)$order['purpose'],(bool)$order['final_consumer'],(($recipient['state_registration_indicator']??'9')==='1'),(string)$order['presence_indicator'],new DateTimeImmutable($order['operation_date']),(string)$item['quantity'],(string)$item['unit_price'],$cfopHint?:null);
                    try{$rule=$this->resolver->resolve($context);$calculation=(new DecimalTaxCalculator())->calculate($context,$rule);$resolution=get_object_vars($rule);foreach(['icms','ipi','pis','cofins']as$group)$resolution[$group]=$calculation['taxes'][$group];}catch(\Throwable$error){$itemPending[]=$error->getMessage();}
                }
                $pending=array_merge($pending,$itemPending);$resolved[]=[$item,$context,$resolution,$itemPending];
            }
            if(trim((string)$order['payment_method'])==='')$pending[]='Forma de pagamento incompleta.';$pending=array_values(array_unique($pending));$status=$pending?'FISCAL_PENDING':'FISCAL_READY';
            $version=(int)$pdo->query('SELECT COALESCE(MAX(document_version),0)+1 FROM fiscal_documents WHERE tenant_id='.(int)$this->operations->tenantId().' AND source_order_id='.(int)$orderId.' FOR UPDATE')->fetchColumn();
            $transport=['carrier_id'=>$order['carrier_id'],'carrier'=>$carrier,'driver_id'=>$order['driver_id'],'driver'=>$driver,'freight_mode'=>$order['freight_mode'],'vehicle'=>['plate'=>$order['vehicle_plate']??null,'state'=>$order['vehicle_state']??null,'rntc'=>$order['vehicle_rntc']??null],'volume'=>['quantity'=>$order['volume_quantity']??null,'species'=>$order['volume_species']??null,'brand'=>$order['volume_brand']??null,'numbering'=>$order['volume_numbering']??null,'gross_weight'=>$order['gross_weight']??null,'net_weight'=>$order['net_weight']??null]];
            $pdo->prepare('INSERT INTO fiscal_documents(tenant_id,source_order_id,document_version,idempotency_key,status,pending_json,issuer_snapshot_json,recipient_snapshot_json,payment_snapshot_json,transport_snapshot_json,totals_json,created_by) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$this->operations->tenantId(),$orderId,$version,$idempotencyKey,$status,json_encode($pending,JSON_THROW_ON_ERROR),json_encode($issuer?:[],JSON_THROW_ON_ERROR),json_encode($recipient?:[],JSON_THROW_ON_ERROR),json_encode(['condition'=>$order['payment_condition'],'method'=>$order['payment_method'],'due'=>$order['first_due_date'],'amount'=>$order['grand_total']],JSON_THROW_ON_ERROR),json_encode($transport,JSON_THROW_ON_ERROR),json_encode(['model'=>$order['fiscal_model'],'operation_nature'=>$order['operation_nature'],'operation_type'=>$order['operation_type'],'purpose'=>$order['purpose'],'final_consumer'=>(int)$order['final_consumer'],'presence_indicator'=>$order['presence_indicator'],'products'=>$order['products_total'],'discount'=>$order['discount_amount'],'freight'=>$order['freight_amount'],'insurance'=>$order['insurance_amount'],'other'=>$order['other_amount'],'grand'=>$order['grand_total'],'fiscal'=>null],JSON_THROW_ON_ERROR),$userId]);
            $documentId=(int)$pdo->lastInsertId();$insert=$pdo->prepare('INSERT INTO fiscal_document_items(fiscal_document_id,source_order_item_id,product_id,product_snapshot_json,quantity_commercial,quantity_taxable,unit_value_commercial,unit_value_taxable,gross_total,discount_amount,freight_amount,insurance_amount,other_amount,net_total,fiscal_status,tax_context_json,tax_resolution_json) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            foreach($resolved as[$item,$context,$resolution,$itemPending]){$factor=(string)($item['conversion_factor']?:'1');$insert->execute([$documentId,$item['order_item_id'],$item['id'],json_encode($item,JSON_THROW_ON_ERROR),$item['quantity'],bcmul((string)$item['quantity'],$factor,4),$item['unit_price'],bcdiv((string)$item['unit_price'],$factor,4),$item['gross_total'],$item['discount_amount'],$item['item_freight'],$item['item_insurance'],$item['item_other'],$item['net_total'],$itemPending?'FISCAL_PENDING':'FISCAL_READY',json_encode($context?get_object_vars($context):[],JSON_THROW_ON_ERROR),$resolution?json_encode($resolution,JSON_THROW_ON_ERROR):null]);}
            $pdo->prepare('UPDATE fiscal_orders SET fiscal_status=? WHERE id=? AND tenant_id=?')->execute([$status,$orderId,$this->operations->tenantId()]);
            return['id'=>$documentId,'status'=>$status,'pending'=>$pending,'document_version'=>$version,'idempotency_key'=>$idempotencyKey];
        });
    }

    private function row(string$sql,array$params):?array{$statement=$this->operations->pdo()->prepare($sql);$statement->execute($params);return$statement->fetch(PDO::FETCH_ASSOC)?:null;}

    private function partyRow(string$table,int$id):?array
    {
        if(!in_array($table,['clientes','fornecedores','transportadoras','motoristas'],true))return null;
        $pdo=$this->operations->pdo();$tenantColumn=$pdo->query("SHOW COLUMNS FROM {$table} LIKE 'tenant_id'")->fetchColumn();
        return$this->row("SELECT * FROM {$table} WHERE id=?".($tenantColumn?' AND tenant_id=?':'').' LIMIT 1',$tenantColumn?[$id,$this->operations->tenantId()]:[$id]);
    }

    private function carrierSnapshot(int $carrierId):array
    {
        if ($carrierId < 1) {
            return [];
        }

        $row=$this->partyRow('transportadoras',$carrierId);
        if(is_array($row)&&$row!==[])return$row;
        $pdo=$this->operations->pdo();$tenantColumn=$pdo->query("SHOW COLUMNS FROM clientes LIKE 'tenant_id'")->fetchColumn();
        $sql="SELECT * FROM clientes WHERE id=? AND (role_carrier=1 OR FIND_IN_SET('transportadora',REPLACE(COALESCE(tipo_pessoa,''),' ',''))>0)".($tenantColumn?' AND tenant_id=?':'').' LIMIT 1';
        $row=$this->row($sql,$tenantColumn?[$carrierId,$this->operations->tenantId()]:[$carrierId]);
        if(is_array($row)&&$row!==[])return$row;

        return [];
    }
}
