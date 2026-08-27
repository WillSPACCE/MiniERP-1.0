<?php
declare(strict_types=1);
$root=dirname(__DIR__);$repository=(string)file_get_contents($root.'/app/Repository.php');$index=(string)file_get_contents($root.'/public/index.php');$fiscal=(string)file_get_contents($root.'/src/Repositories/FiscalOperationRepository.php');$directory=(string)file_get_contents($root.'/src/Repositories/MasterDataDirectoryRepository.php');
function relation48(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
foreach(['listFornecedores','listMotoristas','listTransportadoras'] as $method)relation48(str_contains($repository,'function '.$method.'(')&&str_contains($index,'->'.$method.'('),'operational repository relation '.$method);
foreach(['fornecedor_id','transportadora_id','motorista_id'] as $field)relation48(str_contains($index,$field),'order form relation '.$field);
foreach(['person_id','carrier_id','driver_id'] as $column)relation48(str_contains($fiscal,$column),'fiscal order relation '.$column);
foreach(['clientes','fornecedores','motoristas','transportadoras','UNION ALL','source_ref','sources'] as $contract)relation48(str_contains($directory,$contract),'people facade contract '.$contract);
relation48(!preg_match('/DROP\s+TABLE|TRUNCATE\s+TABLE|ALTER\s+TABLE/i',$directory),'directory is read-only');
echo "MasterDataRelationRegressionTest OK\n";
