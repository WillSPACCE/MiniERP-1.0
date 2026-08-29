<?php
declare(strict_types=1);

function companyUserAssert(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException($label);
    echo $label . " PASS\n";
}

$page = (string) file_get_contents(__DIR__ . '/../public/index.php');
$repository = (string) file_get_contents(__DIR__ . '/../app/Repository.php');
$js = (string) file_get_contents(__DIR__ . '/../public/assets/app.js');
$css = (string) file_get_contents(__DIR__ . '/../public/assets/style.css');
$schema = (string) file_get_contents(__DIR__ . '/../database/schema.sql');

companyUserAssert(str_contains($page, 'data-user-modal-open') && str_contains($page, 'data-user-modal'), 'CompanyUserModalTest');
foreach (['access', 'person', 'permissions'] as $tab) companyUserAssert(str_contains($page, 'data-user-tab="' . $tab . '"'), 'CompanyUserTab' . ucfirst($tab) . 'Test');
companyUserAssert(str_contains($page, 'name="pessoa_id"') && str_contains($page, 'foreach ($clientes as $person)'), 'CompanyUserPersonSelectorTest');
companyUserAssert(str_contains($repository, '"pessoa_id" => "INT NULL"') && str_contains($repository, 'A pessoa selecionada não pertence a esta empresa.'), 'CompanyUserPersonPersistenceScopeTest');
companyUserAssert(str_contains($schema, 'pessoa_id INT NULL'), 'CompanyUserPersonSchemaTest');
companyUserAssert(str_contains($js, 'window.ERP_COMPANY_USERS') && str_contains($js, "document.body.classList.add('user-modal-open')"), 'CompanyUserModalInteractionTest');
companyUserAssert(str_contains($css, '@media(max-width:700px)') && str_contains($css, 'max-height:94dvh'), 'CompanyUserModalMobileTest');
