<?php
declare(strict_types=1);
function mobileActionAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$page=file_get_contents(__DIR__.'/../public/index.php');$css=file_get_contents(__DIR__.'/../public/assets/style.css');
foreach(['Gravar','Preparar Nota','Prévia DANFE','Preencher teste','>Novo<','>Financeiro<','data-order-action-bar']as$needle)mobileActionAssert(str_contains($page,$needle),'action '.$needle);
foreach(['@media (max-width: 700px)','grid-template-columns: repeat(2,minmax(0,1fr))','grid-column: 1 / -1','min-height: 48px','white-space: normal','touch-action: manipulation','.order-action-bar__divider { display: none; }']as$needle)mobileActionAssert(str_contains($css,$needle),'mobile action CSS '.$needle);
mobileActionAssert(strpos($css,'overflow: visible')>strpos($css,'@media (max-width: 700px)'),'mobile actions must not require horizontal scrolling');
echo "OrderActionBarMobile OK\n";
