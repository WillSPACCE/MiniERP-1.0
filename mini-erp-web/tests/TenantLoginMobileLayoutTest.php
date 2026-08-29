<?php
declare(strict_types=1);
function mobileLoginAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$page=file_get_contents(__DIR__.'/../public/login.php');$css=file_get_contents(__DIR__.'/../public/assets/login.css');
foreach(['login.css?v=login-mobile2','tenant-login-name','tenant-login-logo','name="email"','name="senha"','id="loginBtn"']as$needle)mobileLoginAssert(str_contains($page,$needle),'login page '.$needle);
foreach(['@media (max-width:700px)','100dvh','.sign-up-container,.login-shell .overlay-container{display:none!important','width:100%;max-width:430px','min-height:50px','font-size:16px','.password-wrap{position:relative;width:100%']as$needle)mobileLoginAssert(str_contains($css,$needle),'mobile CSS '.$needle);
echo "TenantLoginMobileLayout OK\n";
