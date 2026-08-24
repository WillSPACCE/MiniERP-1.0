<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use PDO;use RuntimeException;use Throwable;
final readonly class FiscalNumberAllocator {
 public function __construct(private PDO $pdo,private int $tenantId){}
 public function reserve(int $est,string $model,int $series,int $document,int $user):array{$owner=!$this->pdo->inTransaction();if($owner)$this->pdo->beginTransaction();try{
  $s=$this->pdo->prepare('SELECT id,next_number,environment FROM fiscal_series WHERE tenant_id=? AND establishment_id=? AND model=? AND series=? AND active=1 FOR UPDATE');$s->execute([$this->tenantId,$est,$model,$series]);$config=$s->fetch(PDO::FETCH_ASSOC);if(!$config)throw new RuntimeException('Serie fiscal ativa nao configurada.');
  $existing=$this->pdo->prepare('SELECT * FROM fiscal_number_reservations WHERE tenant_id=? AND fiscal_document_id=? AND fiscal_document_version=1 LIMIT 1');$existing->execute([$this->tenantId,$document]);if($row=$existing->fetch(PDO::FETCH_ASSOC)){if($owner)$this->pdo->commit();return $row;}
  $number=(int)$config['next_number'];
  $this->pdo->prepare('UPDATE fiscal_series SET next_number=next_number+1 WHERE id=?')->execute([$config['id']]);
  $this->pdo->prepare("INSERT INTO fiscal_number_reservations(tenant_id,establishment_id,fiscal_document_id,fiscal_document_version,fiscal_series_id,model,series,number,environment,status,created_by) VALUES(?,?,?,?,?,?,?,?,?,'RESERVED',?)")->execute([$this->tenantId,$est,$document,1,(int)$config['id'],$model,$series,$number,$config['environment'],$user]);
  $id=(int)$this->pdo->lastInsertId();
  if($owner)$this->pdo->commit();
  return ['id'=>$id,'number'=>$number,'series'=>$series,'model'=>$model,'environment'=>$config['environment'],'status'=>'RESERVED'];
 }catch(Throwable $e){if($owner&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}}
}
