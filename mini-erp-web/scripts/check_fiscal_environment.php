<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
$required = ['curl', 'dom', 'json', 'libxml', 'openssl', 'simplexml', 'soap', 'zlib', 'mbstring', 'fileinfo', 'zip'];
$recommended = ['gd'];
$result = [
    'php_binary' => PHP_BINARY,
    'php_version' => PHP_VERSION,
    'php_sapi' => PHP_SAPI,
    'php_ini' => php_ini_loaded_file(),
    'required_extensions' => array_combine($required, array_map('extension_loaded', $required)),
    'recommended_extensions' => array_combine($recommended, array_map('extension_loaded', $recommended)),
    'autoload' => is_file($autoload),
];

if ($result['autoload']) {
    require $autoload;
    $result['sped_nfe_version'] = Composer\InstalledVersions::getPrettyVersion('nfephp-org/sped-nfe');
    $result['classes'] = [
        NFePHP\NFe\Make::class => class_exists(NFePHP\NFe\Make::class),
        NFePHP\NFe\Tools::class => class_exists(NFePHP\NFe\Tools::class),
        NFePHP\Common\Keys::class => class_exists(NFePHP\Common\Keys::class),
    ];
    $result['rtc_methods'] = array_combine(
        ['tagIBSCBS', 'tagIBSCBSTot', 'tagIS', 'tagISTot'],
        array_map(static fn (string $method): bool => method_exists(NFePHP\NFe\Make::class, $method), ['tagIBSCBS', 'tagIBSCBSTot', 'tagIS', 'tagISTot'])
    );
    $schemaRoot = $root . '/vendor/nfephp-org/sped-nfe/schemes';
    $result['schema_root'] = realpath($schemaRoot) ?: null;
    $result['schema_packages'] = is_dir($schemaRoot) ? array_values(array_filter(scandir($schemaRoot), static fn (string $name): bool => $name[0] !== '.')) : [];
}

$ok = version_compare(PHP_VERSION, '8.2.0', '>=')
    && !in_array(false, $result['required_extensions'], true)
    && ($result['autoload'] ?? false)
    && !in_array(false, $result['classes'] ?? [false], true)
    && !in_array(false, $result['rtc_methods'] ?? [false], true);

echo json_encode($result + ['local_toolchain_ok' => $ok], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
exit($ok ? 0 : 1);
