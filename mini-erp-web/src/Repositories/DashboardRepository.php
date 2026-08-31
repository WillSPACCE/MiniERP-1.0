<?php
declare(strict_types=1);

namespace MiniErp\Repositories;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class DashboardRepository
{
    private const SALES_STATUSES = ['SAVED'];

    public function __construct(
        private readonly PDO $pdo,
        private readonly int $tenantId,
        private readonly DateTimeZone $timezone = new DateTimeZone('America/Sao_Paulo'),
    ) {}

    public function read(?DateTimeImmutable $today = null): array
    {
        $today = ($today ?? new DateTimeImmutable('today', $this->timezone))->setTimezone($this->timezone);
        $from = $today->modify('-6 days');
        $sales = $this->salesSummary();
        $lowStock = $this->lowStockProducts();

        return [
            'clientes' => $this->countMaster('clientes'),
            'produtos' => $this->countMaster('produtos'),
            'vendas' => $sales['count'],
            'faturamento' => $sales['revenue'],
            'sales_last_7_days' => $this->salesLast7Days($from, $today),
            'low_stock_products' => $lowStock,
            'estoque_baixo' => count($lowStock),
            'stock_movements' => $this->stockMovementSource(),
        ];
    }

    public function salesSummary(): array
    {
        [$where, $params] = $this->salesFilter();
        $statement = $this->pdo->prepare('SELECT COUNT(*) sale_count,COALESCE(SUM(grand_total),0) revenue FROM fiscal_orders WHERE '.$where);
        $statement->execute($params);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['count'=>(int)($row['sale_count']??0),'revenue'=>(float)($row['revenue']??0)];
    }

    public function salesLast7Days(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        [$where, $params] = $this->salesFilter();
        $params['date_from']=$from->format('Y-m-d');$params['date_to']=$to->format('Y-m-d');
        $statement=$this->pdo->prepare('SELECT operation_date,COUNT(*) sale_count,COALESCE(SUM(grand_total),0) revenue FROM fiscal_orders WHERE '.$where.' AND operation_date BETWEEN :date_from AND :date_to GROUP BY operation_date ORDER BY operation_date');
        $statement->execute($params);$indexed=[];
        foreach($statement->fetchAll(PDO::FETCH_ASSOC) as$row)$indexed[(string)$row['operation_date']]=['count'=>(int)$row['sale_count'],'revenue'=>(float)$row['revenue']];
        $days=[];
        for($date=$from;$date<=$to;$date=$date->modify('+1 day')){$key=$date->format('Y-m-d');$days[]=['date'=>$key,'label'=>$date->format('d/m'),'count'=>$indexed[$key]['count']??0,'revenue'=>$indexed[$key]['revenue']??0.0];}
        return$days;
    }

