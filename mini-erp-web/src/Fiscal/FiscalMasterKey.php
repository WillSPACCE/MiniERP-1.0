<?php
declare(strict_types=1);

namespace MiniErp\Fiscal;

use RuntimeException;

final class FiscalMasterKey
{
    public static function resolve(?string $baseDir = null): string
    {
        $envValue = getenv('FISCAL_SECRET_KEY');
        if (is_string($envValue) && trim($envValue) !== '' && strlen(trim($envValue)) >= 32) {
            return trim($envValue);
        }

        $root = rtrim($baseDir ?? realpath(__DIR__ . '/../..') ?: __DIR__ . '/../..', '/\\');
        $file = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'fiscal' . DIRECTORY_SEPARATOR . 'master.key';

        if (is_file($file)) {
            $persisted = trim((string) file_get_contents($file));
            if (strlen($persisted) >= 32) {
                putenv('FISCAL_SECRET_KEY=' . $persisted);
                return $persisted;
            }
        }

        if (!is_dir(dirname($file))) {
            if (!mkdir(dirname($file), 0700, true) && !is_dir(dirname($file))) {
                throw new RuntimeException('Nao foi possivel criar o storage privado da chave fiscal.');
            }
        }

        $generated = bin2hex(random_bytes(32));
        $written = @file_put_contents($file, $generated . PHP_EOL, LOCK_EX);
        if ($written === false) {
            throw new RuntimeException('Nao foi possivel gravar a chave fiscal persistente.');
        }
        @chmod($file, 0600);
        putenv('FISCAL_SECRET_KEY=' . $generated);
        return $generated;
    }

    public static function source(?string $baseDir = null): string
    {
        $envValue = getenv('FISCAL_SECRET_KEY');
        if (is_string($envValue) && strlen(trim($envValue)) >= 32) {
            return 'ENV';
        }

        $root = rtrim($baseDir ?? realpath(__DIR__ . '/../..') ?: __DIR__ . '/../..', '/\\');
        $file = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'fiscal' . DIRECTORY_SEPARATOR . 'master.key';
        if (is_file($file)) {
            $persisted = trim((string) file_get_contents($file));
            if (strlen($persisted) >= 32) {
                return 'LOCAL_SECRET_FILE';
            }
        }

        return 'LOCAL_SECRET_FILE';
    }
}
