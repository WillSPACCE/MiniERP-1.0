<?php
declare(strict_types=1);require __DIR__.'/../vendor/autoload.php';use MiniErp\Services\BrazilianDocumentValidator;
function formAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
formAssert(BrazilianDocumentValidator::cpf('529.982.247-25'),'valid CPF');formAssert(!BrazilianDocumentValidator::cpf('123.456.789-10'),'invalid CPF');formAssert(BrazilianDocumentValidator::cnpj('19.131.243/0001-97'),'valid CNPJ');formAssert(!BrazilianDocumentValidator::cnpj('11.111.111/1111-11'),'invalid CNPJ');
$root=dirname(__DIR__);$js=(string)file_get_contents($root.'/public/assets/master-data.js');$css=(string)file_get_contents($root.'/public/assets/master-data-fixes.css');
foreach(['validateForm','cpfValid','cnpjValid','Campo obrigatório','if(saving)return','if(!dialog.open)dialog.show()'] as $needle)formAssert(str_contains($js,$needle),'resilience '.$needle);
foreach(['flex-direction:column','overflow-y:auto','.md-required','.md-invalid','.md-form>footer .btn'] as $needle)formAssert(str_contains($css,$needle),'visual '.$needle);
formAssert(str_contains($js,"confirm('Fechar sem salvar as alterações?')")&&str_contains($js,'dialog.close()'),'modal close after error');echo "MasterDataFormResilienceTest OK\n";
