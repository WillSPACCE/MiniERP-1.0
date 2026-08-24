<?php
declare(strict_types=1);
$source=(string)file_get_contents(__DIR__.'/../public/index.php');
function order_page_assert(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
$start=strpos($source,"case 'pedidos'");$end=strpos($source,"case 'cadastro'",$start);$orders=substr($source,$start,$end-$start);
order_page_assert(str_contains($orders,'SELECT primary_model FROM establishment_fiscal_settings'),'minimal primary model query');
order_page_assert(str_contains($orders,'catch(\\Throwable $previewModelError)'),'missing/legacy settings must not break order pages');
order_page_assert(!str_contains($orders,'$previewSettingsRepo->all'),'must not load every fiscal settings table');
order_page_assert(str_contains($orders,"\$previewCompanyModel = '55'"),'safe display fallback');
foreach(['tab=entrada','tab=saida','Modelo fiscal pretendido','previewSelectedModel']as$needle)order_page_assert(str_contains($source,$needle),'order UI missing '.$needle);
echo "OrderPagesPrimaryModelResilience PASS\n";
