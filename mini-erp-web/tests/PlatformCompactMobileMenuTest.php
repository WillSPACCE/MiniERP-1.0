<?php
declare(strict_types=1);
function compactMenuAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$layout=file_get_contents(__DIR__.'/../public/plataforma/_layout.php');$index=file_get_contents(__DIR__.'/../public/plataforma/index.php');$css=file_get_contents(__DIR__.'/../public/assets/platform.css');
foreach(['/plataforma/','/plataforma/tecnicos.php','/plataforma/operacoes-multitenant.php','/plataforma/auditoria.php','/plataforma/configuracoes.php','/plataforma/minha-conta.php','platform-mobile-nav']as$needle)compactMenuAssert(str_contains($layout,$needle)&&str_contains($index,$needle),'mobile navigation '.$needle);
foreach(['position: fixed','grid-template-columns: repeat(6','min-height: 52px','env(safe-area-inset-bottom)','.platform-sidebar { display: none; }','.platform-mobile-nav a.active']as$needle)compactMenuAssert(str_contains($css,$needle),'compact mobile CSS '.$needle);
compactMenuAssert(str_contains($css,'padding: 14px 12px calc(92px'),'content must not sit behind bottom navigation');
echo "PlatformCompactMobileMenu OK\n";
