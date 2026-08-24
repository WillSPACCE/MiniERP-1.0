<?php
declare(strict_types=1);
namespace MiniErp\Services;
use MiniErp\Fiscal\DecimalTaxCalculator; use MiniErp\Fiscal\FiscalTaxContext; use MiniErp\Fiscal\TaxRuleResolver;
final readonly class PreviewTaxForItemService { public function __construct(private TaxRuleResolver $resolver,private DecimalTaxCalculator $calculator){} public function preview(FiscalTaxContext $context):array{$resolution=$this->resolver->resolve($context);return ['resolution'=>$resolution,'calculation'=>$this->calculator->calculate($context,$resolution)];} }
