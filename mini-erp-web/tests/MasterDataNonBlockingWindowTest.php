<?php
declare(strict_types=1);
function nonBlockingAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$js=file_get_contents(__DIR__.'/../public/assets/master-data.js');$css=file_get_contents(__DIR__.'/../public/assets/master-data-fixes.css');
nonBlockingAssert(str_contains($js,'dialog.show()')&&!str_contains($js,'dialog.showModal()'),'master data must open without blocking navigation');
nonBlockingAssert(!str_contains($js,"classList.add('md-modal-open')"),'page scrolling must not be locked');
nonBlockingAssert(str_contains($css,'background:transparent')&&str_contains($css,'position:fixed'),'window must remain visible without a gray backdrop');
nonBlockingAssert(str_contains($js,'CPF genérico não é permitido'),'all-zero consumer CPF must have a specific explanation');
echo "MasterDataNonBlockingWindow OK\n";
