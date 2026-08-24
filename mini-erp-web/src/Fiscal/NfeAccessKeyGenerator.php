<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use InvalidArgumentException;

final class NfeAccessKeyGenerator
{
    public function generate(string $ufCode, string $yearMonth, string $cnpj, string $model, int $series, int $number, int $emissionType, string $numericCode): string
    {
        $cnpj = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $cnpj) ?? '');
        $parts = [
            'cUF' => [$ufCode, '/^\d{2}$/'], 'AAMM' => [$yearMonth, '/^\d{4}$/'],
            'CNPJ' => [$cnpj, '/^[A-Z0-9]{12}\d{2}$/'], 'mod' => [$model, '/^(55|65)$/'],
            'serie' => [str_pad((string) $series, 3, '0', STR_PAD_LEFT), '/^\d{3}$/'],
            'nNF' => [str_pad((string) $number, 9, '0', STR_PAD_LEFT), '/^\d{9}$/'],
            'tpEmis' => [(string) $emissionType, '/^\d$/'], 'cNF' => [$numericCode, '/^\d{8}$/'],
        ];
        $base = '';
        foreach ($parts as $name => [$value, $pattern]) {
            if (!preg_match($pattern, $value)) {
                throw new InvalidArgumentException("Componente inválido da chave: {$name}.");
            }
            $base .= $value;
        }
        if (strlen($base) !== 43) {
            throw new InvalidArgumentException('A base da chave deve possuir 43 caracteres.');
        }
        return $base . $this->digit($base);
    }

    public function digit(string $base): int
    {
        $base = strtoupper($base);
        if (strlen($base) !== 43 || !preg_match('/^[A-Z0-9]{43}$/', $base)) {
            throw new InvalidArgumentException('Base inválida para cálculo do DV.');
        }
        $sum = 0;
        $weight = 2;
        for ($index = 42; $index >= 0; --$index) {
            $sum += (ord($base[$index]) - 48) * $weight;
            $weight = $weight === 9 ? 2 : $weight + 1;
        }
        $remainder = $sum % 11;
        return $remainder === 0 || $remainder === 1 ? 0 : 11 - $remainder;
    }
}
