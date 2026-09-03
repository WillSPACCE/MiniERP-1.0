<?php
declare(strict_types=1);

namespace MiniErp\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use PDO;
use RuntimeException;
use Throwable;

final class NfeEntryXmlImportService
{
    private DOMXPath $xpath;
    private DOMElement $info;

    public function __construct(private readonly PDO $pdo) {}

    public function analyze(string $xml, string $fileName = 'nfe.xml'): array
    {
        if ($xml === '' || strlen($xml) > 10 * 1024 * 1024) throw new RuntimeException('XML vazio ou maior que 10 MB.');
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOCDATA);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        if (!$loaded || $document->doctype !== null) throw new RuntimeException('XML de NF-e inválido ou não permitido.');
        $this->xpath = new DOMXPath($document);
        $node = $this->xpath->query('//*[local-name()="infNFe"]')->item(0);
        if (!$node instanceof DOMElement) throw new RuntimeException('O arquivo não contém uma NF-e (infNFe).');
        $this->info = $node;
        $model = $this->value('./*[local-name()="ide"]/*[local-name()="mod"]', $node);
        if ($model !== '55') throw new RuntimeException('Somente XML de NF-e modelo 55 pode ser importado como entrada.');

        $supplierNode = $this->one('./*[local-name()="emit"]', $node);
        $recipientNode = $this->one('./*[local-name()="dest"]', $node);
        $recipientDocument = $this->digits($this->value('./*[local-name()="CNPJ"]', $recipientNode) ?: $this->value('./*[local-name()="CPF"]', $recipientNode));
        $establishment = $this->pdo->query("SELECT tax_id FROM establishments WHERE is_primary=1 AND status='ativo' ORDER BY id LIMIT 1")->fetchColumn();
        $establishmentDocument = $this->digits((string)$establishment);
        $recipientMatch = $establishmentDocument !== '' && hash_equals($establishmentDocument, $recipientDocument);
        $address = $this->one('./*[local-name()="enderEmit"]', $supplierNode);
        $documentNumber = $this->digits($this->value('./*[local-name()="CNPJ"]', $supplierNode) ?: $this->value('./*[local-name()="CPF"]', $supplierNode));
        if (!in_array(strlen($documentNumber), [11, 14], true)) throw new RuntimeException('CPF/CNPJ do emitente não foi encontrado no XML.');
        $supplier = [
            'document' => $documentNumber,
            'name' => $this->value('./*[local-name()="xNome"]', $supplierNode),
            'trade_name' => $this->value('./*[local-name()="xFant"]', $supplierNode),
            'state_registration' => $this->value('./*[local-name()="IE"]', $supplierNode),
            'phone' => $this->value('./*[local-name()="fone"]', $address),
            'postal_code' => $this->value('./*[local-name()="CEP"]', $address),
            'street' => $this->value('./*[local-name()="xLgr"]', $address),
            'number' => $this->value('./*[local-name()="nro"]', $address),
            'complement' => $this->value('./*[local-name()="xCpl"]', $address),
            'district' => $this->value('./*[local-name()="xBairro"]', $address),
            'city' => $this->value('./*[local-name()="xMun"]', $address),
            'state' => $this->value('./*[local-name()="UF"]', $address),
        ];
        $existingSupplier = $this->findIdByDocument('fornecedores', $documentNumber);
        $existingClient = $this->findIdByDocument('clientes', $documentNumber);

