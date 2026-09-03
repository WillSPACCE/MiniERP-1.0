<?php
declare(strict_types=1);
$root=dirname(__DIR__);$page=(string)file_get_contents($root.'/public/index.php');$view=(string)file_get_contents($root.'/public/includes/xml_catalog_registration.php');$js=(string)file_get_contents($root.'/public/assets/xml-catalog.js');$endpoint=(string)file_get_contents($root.'/public/entry_xml_import.php');
function xmlCatalogAssert(bool$c,string$l):void{if(!$c)throw new RuntimeException($l);}
xmlCatalogAssert(str_contains($page,"['pessoas', 'produtos', 'cfops', 'xml']")&&str_contains($page,"includes/xml_catalog_registration.php"),'XML catalog route');
xmlCatalogAssert(str_contains($view,'Cadastro por XML')&&str_contains($view,'value="fornecedor"')&&str_contains($view,'value="cliente"'),'party choice UI');
xmlCatalogAssert(str_contains($view,'data-xml-products')&&str_contains($view,'Cadastrar empresa, produtos e impostos'),'analysis preview UI');
xmlCatalogAssert(str_contains($js,"request('catalog',partyType)")&&str_contains($js,'people_type=${destination}'),'catalog distribution');
xmlCatalogAssert(str_contains($endpoint,"['catalog','import']")&&str_contains($endpoint,"\$partyType"),'endpoint modes');
echo "XmlCatalogRegistrationUiTest OK\n";
