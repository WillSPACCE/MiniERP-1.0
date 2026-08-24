<?php
declare(strict_types=1);

use MiniErp\Infrastructure\BrasilApiCnpjProvider;
use MiniErp\Services\CnpjLookupException;
use MiniErp\Services\CnpjLookupService;

require_once __DIR__ . '/_context.php';
requireAuthorizedPlatformContext();
header('Content-Type: application/json; charset=utf-8');
try {
    $now=time();
    $_SESSION['cnpj_lookup_times']=array_values(array_filter((array)($_SESSION['cnpj_lookup_times']??[]),static fn($t)=>(int)$t>$now-60));
    if(count($_SESSION['cnpj_lookup_times'])>=10)throw new CnpjLookupException('CNPJ_RATE_LIMIT','Aguarde.');
    $_SESSION['cnpj_lookup_times'][]=$now;
    $result=(new CnpjLookupService(new BrasilApiCnpjProvider(),__DIR__.'/../../storage/cache/cnpj'))->lookup((string)($_GET['cnpj']??''));
    if(!$result){http_response_code(404);echo json_encode(['success'=>false,'error'=>'CNPJ_NOT_FOUND']);exit;}
    echo json_encode(['success'=>true,'data'=>$result->toArray()],JSON_UNESCAPED_UNICODE);
}catch(CnpjLookupException $e){$map=['CNPJ_INVALID'=>400,'CNPJ_RATE_LIMIT'=>429,'CNPJ_SERVICE_TIMEOUT'=>504,'CNPJ_SERVICE_UNAVAILABLE'=>503,'CNPJ_PROVIDER_INVALID_RESPONSE'=>502];http_response_code($map[$e->reason]??500);echo json_encode(['success'=>false,'error'=>$e->reason]);}
