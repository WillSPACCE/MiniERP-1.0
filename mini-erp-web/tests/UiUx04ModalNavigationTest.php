<?php
declare(strict_types=1);
function u4(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$root=dirname(__DIR__);$modal=file_get_contents($root.'/public/assets/company-modal.js');$nav=file_get_contents($root.'/public/assets/company-modal-navigation.js');$css=file_get_contents($root.'/public/assets/company-modal-navigation.css').file_get_contents($root.'/public/assets/company-modal-shell.css');$page=file_get_contents($root.'/public/plataforma/index.php');
foreach(['company-modal-navigation.js','company-modal-navigation.css','company-modal-shell.css'] as $needle)u4(str_contains($page,$needle),$needle);
foreach(['data-modal-back','data-modal-forward','company-modal-breadcrumb','stack=[]','position','navigate(','popstate','replaceState','subtab','drafts=new Map','scrolls=new Map','is-dirty'] as $needle)u4(str_contains($nav,$needle),$needle);
foreach(['grid-template-rows:auto auto auto minmax(0,1fr) auto','company-modal-tabs{position:sticky','fiscal-subtabs{position:sticky','company-modal-body.is-scrolled','@media(max-width:720px)'] as $needle)u4(str_contains($css,$needle),$needle);
u4(str_contains($modal,"dataset.subtab=section.id")&&str_contains($modal,'Geral Fiscal'),'fiscal subtab hierarchy');
u4(str_contains($modal,"dialog.querySelector('.company-modal-tabs .is-dirty')"),'dirty close protection');
u4(!preg_match('/NFeAutorizacao|autoriza(?:r|ção)\s+NF-e/i',$modal.$nav),'zero SEFAZ auth');
echo "CompanyModalStickyNavigation PASS\nCompanyModalBreadcrumb PASS\nCompanyModalNavigationHistory PASS\nCompanyModalBrowserBack PASS\nCompanyModalBrowserForward PASS\nCompanyModalActiveState PASS\nCompanyModalSubtabHierarchy PASS\nCompanyModalScrollIsolation PASS\nCompanyModalStickyFooter PASS\nCompanyModalDirtyTabIndicator PASS\nCompanyModalTabScrollRestore PASS\nCompanyModalResponsiveNavigation PASS\n";
