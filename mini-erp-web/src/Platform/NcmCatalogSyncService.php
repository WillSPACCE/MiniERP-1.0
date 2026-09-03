<?php
declare(strict_types=1);
namespace MiniErp\Platform;
use DateTimeImmutable;
use PDO;
use RuntimeException;
use Throwable;

final class NcmCatalogSyncService
{
    public const SOURCE_URL='https://portalunico.siscomex.gov.br/classif/api/publico/nomenclatura/download/json';
    public function download():string
    {
        $context=stream_context_create(['http'=>['timeout'=>45,'user_agent'=>'MiniERP-NCM-Sync/1.0','follow_location'=>1],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);$json=@file_get_contents(self::SOURCE_URL,false,$context,0,25165824);if($json===false||strlen($json)<100)throw new RuntimeException('Não foi possível baixar a tabela NCM oficial do Siscomex.');return$json;
    }
    public function synchronize(PDO$pdo,string$json):array
    {
        $this->ensureSchema($pdo);
        $payload=json_decode($json,true,512,JSON_THROW_ON_ERROR);$items=$payload['Nomenclaturas']??null;if(!is_array($items))throw new RuntimeException('O arquivo oficial de NCM possui estrutura inesperada.');$checksum=hash('sha256',$json);$existing=$pdo->prepare("SELECT id,item_count,source_version FROM platform_fiscal_catalog_versions WHERE catalog_type='NCM' AND checksum_sha256=? LIMIT 1");$existing->execute([$checksum]);if($row=$existing->fetch(PDO::FETCH_ASSOC))return['version_id'=>(int)$row['id'],'count'=>(int)$row['item_count'],'version'=>$row['source_version'],'unchanged'=>true];
        $version=trim((string)($payload['Ato']??$payload['Data_Ultima_Atualizacao_NCM']??date('Y-m-d')));$sourceDate=$this->sourceDate((string)($payload['Data_Ultima_Atualizacao_NCM']??''));$rows=[];foreach($items as$item){$code=preg_replace('/\D/','',(string)($item['Codigo']??''));if(strlen((string)$code)!==8)continue;$description=trim((string)($item['Descricao']??''));if($description==='')continue;$rows[$code]=['description'=>$description,'valid_from'=>$this->date((string)($item['Data_Inicio']??'')),'valid_to'=>$this->date((string)($item['Data_Fim']??''),true),'metadata'=>['initial_act_type'=>$item['Tipo_Ato_Ini']??null,'initial_act_number'=>$item['Numero_Ato_Ini']??null,'initial_act_year'=>$item['Ano_Ato_Ini']??null]];}
        if(count($rows)<1000)throw new RuntimeException('A fonte oficial retornou poucos códigos NCM; sincronização cancelada.');$pdo->beginTransaction();try{$pdo->exec("UPDATE platform_fiscal_catalog_versions SET active=0 WHERE catalog_type='NCM'");$insertVersion=$pdo->prepare("INSERT INTO platform_fiscal_catalog_versions(catalog_type,source_name,source_version,source_date,checksum_sha256,active,item_count) VALUES('NCM','Portal Único Siscomex',?,?,?,?,?)");$insertVersion->execute([$version,$sourceDate,$checksum,1,count($rows)]);$versionId=(int)$pdo->lastInsertId();$insert=$pdo->prepare('INSERT INTO platform_fiscal_catalog_entries(version_id,code,description,valid_from,valid_to,metadata_json) VALUES(?,?,?,?,?,?)');foreach($rows as$code=>$row)$insert->execute([$versionId,$code,$row['description'],$row['valid_from'],$row['valid_to'],json_encode($row['metadata'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);$pdo->commit();return['version_id'=>$versionId,'count'=>count($rows),'version'=>$version,'source_date'=>$sourceDate,'unchanged'=>false];}catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    }
    private function sourceDate(string$value):string{if(preg_match('/(\d{2}\/\d{2}\/\d{4})/',$value,$m))return$this->date($m[1])??date('Y-m-d');return date('Y-m-d');}
    private function date(string$value,bool$openEnded=false):?string{$date=DateTimeImmutable::createFromFormat('!d/m/Y',trim($value));if(!$date)return null;$formatted=$date->format('Y-m-d');return$openEnded&&$formatted==='9999-12-31'?null:$formatted;}
    private function ensureSchema(PDO$pdo):void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS platform_fiscal_catalog_versions(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,catalog_type VARCHAR(20) NOT NULL,source_name VARCHAR(120) NOT NULL,source_version VARCHAR(120) NOT NULL,source_date DATE NOT NULL,checksum_sha256 CHAR(64) NOT NULL,active TINYINT(1) NOT NULL DEFAULT 1,item_count INT UNSIGNED NOT NULL DEFAULT 0,created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY uq_platform_fiscal_catalog_checksum(catalog_type,checksum_sha256),KEY ix_platform_fiscal_catalog_active(catalog_type,active,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS platform_fiscal_catalog_entries(version_id BIGINT UNSIGNED NOT NULL,code VARCHAR(20) NOT NULL,description VARCHAR(1000) NOT NULL,valid_from DATE NULL,valid_to DATE NULL,metadata_json JSON NOT NULL,PRIMARY KEY(version_id,code),KEY ix_platform_fiscal_catalog_code(code),FULLTEXT KEY ft_platform_fiscal_catalog_description(description),CONSTRAINT fk_platform_fiscal_catalog_version FOREIGN KEY(version_id) REFERENCES platform_fiscal_catalog_versions(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
}
