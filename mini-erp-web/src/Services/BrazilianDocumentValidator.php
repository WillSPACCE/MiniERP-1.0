<?php
declare(strict_types=1);
namespace MiniErp\Services;

final class BrazilianDocumentValidator
{
    public static function normalize(string $value):string{return strtoupper(preg_replace('/[^A-Z0-9]/','',trim($value))??'');}
    public static function normalizeCpf(string $value):string{return preg_replace('/\D/','',$value)??'';}
    public static function cpf(string $value):bool
    {
        $cpf=self::normalizeCpf($value);if(strlen($cpf)!==11||preg_match('/^(\d)\1{10}$/',$cpf))return false;
        for($digit=9;$digit<11;$digit++){$sum=0;for($i=0;$i<$digit;$i++)$sum+=(int)$cpf[$i]*(($digit+1)-$i);$check=(10*$sum)%11;if($check===10)$check=0;if((int)$cpf[$digit]!==$check)return false;}return true;
    }
    public static function cnpj(string $value):bool{return CnpjLookupService::isValid(self::normalize($value));}
    public static function forType(string $value,string $type):bool{return strtoupper($type)==='PF'?self::cpf($value):(strtoupper($type)==='PJ'&&self::cnpj($value));}
}
