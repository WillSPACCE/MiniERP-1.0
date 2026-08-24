<?php
declare(strict_types=1);
namespace MiniErp\Repositories;
use PDO;
final class PlatformFiscalDefaultsRepository{
 public function __construct(private PDO$pdo){}
 public function get():array{$row=$this->pdo->query('SELECT * FROM platform_fiscal_defaults WHERE id=1')->fetch(PDO::FETCH_ASSOC);return$row?:['entry_internal_cfop'=>'1102','entry_interstate_cfop'=>'2102','exit_internal_cfop'=>'5102','exit_interstate_cfop'=>'6102'];}
 public function save(array$d,int$actor):void{$values=[];foreach(['entry_internal_cfop'=>'1','entry_interstate_cfop'=>'2','exit_internal_cfop'=>'5','exit_interstate_cfop'=>'6']as$key=>$prefix){$value=preg_replace('/\D/','',(string)($d[$key]??''));if(!preg_match('/^'.$prefix.'\d{3}$/',$value))throw new \InvalidArgumentException("CFOP global inválido: {$key}.");$values[$key]=$value;}$s=$this->pdo->prepare('INSERT INTO platform_fiscal_defaults(id,entry_internal_cfop,entry_interstate_cfop,exit_internal_cfop,exit_interstate_cfop,updated_by) VALUES(1,?,?,?,?,?) ON DUPLICATE KEY UPDATE entry_internal_cfop=VALUES(entry_internal_cfop),entry_interstate_cfop=VALUES(entry_interstate_cfop),exit_internal_cfop=VALUES(exit_internal_cfop),exit_interstate_cfop=VALUES(exit_interstate_cfop),updated_by=VALUES(updated_by)');$s->execute([...array_values($values),$actor]);}
}
