<?php
declare(strict_types=1);
use MiniErp\Platform\NcmCatalogSyncService;
use MiniErp\Repositories\PlatformAdminRepository;
require_once __DIR__.'/_context.php';
[$main,,,$identity]=requireAuthorizedPlatformContext();require_once __DIR__.'/../../vendor/autoload.php';
try{if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')throw new RuntimeException('Método inválido.');if(!in_array($identity->getRole(),['SUPER_ADMIN','GLOBAL_TECH'],true))throw new RuntimeException('Perfil sem permissão.');if(!hash_equals((string)($_SESSION['platform_operations_csrf']??''),(string)($_POST['csrf_token']??'')))throw new RuntimeException('Sessão expirada.');$service=new NcmCatalogSyncService();$result=$service->synchronize($main,$service->download());(new PlatformAdminRepository($main))->audit($identity->getUserId(),'NCM_CATALOG_SYNCHRONIZED','platform_fiscal_catalog','NCM',$_SERVER['REMOTE_ADDR']??null,$result+['source'=>NcmCatalogSyncService::SOURCE_URL]);$_SESSION['platform_ncm_sync_message']=$result['unchanged']?'A tabela NCM oficial já estava atualizada.':'Tabela NCM atualizada: '.$result['count'].' códigos vigentes.';}catch(Throwable$e){$_SESSION['platform_ncm_sync_error']=$e->getMessage();}
header('Location: /plataforma/operacoes-multitenant.php#catalogos-fiscais');exit;
