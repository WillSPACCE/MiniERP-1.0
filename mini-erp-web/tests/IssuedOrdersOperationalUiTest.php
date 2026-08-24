<?php
declare(strict_types=1);
$root=dirname(__DIR__);$page=file_get_contents($root.'/public/index.php');$endpoint=file_get_contents($root.'/public/issued_order_action.php');$service=file_get_contents($root.'/src/Services/IssuedOrderManagementService.php');$repository=file_get_contents($root.'/src/Repositories/IssuedOrdersRepository.php');$js=file_get_contents($root.'/public/assets/issued-orders.js');
function io44(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['io_q','io_from','io_to','io_model','io_status','io_per_page','data-issued-action="preview"','data-issued-action="issue"','data-issued-action="clone"','data-issued-action="delete"']as$token)io44(str_contains($page,$token),'UI '.$token);
io44(str_contains($repository,'COUNT(*)')&&str_contains($repository,'LIMIT {$perPage} OFFSET'),'server pagination');
foreach(['hash_equals','tenant','erp_fiscal_csrf','cross-site']as$token)io44(str_contains($endpoint,$token),'security '.$token);
io44(!preg_match('/sefazEnviaLote|sefazConsultaRecibo|sefazConsultaChave|RetAutorizacao/',$endpoint),'no SEFAZ');
foreach(['ORDER_HAS_FISCAL_RESERVATION','ORDER_HAS_FISCAL_DOCUMENT','fiscal_mirrors','DELETE FROM fiscal_order_items']as$token)io44(str_contains($service,$token),'delete guard '.$token);
io44(str_contains($js,"window.open('', '_blank')")&&str_contains($endpoint,'page=fiscal_notes&context=preview'),'preview/central flow');
echo "IssuedOrdersOperationalUiTest OK\n";