        $items = [];
        foreach ($this->xpath->query('./*[local-name()="det"]', $node) ?: [] as $detail) {
            if (!$detail instanceof DOMElement) continue;
            $product = $this->one('./*[local-name()="prod"]', $detail);
            $tax = $this->one('./*[local-name()="imposto"]', $detail);
            $code = trim($this->value('./*[local-name()="cProd"]', $product));
            if ($code === '') $code = 'XML-' . (count($items) + 1);
            $gtin = $this->gtin($this->value('./*[local-name()="cEAN"]', $product));
            $found = $this->findProduct($code, $gtin);
            $icmsNode = $this->one('./*[local-name()="ICMS"]/*[1]', $tax, false);
            $ipiNode = $this->one('./*[local-name()="IPI"]/*[not(local-name()="cEnq")][1]', $tax, false);
            $pisNode = $this->one('./*[local-name()="PIS"]/*[1]', $tax, false);
            $cofinsNode = $this->one('./*[local-name()="COFINS"]/*[1]', $tax, false);
            $items[] = [
                'line' => (int)$detail->getAttribute('nItem'), 'existing_product_id' => $found['id'] ?? null,
                'code' => $code, 'name' => $this->value('./*[local-name()="xProd"]', $product),
                'ncm' => $this->digits($this->value('./*[local-name()="NCM"]', $product)),
                'cest' => $this->digits($this->value('./*[local-name()="CEST"]', $product)),
                'cfop_source' => $this->digits($this->value('./*[local-name()="CFOP"]', $product)),
                'unit' => strtoupper($this->value('./*[local-name()="uCom"]', $product) ?: 'UN'),
                'taxable_unit' => strtoupper($this->value('./*[local-name()="uTrib"]', $product) ?: 'UN'),
                'quantity' => $this->decimal($this->value('./*[local-name()="qCom"]', $product)),
                'taxable_quantity' => $this->decimal($this->value('./*[local-name()="qTrib"]', $product)),
                'unit_price' => $this->decimal($this->value('./*[local-name()="vUnCom"]', $product), 4),
                'total' => $this->decimal($this->value('./*[local-name()="vProd"]', $product)),
                'discount' => $this->decimal($this->value('./*[local-name()="vDesc"]', $product)),
                'gtin' => $gtin, 'gtin_taxable' => $this->gtin($this->value('./*[local-name()="cEANTrib"]', $product)),
                'origin' => $this->value('./*[local-name()="orig"]', $icmsNode),
                'taxes' => [
                    'icms_cst' => $this->value('./*[local-name()="CST"]', $icmsNode),
                    'icms_csosn' => $this->value('./*[local-name()="CSOSN"]', $icmsNode),
                    'icms_rate' => $this->decimalOrNull($this->value('./*[local-name()="pICMS"]', $icmsNode)),
                    'ipi_cst' => $this->value('./*[local-name()="CST"]', $ipiNode),
                    'ipi_rate' => $this->decimalOrNull($this->value('./*[local-name()="pIPI"]', $ipiNode)),
                    'pis_cst' => $this->value('./*[local-name()="CST"]', $pisNode),
                    'pis_rate' => $this->decimalOrNull($this->value('./*[local-name()="pPIS"]', $pisNode)),
                    'cofins_cst' => $this->value('./*[local-name()="CST"]', $cofinsNode),
                    'cofins_rate' => $this->decimalOrNull($this->value('./*[local-name()="pCOFINS"]', $cofinsNode)),
                ],
            ];
        }
        if (!$items) throw new RuntimeException('O XML não possui itens de produto.');

