<?php
declare(strict_types=1);$root=dirname(__DIR__);$page=file_get_contents($root.'/public/includes/company_configuration.php');$js=file_get_contents($root.'/public/assets/erp-companies.js');$css=file_get_contents($root.'/public/assets/erp-companies.css');$action=file_get_contents($root.'/public/erp_company_action.php');function ce45(bool$ok,string$m):void{if(!$ok)throw new RuntimeException($m);}
foreach(['Gerencie empresas e estabelecimentos.','company_status','company_q','data-company-row','data-erp-company-open','erp-company-modal','app-modal app-modal--xl','role="tablist"','data-company-back','data-company-forward','data-company-breadcrumb','data-company-save','data-company-save-close']as$n)ce45(str_contains($page,$n),'UI '.$n);
foreach(['Geral','Endereço','Fiscal','Certificado Digital','NF-e / NFC-e','Usuários','Prontidão']as$n)ce45(str_contains($page,$n),'tab '.$n);
ce45(str_contains($css,'grid-template-rows:auto auto auto minmax(0,1fr) auto')&&str_contains($css,'overflow:auto'),'sticky/internal scroll');
foreach(['dirty','Existem alterações não salvas','fetch(\'/erp_company_action.php\'','Consultar CNPJ','history']as$n)ce45(str_contains($js,$n),'JS '.$n);
foreach(['erp_tenant_id','erp_user_id','hash_equals','HTTP_SEC_FETCH_SITE','WHERE id=?','form_area']as$n)ce45(str_contains($action,$n),'security '.$n);
foreach(['A1CertificateInspector','PrivateCertificateStorage','certificate_action','saveSeries','EstablishmentService']as$n)ce45(str_contains($action,$n),'integration '.$n);
ce45(!str_contains($action,"\$_POST['tenant_id']")&&!preg_match('/SEFAZ|sefazEnviaLote|sefazConsultaRecibo/',$action),'tenant input/no SEFAZ');
echo "CompanyErpModalUiTest OK\n";
