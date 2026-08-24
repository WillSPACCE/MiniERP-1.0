<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;

final readonly class EstablishmentData
{
    public const CRT = ['1', '2', '3', '4'];
    public const STATUSES = ['ativo', 'inativo'];

    private array $data;

    public function __construct(array $input)
    {
        $text = static fn (string $key, int $max = 255): string => mb_substr(trim((string) ($input[$key] ?? '')), 0, $max);
        $taxId = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($input['tax_id'] ?? $input['cnpj'] ?? '')) ?? '');
        if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $taxId)) throw new InvalidArgumentException('CNPJ deve ter 14 posições; as 12 primeiras aceitam letras e números e as duas últimas devem ser dígitos verificadores.');
        $required = ['legal_name' => 150, 'state_registration' => 30, 'street' => 150, 'number' => 20, 'district' => 100, 'city_ibge_code' => 7, 'city_name' => 100, 'state' => 2, 'postal_code' => 8];
        foreach ($required as $field => $max) if ($text($field, $max) === '') throw new InvalidArgumentException("Campo fiscal obrigatório ausente: {$field}.");
        $state = strtoupper($text('state', 2));
        if (!preg_match('/^[A-Z]{2}$/', $state)) throw new InvalidArgumentException('UF deve conter duas letras.');
        $cityCode = preg_replace('/\D/', '', $text('city_ibge_code', 7)) ?? '';
        if (strlen($cityCode) !== 7) throw new InvalidArgumentException('Código IBGE do município deve conter 7 dígitos.');
        $postalCode = preg_replace('/\D/', '', $text('postal_code', 8)) ?? '';
        if (strlen($postalCode) !== 8) throw new InvalidArgumentException('CEP deve conter 8 dígitos.');
        $crt = $text('tax_regime_code', 1);
        if (!in_array($crt, self::CRT, true)) throw new InvalidArgumentException('CRT deve ser 1, 2, 3 ou 4.');
        $status = strtolower($text('status', 10) ?: 'ativo');
        if (!in_array($status, self::STATUSES, true)) throw new InvalidArgumentException('Status do estabelecimento inválido.');
        $this->data = [
            'tax_id' => $taxId, 'legal_name' => $text('legal_name', 150), 'trade_name' => $text('trade_name', 150),
            'state_registration' => $text('state_registration', 30), 'st_registration' => $text('st_registration', 30),
            'municipal_registration' => $text('municipal_registration', 30), 'cnae' => preg_replace('/\D/', '', $text('cnae', 7)) ?? '',
            'tax_regime_code' => $crt, 'street' => $text('street', 150), 'number' => $text('number', 20),
            'complement' => $text('complement', 100), 'district' => $text('district', 100), 'city_ibge_code' => $cityCode,
            'city_name' => $text('city_name', 100), 'state' => $state, 'postal_code' => $postalCode,
            'country_code' => preg_replace('/\D/', '', $text('country_code', 4) ?: '1058') ?: '1058',
            'country_name' => strtoupper($text('country_name', 60) ?: 'BRASIL'), 'phone' => $text('phone', 30),
            'email' => strtolower($text('email', 150)), 'establishment_type' => 'MATRIZ', 'is_primary' => 1,
            'status' => $status, 'fiscal_readiness' => 'INCOMPLETE',
        ];
        if ($this->data['email'] !== '' && !filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('E-mail do estabelecimento inválido.');
    }

    public function toArray(): array { return $this->data; }
}