        $ide = $this->one('./*[local-name()="ide"]', $node);
        $totals = $this->one('./*[local-name()="total"]/*[local-name()="ICMSTot"]', $node);
        $sourceCfop = $items[0]['cfop_source']; $entryCfop = $this->entryCfop($sourceCfop);
        $cfop = $this->findCfop($entryCfop);
        $dateTime = $this->value('./*[local-name()="dhEmi"]', $ide) ?: $this->value('./*[local-name()="dEmi"]', $ide);
        return [
            'file_name' => basename($fileName), 'access_key' => preg_replace('/^NFe/', '', $node->getAttribute('Id')),
            'supplier' => $supplier + ['existing_id' => $existingSupplier, 'existing_supplier_id'=>$existingSupplier, 'existing_client_id'=>$existingClient],
            'invoice' => [
                'number' => $this->value('./*[local-name()="nNF"]', $ide), 'series' => $this->value('./*[local-name()="serie"]', $ide),
                'recipient_document' => $recipientDocument, 'establishment_document' => $establishmentDocument, 'recipient_match' => $recipientMatch,
                'date' => substr($dateTime, 0, 10), 'model' => $model, 'source_cfop' => $sourceCfop,
                'entry_cfop' => $entryCfop, 'cfop_id' => $cfop['id'] ?? null, 'cfop_description' => $cfop['descricao'] ?? '',
                'freight_mode' => $this->value('./*[local-name()="transp"]/*[local-name()="modFrete"]', $node) ?: '9',
                'products_total' => $this->decimal($this->value('./*[local-name()="vProd"]', $totals)),
                'freight' => $this->decimal($this->value('./*[local-name()="vFrete"]', $totals)),
                'insurance' => $this->decimal($this->value('./*[local-name()="vSeg"]', $totals)),
                'discount' => $this->decimal($this->value('./*[local-name()="vDesc"]', $totals)),
                'other' => $this->decimal($this->value('./*[local-name()="vOutro"]', $totals)),
                'total' => $this->decimal($this->value('./*[local-name()="vNF"]', $totals)),
                'notes' => $this->value('./*[local-name()="infAdic"]/*[local-name()="infCpl"]', $node),
            ],
            'items' => $items,
            'summary' => ['items' => count($items), 'new_products' => count(array_filter($items, fn(array $i) => empty($i['existing_product_id']))), 'supplier_action' => $existingSupplier ? 'use' : 'create'],
        ];
    }

    public function persist(array $data, string $sourceFile, string $partyType='fornecedor'): array
    {
        if(!in_array($partyType,['cliente','fornecedor'],true))throw new RuntimeException('Escolha cliente ou fornecedor para o cadastro.');
        $ownsTransaction=!$this->pdo->inTransaction();if($ownsTransaction)$this->pdo->beginTransaction();
        try {
            $supplier = $data['supplier'];$partyId=$this->saveParty($supplier,$partyType);$supplierId=$partyType==='fornecedor'?$partyId:0;
            $products=[];
            foreach ($data['items'] as $item) {
                $productId=(int)($item['existing_product_id']??0);
                if($productId<1)$productId=(int)($this->findProduct((string)$item['code'],(string)$item['gtin'])['id']??0);
                if($productId<1){
                    $sql='INSERT INTO produtos(nome,codigo,ncm,cest,merchandise_origin,unidade,taxable_unit,conversion_factor,gtin,gtin_tributable,cfop_padrao,cost_price,preco,estoque_atual,status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,0,\'ativo\')';
                    $conversion=(float)$item['quantity']>0?(float)$item['taxable_quantity']/(float)$item['quantity']:1;
                    $this->pdo->prepare($sql)->execute([$item['name'],$item['code'],$item['ncm'],$item['cest'],$item['origin'],$item['unit'],$item['taxable_unit'],$conversion,$item['gtin'],$item['gtin_taxable'],$data['invoice']['entry_cfop'],$item['unit_price'],$item['unit_price']]);
                    $productId=(int)$this->pdo->lastInsertId();
                }
                $this->saveTaxes($productId,$item['taxes'],$sourceFile);
                $products[]=['id'=>$productId,'product_id'=>$productId,'codigo'=>$item['code'],'nome'=>$item['name'],'un'=>$item['unit'],'preco'=>(float)$item['unit_price'],'estoque'=>0,'status'=>'ativo','stock_control_by_lot'=>false,'saved'=>['quantity'=>$item['quantity'],'unit_price'=>$item['unit_price'],'discount_amount'=>$item['discount'],'net_total'=>$item['total'],'icms'=>(string)($item['taxes']['icms_rate']??''),'ipi'=>(string)($item['taxes']['ipi_rate']??''),'pis'=>(string)($item['taxes']['pis_rate']??''),'cofins'=>(string)($item['taxes']['cofins_rate']??'')]];
            }
            if($ownsTransaction)$this->pdo->commit();
            return ['supplier_id'=>$supplierId,'supplier_name'=>$supplier['name'],'party_id'=>$partyId,'party_type'=>$partyType,'party_name'=>$supplier['name'],'products'=>$products,'invoice'=>$data['invoice']];
        } catch (Throwable $error) { if($ownsTransaction&&$this->pdo->inTransaction())$this->pdo->rollBack(); throw $error; }
    }
    private function saveParty(array$data,string$type):int
    {
        $table=$type==='cliente'?'clientes':'fornecedores';$existing=$this->findIdByDocument($table,(string)$data['document']);if($existing>0)return$existing;
        $candidate=['nome'=>$data['name'],'nome_fantasia'=>$data['trade_name'],'cpf_cnpj'=>$data['document'],'inscricao_estadual'=>$data['state_registration'],'telefone'=>$data['phone'],'cep'=>$data['postal_code'],'logradouro'=>$data['street'],'numero'=>$data['number'],'complemento'=>$data['complement'],'bairro'=>$data['district'],'municipio'=>$data['city'],'cidade'=>$data['city'],'uf'=>$data['state'],'status'=>'ativo'];
        if($type==='cliente')$candidate+=['person_type'=>strlen((string)$data['document'])===14?'PJ':'PF','state_registration_indicator'=>$data['state_registration']!==''?'1':'9','role_customer'=>1,'role_supplier'=>0];
        $available=[];foreach($this->pdo->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC)as$column)$available[]=(string)$column['Field'];$row=array_intersect_key($candidate,array_flip($available));$fields=array_keys($row);
        $this->pdo->prepare("INSERT INTO {$table}(".implode(',',$fields).') VALUES('.implode(',',array_fill(0,count($fields),'?')).')')->execute(array_values($row));return(int)$this->pdo->lastInsertId();
    }

    private function saveTaxes(int $productId,array $taxes,string $source):void
    {
        $icms=(string)($taxes['icms_rate']??'');$ipi=(string)($taxes['ipi_rate']??'');$pis=(string)($taxes['pis_rate']??'');$cofins=(string)($taxes['cofins_rate']??'');
        $available=[];foreach($this->pdo->query('SHOW COLUMNS FROM product_taxes')->fetchAll(PDO::FETCH_ASSOC)as$column)$available[]=(string)$column['Field'];
        $candidate=['product_id'=>$productId,'icms'=>$icms,'icms_cst'=>$taxes['icms_cst'],'icms_csosn'=>$taxes['icms_csosn'],'icms_rate'=>$taxes['icms_rate'],'ipi'=>$ipi,'ipi_input_cst'=>$taxes['ipi_cst'],'pis'=>$pis,'pis_input_cst'=>$taxes['pis_cst'],'pis_input_rate'=>$taxes['pis_rate'],'cofins'=>$cofins,'cofins_input_cst'=>$taxes['cofins_cst'],'cofins_input_rate'=>$taxes['cofins_rate'],'source_document'=>basename($source)];
        $data=array_intersect_key($candidate,array_flip($available));$fields=array_keys($data);$updates=array_values(array_filter($fields,fn(string$field)=>$field!=='product_id'));
        $sql='INSERT INTO product_taxes('.implode(',',$fields).') VALUES('.implode(',',array_fill(0,count($fields),'?')).') ON DUPLICATE KEY UPDATE '.implode(',',array_map(fn(string$field)=>"{$field}=VALUES({$field})",$updates));
        $this->pdo->prepare($sql)->execute(array_values($data));
    }
    private function findIdByDocument(string$table,string$document):int{$s=$this->pdo->prepare("SELECT id FROM {$table} WHERE REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj,'.',''),'/',''),'-',''),' ','')=? LIMIT 1");$s->execute([$document]);return(int)$s->fetchColumn();}
    private function findProduct(string$code,string$gtin):array{$s=$this->pdo->prepare($gtin!==''?'SELECT id FROM produtos WHERE gtin=? OR codigo=? ORDER BY gtin=? DESC LIMIT 1':'SELECT id FROM produtos WHERE codigo=? LIMIT 1');$s->execute($gtin!==''?[$gtin,$code,$gtin]:[$code]);return$s->fetch(PDO::FETCH_ASSOC)?:[];}
    private function findCfop(string$code):array{$s=$this->pdo->prepare("SELECT id,descricao FROM cfops WHERE codigo=? AND status='ativo' LIMIT 1");$s->execute([$code]);return$s->fetch(PDO::FETCH_ASSOC)?:[];}
    private function entryCfop(string$source):string{return match($source[0]??''){'5'=>'1'.substr($source,1),'6'=>'2'.substr($source,1),'7'=>'3'.substr($source,1),default=>$source};}
    private function one(string$query,?DOMNode$context=null,bool$required=true):?DOMElement{$node=$this->xpath->query($query,$context??$this->info)->item(0);if($node instanceof DOMElement)return$node;if($required)throw new RuntimeException('Estrutura obrigatória ausente no XML.');return null;}
    private function value(string$query,?DOMNode$context=null):string{$node=$this->xpath->query($query,$context??$this->info)->item(0);return trim((string)($node?->textContent??''));}
    private function digits(string$value):string{return preg_replace('/\D+/','',$value)??'';}
    private function gtin(string$value):string{$value=$this->digits($value);return in_array(strlen($value),[8,12,13,14],true)?$value:'';}
    private function decimal(string$value,int$scale=2):string{return number_format(is_numeric($value)?(float)$value:0,$scale,'.','');}
    private function decimalOrNull(string$value):?string{return $value===''?null:$this->decimal($value,6);}
}
