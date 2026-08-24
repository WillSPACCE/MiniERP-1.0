<?php
declare(strict_types=1);

$r = dirname(__DIR__);
$ui = file_get_contents($r . '/public/index.php');
$dto = file_get_contents($r . '/src/Services/PersonFiscalData.php');
$repo = file_get_contents($r . '/app/Repository.php');
$schema = file_get_contents($r . '/migrations/20260821_extend_clientes_as_fiscal_people.sql');
$contract = file_get_contents($r . '/docs/fiscal/contracts/person.md');

function pc(bool $value, string $message): void
{
    if (!$value) {
        fwrite(STDERR, "ASSERTION FAILED: {$message}\n");
        exit(1);
    }
}

$formStart = strpos($ui, '<form method="POST" class="register-form">');
$formEnd = $formStart === false ? false : strpos($ui, '</form>', $formStart);
pc($formStart !== false && $formEnd !== false, 'people form found');
$form = substr($ui, $formStart, $formEnd - $formStart);
preg_match_all('/name="([^"]+)"/', $form, $matches);
$controls = ['action', 'csrf_token', 'id'];
foreach (array_unique($matches[1]) as $field) {
    if (!in_array($field, $controls, true)) {
        pc(str_contains($contract, '`' . $field . '`'), "contract covers visible field {$field}");
    }
}

$fields = ['person_type', 'cpf_cnpj', 'foreign_id', 'state_registration_indicator', 'rg', 'inscricao_estadual', 'suprama', 'im', 'codigo_ibge', 'country_code', 'country_name', 'observations'];
foreach ($fields as $field) {
    pc(str_contains($ui, 'name="' . $field . '"'), "UI {$field}");
    pc(str_contains($dto, "'{$field}'"), "DTO {$field}");
    pc(str_contains($repo, '$fiscalData'), 'repository normalization');
}
foreach (['role_customer', 'role_supplier', 'role_seller', 'role_carrier'] as $field) {
    pc(str_contains($schema, $field), "schema {$field}");
    pc(str_contains($dto, "'{$field}'"), "DTO {$field}");
}
pc(str_contains($repo, "UPDATE clientes SET status = 'inativo'"), 'delete becomes inactivation');
pc(str_contains($repo, 'WHERE id = :id'), 'scoped mutations');
pc(!str_contains($form, 'name="tenant_id"') && !str_contains($form, 'name="db_name"'), 'UI cannot choose tenant/database');
pc(!str_contains($form, 'generateXml') && !str_contains($form, 'SEFAZ'), 'no emission');
echo "PersonFiscalFieldCoverage OK\n";
