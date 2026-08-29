<?php
declare(strict_types=1);
function techManagementAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$page=file_get_contents(__DIR__.'/../public/plataforma/tecnicos.php');$repo=file_get_contents(__DIR__.'/../src/Repositories/PlatformAdminRepository.php');$css=file_get_contents(__DIR__.'/../public/assets/platform.css');
foreach(['toggle_status','reset_password','password_confirmation','value="delete"','Somente SUPER_ADMIN','Conta protegida','csrf_token']as$needle)techManagementAssert(str_contains($page,$needle),'UI/security '.$needle);
foreach(['setTechnicalUserActive','resetTechnicalUserPassword','deleteTechnicalUser',"role='GLOBAL_TECH'",'GLOBAL_TECH_BLOCKED','GLOBAL_TECH_PASSWORD_RESET','GLOBAL_TECH_DELETED']as$needle)techManagementAssert(str_contains($repo,$needle),'repository '.$needle);
techManagementAssert(str_contains($page,'$targetId===$actorId')&&str_contains($page,"\$target['role']!=='GLOBAL_TECH'"),'self and SUPER_ADMIN protection');
techManagementAssert(str_contains($repo,'password_changed_at=NOW()')&&str_contains($repo,'failed_login_attempts=0,locked_until=NULL'),'password reset clears lock state');
techManagementAssert(str_contains($css,'.tech-password-form')&&str_contains($css,'.tech-actions > form'),'action controls styled');
echo "GlobalTechnicalUserManagement OK\n";
