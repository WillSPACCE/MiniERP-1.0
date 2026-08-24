<?php
declare(strict_types=1);
namespace MiniErp\Services;
final class FiscalErrorSanitizer
{
    public static function code(\Throwable$e):string{return preg_replace('/[^A-Z0-9_].*$/','',strtoupper($e->getMessage()))?:'LOCAL_PIPELINE_FAILED';}
    public static function message(string$code):string{return match($code){'CUSTOMER_DOCUMENT_MISSING'=>'Informe CPF ou CNPJ válido no cadastro do cliente.','CUSTOMER_ADDRESS_MISSING'=>'Complete o endereço do cliente antes de emitir a NF-e.','PRODUCT_NCM_MISSING'=>'Corrija o NCM do produto antes de emitir a NF-e.','CFOP_NOT_RESOLVED'=>'Configure o CFOP aplicável à operação.','FISCAL_RULE_NOT_FOUND','FISCAL_PENDING'=>'Configure a regra tributária aplicável ao produto e à operação.','SERIES_NOT_CONFIGURED'=>'Configure uma série fiscal de homologação para o modelo.','PAYMENT_METHOD_INVALID','PAYMENT_METHOD_MISSING'=>'Configure uma forma de pagamento fiscal válida.','ARTIFACT_HASH_MISMATCH','ARTIFACT_INTEGRITY_FAILED'=>'O XML não passou na verificação de integridade.','XML_SCHEMA_VALIDATION_FAILED'=>'O XML não passou na validação fiscal.','DANFE_RENDER_FAILED'=>'Não foi possível gerar o DANFE.','DOCUMENT_NOT_FOUND'=>'Documento não encontrado.',default=>'Não foi possível preparar o documento fiscal. Revise os cadastros e tente novamente.'};}
}
