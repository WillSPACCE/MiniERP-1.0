<?php
declare(strict_types=1);
namespace MiniErp\Services;

use PDO;
use Repository;
use RuntimeException;
use Throwable;

final class PeoplePersistenceService
{
    public function __construct(private PDO $pdo,private Repository $repository,private int $tenantId){}

    /** @return array{person_id:int,created:bool,affected_rows:int,record:array,updated_sections:list<string>} */
    public function saveClient(array $payload):array
    {
        $requestedId=(int)($payload['id']??0);$created=$requestedId<1;
        if(!$created&&!$this->repository->findCliente($requestedId))throw new RuntimeException('RECORD_NOT_FOUND');
        $ownsTransaction=!$this->pdo->inTransaction();if($ownsTransaction)$this->pdo->beginTransaction();
        try{
            $sqlAffected=$this->repository->saveCliente($payload);
            $personId=$created?(int)$this->pdo->lastInsertId():$requestedId;
            if($personId<1)throw new RuntimeException('PERSON_ID_NOT_RETURNED');
            $record=$this->repository->findCliente($personId);if(!$record)throw new RuntimeException('PERSON_READ_BACK_FAILED');
            if($ownsTransaction)$this->pdo->commit();
            return['person_id'=>$personId,'created'=>$created,'affected_rows'=>$sqlAffected,'record'=>$record,'updated_sections'=>$this->sections($payload)];
        }catch(Throwable $error){if($ownsTransaction&&$this->pdo->inTransaction())$this->pdo->rollBack();throw$error;}
    }

    /** @return list<string> */
    private function sections(array $payload):array
    {
        $map=['general'=>['person_type','cpf_cnpj','nome','nome_fantasia','status'],'contact'=>['nome_contato','email','fone_principal','fone_2','fone_3'],'address'=>['cep','logradouro','numero','complemento','bairro','municipio','uf','codigo_ibge'],'fiscal'=>['inscricao_estadual','im','state_registration_indicator','suprama'],'financial'=>['limite_credito','forma_pagamento'],'roles'=>['tipo_pessoa'],'transport'=>['placa','placa_uf','antt','frete']];$sections=[];
        foreach($map as$section=>$fields)foreach($fields as$field)if(array_key_exists($field,$payload)){$sections[]=$section;break;}
        return$sections;
    }
}
