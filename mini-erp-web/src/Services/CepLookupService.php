<?php
declare(strict_types=1);
namespace MiniErp\Services;

use InvalidArgumentException;
use RuntimeException;

final class CepLookupService
{
    private ?\Closure $transport;
    public function __construct(private ?string $cacheDirectory=null,?\Closure $transport=null){$this->transport=$transport;}
    public static function normalize(string $cep):string{return preg_replace('/\D/','',trim($cep))??'';}
    public function lookup(string $cep):?array
    {
        $cep=self::normalize($cep);if(strlen($cep)!==8)throw new InvalidArgumentException('CEP deve conter 8 dígitos.');$cache=$this->cacheDirectory?rtrim($this->cacheDirectory,'/\\').DIRECTORY_SEPARATOR.hash('sha256',$cep).'.json':null;
        if($cache&&is_file($cache)&&filemtime($cache)>=time()-86400){$data=json_decode((string)file_get_contents($cache),true);if(is_array($data))return$data;}
        $url='https://brasilapi.com.br/api/cep/v2/'.rawurlencode($cep);
        if($this->transport){$http=($this->transport)($url);$body=$http['body']??false;$status=(int)($http['status']??0);$error=(string)($http['error']??'');}else{if(!function_exists('curl_init'))throw new RuntimeException('CEP_SERVICE_UNAVAILABLE');$curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>8,CURLOPT_USERAGENT=>'MiniERP/1.0',CURLOPT_HTTPHEADER=>['Accept: application/json']]);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);$error=curl_error($curl);curl_close($curl);}
        if($body===false)throw new RuntimeException(str_contains(strtolower($error),'timed out')?'CEP_SERVICE_TIMEOUT':'CEP_SERVICE_UNAVAILABLE');if($status===404)return null;if($status===429)throw new RuntimeException('CEP_RATE_LIMIT');if($status<200||$status>=300)throw new RuntimeException('CEP_SERVICE_UNAVAILABLE');$raw=json_decode((string)$body,true);if(!is_array($raw))throw new RuntimeException('CEP_INVALID_RESPONSE');
        $result=['postal_code'=>$cep,'street'=>trim((string)($raw['street']??'')),'district'=>trim((string)($raw['neighborhood']??'')),'city'=>trim((string)($raw['city']??'')),'state'=>strtoupper(trim((string)($raw['state']??''))),'city_ibge_code'=>preg_replace('/\D/','',(string)($raw['ibge']['city']??''))??''];
        if($cache){if(!is_dir(dirname($cache)))@mkdir(dirname($cache),0770,true);@file_put_contents($cache,json_encode($result,JSON_UNESCAPED_UNICODE),LOCK_EX);}return$result;
    }
}
