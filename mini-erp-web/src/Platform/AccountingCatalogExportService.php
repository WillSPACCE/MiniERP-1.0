<?php
declare(strict_types=1);
namespace MiniErp\Platform;

use PDO;
use RuntimeException;

final class AccountingCatalogExportService
{
    public const REPORTS=['normal','simples','simples_simplificado'];
    public const TEMPLATES=['produtos','clientes','fornecedores','cfops','impostos','icms_uf'];

    public function export(PDO $pdo,string $report):array
    {
        if(!in_array($report,self::REPORTS,true))throw new RuntimeException('Relatório contábil inválido.');
        $products=$pdo->query("SELECT * FROM produtos WHERE COALESCE(status,'ativo')='ativo' ORDER BY ncm,codigo")->fetchAll(PDO::FETCH_ASSOC);
        $rules=$pdo->query("SELECT * FROM tax_rule_versions WHERE status='ACTIVE' AND fixture_kind='PRODUCTION' ORDER BY priority DESC,rule_version DESC,id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $headers=$this->headers($report);$rows=[];
        foreach($products as$product){$exit=$this->rule($rules,$product,'EXIT','55');$consumer=$this->rule($rules,$product,'EXIT','65');$entry=$this->rule($rules,$product,'ENTRY','55');$rows[]=$this->row($report,$product,$exit,$consumer,$entry);}
        return['headers'=>$headers,'rows'=>$rows];
    }

    public function template(string $entity):array
    {
        if(!in_array($entity,self::TEMPLATES,true))throw new RuntimeException('Modelo de importação inválido.');
        $headers=match($entity){
            'produtos'=>['codigo','nome','ncm','cest','origem','unidade','unidade_tributavel','fator_conversao','gtin','gtin_tributavel','cfop_padrao','categoria','preco','custo','estoque','estoque_minimo','status','sit','csosn','pis_st','cofins_st','st_ipi','codigo_beneficio_fiscal'],
            'clientes','fornecedores'=>['tipo_pessoa','cpf_cnpj','nome','nome_fantasia','indicador_ie','inscricao_estadual','email','telefone','cep','logradouro','numero','complemento','bairro','municipio','codigo_ibge','uf','status'],
            'cfops'=>['codigo','descricao','natureza','aplicacao','status'],
            'icms_uf'=>['uf_destino','aliquota','aliquota_pf','aliquota_cf','aliquota_red','aliquota_dif','aliquota_bcsubst','aliquota_subst','aliquota_frete','aliquota_fcp','aliquota_interna','aliquota_interestadual'],
            'impostos'=>['codigo_regra','versao','prioridade','vigencia_inicio','vigencia_fim','direcao','modelo','ncm','origem','cfop','cst_icms_csosn','aliquota_icms','cst_pis','aliquota_pis','cst_cofins','aliquota_cofins','cst_ipi','aliquota_ipi','cst_ibs_cbs','classificacao_tributaria','status'],
        };
        return['headers'=>$headers,'rows'=>[]];
    }

    public function csv(array $table):string
    {
        $stream=fopen('php://temp','w+');if($stream===false)throw new RuntimeException('Não foi possível criar o CSV.');
        fwrite($stream,"\xEF\xBB\xBFsep=;\r\n");fputcsv($stream,$table['headers'],';', '"', '\\');
        foreach($table['rows'] as$row)fputcsv($stream,array_map([$this,'safeCell'],$row),';','"','\\');
        rewind($stream);$csv=stream_get_contents($stream);fclose($stream);return(string)$csv;
    }

    private function headers(string $report):array
    {
        return match($report){
            'normal'=>['CODIGO','DESCRICAO','CODIGO_NCM','CST','CODIGO_BENEFICIO_FISCAL','CST_CONSUMIDOR','CODIGO_BENEFICIO_FISCAL_CONSUMIDOR','CST_PIS_SAIDA','ALIQUOTA_PIS_SAIDA','CST_PIS_ENTRADA','ALIQUOTA_PIS_ENTRADA','CST_COFINS_SAIDA','ALIQUOTA_COFINS_SAIDA','CST_COFINS_ENTRADA','ALIQUOTA_COFINS_ENTRADA','CST_IPI_SAIDA','ALIQUOTA_IPI_SAIDA','CST_IPI_ENTRADA','ALIQUOTA_IPI_ENTRADA'],
            'simples'=>['CODIGO','DESCRICAO','CSOSN','CSOSN_CONSUMIDOR','CST_PIS_SAIDA','CST_PIS_ENTRADA','CST_COFINS_SAIDA','CST_COFINS_ENTRADA','CST_IPI_SAIDA','CST_IPI_ENTRADA'],
            default=>['DESCRICAO','CODIGO_NCM','UNIDADE','CSOSN','CFOP'],
        };
    }

    private function row(string$report,array$p,?array$exit,?array$consumer,?array$entry):array
    {
        $icms=$this->group($exit,'icms_json');$icmsConsumer=$this->group($consumer,'icms_json');$icmsEntry=$this->group($entry,'icms_json');$pis=$this->group($exit,'pis_json');$pisEntry=$this->group($entry,'pis_json');$cofins=$this->group($exit,'cofins_json');$cofinsEntry=$this->group($entry,'cofins_json');$ipi=$this->group($exit,'ipi_json');$ipiEntry=$this->group($entry,'ipi_json');
        $cst=static fn(array$g):string=>(string)($g['cst']??$g['CST']??$g['csosn']??$g['CSOSN']??'');$rate=static fn(array$g):string=>(string)($g['rate']??$g['pICMS']??$g['pPIS']??$g['pCOFINS']??$g['pIPI']??'');
        if($report==='normal')return[$p['codigo'],$p['nome'],$p['ncm'],$cst($icms),$p['tax_benefit_code'],$cst($icmsConsumer),$p['tax_benefit_code'],$cst($pis),$rate($pis),$cst($pisEntry),$rate($pisEntry),$cst($cofins),$rate($cofins),$cst($cofinsEntry),$rate($cofinsEntry),$cst($ipi),$rate($ipi),$cst($ipiEntry),$rate($ipiEntry)];
        if($report==='simples')return[$p['codigo'],$p['nome'],$cst($icms),$cst($icmsConsumer),$cst($pis),$cst($pisEntry),$cst($cofins),$cst($cofinsEntry),$cst($ipi),$cst($ipiEntry)];
        return[$p['nome'],$p['ncm'],$p['unidade'],$cst($icms),(string)($exit['cfop']??$p['cfop_padrao']??'')];
    }

    private function rule(array$rules,array$product,string$direction,string$model):?array
    {
        foreach($rules as$rule){$c=json_decode((string)$rule['conditions_json'],true)?:[];if(isset($c['direction'])&&$c['direction']!==$direction)continue;if(isset($c['model'])&&(string)$c['model']!==$model)continue;if(isset($c['ncm'])&&(string)$c['ncm']!==(string)$product['ncm'])continue;if(isset($c['product_origin'])&&(string)$c['product_origin']!==(string)$product['merchandise_origin'])continue;return$rule;}return null;
    }
    private function group(?array$rule,string$key):array{return$rule?(json_decode((string)($rule[$key]??'[]'),true)?:[]):[];}
    private function safeCell(mixed$value):string{$value=(string)$value;return preg_match('/^[=+\-@]/',$value)?"'".$value:$value;}
}
