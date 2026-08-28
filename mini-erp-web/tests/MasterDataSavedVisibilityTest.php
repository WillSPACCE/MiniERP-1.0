<?php
declare(strict_types=1);
function savedVisibilityAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$js=file_get_contents(__DIR__.'/../public/assets/master-data.js');
savedVisibilityAssert(str_contains($js,"listUrl.searchParams.set('q',clean)"),'saved person must return with its document filtered');
savedVisibilityAssert(str_contains($js,"listUrl.searchParams.delete('p')"),'saved person must return to the first result page');
savedVisibilityAssert(str_contains($js,'location.assign(listUrl.href)'),'save and close must navigate to the matching row');
savedVisibilityAssert(str_contains($js,"dialog.addEventListener('close',()=>{if(pendingListRefresh)"),'save without closing must refresh the list when closed');
echo "MasterDataSavedVisibility OK\n";
