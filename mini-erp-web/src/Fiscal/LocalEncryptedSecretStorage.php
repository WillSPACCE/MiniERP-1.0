<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use MiniErp\Contracts\SecretStorageContract;use RuntimeException;
final class LocalEncryptedSecretStorage implements SecretStorageContract {
 public function __construct(private string $root,private string $applicationKey){if(strlen($applicationKey)<32)throw new RuntimeException('FISCAL_SECRET_KEY ausente ou curta.');}
 public function put(string $scope,string $secret):string{$scope=$this->safe($scope);$dir=rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.$scope;if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Falha no storage privado.');$ref=$scope.'/'.bin2hex(random_bytes(16)).'.secret';$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($secret,'aes-256-gcm',hash('sha256',$this->applicationKey,true),OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('Falha ao cifrar segredo.');file_put_contents(rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.$ref,$iv.$tag.$cipher,LOCK_EX);return $ref;}
 public function get(string $reference):string{$raw=@file_get_contents($this->path($reference));if($raw===false||strlen($raw)<29)throw new RuntimeException('Segredo indisponivel.');$plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',hash('sha256',$this->applicationKey,true),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16));if($plain===false)throw new RuntimeException('Segredo invalido.');return $plain;}
 public function delete(string $reference):void{$path=$this->path($reference);if(is_file($path)&&!unlink($path))throw new RuntimeException('Falha ao remover segredo.');}
 private function path(string $ref):string{$safe=$this->safe($ref);$root=rtrim($this->root,'/\\').DIRECTORY_SEPARATOR;$path=$root.str_replace('/',DIRECTORY_SEPARATOR,$safe);if(!str_starts_with($path,$root))throw new RuntimeException('Referencia invalida.');return $path;}
 private function safe(string $value):string{if($value===''||str_contains($value,'..')||preg_match('/[^A-Za-z0-9_.\/-]/',$value))throw new RuntimeException('Referencia de storage invalida.');return trim(str_replace('\\','/',$value),'/');}
}
