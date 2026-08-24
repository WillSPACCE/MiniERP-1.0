<?php
declare(strict_types=1);
namespace MiniErp\Adapters;
use MiniErp\Contracts\SefazStatusClientContract;use NFePHP\Common\Certificate;use NFePHP\Common\Soap\SoapNative;use NFePHP\NFe\Tools;use RuntimeException;
final class NfePhpSefazStatusClient implements SefazStatusClientContract{
 public function status(string$configJson,object$certificate,string$uf,int$timeoutSeconds):string{if(!$certificate instanceof Certificate)throw new RuntimeException('SEFAZ_CERTIFICATE_ERROR');$tools=new Tools($configJson,$certificate);$tools->model(55);$soap=new SoapNative($certificate);$soap->disableSecurity(false);$soap->timeout(max(5,min(30,$timeoutSeconds)));$tools->loadSoapClass($soap);return$tools->sefazStatus($uf,2,true);}
}
