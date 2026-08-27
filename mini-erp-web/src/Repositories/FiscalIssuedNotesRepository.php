<?php
declare(strict_types=1);
namespace MiniErp\Repositories;
use PDO;

final class FiscalIssuedNotesRepository
{
    // Normalização compatível: 'status'=>'ESPELHO'; Documento interno sem valor fiscal.
    private array $tableCache=[],$columnCache=[];
    public function __construct(private PDO $pdo,private int $tenantId){}

    public function paginate(array $filters,int $page,int $perPage):array
    {
        $page=max(1,$page);$perPage=in_array($perPage,[10,25,50,100],true)?$perPage:25;
        [$union,$params]=$this->listingUnion($filters);
        if($union==='')return['rows'=>[],'total'=>0,'page'=>1,'per_page'=>$perPage,'pages'=>1];
        $count=$this->pdo->prepare('SELECT COUNT(*) FROM ('.$union.') fiscal_central_count');$count->execute($params);$total=(int)$count->fetchColumn();
        $pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);$offset=($page-1)*$perPage;
        $query=$this->pdo->prepare('SELECT * FROM ('.$union.') fiscal_central ORDER BY created_at DESC,sort_id DESC LIMIT '.$perPage.' OFFSET '.$offset);$query->execute($params);
        return['rows'=>$query->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'per_page'=>$perPage,'pages'=>$pages];
    }

    public function series():array
    {
        if(!$this->tableExists('fiscal_series'))return[];$s=$this->pdo->prepare('SELECT DISTINCT series FROM fiscal_series WHERE tenant_id=? ORDER BY series');$s->execute([$this->tenantId]);return$s->fetchAll(PDO::FETCH_COLUMN);
    }

    public function detail(int $id):?array
    {
        if(!$this->tableExists('fiscal_documents'))return null;$number=$this->numberExpression('r');$cnf=$this->columnExists('fiscal_number_reservations','cnf')?'r.cnf':'r.numeric_code';$hasArtifacts=$this->tableExists('fiscal_artifacts');
        $artifactJoin=$hasArtifacts?' LEFT JOIN fiscal_artifacts a ON a.id=(SELECT MAX(a2.id) FROM fiscal_artifacts a2 WHERE a2.tenant_id=d.tenant_id AND a2.fiscal_document_id=d.id)':'';
        $artifactSelect=$hasArtifacts?'a.id artifact_id,a.status artifact_status,a.sha256':'NULL artifact_id,NULL artifact_status,NULL sha256';
        $sql='SELECT d.*,o.operation_type,o.internal_code,o.operation_date,r.id reservation_id,'.$number.' fiscal_number,'.$cnf.' cnf,r.access_key reservation_access_key,r.access_key,r.model,r.series,r.environment,'.$artifactSelect.' FROM fiscal_documents d LEFT JOIN fiscal_orders o ON o.id=d.source_order_id AND o.tenant_id=d.tenant_id LEFT JOIN fiscal_number_reservations r ON r.id=(SELECT MAX(r2.id) FROM fiscal_number_reservations r2 WHERE r2.tenant_id=d.tenant_id AND r2.fiscal_document_id=d.id)'.$artifactJoin.' WHERE d.tenant_id=? AND d.id=? LIMIT 1';
        $s=$this->pdo->prepare($sql);$s->execute([$this->tenantId,$id]);$row=$s->fetch(PDO::FETCH_ASSOC);if(!$row)return null;$row['items']=[];
        if($this->tableExists('fiscal_document_items')){$items=$this->pdo->prepare('SELECT * FROM fiscal_document_items WHERE fiscal_document_id=? ORDER BY id');$items->execute([$id]);$row['items']=$items->fetchAll(PDO::FETCH_ASSOC);}return$row;
    }

    public function mirrorDetail(int $id):?array
    {
        if(!$this->tableExists('fiscal_mirrors'))return null;$s=$this->pdo->prepare('SELECT m.*,o.operation_type,o.internal_code,o.operation_date FROM fiscal_mirrors m LEFT JOIN fiscal_orders o ON o.id=m.source_order_id AND o.tenant_id=m.tenant_id WHERE m.tenant_id=? AND m.id=? LIMIT 1');$s->execute([$this->tenantId,$id]);$row=$s->fetch(PDO::FETCH_ASSOC);if(!$row)return null;$row['snapshot']=json_decode((string)$row['operation_snapshot_json'],true)?:[];return$row;
    }

    public function timeline(int $documentId):array
    {
        if(!$this->tableExists('fiscal_document_events'))return[];$s=$this->pdo->prepare('SELECT id,event_type,stage,status,code,message,metadata_json,created_by,created_at FROM fiscal_document_events WHERE tenant_id=? AND fiscal_document_id=? ORDER BY id DESC');$s->execute([$this->tenantId,$documentId]);return$s->fetchAll(PDO::FETCH_ASSOC);
    }

    private function listingUnion(array $f):array
    {
        $parts=[];$params=[];$type=(string)($f['type']??'');$status=(string)($f['status']??'');$model=(string)($f['model']??'');
        if($this->tableExists('fiscal_documents')&&$type!=='MIRROR'&&$model!=='MIRROR'&&$status!=='MIRROR'){[$sql,$p]=$this->documentsSelect($f);$parts[]=$sql;array_push($params,...$p);}
        $mirrorAllowed=$this->tableExists('fiscal_mirrors')&&!in_array($type,['ENTRY','EXIT'],true)&&($status===''||$status==='MIRROR')&&($model===''||$model==='MIRROR')&&(string)($f['series']??'')===''&&(string)($f['environment']??'')==='';
        if($mirrorAllowed){[$sql,$p]=$this->mirrorsSelect($f);$parts[]=$sql;array_push($params,...$p);}return[implode(' UNION ALL ',$parts),$params];
    }

    private function documentsSelect(array $f):array
    {
        $where=['d.tenant_id=?'];$params=[$this->tenantId];$number=$this->numberExpression('r');$artifacts=$this->tableExists('fiscal_artifacts');$statusExpression=$artifacts?'COALESCE(a.status,d.status)':'d.status';$resolvedModel="COALESCE(r.model,NULLIF(JSON_UNQUOTE(JSON_EXTRACT(d.totals_json,'$.model')),''),o.fiscal_model)";
        foreach(['model'=>$resolvedModel,'environment'=>'r.environment','series'=>'r.series']as$key=>$column)if((string)($f[$key]??'')!==''){$where[]=$column.'=?';$params[]=$f[$key];}
        if(in_array(($f['type']??''),['ENTRY','EXIT'],true)){$where[]='o.operation_type=?';$params[]=$f['type'];}
        if(($f['status']??'')!==''){$map=['PREPARING'=>['FISCAL_PENDING','FISCAL_READY','DRAFT','PREPARING'],'PENDING'=>['PENDING_TRANSMISSION','AGUARDANDO_TRANSMISSAO','XSD_VALID_OFFLINE','PROCESSING'],'AUTHORIZED'=>['AUTHORIZED','AUTORIZADA'],'REJECTED'=>['REJECTED','REJEITADA'],'FAILED'=>['FAILED','FAILURE','ERROR'],'CANCELLED'=>['CANCELLED','CANCELED','CANCELADA']];$values=$map[(string)$f['status']]??[(string)$f['status']];$where[]=$statusExpression.' IN ('.implode(',',array_fill(0,count($values),'?')).')';array_push($params,...$values);}
        $this->dates($where,$params,$f,'d.created_at');if(($f['q']??'')!==''){$where[]="CONVERT(CONCAT_WS(' ',d.id,o.id,o.internal_code,{$number},r.access_key,d.recipient_snapshot_json) USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci";$params[]='%'.$f['q'].'%';}
        $events=$this->tableExists('fiscal_document_events');$eventJoin=$events?' LEFT JOIN fiscal_document_events e ON e.id=(SELECT MAX(e2.id) FROM fiscal_document_events e2 WHERE e2.tenant_id=d.tenant_id AND e2.fiscal_document_id=d.id)':'';$eventSelect=$events?'e.code,e.message,e.stage':'NULL code,NULL message,NULL stage';
        $artifactJoin=$artifacts?' LEFT JOIN fiscal_artifacts a ON a.id=(SELECT MAX(a2.id) FROM fiscal_artifacts a2 WHERE a2.tenant_id=d.tenant_id AND a2.fiscal_document_id=d.id)':'';$artifactSelect=$artifacts?'a.id artifact_id,a.status artifact_status':'NULL artifact_id,NULL artifact_status';
        $model=$resolvedModel;
        $certificateReady=$this->tableExists('fiscal_certificates')?"EXISTS(SELECT 1 FROM fiscal_certificates cert WHERE cert.tenant_id=d.tenant_id AND cert.establishment_id=o.establishment_id AND cert.active=1 AND cert.status IN ('VALID','EXPIRING_SOON') AND cert.valid_from<=NOW() AND cert.valid_until>NOW())":'0';
        $seriesReady=$this->tableExists('fiscal_series')?"EXISTS(SELECT 1 FROM fiscal_series fs WHERE fs.tenant_id=d.tenant_id AND fs.establishment_id=o.establishment_id AND fs.model={$model} AND fs.active=1 AND fs.environment=2)":'0';
        return["SELECT 'DOCUMENT' record_type,d.id record_id,d.id sort_id,NULL mirror_id,d.source_order_id,d.created_at,d.recipient_snapshot_json,d.totals_json,o.operation_type,o.establishment_id,d.status document_status,{$artifactSelect},{$number} fiscal_number,r.access_key,{$model} model,r.series,COALESCE(r.environment,(SELECT fs2.environment FROM fiscal_series fs2 WHERE fs2.tenant_id=d.tenant_id AND fs2.establishment_id=o.establishment_id AND fs2.model={$model} AND fs2.active=1 ORDER BY fs2.id DESC LIMIT 1)) environment,{$certificateReady} certificate_ready,{$seriesReady} series_ready,{$eventSelect} FROM fiscal_documents d LEFT JOIN fiscal_orders o ON o.id=d.source_order_id AND o.tenant_id=d.tenant_id LEFT JOIN fiscal_number_reservations r ON r.id=(SELECT MAX(r2.id) FROM fiscal_number_reservations r2 WHERE r2.tenant_id=d.tenant_id AND r2.fiscal_document_id=d.id){$artifactJoin}{$eventJoin} WHERE ".implode(' AND ',$where),$params];
    }

    private function mirrorsSelect(array $f):array
    {
        $where=['m.tenant_id=?'];$params=[$this->tenantId];$this->dates($where,$params,$f,'m.created_at');if(($f['q']??'')!==''){$where[]="CONVERT(CONCAT_WS(' ',m.id,m.source_order_id,o.internal_code,m.operation_snapshot_json,c.nome,c.cpf_cnpj,f.nome,f.cpf_cnpj) USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci";$params[]='%'.$f['q'].'%';}
        return["SELECT 'MIRROR' record_type,CONCAT('M',m.id) record_id,m.id sort_id,m.id mirror_id,m.source_order_id,m.created_at,JSON_OBJECT('nome',COALESCE(c.nome,f.nome),'cpf_cnpj',COALESCE(c.cpf_cnpj,f.cpf_cnpj)) recipient_snapshot_json,m.operation_snapshot_json totals_json,o.operation_type,o.establishment_id,'ESPELHO' document_status,NULL artifact_id,NULL artifact_status,NULL fiscal_number,NULL access_key,JSON_UNQUOTE(JSON_EXTRACT(m.operation_snapshot_json,'$.fiscal_model')) model,NULL series,NULL environment,0 certificate_ready,0 series_ready,NULL code,'Não transmitido à SEFAZ.' message,'ESPELHO' stage FROM fiscal_mirrors m LEFT JOIN fiscal_orders o ON o.id=m.source_order_id AND o.tenant_id=m.tenant_id LEFT JOIN clientes c ON o.operation_type='EXIT' AND c.id=CAST(JSON_UNQUOTE(JSON_EXTRACT(m.operation_snapshot_json,'$.person_id')) AS UNSIGNED) LEFT JOIN fornecedores f ON o.operation_type='ENTRY' AND f.id=CAST(JSON_UNQUOTE(JSON_EXTRACT(m.operation_snapshot_json,'$.person_id')) AS UNSIGNED) WHERE ".implode(' AND ',$where),$params];
    }

    private function dates(array&$where,array&$params,array$f,string$column):void{if(($f['from']??'')!==''){$where[]='DATE('.$column.')>=?';$params[]=$f['from'];}if(($f['to']??'')!==''){$where[]='DATE('.$column.')<=?';$params[]=$f['to'];}}
    private function numberExpression(string$alias):string{if(!$this->tableExists('fiscal_number_reservations'))return'NULL';return$this->columnExists('fiscal_number_reservations','number')?$alias.'.number':$alias.'.fiscal_number';}
    private function tableExists(string$table):bool{if(array_key_exists($table,$this->tableCache))return$this->tableCache[$table];$s=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');$s->execute([$table]);return$this->tableCache[$table]=(int)$s->fetchColumn()===1;}
    private function columnExists(string$table,string$column):bool{$key=$table.'.'.$column;if(array_key_exists($key,$this->columnCache))return$this->columnCache[$key];$s=$this->pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$s->execute([$table,$column]);return$this->columnCache[$key]=(int)$s->fetchColumn()===1;}
}
