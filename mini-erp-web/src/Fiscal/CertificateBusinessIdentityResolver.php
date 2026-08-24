<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use NFePHP\Common\Certificate;
final class CertificateBusinessIdentityResolver {
 public const ICP_BRASIL_PJ_OID='2.16.76.1.3.3';
 public function resolve(Certificate$certificate,array$parsed,string$expectedTaxId):array{$expected=$this->normalize($expectedTaxId);$taxId=$this->normalize((string)($certificate->getCnpj()??''));$source='ICP_BRASIL_SAN_OTHERNAME_OID_'.self::ICP_BRASIL_PJ_OID;$confidence='HIGH';if($taxId===''){$serial=$parsed['subject']['serialNumber']??'';if(is_array($serial))$serial=count($serial)===1?reset($serial):'';$candidate=$this->normalize((string)$serial);if((bool)preg_match('/^(?:\d{14}|[A-Z0-9]{12}\d{2})$/',$candidate)){$taxId=$candidate;$source='X509_SUBJECT_SERIALNUMBER_FALLBACK';$confidence='MEDIUM';}}$status=$taxId===''?'NOT_FOUND':(($expected!==''&&hash_equals($expected,$taxId))?'MATCH':'MISMATCH');return['certificate_tax_id'=>$taxId!==''?$taxId:null,'source'=>$source,'confidence'=>$confidence,'status'=>$status,'expected_tax_id'=>$expected];}
 public function normalize(string$taxId):string{return strtoupper((string)preg_replace('/[^A-Z0-9]/i','',$taxId));}
}
