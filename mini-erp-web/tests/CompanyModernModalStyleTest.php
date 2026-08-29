<?php
declare(strict_types=1);

function companyStyleAssert(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException($label);
    echo $label . " PASS\n";
}

$page = (string) file_get_contents(__DIR__ . '/../public/index.php');
$css = (string) file_get_contents(__DIR__ . '/../public/assets/erp-companies-modern.css');
$js = (string) file_get_contents(__DIR__ . '/../public/assets/erp-companies.js');

companyStyleAssert(str_contains($page, "assetUrl('erp-companies-modern.css')"), 'CompanyModernStyleLoadedTest');
companyStyleAssert(str_contains($css, '#erp-company-modal .app-tabs__tab[aria-selected="true"]'), 'CompanyModernTabsTest');
companyStyleAssert(str_contains($css, 'border-radius: 20px 20px 0 0') && str_contains($css, 'max-height: 94dvh'), 'CompanyMobileBottomSheetTest');
companyStyleAssert(str_contains($css, '.company-list-table td::before') && str_contains($js, "cell.dataset.label=companyLabels[index]"), 'CompanyMobileCardsTest');
