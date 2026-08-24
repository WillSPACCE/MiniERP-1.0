<?php
declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;
use MiniErp\Contracts\CnpjLookupProviderContract;

final class CnpjLookupService
{
    public function __construct(private readonly CnpjLookupProviderContract $provider, private readonly ?string $cacheDirectory = null, private readonly int $cacheTtlSeconds = 900)
    {
    }

    public function lookup(string $cnpj): ?CnpjLookupResult
    {
        $normalized = self::normalize($cnpj);
        if (!self::isValid($normalized)) {
            throw new CnpjLookupException('CNPJ_INVALID', 'CNPJ invalido.');
        }
        $cacheFile = $this->cacheFile($normalized);
        if ($cacheFile && is_file($cacheFile) && filemtime($cacheFile) >= time() - $this->cacheTtlSeconds) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached)) return CnpjLookupResult::fromArray($cached);
        }
        $result = $this->provider->lookup($normalized);
        if ($result && $cacheFile) {
            if (!is_dir(dirname($cacheFile))) @mkdir(dirname($cacheFile), 0770, true);
            @file_put_contents($cacheFile, json_encode($result->toArray(), JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
        return $result;
    }

    public static function normalize(string $cnpj): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', strtoupper(trim($cnpj))) ?? '';
    }

    public static function isValid(string $cnpj): bool
    {
        $cnpj = self::normalize($cnpj);
        if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj) || preg_match('/^(.)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([12, 13] as $length) {
            $sum = 0;
            $weight = $length - 7;
            for ($index = 0; $index < $length; $index++) {
                $sum += (ord($cnpj[$index]) - 48) * $weight--;
                if ($weight < 2) {
                    $weight = 9;
                }
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
            if ((int) $cnpj[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }

    private function cacheFile(string $cnpj): ?string
    { return $this->cacheDirectory ? rtrim($this->cacheDirectory, '/\\') . DIRECTORY_SEPARATOR . hash('sha256', $cnpj) . '.json' : null; }
}