    public function lowStockProducts(): array
    {
        if(!$this->columnExists('produtos','minimum_stock'))return[];
        $where=['estoque_atual<=minimum_stock'];$params=[];
        if($this->columnExists('produtos','tenant_id')){$where[]='tenant_id=:tenant';$params['tenant']=$this->tenantId;}
        if($this->columnExists('produtos','status'))$where[]="COALESCE(status,'ativo')='ativo'";
        $unit=$this->columnExists('produtos','unidade')?'unidade':"'UN'";
        $statement=$this->pdo->prepare('SELECT id,nome,estoque_atual,minimum_stock,'.$unit.' unidade FROM produtos WHERE '.implode(' AND ',$where).' ORDER BY estoque_atual ASC,nome ASC');
        $statement->execute($params);return$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function stockMovementSource(): array
    {
        foreach(['stock_movements','inventory_movements','movimentacao_estoque','movimentacoes_estoque']as$table)if($this->tableExists($table))return['available'=>true,'table'=>$table,'orders'=>[],'note'=>'Ledger encontrado; a semântica deve ser lida do próprio movimento.'];
        return['available'=>false,'table'=>null,'orders'=>[],'note'=>'Nenhum ledger de movimentação de estoque existe neste tenant; pedidos gravados não são tratados como baixa.'];
    }

    public function analytics(array $filters): array
    {
        [$salesWhere,$salesParams]=$this->filteredSalesWhere($filters,'o');
        $summaryStatement=$this->pdo->prepare('SELECT COUNT(*) sale_count,COALESCE(SUM(o.grand_total),0) revenue,COALESCE(MAX(o.grand_total),0) largest_sale FROM fiscal_orders o WHERE '.$salesWhere);
        $summaryStatement->execute($salesParams);$summary=$summaryStatement->fetch(PDO::FETCH_ASSOC)?:[];$salesCount=(int)($summary['sale_count']??0);$revenue=(float)($summary['revenue']??0);
        $byDay=$this->filteredSalesByDay($filters,$salesWhere,$salesParams);
        $topProducts=$this->topProducts($salesWhere,$salesParams);
        $lastItems=$this->lastSoldItems($salesWhere,$salesParams);
        $topCustomers=$this->topCustomers($salesWhere,$salesParams);
        $notes=$this->notesAnalytics($filters);
        $lowStock=$this->lowStockProducts();
        $soldItems=$this->soldItemsSummary($salesWhere,$salesParams);
        $stock=$this->stockSummary();
        return[
            'clientes'=>$this->countMaster('clientes'),'produtos'=>$this->countMaster('produtos'),'vendas'=>$salesCount,'issued_orders'=>$this->issuedOrdersCount($filters),'faturamento'=>$revenue,
            'ticket_average'=>$salesCount>0?$revenue/$salesCount:0.0,'largest_sale'=>(float)($summary['largest_sale']??0),'sales_by_day'=>$byDay,
            'top_products'=>$topProducts,'last_sold_items'=>$lastItems,'top_customers'=>$topCustomers,'best_customer'=>$topCustomers[0]??null,
            'notes'=>$notes,'low_stock_products'=>$lowStock,'estoque_baixo'=>count($lowStock),'stock_balance'=>$stock['balance'],'stock_products'=>$stock['products'],
            'stock'=>['sold_item_lines'=>$soldItems['lines'],'sold_quantity'=>$soldItems['quantity'],'movement'=>$this->stockMovementSource()],
        ];
    }

    private function issuedOrdersCount(array$filters):int
    {
        $sql="SELECT COUNT(*) FROM fiscal_orders o LEFT JOIN fiscal_documents d ON d.id=(SELECT MAX(d2.id) FROM fiscal_documents d2 WHERE d2.tenant_id=o.tenant_id AND d2.source_order_id=o.id) WHERE o.tenant_id=:tenant AND o.operation_date BETWEEN :date_from AND :date_to AND d.id IS NULL";
        $statement=$this->pdo->prepare($sql);$statement->execute(['tenant'=>$this->tenantId,'date_from'=>$filters['from'],'date_to'=>$filters['to']]);return(int)$statement->fetchColumn();
    }

    private function stockSummary():array
    {
        $tenantWhere=$this->columnExists('produtos','tenant_id')?' WHERE p.tenant_id=:tenant':'';$params=$tenantWhere!==''?['tenant'=>$this->tenantId]:[];
        if($this->columnExists('produtos','stock_control_by_lot')&&$this->tableExists('stock_lots')){
            $sql="SELECT COUNT(*) products,COALESCE(SUM(CASE WHEN p.stock_control_by_lot=1 THEN COALESCE(l.balance,0) ELSE p.estoque_atual END),0) balance FROM produtos p LEFT JOIN (SELECT product_id,SUM(quantity_available) balance FROM stock_lots WHERE tenant_id=:lot_tenant AND status='ACTIVE' GROUP BY product_id) l ON l.product_id=p.id".$tenantWhere;
            $params['lot_tenant']=$this->tenantId;
        }else{$sql='SELECT COUNT(*) products,COALESCE(SUM(p.estoque_atual),0) balance FROM produtos p'.$tenantWhere;}
        $statement=$this->pdo->prepare($sql);$statement->execute($params);$row=$statement->fetch(PDO::FETCH_ASSOC)?:[];return['products'=>(int)($row['products']??0),'balance'=>(float)($row['balance']??0)];
    }

    public function customerOptions(): array
    {
        $where=[];$params=[];if($this->columnExists('clientes','tenant_id')){$where[]='tenant_id=:tenant';$params['tenant']=$this->tenantId;}
        $statement=$this->pdo->prepare('SELECT id,nome FROM clientes'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY nome');$statement->execute($params);return$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function filteredSalesByDay(array$filters,string$where,array$params):array
    {
        $statement=$this->pdo->prepare('SELECT o.operation_date,COUNT(*) sale_count,COALESCE(SUM(o.grand_total),0) revenue FROM fiscal_orders o WHERE '.$where.' GROUP BY o.operation_date ORDER BY o.operation_date');$statement->execute($params);$indexed=[];
        foreach($statement->fetchAll(PDO::FETCH_ASSOC)as$row)$indexed[(string)$row['operation_date']]=['count'=>(int)$row['sale_count'],'revenue'=>(float)$row['revenue']];
        $from=new DateTimeImmutable((string)$filters['from'],$this->timezone);$to=new DateTimeImmutable((string)$filters['to'],$this->timezone);$days=[];
        for($date=$from;$date<=$to;$date=$date->modify('+1 day')){$key=$date->format('Y-m-d');$days[]=['date'=>$key,'label'=>$date->format('d/m'),'count'=>$indexed[$key]['count']??0,'revenue'=>$indexed[$key]['revenue']??0.0];}
        return$days;
    }

    private function topProducts(string$where,array$params):array
    {
        $sql='SELECT p.id,p.nome,p.unidade,COALESCE(SUM(i.quantity),0) quantity,COALESCE(SUM(i.net_total),0) revenue FROM fiscal_orders o JOIN fiscal_order_items i ON i.order_id=o.id JOIN produtos p ON p.id=i.product_id WHERE '.$where.' GROUP BY p.id,p.nome,p.unidade ORDER BY quantity DESC,revenue DESC,p.nome LIMIT 10';
        $statement=$this->pdo->prepare($sql);$statement->execute($params);return$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lastSoldItems(string$where,array$params):array
    {
        $sql="SELECT o.operation_date,o.id order_id,COALESCE(c.nome,'Cliente') customer_name,p.nome product_name,i.quantity,i.unit_price,i.net_total,p.estoque_atual,p.unidade FROM fiscal_orders o JOIN fiscal_order_items i ON i.order_id=o.id JOIN produtos p ON p.id=i.product_id LEFT JOIN clientes c ON c.id=o.person_id WHERE {$where} ORDER BY o.operation_date DESC,o.id DESC,i.id DESC LIMIT 10";
        $statement=$this->pdo->prepare($sql);$statement->execute($params);return$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function soldItemsSummary(string$where,array$params):array
    {
        $statement=$this->pdo->prepare('SELECT COUNT(*) item_lines,COALESCE(SUM(i.quantity),0) quantity FROM fiscal_orders o JOIN fiscal_order_items i ON i.order_id=o.id WHERE '.$where);
        $statement->execute($params);$row=$statement->fetch(PDO::FETCH_ASSOC)?:[];
        return['lines'=>(int)($row['item_lines']??0),'quantity'=>(float)($row['quantity']??0)];
    }

    private function topCustomers(string$where,array$params):array
    {
        $sql="SELECT o.person_id,COALESCE(c.nome,'Cliente') customer_name,COUNT(*) orders_count,COALESCE(SUM(items.item_quantity),0) items_count,COALESCE(SUM(o.grand_total),0) revenue,COALESCE(AVG(o.grand_total),0) average_ticket FROM fiscal_orders o LEFT JOIN clientes c ON c.id=o.person_id LEFT JOIN (SELECT order_id,SUM(quantity) item_quantity FROM fiscal_order_items GROUP BY order_id) items ON items.order_id=o.id WHERE {$where} GROUP BY o.person_id,c.nome ORDER BY revenue DESC,orders_count DESC LIMIT 10";
        $statement=$this->pdo->prepare($sql);$statement->execute($params);return$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function notesAnalytics(array$filters):array
    {
        if(!$this->tableExists('fiscal_documents'))return['total'=>0,'pending'=>0,'rejected'=>0,'attention'=>0,'authorized'=>0,'other'=>0,'fiscal_total'=>0.0,'by_status'=>[],'by_day'=>[],'by_model'=>['55'=>0,'65'=>0]];
        [$where,$params]=$this->notesWhere($filters);$model=$this->jsonText("d.totals_json",'model');$grand=$this->jsonText("d.totals_json",'grand');
        $statement=$this->pdo->prepare("SELECT d.status,{$model} model,COUNT(*) qty,COALESCE(SUM(CASE WHEN d.status IN ('AUTHORIZED','AUTORIZADA') THEN CAST(NULLIF({$grand},'') AS DECIMAL(18,2)) ELSE 0 END),0) fiscal_total FROM fiscal_documents d LEFT JOIN fiscal_orders o ON o.id=d.source_order_id AND o.tenant_id=d.tenant_id WHERE {$where} GROUP BY d.status,{$model}");$statement->execute($params);
        $summary=['total'=>0,'pending'=>0,'rejected'=>0,'attention'=>0,'authorized'=>0,'other'=>0,'fiscal_total'=>0.0,'by_status'=>['Autorizadas'=>0,'Pendentes'=>0,'Rejeitadas'=>0,'Outros'=>0],'by_model'=>['55'=>0,'65'=>0]];
        foreach($statement->fetchAll(PDO::FETCH_ASSOC)as$row){$qty=(int)$row['qty'];$category=$this->noteCategory((string)$row['status']);$summary['total']+=$qty;$summary[$category]+=$qty;if(in_array($category,['pending','rejected'],true))$summary['attention']+=$qty;$summary['fiscal_total']+=(float)$row['fiscal_total'];$label=['authorized'=>'Autorizadas','pending'=>'Pendentes','rejected'=>'Rejeitadas','other'=>'Outros'][$category];$summary['by_status'][$label]+=$qty;$resolved=(string)($row['model']??'');if(isset($summary['by_model'][$resolved]))$summary['by_model'][$resolved]+=$qty;}
        $day=$this->pdo->prepare("SELECT DATE(d.created_at) note_date,COUNT(*) qty FROM fiscal_documents d LEFT JOIN fiscal_orders o ON o.id=d.source_order_id AND o.tenant_id=d.tenant_id WHERE {$where} GROUP BY DATE(d.created_at) ORDER BY note_date");$day->execute($params);$indexed=[];foreach($day->fetchAll(PDO::FETCH_ASSOC)as$row)$indexed[(string)$row['note_date']]=(int)$row['qty'];
        $from=new DateTimeImmutable((string)$filters['from'],$this->timezone);$to=new DateTimeImmutable((string)$filters['to'],$this->timezone);$summary['by_day']=[];for($date=$from;$date<=$to;$date=$date->modify('+1 day')){$key=$date->format('Y-m-d');$summary['by_day'][]=['date'=>$key,'label'=>$date->format('d/m'),'count'=>$indexed[$key]??0];}
        return$summary;
    }

    private function filteredSalesWhere(array$filters,string$alias):array
    {
        [$base,$params]=$this->salesFilter();$base=str_replace(['tenant_id','operation_type','commercial_status'],[$alias.'.tenant_id',$alias.'.operation_type',$alias.'.commercial_status'],$base);
        $base.=' AND '.$alias.'.operation_date BETWEEN :filter_from AND :filter_to';$params['filter_from']=$filters['from'];$params['filter_to']=$filters['to'];
        if((int)($filters['customer_id']??0)>0){$base.=' AND '.$alias.'.person_id=:customer_id';$params['customer_id']=(int)$filters['customer_id'];}
        if(in_array(($filters['model']??''),['55','65'],true)){$base.=' AND '.$alias.'.fiscal_model=:model';$params['model']=$filters['model'];}
        return[$base,$params];
    }

    private function notesWhere(array$filters):array
    {
        $where='d.tenant_id=:note_tenant AND DATE(d.created_at) BETWEEN :note_from AND :note_to';$params=['note_tenant'=>$this->tenantId,'note_from'=>$filters['from'],'note_to'=>$filters['to']];
        if((int)($filters['customer_id']??0)>0){$where.=' AND o.person_id=:note_customer';$params['note_customer']=(int)$filters['customer_id'];}
        if(in_array(($filters['model']??''),['55','65'],true)){$where.=' AND '.$this->jsonText('d.totals_json','model').'=:note_model';$params['note_model']=$filters['model'];}
        $status=(string)($filters['status']??'');$map=['pending'=>['FISCAL_PENDING','FISCAL_READY','PENDING_TRANSMISSION','PREPARING','PROCESSING'],'rejected'=>['REJECTED','REJEITADA'],'authorized'=>['AUTHORIZED','AUTORIZADA'],'preparing'=>['FISCAL_PENDING','FISCAL_READY','PREPARING']];if(isset($map[$status])){$holders=[];foreach($map[$status]as$i=>$value){$key='note_status'.$i;$holders[]=':'.$key;$params[$key]=$value;}$where.=' AND d.status IN ('.implode(',',$holders).')';}
        return[$where,$params];
    }

    private function noteCategory(string$status):string{$status=strtoupper($status);if(in_array($status,['AUTHORIZED','AUTORIZADA'],true))return'authorized';if(in_array($status,['REJECTED','REJEITADA','DENIED'],true))return'rejected';if(in_array($status,['FISCAL_PENDING','FISCAL_READY','PENDING_TRANSMISSION','PREPARING','PROCESSING','XSD_VALID_OFFLINE'],true))return'pending';return'other';}
    private function jsonText(string$column,string$key):string{return$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'?"json_extract({$column}, '$.{$key}')":"JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.{$key}'))";}

    private function salesFilter(): array
    {
        $params=['tenant'=>$this->tenantId];$statuses=[];
        foreach(self::SALES_STATUSES as$index=>$status){$key='status'.$index;$statuses[]=':'.$key;$params[$key]=$status;}
        return['tenant_id=:tenant AND operation_type=\'EXIT\' AND commercial_status IN ('.implode(',',$statuses).')',$params];
    }

    private function countMaster(string$table): int
    {
        $where='';$params=[];if($this->columnExists($table,'tenant_id')){$where=' WHERE tenant_id=:tenant';$params['tenant']=$this->tenantId;}
        $statement=$this->pdo->prepare('SELECT COUNT(*) FROM '.$table.$where);$statement->execute($params);return(int)$statement->fetchColumn();
    }

    private function tableExists(string$table): bool
    {
        if($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'){$statement=$this->pdo->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name=?");$statement->execute([$table]);return(int)$statement->fetchColumn()===1;}
        $statement=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$statement->execute([$table]);return(int)$statement->fetchColumn()===1;
    }

    private function columnExists(string$table,string$column): bool
    {
        if(!$this->tableExists($table))return false;
        if($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlite'){foreach($this->pdo->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC)as$row)if(($row['name']??'')===$column)return true;return false;}
        $statement=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$statement->execute([$table,$column]);return(int)$statement->fetchColumn()===1;
    }
}
