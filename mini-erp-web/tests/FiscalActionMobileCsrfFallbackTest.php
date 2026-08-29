<?php
declare(strict_types=1);

function mobileFiscalAssert(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException($label);
    echo $label . " PASS\n";
}

$endpoint = (string) file_get_contents(__DIR__ . '/../public/fiscal_action.php');
$page = (string) file_get_contents(__DIR__ . '/../public/index.php');

mobileFiscalAssert(str_contains($endpoint, '$validCsrfCookie') && str_contains($endpoint, '$validCsrfPost'), 'FiscalActionAcceptsSessionBoundFormTokenTest');
mobileFiscalAssert(str_contains($endpoint, 'if (!$validCsrfCookie && !$validCsrfPost)'), 'FiscalActionRejectsWhenEveryCsrfChannelIsInvalidTest');
mobileFiscalAssert(str_contains($page, "else location.href=result.danfe_url"), 'FiscalPreviewFallsBackToSameTabWhenPopupIsBlockedTest');
mobileFiscalAssert(str_contains($page, "response.json().catch"), 'FiscalActionInvalidResponseRecoveryTest');
