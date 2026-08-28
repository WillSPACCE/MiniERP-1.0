<?php
declare(strict_types=1);
function loaderAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$app=file_get_contents(__DIR__.'/../public/assets/app.js');$master=file_get_contents(__DIR__.'/../public/assets/master-data.js');$view=file_get_contents(__DIR__.'/../public/includes/master_data_configuration.php');
loaderAssert(str_contains($app,'!e.defaultPrevented')&&str_contains($app,"[data-ajax-form], .md-form"),'global loader must ignore prevented AJAX submissions');
loaderAssert(str_contains($view,'data-ajax-form'),'master form must declare AJAX behavior');
loaderAssert(str_contains($master,'fetchWithTimeout')&&str_contains($master,'controller.abort()'),'save and document lookup must have a timeout');
loaderAssert(substr_count($master,"fetchWithTimeout('/master_data_action.php")>=2,'both master data requests must use timeout');
echo "MasterDataInfiniteLoader OK\n";
