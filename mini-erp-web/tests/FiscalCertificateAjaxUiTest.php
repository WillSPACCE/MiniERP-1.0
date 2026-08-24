<?php
declare(strict_types=1);
function au(bool $value,string $message):void{if(!$value)throw new RuntimeException($message);}
$page=(string)file_get_contents(__DIR__.'/../public/plataforma/empresa-certificado.php');
$ajax=(string)file_get_contents(__DIR__.'/../public/plataforma/ajax-certificado.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/certificate-install.js');
foreach(['Certificado instalado','Instalar novo certificado','CNPJ esperado','Testar Certificado','Remover Certificado','data-certificate-diagnostic']as$text)au(str_contains($page,$text),$text);
au(str_contains($ajax,'requirePlatformUserCsrf')&&str_contains($ajax,'getSelectedTenantId'),'CSRF/tenant authority');
au(!str_contains($ajax,'$_POST[\'tax_id\']'),'tax id not accepted from POST');
au(str_contains($js,'fetch(')&&str_contains($js,"[name=password]').value=''"),'AJAX/password cleared');
$service=(string)file_get_contents(__DIR__.'/../src/Services/EstablishmentFiscalConfigurationService.php');
au(strpos($service,'$this->certs->store')<strpos($service,'$this->inspector->inspect'),'store before inspect');
echo"FiscalCertificateAjaxUi OK\n";
