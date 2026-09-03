<?php
declare(strict_types=1);
namespace MiniErp\Platform;
use RuntimeException;

final class ImportPlanStorage
{
    public function __construct(private string$root,private int$ttl=900){}
    public function create(int$adminId,array$plan):string{$id=bin2hex(random_bytes(16));$plan+=['created_at'=>time(),'expires_at'=>time()+$this->ttl];$plan['checksum']=hash('sha256',json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));$dir=$this->directory($adminId);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Não foi possível preparar a simulação.');$json=json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);if(file_put_contents($dir.DIRECTORY_SEPARATOR.$id.'.json',$json,LOCK_EX)===false)throw new RuntimeException('Não foi possível salvar a simulação.');return$id;}
    public function load(string$id,int$adminId):array{if(!preg_match('/^[a-f0-9]{32}$/',$id))throw new RuntimeException('Plano de importação inválido.');$path=$this->directory($adminId).DIRECTORY_SEPARATOR.$id.'.json';$json=@file_get_contents($path);if($json===false)throw new RuntimeException('Plano de importação inexistente ou já utilizado.');$plan=json_decode($json,true,512,JSON_THROW_ON_ERROR);$checksum=$plan['checksum']??'';unset($plan['checksum']);if(!hash_equals((string)$checksum,hash('sha256',json_encode($plan,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))))throw new RuntimeException('Plano de importação foi alterado.');if((int)$plan['expires_at']<time())throw new RuntimeException('A simulação expirou. Envie a planilha novamente.');$plan['checksum']=$checksum;return$plan;}
    public function consume(string$id,int$adminId):array{$plan=$this->load($id,$adminId);$path=$this->directory($adminId).DIRECTORY_SEPARATOR.$id.'.json';if(!unlink($path))throw new RuntimeException('Não foi possível consumir o plano de importação.');return$plan;}
    private function directory(int$id):string{if($id<1)throw new RuntimeException('Administrador inválido.');return rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.'admin-'.$id;}
}
