<?php
declare(strict_types=1);
function auditControlAssert(bool$c,string$l):void{if(!$c)throw new RuntimeException($l);echo$l." PASS\n";}
$repo=(string)file_get_contents(__DIR__.'/../src/Repositories/TenantAccessPolicyRepository.php');$page=(string)file_get_contents(__DIR__.'/../public/plataforma/auditoria.php');$layout=(string)file_get_contents(__DIR__.'/../public/plataforma/_layout.php');$erp=(string)file_get_contents(__DIR__.'/../public/index.php');$fiscal=(string)file_get_contents(__DIR__.'/../public/fiscal_action.php');$issued=(string)file_get_contents(__DIR__.'/../public/issued_order_action.php');$login=(string)file_get_contents(__DIR__.'/../public/login.php');$css=(string)file_get_contents(__DIR__.'/../public/assets/platform-audit.css');
foreach(['FULL','READ_ONLY','BLOCKED','can_issue_fiscal','can_manage_users','can_use_financial','expires_at','revoked_at']as$n)auditControlAssert(str_contains($repo,$n),'Policy '.$n);
foreach(['tenant_access_rules','setRule','effectiveForTenant','FISCAL','USERS','FINANCIAL']as$n)auditControlAssert(str_contains($repo,$n),'Independent rule '.$n);
foreach(['5 dias','10 dias','15 dias','30 dias','data-audit-modal','Histórico recente']as$n)auditControlAssert(str_contains($page,$n),'Audit UI '.$n);
auditControlAssert(str_contains($layout,'/plataforma/auditoria.php'),'Audit navigation');
auditControlAssert(str_contains($erp,'modo somente consulta')&&str_contains($erp,'can_manage_users'),'ERP write enforcement');
auditControlAssert(str_contains($fiscal,'TENANT_FISCAL_BLOCKED')&&str_contains($issued,'can_issue_fiscal'),'Fiscal enforcement');
auditControlAssert(str_contains($login,"access_mode']??'FULL') === 'BLOCKED'"),'Login enforcement');
auditControlAssert(str_contains($login,'Motivo: ')&&str_contains($login,'Liberação automática em'),'Blocked login reason and expiry');
auditControlAssert(str_contains($erp,"in_array(\$page, ['pedidos','fiscal_notes']")&&str_contains($erp,'Entrada, Saída, Pedidos Emitidos e Central de Notas'),'Read only route enforcement');
auditControlAssert(str_contains($css,'max-height:94dvh')&&str_contains($css,'.audit-company-table td:before'),'Audit mobile layout');
$ruleAction=(string)file_get_contents(__DIR__.'/../public/plataforma/audit-rule-action.php');auditControlAssert(str_contains($ruleAction,'platform_audit_csrf')&&str_contains($ruleAction,'TENANT_ACCESS_RULE_CHANGED'),'Independent rule endpoint');
