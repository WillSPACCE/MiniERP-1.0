<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use MiniErp\Services\NfeEntryXmlImportService;

function nfeImportAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);}

$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$pdo->exec("CREATE TABLE establishments(id INTEGER PRIMARY KEY,tax_id TEXT,is_primary INTEGER,status TEXT);CREATE TABLE fornecedores(id INTEGER PRIMARY KEY,cpf_cnpj TEXT);CREATE TABLE clientes(id INTEGER PRIMARY KEY,cpf_cnpj TEXT);CREATE TABLE produtos(id INTEGER PRIMARY KEY,codigo TEXT,gtin TEXT);CREATE TABLE cfops(id INTEGER PRIMARY KEY,codigo TEXT,descricao TEXT,status TEXT);INSERT INTO establishments VALUES(1,'12345678000199',1,'ativo');INSERT INTO cfops VALUES(7,'2102','Compra interestadual','ativo');");
$xml='<?xml version="1.0" encoding="UTF-8"?><nfeProc xmlns="http://www.portalfiscal.inf.br/nfe"><NFe><infNFe Id="NFe41260911111111000111550010000001231000001234"><ide><mod>55</mod><serie>1</serie><nNF>123</nNF><dhEmi>2026-09-02T10:00:00-03:00</dhEmi></ide><emit><CNPJ>11111111000111</CNPJ><xNome>FORNECEDOR TESTE</xNome><IE>123</IE><enderEmit><xLgr>Rua A</xLgr><nro>10</nro><xBairro>Centro</xBairro><cMun>4106902</cMun><xMun>Curitiba</xMun><UF>PR</UF><CEP>80000000</CEP></enderEmit></emit><dest><CNPJ>12345678000199</CNPJ><xNome>EMPRESA DESTINO</xNome></dest><det nItem="1"><prod><cProd>P1</cProd><cEAN>SEM GTIN</cEAN><xProd>Produto teste</xProd><NCM>12345678</NCM><CFOP>6102</CFOP><uCom>UN</uCom><qCom>2.0000</qCom><vUnCom>10.0000</vUnCom><vProd>20.00</vProd><cEANTrib>SEM GTIN</cEANTrib><uTrib>UN</uTrib><qTrib>2.0000</qTrib></prod><imposto><ICMS><ICMS00><orig>0</orig><CST>00</CST><pICMS>12.00</pICMS></ICMS00></ICMS><PIS><PISAliq><CST>01</CST><pPIS>1.65</pPIS></PISAliq></PIS><COFINS><COFINSAliq><CST>01</CST><pCOFINS>7.60</pCOFINS></COFINSAliq></COFINS></imposto></det><total><ICMSTot><vProd>20.00</vProd><vFrete>0.00</vFrete><vSeg>0.00</vSeg><vDesc>0.00</vDesc><vOutro>0.00</vOutro><vNF>20.00</vNF></ICMSTot></total><transp><modFrete>9</modFrete></transp></infNFe></NFe></nfeProc>';
$result=(new NfeEntryXmlImportService($pdo))->analyze($xml,'teste.xml');
nfeImportAssert($result['supplier']['name']==='FORNECEDOR TESTE'&&$result['summary']['supplier_action']==='create','supplier parse');
nfeImportAssert($result['invoice']['source_cfop']==='6102'&&$result['invoice']['entry_cfop']==='2102'&&(int)$result['invoice']['cfop_id']===7,'entry CFOP mapping');
nfeImportAssert(count($result['items'])===1&&$result['items'][0]['taxes']['icms_cst']==='00'&&$result['items'][0]['taxes']['pis_rate']==='1.650000','items and taxes parse');
nfeImportAssert($result['invoice']['total']==='20.00'&&$result['access_key']==='41260911111111000111550010000001231000001234','invoice totals and key');
$mismatch=(new NfeEntryXmlImportService($pdo))->analyze(str_replace('<CNPJ>12345678000199</CNPJ><xNome>EMPRESA DESTINO</xNome>','<CNPJ>99999999000199</CNPJ><xNome>OUTRO DESTINO</xNome>',$xml),'divergente.xml');
nfeImportAssert($mismatch['invoice']['recipient_match']===false&&$mismatch['invoice']['recipient_document']==='99999999000199','recipient mismatch available for controlled test override');
echo "NfeEntryXmlImportServiceTest OK\n";
