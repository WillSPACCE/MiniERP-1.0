<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use RuntimeException;
final readonly class FiscalDanfeLogoStorage{
 public function __construct(private string$root){}
 public function resolve(int$tenantId,string$reference):string{if($tenantId<1||$reference===''||str_contains($reference,'..')||str_starts_with($reference,'/')||preg_match('/^[A-Za-z]:/',$reference))throw new RuntimeException('DANFE_LOGO_REFERENCE_BLOCKED');$prefix='tenant-'.$tenantId.'/';if(!str_starts_with(str_replace('\\','/',$reference),$prefix))throw new RuntimeException('DANFE_LOGO_CROSS_TENANT_BLOCKED');$root=realpath($this->root);$candidate=realpath(rtrim($this->root,'/\\').DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$reference));if($root===false||$candidate===false||!str_starts_with(strtolower($candidate),strtolower($root.DIRECTORY_SEPARATOR))||!is_file($candidate))throw new RuntimeException('DANFE_LOGO_UNAVAILABLE');$type=@exif_imagetype($candidate);if(!in_array($type,[IMAGETYPE_PNG,IMAGETYPE_JPEG],true))throw new RuntimeException('DANFE_LOGO_UNAVAILABLE');return$candidate;}
}
