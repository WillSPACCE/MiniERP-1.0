<?php
declare(strict_types=1);
if(getenv('RUN_CERTIFICATE_CANDIDATE_TEST')!=='1'){echo "CertificateCandidatePersistence SKIPPED\n";exit;}
require __DIR__.'/../vendor/autoload.php';
use MiniErp\Fiscal\{A1CertificateInspector,FiscalXmlSigner,LocalEncryptedSecretStorage,PrivateCertificateStorage};
use MiniErp\Repositories\FiscalConfigurationRepository;
use MiniErp\Services\EstablishmentFiscalConfigurationService;

$path=(string)getenv('CERTIFICATE_CANDIDATE_PATH');$password=(string)getenv('CERTIFICATE_CANDIDATE_PASSWORD');
if(!is_file($path)||$password==='')throw new RuntimeException('Certificate fixture unavailable');
$cfg=require __DIR__.'/../config.php';$db=$cfg['db'];$pdo=new PDO(sprintf('mysql:host=%s;port=%s;dbname=mini_erp_tenant_14;charset=utf8mb4',$db['host'],$db['port']),$db['username'],$db['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$tenant=980000+random_int(1,999);$establishment=980000+random_int(1,999);$root=sys_get_temp_dir().'/minierp-candidate-'.bin2hex(random_bytes(5));$certs=new PrivateCertificateStorage($root.'/certs');$secrets=new LocalEncryptedSecretStorage($root.'/secrets',str_repeat('C',32));$repo=new FiscalConfigurationRepository($pdo,$tenant);
try{
 $service=new EstablishmentFiscalConfigurationService($repo,new A1CertificateInspector(),$certs,$secrets,$tenant);
 $result=$service->upload($establishment,basename($path),(string)file_get_contents($path),$password,'12558511000102',999);
 if(empty($result['stored'])||!empty($result['activated'])||empty($result['local_signature']))throw new RuntimeException('candidate state');
 $row=$repo->latestCertificate($establishment);if(!$row||(int)$row['active']!==0||$row['status']!=='INVALID_IDENTITY')throw new RuntimeException('candidate persistence');
 $unsigned='<?xml version="1.0" encoding="UTF-8"?><NFe xmlns="http://www.portalfiscal.inf.br/nfe"><infNFe Id="NFe41123456789012345678901234567890123456789012" versao="4.00"><ide><cUF>41</cUF></ide></infNFe></NFe>';
 $signed=(new FiscalXmlSigner())->signTestOnly($unsigned,(string)file_get_contents($path),$password);
 if($signed['status']!=='SIGNED_TEST_ONLY'||!(new FiscalXmlSigner())->verify($signed['xml']))throw new RuntimeException('local XML signature');
 echo 'CertificateCandidatePersistence OK status='.$row['status'].' signed_sha256='.$signed['signed_sha256']."\n";
}finally{
 foreach($repo->certificateHistory($establishment)as$row){try{$certs->delete($row['storage_reference']);}catch(Throwable){}try{$secrets->delete($row['secret_reference']);}catch(Throwable){}}
 $pdo->prepare('DELETE FROM fiscal_certificate_audit WHERE tenant_id=? AND establishment_id=?')->execute([$tenant,$establishment]);$pdo->prepare('DELETE FROM fiscal_certificates WHERE tenant_id=? AND establishment_id=?')->execute([$tenant,$establishment]);
 foreach([$root.'/certs/tenant-'.$tenant.'/establishment-'.$establishment,$root.'/certs/tenant-'.$tenant,$root.'/certs',$root.'/secrets/tenant-'.$tenant.'/establishment-'.$establishment,$root.'/secrets/tenant-'.$tenant,$root.'/secrets',$root]as$directory)@rmdir($directory);
}
