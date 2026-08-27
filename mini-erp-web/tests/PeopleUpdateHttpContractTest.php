<?php
declare(strict_types=1);
$root=dirname(__DIR__);$action=(string)file_get_contents($root.'/public/master_data_action.php');$service=(string)file_get_contents($root.'/src/Services/PeoplePersistenceService.php');$repository=(string)file_get_contents($root.'/app/Repository.php');$js=(string)file_get_contents($root.'/public/assets/master-data.js');
function http49(bool$ok,string$message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['new FormData(form)',"body.set('csrf'","body.set('operation','save')",'form.elements.id.value=payload.id','dirty=false','data-stay']as$needle)http49(str_contains($js,$needle),'JS save contract '.$needle);
foreach(['PeoplePersistenceService','saveClient($_POST)',"'person_id'=>\$id","'updated_sections'","'affected_rows'","'record'"]as$needle)http49(str_contains($action,$needle),'HTTP response contract '.$needle);
foreach(['findCliente($requestedId)','beginTransaction','commit()','rollBack()','PERSON_READ_BACK_FAILED','RECORD_NOT_FOUND']as$needle)http49(str_contains($service,$needle),'transaction/read-back '.$needle);
http49(str_contains($repository,'public function saveCliente(array $data): int')&&str_contains($repository,'return $stmt->rowCount();'),'real SQL row count');
http49(str_contains($action,'hash_equals')&&str_contains($action,"\$_SESSION['erp_tenant_id']")&&!str_contains($action,"\$_POST['tenant_id']"),'CSRF and tenant session');
http49(str_contains($action,"default=>'Não foi possível concluir. Os dados foram mantidos.'")&&!str_contains(substr($action,strpos($action,'catch(Throwable')), 'getTraceAsString'),'no SQLSTATE/stack leak');
echo "PeopleUpdateHttpContractTest OK\n";
