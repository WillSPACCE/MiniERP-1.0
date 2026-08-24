<?php
declare(strict_types=1);
namespace MiniErp\Services;
use InvalidArgumentException;
final readonly class ProductFiscalData
{
 public const ORIGINS=['0','1','2','3','4','5','6','7','8']; private array $data;
 public function __construct(array $input)
 {
  $s=static fn(string $k,int $m=255):string=>mb_substr(trim((string)($input[$k]??'')),0,$m);$digits=static fn(string $v):string=>preg_replace('/\D/','',$v)??'';
  $ncm=$digits($s('ncm',20));$cest=$digits($s('cest',20));$origin=$s('merchandise_origin',1);$u=strtoupper($s('unidade',6)?:'UN');$uTrib=strtoupper($s('taxable_unit',6)?:$u);$gtin=strtoupper($s('gtin',20)?:'SEM GTIN');$gtinTrib=strtoupper($s('gtin_tributable',20)?:$gtin);$factorInput=$s('conversion_factor',20);$factor=(float)str_replace(',','.',$factorInput===''?'1':$factorInput);
  if($ncm!==''&&strlen($ncm)!==8)throw new InvalidArgumentException('NCM deve possuir 8 dígitos.');if($cest!==''&&strlen($cest)!==7)throw new InvalidArgumentException('CEST deve possuir 7 dígitos quando aplicável.');if($origin!==''&&!in_array($origin,self::ORIGINS,true))throw new InvalidArgumentException('Origem da mercadoria inválida.');
  foreach([$gtin,$gtinTrib] as $g)if($g!=='SEM GTIN'&&!preg_match('/^(\d{8}|\d{12}|\d{13}|\d{14})$/',$g))throw new InvalidArgumentException('GTIN deve ter 8, 12, 13 ou 14 dígitos, ou SEM GTIN.');if($factor<=0)throw new InvalidArgumentException('Fator de conversão deve ser maior que zero.');$cfop=$digits($s('cfop_padrao',4));if($cfop!==''&&strlen($cfop)!==4)throw new InvalidArgumentException('CFOP padrão deve possuir 4 dígitos.');
  $this->data=['ncm'=>$ncm,'cest'=>$cest,'unidade'=>$u,'gtin'=>$gtin,'gtin_tributable'=>$gtinTrib,'taxable_unit'=>$uTrib,'conversion_factor'=>$factor,'merchandise_origin'=>$origin,'extipi'=>strtoupper($s('extipi',3)),'tax_benefit_code'=>strtoupper($s('tax_benefit_code',20)),'fci_number'=>strtoupper($s('fci_number',36)),'cfop_padrao'=>$cfop,'cost_price'=>max(0,(float)str_replace(',','.',$s('cost_price',20)?:'0')),'minimum_stock'=>max(0,(float)str_replace(',','.',$s('minimum_stock',20)?:'0'))];
 }
 public function toArray():array{return $this->data;}
}
