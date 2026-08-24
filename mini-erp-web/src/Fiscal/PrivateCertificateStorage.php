<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use RuntimeException;
final class PrivateCertificateStorage {
 public function __construct(private string $root){$public=realpath(__DIR__.'/../../public');$resolved=realpath($root);if($resolved&&$public&&str_starts_with($resolved,$public))throw new RuntimeException('Storage de certificado nao pode estar em public.');}
 public function store(int $tenant,int $est,string $ext,string $content):string{if(!in_array($ext,['pfx','p12'],true))throw new RuntimeException('Extensao invalida.');$scope="tenant-{$tenant}/establishment-{$est}";$dir=rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$scope);if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Falha no storage privado.');$ref=$scope.'/'.bin2hex(random_bytes(16)).'.'.$ext;$path=rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$ref);if(file_put_contents($path,$content,LOCK_EX)===false)throw new RuntimeException('Falha ao salvar certificado.');@chmod($path,0600);return $ref;}
 public function read(string $ref):string{$data=@file_get_contents($this->path($ref));if($data===false)throw new RuntimeException('Certificado indisponivel.');return $data;}
 public function delete(string $ref):void{$path=$this->path($ref);if(is_file($path)&&!unlink($path))throw new RuntimeException('Falha ao remover certificado.');}
 private function path(string $ref):string{if($ref===''||str_contains($ref,'..')||preg_match('#^[\\/]#',$ref)||preg_match('/^[A-Za-z]:/',$ref)||preg_match('/[^A-Za-z0-9_.\/-]/',$ref))throw new RuntimeException('Referencia invalida.');$root=rtrim($this->root,'/\\').DIRECTORY_SEPARATOR;$path=$root.str_replace('/',DIRECTORY_SEPARATOR,$ref);if(!str_starts_with($path,$root))throw new RuntimeException('Referencia invalida.');return $path;}
}
