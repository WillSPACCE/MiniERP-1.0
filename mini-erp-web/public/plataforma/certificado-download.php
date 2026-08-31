<?php
declare(strict_types=1);

use MiniErp\Fiscal\PrivateCertificateStorage;
use MiniErp\Infrastructure\TenantConnectionResolver;
use MiniErp\Repositories\{FiscalConfigurationRepository,TenantEstablishmentRepository};

require_once __DIR__.'/_tenant_users.php';
require_once __DIR__.'/../../vendor/autoload.php';
[$identity,,$service,$context]=requireTenantUserContext();
$token=(string)($_GET['token']??'');
if(!hash_equals((string)($_SESSION['platform_user_csrf']??''),$token)){http_response_code(403);exit('Download nao autorizado.');}

try{
 $tenantId=$context->getSelectedTenantId();$pdo=(new TenantConnectionResolver(__DIR__.'/../../config.php'))->resolveAdministrative($context);
 $est=(new TenantEstablishmentRepository($pdo))->findPrimaryForTenant($tenantId);if(!$est)throw new RuntimeException('Estabelecimento nao encontrado.');
 $certificate=(new FiscalConfigurationRepository($pdo,$tenantId))->latestCertificate((int)$est['id']);if(!$certificate)throw new RuntimeException('Certificado nao encontrado.');
 $content=(new PrivateCertificateStorage(__DIR__.'/../../storage/fiscal/certificates'))->read((string)$certificate['storage_reference']);
 $name=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)($certificate['file_name']?:'certificado.pfx')))?:'certificado.pfx';
 header('Content-Type: application/x-pkcs12');header('Content-Length: '.strlen($content));header('Content-Disposition: attachment; filename="'.$name.'"');
 header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');echo $content;
}catch(Throwable $e){error_log('PLATFORM_CERTIFICATE_DOWNLOAD_FAILED tenant='.$context->getSelectedTenantId().' actor='.$identity->getUserId().' type='.get_class($e));http_response_code(404);echo 'Certificado indisponivel.';}
