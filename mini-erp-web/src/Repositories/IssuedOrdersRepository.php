<?php
declare(strict_types=1);
namespace MiniErp\Repositories;

use PDO;

final class IssuedOrdersRepository
{
    public function __construct(private PDO $pdo, private int $tenantId) {}

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $page=max(1,$page);$perPage=in_array($perPage,[10,20,50,100],true)?$perPage:20;
        $where=['o.tenant_id=?'];$params=[$this->tenantId];
        $numberColumn=$this->reservationNumberColumn();
        $numberExpression=$numberColumn!==null?'r.'.$numberColumn:'NULL';
        if(in_array(($filters['model']??''),['55','65'],true)){$where[]='o.fiscal_model=?';$params[]=$filters['model'];}
        if(($filters['status']??'')!==''){$where[]='o.fiscal_status=?';$params[]=$filters['status'];}
        foreach(['from'=>'>=','to'=>'<='] as $key=>$operator){if(preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($filters[$key]??''))){$where[]="o.operation_date {$operator}?";$params[]=$filters[$key];}}
        if(trim((string)($filters['q']??''))!==''){$where[]="CONVERT(CONCAT_WS(' ',o.id,o.internal_code,c.nome,c.cpf_cnpj,f.nome,f.cpf_cnpj,r.access_key,{$numberExpression}) USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci";$params[]='%'.trim((string)$filters['q']).'%';}
        $joins=" LEFT JOIN clientes c ON c.id=o.person_id AND o.operation_type='EXIT' LEFT JOIN fornecedores f ON f.id=o.person_id AND o.operation_type='ENTRY' LEFT JOIN fiscal_documents d ON d.id=(SELECT MAX(d2.id) FROM fiscal_documents d2 WHERE d2.tenant_id=o.tenant_id AND d2.source_order_id=o.id) LEFT JOIN fiscal_number_reservations r ON r.id=(SELECT MAX(r2.id) FROM fiscal_number_reservations r2 WHERE r2.tenant_id=o.tenant_id AND r2.fiscal_document_id=d.id)";
        $sql=' FROM fiscal_orders o'.$joins.' WHERE '.implode(' AND ',$where);
        $count=$this->pdo->prepare('SELECT COUNT(*)'.$sql);$count->execute($params);$total=(int)$count->fetchColumn();
        $pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
        $query=$this->pdo->prepare("SELECT o.*,COALESCE(c.nome,f.nome,'Pessoa') person_name,d.id document_id,d.status document_status,r.id reservation_id,{$numberExpression} fiscal_number,r.access_key{$sql} ORDER BY o.id DESC LIMIT {$perPage} OFFSET {$offset}");
        $query->execute($params);
        return ['rows'=>$query->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage];
    }

    private function reservationNumberColumn(): ?string
    {
        $query=$this->pdo->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fiscal_number_reservations' AND COLUMN_NAME IN ('number','fiscal_number') ORDER BY FIELD(COLUMN_NAME,'number','fiscal_number') LIMIT 1");
        $query->execute();$column=$query->fetchColumn();
        return $column!==false?(string)$column:null;
    }
}
