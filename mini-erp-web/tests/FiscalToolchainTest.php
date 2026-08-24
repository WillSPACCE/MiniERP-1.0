<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use MiniErp\Fiscal\NfeAccessKeyGenerator;
use NFePHP\Common\Keys;
use NFePHP\NFe\Make;

function toolchainAssert(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }

toolchainAssert(PHP_VERSION_ID >= 80200, 'PHP 8.2');
foreach (['dom', 'libxml', 'openssl', 'simplexml', 'soap', 'zlib'] as $extension) toolchainAssert(extension_loaded($extension), "ext-{$extension}");
foreach ([Make::class, NFePHP\NFe\Tools::class, Keys::class] as $class) toolchainAssert(class_exists($class), "autoload {$class}");
foreach (['tagIBSCBS', 'tagIBSCBSTot', 'tagIS', 'tagISTot'] as $method) toolchainAssert(method_exists(Make::class, $method), "RTC {$method}");
$rtcSource = file_get_contents(__DIR__ . '/../vendor/nfephp-org/sped-nfe/src/Traits/TraitTagDetIBSCBS.php');
toolchainAssert(str_contains($rtcSource, 'cClassTrib') && str_contains($rtcSource, 'gCBS_vCBS') && str_contains($rtcSource, 'gIBSUF_vIBSUF'), 'cClassTrib/IBS/CBS');

$make = new Make('PL_010_V1.30');
$make->taginfNFe((object) ['Id' => null, 'versao' => '4.00']);
$emit = $make->tagEmit((object) ['xNome' => 'TEST_ONLY', 'xFant' => null, 'IE' => '123', 'IEST' => null, 'IM' => null, 'CNAE' => null, 'CRT' => 3, 'CNPJ' => '12ABC34501DE35', 'CPF' => null]);
toolchainAssert($emit->getElementsByTagName('CNPJ')->item(0)?->nodeValue === '12ABC34501DE35', 'CNPJ alfanumérico sobrevive à API');

$ourKey = (new NfeAccessKeyGenerator())->generate('35', '2608', '12ABC34501DE35', '55', 1, 123, 1, '87654321');
toolchainAssert(Keys::isValid($ourKey), 'chave aceita pelo sped-common');
toolchainAssert(Keys::verifyingDigit(substr($ourKey, 0, 43)) === substr($ourKey, -1), 'DV compatível');

$schema = __DIR__ . '/../vendor/nfephp-org/sped-nfe/schemes/PL_010_V1.30/DFeTiposBasicos_v1.00.xsd';
toolchainAssert(is_file($schema) && str_contains(file_get_contents($schema), '[0-9]{6}[A-Z0-9]{12}[0-9]{26}'), 'schema local alfanumérico');
echo "FiscalToolchain OK\n";
