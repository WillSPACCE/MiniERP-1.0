<?php
declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'minierp-fiscal-key-' . bin2hex(random_bytes(6));
mkdir($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'fiscal' . DIRECTORY_SEPARATOR . 'secrets', 0700, true);

$autoload = realpath(__DIR__ . '/../vendor/autoload.php');
if ($autoload === false) {
    throw new RuntimeException('vendor/autoload.php ausente.');
}

$processA = $root . DIRECTORY_SEPARATOR . 'process-a.php';
file_put_contents($processA, "<?php\n" .
    "declare(strict_types=1);\n" .
    "require " . var_export($autoload, true) . ";\n" .
    "use MiniErp\\Fiscal\\{FiscalMasterKey,LocalEncryptedSecretStorage};\n" .
    "\$root = " . var_export($root, true) . ";\n" .
    "\$key = FiscalMasterKey::resolve(\$root);\n" .
    "\$vault = new LocalEncryptedSecretStorage(\$root . '/storage/fiscal/secrets', \$key);\n" .
    "\$ref = \$vault->put('tenant-14/establishment-7', 'correct-horse-battery-staple');\n" .
    "file_put_contents(\$root . '/metadata.json', json_encode(['ref' => \$ref]));\n" .
    "echo 'PROCESS_A_OK\\n';\n");

$processB = $root . DIRECTORY_SEPARATOR . 'process-b.php';
file_put_contents($processB, "<?php\n" .
    "declare(strict_types=1);\n" .
    "require " . var_export($autoload, true) . ";\n" .
    "use MiniErp\\Fiscal\\{FiscalMasterKey,LocalEncryptedSecretStorage};\n" .
    "\$root = " . var_export($root, true) . ";\n" .
    "\$key = FiscalMasterKey::resolve(\$root);\n" .
    "\$vault = new LocalEncryptedSecretStorage(\$root . '/storage/fiscal/secrets', \$key);\n" .
    "\$meta = json_decode((string) file_get_contents(\$root . '/metadata.json'), true);\n" .
    "if (!isset(\$meta['ref'])) { throw new RuntimeException('missing ref'); }\n" .
    "\$secret = \$vault->get((string) \$meta['ref']);\n" .
    "if (\$secret !== 'correct-horse-battery-staple') { throw new RuntimeException('secret mismatch'); }\n" .
    "echo 'PROCESS_B_OK\\n';\n");

$exitA = 0;
$stdoutA = [];
$cmdA = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($processA);
exec($cmdA . ' 2>&1', $stdoutA, $exitA);
if ($exitA !== 0) {
    throw new RuntimeException('processo A falhou: ' . implode("\n", $stdoutA));
}

$exitB = 0;
$stdoutB = [];
$cmdB = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($processB);
exec($cmdB . ' 2>&1', $stdoutB, $exitB);
if ($exitB !== 0) {
    throw new RuntimeException('processo B falhou: ' . implode("\n", $stdoutB));
}

$keyFile = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'fiscal' . DIRECTORY_SEPARATOR . 'master.key';
if (!is_file($keyFile)) {
    throw new RuntimeException('master key persistente nao foi criada.');
}
$keyValue = trim((string) file_get_contents($keyFile));
if (strlen($keyValue) < 32) {
    throw new RuntimeException('master key persistente insuficiente.');
}

if (!file_exists($root . DIRECTORY_SEPARATOR . 'metadata.json')) {
    throw new RuntimeException('metadata do processo A nao persistiu.');
}

echo "CERTIFICATE_CROSS_PROCESS_READ=PASS\n";
