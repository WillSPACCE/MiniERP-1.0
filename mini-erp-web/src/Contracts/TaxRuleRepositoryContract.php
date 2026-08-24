<?php
declare(strict_types=1);
namespace MiniErp\Contracts;
use MiniErp\Fiscal\FiscalTaxContext;
use MiniErp\Fiscal\FiscalTaxRule;
interface TaxRuleRepositoryContract { public function findCandidates(FiscalTaxContext $context): array; public function addVersion(FiscalTaxRule $rule): int; }
