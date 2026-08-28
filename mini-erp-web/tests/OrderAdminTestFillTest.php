<?php
declare(strict_types=1);

function orderTestFillAssert(bool $condition,string $label):void{if(!$condition)throw new RuntimeException($label);echo$label." PASS\n";}

$page=(string)file_get_contents(__DIR__.'/../public/index.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/app.js');

orderTestFillAssert(str_contains($page,"\$globalTechnicalId > 0")&&str_contains($page,"(int)(\$currentUser['id'] ?? 0) === 9")&&str_contains($page,"strtolower((string)(\$currentUser['role'] ?? '')) === 'admin'"),'OrderTestFillAdminNineGateTest');
orderTestFillAssert(substr_count($page,'data-order-test-fill')===1&&str_contains($page,'if($canUseOrderTestFill)'),'OrderTestFillServerVisibilityTest');
orderTestFillAssert(str_contains($js,"document.querySelector('[data-order-test-fill]')")&&str_contains($js,"window.PRODUCTS")&&str_contains($js,"selectFirst('cfop_id')"),'OrderTestFillRequiredDataTest');
orderTestFillAssert(!str_contains($js,"data-order-test-fill').closest('form').submit")&&!str_contains($js,"data-order-test-fill').closest('form').requestSubmit"),'OrderTestFillNeverAutoSubmitsTest');
