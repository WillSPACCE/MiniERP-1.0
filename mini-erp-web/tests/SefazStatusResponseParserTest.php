<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';$p=new MiniErp\Services\SefazStatusResponseParser();$r=$p->parse('<?xml version="1.0"?><retConsStatServ><tpAmb>2</tpAmb><cStat>107</cStat><xMotivo>Servico em Operacao</xMotivo><dhRecbto>2026-08-24T12:00:00-03:00</dhRecbto></retConsStatServ>');if($r['cStat']!=='107'||$r['tpAmb']!=='2')throw new RuntimeException('parse');try{$p->parse('<x/>');throw new RuntimeException('invalid accepted');}catch(RuntimeException$e){if($e->getMessage()!=='SEFAZ_INVALID_RESPONSE')throw$e;}echo"SefazStatusResponseParserTest OK\n";
