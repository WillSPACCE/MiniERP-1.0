<?php
declare(strict_types=1);
namespace MiniErp\Services;

final class FlashFormState
{
    private const SENSITIVE = ['senha','password','certificate_password','csc_token','secret','csrf_token','token','pfx','p12','fiscal_secret_key'];

    /** @param array<string,mixed> $session @param array<string,mixed> $input @param array<string,string> $errors */
    public static function store(array &$session, string $action, array $input, array $errors): void
    { $session['_form_state']=['action'=>$action,'old_input'=>self::filter($input),'errors'=>$errors]; }

    /** @param array<string,mixed> $session @return array{action:string,old_input:array<string,mixed>,errors:array<string,string>}|null */
    public static function consume(array &$session): ?array
    { $state=$session['_form_state']??null;unset($session['_form_state']);return is_array($state)?$state:null; }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private static function filter(array $input): array
    {
        $clean=[];
        foreach($input as $key=>$value){
            if(in_array(strtolower((string)$key),self::SENSITIVE,true))continue;
            $clean[$key]=is_array($value)?self::filter($value):(is_scalar($value)?(string)$value:'');
        }
        return $clean;
    }
}
