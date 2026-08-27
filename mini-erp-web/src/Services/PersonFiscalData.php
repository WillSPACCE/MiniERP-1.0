<?php
declare(strict_types=1);
namespace MiniErp\Services;
use InvalidArgumentException;
require_once __DIR__.'/CnpjLookupService.php';
require_once __DIR__.'/CnpjLookupException.php';
require_once __DIR__.'/BrazilianDocumentValidator.php';
final readonly class PersonFiscalData
{
 public const TYPES=['PF','PJ','FOREIGN']; public const IE_INDICATORS=['1','2','9']; private array $data;
 public function __construct(array $input)
 {
  $s=static fn(string $k,int $m=255):string=>mb_substr(trim((string)($input[$k]??'')),0,$m);
  $type=strtoupper($s('person_type',7)?:($s('document_type',7)?:((strtolower($s('pessoa_fisica'))==='nao')?'PJ':'PF')));
  if(!in_array($type,self::TYPES,true))throw new InvalidArgumentException('Tipo fiscal da pessoa inválido.');
  $docCandidate=$s('document',20)?:$s('cpf_cnpj',20);$doc=strtoupper(preg_replace('/[^A-Za-z0-9]/','',$docCandidate)??'');$foreign=$s('foreign_id',50);
  if($type==='PF'&&!BrazilianDocumentValidator::cpf($doc))throw new InvalidArgumentException('Informe um CPF válido com 11 dígitos.');
  if($type==='PJ'&&!BrazilianDocumentValidator::cnpj($doc))throw new InvalidArgumentException('Informe um CNPJ válido com 14 caracteres, inclusive alfanumérico.');
  if($type==='FOREIGN'&&$foreign==='')throw new InvalidArgumentException('Pessoa estrangeira exige identificação estrangeira.');
  $ie=$s('state_registration_indicator',1)?:'9';if(!in_array($ie,self::IE_INDICATORS,true))throw new InvalidArgumentException('Indicador de IE deve ser 1, 2 ou 9.');if($ie==='1'&&$s('inscricao_estadual',50)==='')throw new InvalidArgumentException('Contribuinte ICMS exige Inscrição Estadual.');
  $ibge=preg_replace('/\D/','',$s('codigo_ibge',7))??'';if($type!=='FOREIGN'&&$ibge!==''&&strlen($ibge)!==7)throw new InvalidArgumentException('Código IBGE deve possuir 7 dígitos.');
  $roles=(array)($input['tipo_pessoa']??['cliente']);$roles=array_map('strtolower',array_map('trim',$roles));
  $this->data=['person_type'=>$type,'cpf_cnpj'=>$doc,'foreign_id'=>$foreign,'state_registration_indicator'=>$ie,'rg'=>$s('rg',30),'inscricao_estadual'=>$s('inscricao_estadual',50),'suprama'=>$s('suprama',50),'im'=>$s('im',50),'codigo_ibge'=>$ibge,'country_code'=>preg_replace('/\D/','',$s('country_code',4)?:'1058')?:'1058','country_name'=>strtoupper($s('country_name',60)?:'BRASIL'),'observations'=>$s('observations',2000),'role_customer'=>in_array('cliente',$roles,true)?1:0,'role_supplier'=>in_array('fornecedor',$roles,true)?1:0,'role_seller'=>in_array('vendedor',$roles,true)?1:0,'role_carrier'=>in_array('transportadora',$roles,true)?1:0,'role_employee'=>in_array('funcionario',$roles,true)?1:0];
  if(($this->data['role_customer']+$this->data['role_supplier']+$this->data['role_seller']+$this->data['role_carrier']+$this->data['role_employee'])===0)throw new InvalidArgumentException('Selecione ao menos um papel para a pessoa.');
 }
 public function toArray():array{return $this->data;}
}
