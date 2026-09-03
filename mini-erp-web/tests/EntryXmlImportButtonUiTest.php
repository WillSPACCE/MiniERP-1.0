<?php
declare(strict_types=1);

$page = (string) file_get_contents(dirname(__DIR__) . '/public/index.php');

function entryXmlAssert(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
}

preg_match('/<button class="order-routine order-routine--secondary" type="button" data-order-import-xml[\s\S]*?<\/button>/', $page, $match);
$button = $match[0] ?? '';

entryXmlAssert($button !== '', 'EntryXmlImportButtonExistsTest');
entryXmlAssert(str_contains($button, 'class="erp-icon order-routine__icon"'), 'EntryXmlImportButtonLucideIconTest');
entryXmlAssert(!str_contains($button, 'data-feather') && !str_contains($button, ' disabled'), 'EntryXmlImportButtonEnabledTest');
entryXmlAssert(str_contains($page, 'data-order-import-xml-file'), 'EntryXmlImportFileInputTest');
entryXmlAssert(str_contains($page, "xmlImportButton?.addEventListener('click'") && str_contains($page, "request('analyze')") && str_contains($page, 'data-confirm-xml'), 'EntryXmlImportAnalyzeAndConfirmTest');
entryXmlAssert(str_contains($page, "request('import')") && str_contains($page, "erp:entry-xml-products"), 'EntryXmlImportPersistAndFillTest');
entryXmlAssert(str_contains($page, "request('catalog')") && str_contains($page, 'Somente cadastrar') && str_contains($page, 'Importar como entrada'), 'EntryXmlImportSeparateCatalogAndEntryModesTest');

echo "EntryXmlImportButtonUiTest OK\n";
