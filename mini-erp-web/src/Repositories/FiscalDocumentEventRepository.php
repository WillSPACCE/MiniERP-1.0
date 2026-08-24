<?php
declare(strict_types=1);namespace MiniErp\Repositories;use PDO;
final class FiscalDocumentEventRepository{
 public function __construct(private PDO$pdo,private int$tenantId){}
 public function append(int$documentId,string$type,string$stage,string$status,?string$code,?string$message,array$metadata,int$actor):int{$s=$this->pdo->prepare('INSERT INTO fiscal_document_events(tenant_id,fiscal_document_id,event_type,stage,status,code,message,metadata_json,created_by) VALUES(?,?,?,?,?,?,?,?,?)');$s->execute([$this->tenantId,$documentId,$type,$stage,$status,$code,$message,$metadata?json_encode($metadata,JSON_THROW_ON_ERROR):null,$actor]);return(int)$this->pdo->lastInsertId();}
 public function timeline(int$documentId):array{$s=$this->pdo->prepare('SELECT id,event_type,stage,status,code,message,metadata_json,created_by,created_at FROM fiscal_document_events WHERE tenant_id=? AND fiscal_document_id=? ORDER BY id');$s->execute([$this->tenantId,$documentId]);return$s->fetchAll(PDO::FETCH_ASSOC);}
}
