<?php
declare(strict_types=1);
namespace MiniErp\Services;
use RuntimeException;
final class SefazStatusResponseParser{
 public function parse(string$xml):array{$dom=new \DOMDocument();libxml_use_internal_errors(true);if(trim($xml)===''||!$dom->loadXML($xml,LIBXML_NONET))throw new RuntimeException('SEFAZ_INVALID_RESPONSE');$xp=new \DOMXPath($dom);$value=static fn(string$name):?string=>($n=$xp->query('//*[local-name()="'.$name.'"]')->item(0))?trim($n->textContent):null;$cStat=$value('cStat');$xMotivo=$value('xMotivo');if($cStat===null||$xMotivo===null)throw new RuntimeException('SEFAZ_INVALID_RESPONSE');return['cStat'=>$cStat,'xMotivo'=>$xMotivo,'dhRecbto'=>$value('dhRecbto'),'tpAmb'=>$value('tpAmb')];}
}
