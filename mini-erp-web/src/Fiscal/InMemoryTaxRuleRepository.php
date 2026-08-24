<?php
declare(strict_types=1);
namespace MiniErp\Fiscal;
use MiniErp\Contracts\TaxRuleRepositoryContract;
final class InMemoryTaxRuleRepository implements TaxRuleRepositoryContract { private array $rules; public function __construct(array $rules=[]){$this->rules=$rules;} public function findCandidates(FiscalTaxContext $context):array{return array_values(array_filter($this->rules,fn(FiscalTaxRule $r):bool=>$r->tenantId===$context->tenantId));} public function addVersion(FiscalTaxRule $rule):int{$this->rules[]=$rule;return count($this->rules);} }
