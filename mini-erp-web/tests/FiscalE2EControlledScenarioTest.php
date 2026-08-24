<?php
declare(strict_types=1);

if(getenv('RUN_FISCAL_MARIADB_TESTS')!=='1'){echo"FiscalE2EControlledScenario SKIPPED\n";exit;}
require __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../app/Database.php';
require_once __DIR__.'/../app/Repository.php';
require __DIR__.'/helpers/FiscalPipelineTestSupport.php';

use MiniErp\Fiscal\FiscalTaxRule;
use MiniErp\Fiscal\TaxRuleResolver;
use MiniErp\Repositories\CompanyFiscalSettingsRepository;
use MiniErp\Repositories\FiscalConfigurationRepository;
use MiniErp\Repositories\FiscalOperationRepository;
use MiniErp\Repositories\MariaDbTaxRuleRepository;
use MiniErp\Services\CreateInternalFiscalDocumentService;
use MiniErp\Services\FiscalDocumentPreflightService;

[$server,$pdo,$database]=fiscal_test_db();
$tenant=990037;$actor=990037;$establishment=0;
try{
 if(session_status()===PHP_SESSION_NONE)session_start();
 $_SESSION=['erp_user_id'=>$actor,'erp_tenant_id'=>$tenant,'user_id'=>$actor,'tenant_id'=>$tenant,'current_company_id'=>1];
 $pdo->prepare("INSERT INTO establishments(tenant_id,tax_id,legal_name,trade_name,state_registration,tax_regime_code,street,number,district,city_ibge_code,city_name,state,postal_code,country_code,country_name,status,fiscal_readiness) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$tenant,'12345678000195','EMITENTE FISCAL TEST_ONLY HOMOLOGACAO','TEST_ONLY','123456789','1','Rua Teste','100','Centro','4106902','Curitiba','PR','80000000','1058','BRASIL','ativo','READY']);
 $establishment=(int)$pdo->lastInsertId();
 $repo=new Repository($pdo,false);
 $repo->saveCliente(['nome'=>'CLIENTE FISCAL TEST_ONLY HOMOLOGACAO','email'=>'fiscal-037@example.invalid','telefone'=>'41999999999','cpf_cnpj'=>'98765432000110','person_type'=>'PJ','state_registration_indicator'=>'9','logradouro'=>'Rua Teste Cliente','numero'=>'200','bairro'=>'Centro','municipio'=>'Curitiba','codigo_ibge'=>'4106902','estado'=>'PR','cep'=>'80010000','country_code'=>'1058','country_name'=>'BRASIL','pessoa_fisica'=>'nao','tipo_pessoa'=>['cliente'],'status'=>'ativo']);
 $customer=(int)$pdo->lastInsertId();fiscal_assert($customer>0,'customer not persisted by ERP repository');
 $repo->saveProduto(['nome'=>'PRODUTO FISCAL TEST_ONLY HOMOLOGACAO','codigo'=>'FISCAL-E2E-037','ncm'=>'84713012','merchandise_origin'=>'0','unidade'=>'UN','taxable_unit'=>'UN','conversion_factor'=>'1','gtin'=>'SEM GTIN','gtin_tributable'=>'SEM GTIN','cfop_padrao'=>'','categoria'=>'TEST_ONLY','preco'=>'100.00','estoque_atual'=>'0','status'=>'ativo']);
 $product=(int)$pdo->lastInsertId();fiscal_assert($product>0,'product not persisted by ERP repository');
 foreach(['1102','2102','5102','6102']as$cfop)$pdo->prepare("INSERT INTO cfops(codigo,descricao,natureza,aplicacao,status) VALUES(?,?,'TEST_ONLY','TEST_ONLY','ativo')")->execute([$cfop,'CFOP TEST_ONLY '.$cfop]);
 (new CompanyFiscalSettingsRepository($pdo,$tenant))->saveCfops($establishment,['ENTRY_INTERNAL'=>'1102','ENTRY_INTERSTATE'=>'2102','EXIT_INTERNAL'=>'5102','EXIT_INTERSTATE'=>'6102'],$actor);
 $taxRepo=new MariaDbTaxRuleRepository($pdo,$tenant);
 $ruleId=$taxRepo->addVersion(new FiscalTaxRule(null,$tenant,'TEST_ONLY_NCM_84713012_CRT1_PR',1,100,new DateTimeImmutable('2026-01-01'),null,'CENARIO CONTROLADO PROMPT 037','1','2026-08-24',['crt'=>'1','origin_state'=>'PR','destination_state'=>'PR','ncm'=>'84713012','product_origin'=>'0','direction'=>'EXIT','model'=>'55','cfop_hint'=>'5102'],'5102',['csosn'=>'102','orig'=>'0'],[],['cst'=>'49','base'=>'0.00','rate'=>'0.00','amount'=>'0.00'],['cst'=>'49','base'=>'0.00','rate'=>'0.00','amount'=>'0.00'],[],[],'ACTIVE','TEST_ONLY'));
 (new FiscalConfigurationRepository($pdo,$tenant))->saveSeries($establishment,'55',37,1,2,true,$actor,'Serie exclusiva do cenario TEST_ONLY Prompt 037');
 $operations=new FiscalOperationRepository($pdo,$tenant);
 fiscal_assert($operations->contextualCfop($establishment,'EXIT','PR','PR')==='5102','internal CFOP');
 fiscal_assert($operations->contextualCfop($establishment,'EXIT','PR','SP')==='6102','interstate CFOP');
 $order=$operations->createOrder(['tipo'=>'saida','establishment_id'=>$establishment,'cliente_id'=>$customer,'codigo_interno'=>'TEST_ONLY-E2E-037','data_venda'=>'2026-08-24','operation_nature'=>'VENDA TEST_ONLY','fiscal_model'=>'55','purpose'=>'NORMAL','final_consumer'=>1,'presence_indicator'=>'1','condicao_pagamento'=>'avista','payment_method'=>'01','observacoes'=>'TEST_ONLY PROMPT 037'],[['produto_id'=>$product,'quantidade'=>'1','preco_unitario'=>'100.00']],$actor);
 $token=hash('sha256','PROMPT-037-CONTROLLED-E2E');
 $creator=new CreateInternalFiscalDocumentService($operations,new TaxRuleResolver($taxRepo));
 $created=$creator->create($order,$token,$actor);fiscal_assert($created['status']==='FISCAL_READY','document snapshot must be ready: '.json_encode($created['pending']??[]));
 $same=$creator->create($order,$token,$actor);fiscal_assert((int)$same['id']===(int)$created['id'],'idempotent snapshot');
 $document=$operations->document((int)$created['id']);$preflightDocument=$document;$preflightDocument['totals']['model']='55';
 $inspection=(new FiscalDocumentPreflightService())->inspect($preflightDocument);fiscal_assert($inspection['ready'],'preflight: '.json_encode($inspection['errors']));
 $tax=json_decode((string)$document['items'][0]['tax_resolution_json'],true,512,JSON_THROW_ON_ERROR);fiscal_assert($tax['cfop']==='5102'&&$tax['ruleId']===$ruleId,'TaxEngine resolution');
 $snapshot=json_decode((string)$document['items'][0]['product_snapshot_json'],true,512,JSON_THROW_ON_ERROR);fiscal_assert($snapshot['nome']==='PRODUTO FISCAL TEST_ONLY HOMOLOGACAO','original snapshot');
 $productData=$repo->findProduto($product);$productData['nome']='PRODUTO FISCAL TEST_ONLY ALTERADO DEPOIS DO SNAPSHOT';$repo->saveProduto($productData);
 $reloaded=$operations->document((int)$created['id']);$frozen=json_decode((string)$reloaded['items'][0]['product_snapshot_json'],true,512,JSON_THROW_ON_ERROR);fiscal_assert($frozen['nome']==='PRODUTO FISCAL TEST_ONLY HOMOLOGACAO','snapshot immutability');
 $certificateBlocked=false;try{fiscal_pipeline_service($pdo,$tenant)->prepare($tenant,(int)$created['id'],$actor);}catch(Throwable$e){$certificateBlocked=str_contains(strtoupper($e->getMessage()),'CERTIFICATE')||str_contains(strtoupper($e->getMessage()),'CERTIFICADO');}
 fiscal_assert($certificateBlocked,'pipeline must stop only at certificate');
 fiscal_assert((int)$pdo->query("SELECT COUNT(*) FROM fiscal_number_reservations WHERE tenant_id={$tenant}")->fetchColumn()===0,'certificate failure must precede reservation');
 echo json_encode(['tenant'=>$tenant,'database'=>$database,'establishment_id'=>$establishment,'crt'=>'1','issuer_uf'=>'PR','customer_id'=>$customer,'customer_document'=>'98765432000110','customer_uf'=>'PR','customer_ibge'=>'4106902','product_id'=>$product,'ncm'=>'84713012','unit'=>'UN','value'=>'100.00','tax_rule_id'=>$ruleId,'cfop'=>'5102','interstate_cfop'=>'6102','order_id'=>$order,'total'=>'100.00','document_id'=>(int)$created['id'],'document_status'=>$created['status'],'series'=>37,'environment'=>2,'snapshot'=>'PASS','preflight'=>'PASS','tax_engine'=>'PASS','signature'=>'BLOCKED_CERTIFICATE','reservation_count'=>0,'sefaz_calls'=>0],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),PHP_EOL;
}finally{fiscal_drop_database($server,$database);}
