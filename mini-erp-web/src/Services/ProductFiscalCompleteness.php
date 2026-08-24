<?php
declare(strict_types=1);namespace MiniErp\Services;
final class ProductFiscalCompleteness{public function evaluate(array $p):array{$m=[];if(!preg_match('/^\d{8}$/',(string)($p['ncm']??'')))$m[]='NCM';if(!in_array((string)($p['merchandise_origin']??''),ProductFiscalData::ORIGINS,true))$m[]='origem';if(trim((string)($p['unidade']??''))==='')$m[]='unidade comercial';if(trim((string)($p['taxable_unit']??''))==='')$m[]='unidade tributável';if((float)($p['conversion_factor']??0)<=0)$m[]='fator de conversão';return ['complete'=>$m===[],'missing'=>$m];}}
