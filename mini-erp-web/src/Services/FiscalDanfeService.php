<?php
declare(strict_types=1);
namespace MiniErp\Services;
use MiniErp\Fiscal\{FiscalArtifactStorage,FiscalDanfeLogoStorage};
use MiniErp\Repositories\FiscalArtifactRepository;
use NFePHP\DA\NFe\Danfe;

final class FiscalDanfeService{
 public function __construct(private FiscalArtifactRepository$artifacts,private FiscalArtifactStorage$storage,private string$cacheRoot,private string$spedDaVersion='1.1.6',private ?FiscalDanfeLogoStorage$logoStorage=null){}
 public function render(int$artifactId,?string$logoReference=null):array{
  $artifact=$this->artifacts->findById($artifactId);if(!$artifact)throw new FiscalDanfeException('DANFE_ARTIFACT_NOT_FOUND','XML fiscal não encontrado.');
  if((string)$artifact['model']!=='55')throw new FiscalDanfeException('DANFE_UNSUPPORTED_MODEL','DANFE A4 disponível apenas para NF-e modelo 55.');
  if(!in_array((string)$artifact['status'],['XSD_VALID_OFFLINE','SIGNED','READY','AUTHORIZED'],true))throw new FiscalDanfeException('DANFE_ARTIFACT_NOT_READY','XML fiscal ainda não está pronto para DANFE.');
  try{$this->storage->assertIntegrity((string)$artifact['storage_reference'],(string)$artifact['sha256']);$xml=$this->storage->read((string)$artifact['storage_reference']);}catch(\Throwable$e){$code=str_contains($e->getMessage(),'INTEGRITY')?'DANFE_ARTIFACT_INTEGRITY_FAILED':'DANFE_ARTIFACT_FILE_MISSING';throw new FiscalDanfeException($code,'O XML fiscal está ausente ou teve sua integridade comprometida.',$e);}
  libxml_use_internal_errors(true);$dom=new \DOMDocument();if(!$dom->loadXML($xml,LIBXML_NONET)||!$dom->getElementsByTagName('infNFe')->length)throw new FiscalDanfeException('DANFE_XML_INVALID','O XML fiscal está inválido e o DANFE não pode ser gerado.');
  if($dom->getElementsByTagName('mod')->item(0)?->textContent!=='55')throw new FiscalDanfeException('DANFE_UNSUPPORTED_MODEL','Artifact não contém NF-e modelo 55.');
  $logoPath=null;$logoWarning=null;if($logoReference!==null&&$logoReference!==''){try{if(!$this->logoStorage)throw new \RuntimeException('DANFE_LOGO_UNAVAILABLE');$logoPath=$this->logoStorage->resolve((int)$artifact['tenant_id'],$logoReference);}catch(\RuntimeException$e){if(in_array($e->getMessage(),['DANFE_LOGO_REFERENCE_BLOCKED','DANFE_LOGO_CROSS_TENANT_BLOCKED'],true))throw new FiscalDanfeException($e->getMessage(),'Referência de logo bloqueada.',$e);$logoWarning='DANFE_LOGO_UNAVAILABLE';}}
  $logoChecksum=$logoPath?hash_file('sha256',$logoPath):'none';$key=hash('sha256',$artifactId.'|'.$artifact['sha256'].'|'.$this->spedDaVersion.'|'.$logoChecksum);$dir=rtrim($this->cacheRoot,'/\\').DIRECTORY_SEPARATOR.'tenant-'.$artifact['tenant_id'].DIRECTORY_SEPARATOR.'document-'.$artifact['fiscal_document_id'];$path=$dir.DIRECTORY_SEPARATOR.$key.'.pdf';$hit=is_file($path);
  if(!$hit){try{$danfe=new Danfe($xml);$danfe->printParameters('P','A4',2,2);if($logoPath)$danfe->logoParameters($logoPath,'C');$pdf=$danfe->render();}catch(\Throwable$e){throw new FiscalDanfeException('DANFE_RENDER_FAILED','Não foi possível renderizar o DANFE.',$e);}if(!str_starts_with($pdf,'%PDF'))throw new FiscalDanfeException('DANFE_RENDER_FAILED','Renderizador não retornou um PDF válido.');if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new FiscalDanfeException('DANFE_CACHE_FAILED','Não foi possível criar o cache privado.');if(file_put_contents($path,$pdf,LOCK_EX)===false)throw new FiscalDanfeException('DANFE_CACHE_FAILED','Não foi possível gravar o cache privado.');@chmod($path,0600);}else{$pdf=(string)file_get_contents($path);}
  return['bytes'=>$pdf,'filename'=>'DANFE-'.$artifact['access_key'].(((string)$artifact['status'])==='AUTHORIZED'?'':'-HOMOLOGACAO-NAO-TRANSMITIDA').'.pdf','cache'=>$hit?'HIT':'MISS','artifact'=>$artifact,'sha256'=>hash('sha256',$pdf),'offline'=>!str_contains($xml,'<protNFe>'),'logo_applied'=>$logoPath!==null,'logo_warning'=>$logoWarning,'logo_checksum'=>$logoChecksum,'page_format'=>'A4'];
 }
}
