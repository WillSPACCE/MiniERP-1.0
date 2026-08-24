<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/helpers/FiscalPipelineTestSupport.php';

use MiniErp\Fiscal\FiscalArtifactStorage;

$artifactRoot = sys_get_temp_dir() . '/minierp-fiscal-artifacts-test-debug-' . bin2hex(random_bytes(4));
mkdir($artifactRoot, 0700, true);
$storage = new FiscalArtifactStorage($artifactRoot);
$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<root>test</root>";
$info = $storage->storeSignedXml(995020, 995120, 1, $xml, 'NFE');
var_export($info);
$path = $storage->resolve($info['storage_reference']);
var_export($path);
echo "OK\n";
