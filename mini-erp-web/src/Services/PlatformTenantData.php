<?php

declare(strict_types=1);

namespace MiniErp\Services;

use InvalidArgumentException;

final readonly class PlatformTenantData
{
    private string $razaoSocial;
    private string $nomeFantasia;
    private string $cnpj;
    private string $slug;

    public function __construct(string $razaoSocial, string $nomeFantasia, string $cnpj, string $slug = '')
    {
        $this->razaoSocial = trim($razaoSocial);
        $this->nomeFantasia = trim($nomeFantasia);
        $this->cnpj = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cnpj) ?? '');
        $this->slug = self::normalizeSlug($slug !== '' ? $slug : ($this->nomeFantasia ?: $this->razaoSocial));

        if ($this->razaoSocial === '' || $this->nomeFantasia === '') {
            throw new InvalidArgumentException('Razão social e nome fantasia são obrigatórios.');
        }
        if (!preg_match('/^[A-Z0-9]{12}[0-9]{2}$/', $this->cnpj)) {
            throw new InvalidArgumentException('CNPJ deve possuir 14 posições; as 12 primeiras aceitam letras e números e as duas últimas devem ser dígitos.');
        }
        if ($this->slug === '') {
            throw new InvalidArgumentException('Não foi possível gerar um slug válido.');
        }
        if (strlen($this->slug) > 255) {
            throw new InvalidArgumentException('Slug excede o limite permitido.');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            razaoSocial: self::readString($data, 'razao_social'),
            nomeFantasia: self::readString($data, 'nome_fantasia'),
            cnpj: self::readString($data, 'cnpj'),
            slug: self::readString($data, 'slug')
        );
    }

    public function toArray(): array
    {
        return [
            'razao_social' => $this->razaoSocial,
            'nome_fantasia' => $this->nomeFantasia,
            'cnpj' => $this->cnpj,
            'slug' => $this->slug,
        ];
    }

    private static function normalizeSlug(string $value): string
    {
        $value = trim($value);
        if (function_exists('iconv')) {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }

    private static function readString(array $data, string $field): string
    {
        $value = $data[$field] ?? '';
        if (!is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('%s deve ser um valor textual.', $field));
        }
        return (string) $value;
    }
}
