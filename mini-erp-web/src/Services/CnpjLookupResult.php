<?php
declare(strict_types=1);

namespace MiniErp\Services;

final class CnpjLookupResult
{
    /** @param array<string,mixed> $data */
    private function __construct(private readonly array $data) {}

    /** @param array<string,mixed> $payload */
    public static function fromBrasilApi(array $payload): self
    {
        $type = trim((string)($payload['descricao_tipo_de_logradouro'] ?? ''));
        $street = trim((string)($payload['logradouro'] ?? ''));
        if ($type !== '' && $street !== '' && !preg_match('/^'.preg_quote($type, '/').'\b/iu', $street)) {
            $street = $type . ' ' . $street;
        }
        $value = static fn(string $key): mixed => $payload[$key] ?? null;
        return new self([
            'tax_id' => $value('cnpj'), 'legal_name' => $value('razao_social'), 'trade_name' => $value('nome_fantasia'),
            'registration_status' => $value('situacao_cadastral'), 'registration_status_description' => $value('descricao_situacao_cadastral'),
            'opening_date' => $value('data_inicio_atividade'), 'street_type' => $type ?: null, 'street' => $street ?: null,
            'number' => $value('numero'), 'complement' => $value('complemento'), 'district' => $value('bairro'),
            'postal_code' => $value('cep'), 'city' => $value('municipio'), 'state' => $value('uf'),
            'city_ibge_code' => $value('codigo_municipio_ibge'), 'country' => $value('pais'), 'country_code' => $value('codigo_pais'),
            'main_cnae' => $value('cnae_fiscal'), 'main_cnae_description' => $value('cnae_fiscal_descricao'),
            'phone_1' => $value('ddd_telefone_1'), 'phone_2' => $value('ddd_telefone_2'), 'email' => $value('email'),
            'company_size' => $value('porte'), 'legal_nature' => $value('natureza_juridica'),
            'legal_nature_code' => $value('codigo_natureza_juridica'), 'share_capital' => $value('capital_social'),
            'simple_option' => $value('opcao_pelo_simples'), 'mei_option' => $value('opcao_pelo_mei'),
            'headquarters_branch_type' => $value('descricao_identificador_matriz_filial'),
            'secondary_cnaes' => is_array($value('cnaes_secundarios')) ? $value('cnaes_secundarios') : [],
            'partners' => is_array($value('qsa')) ? $value('qsa') : [],
            'tax_regime_history' => is_array($value('regime_tributario')) ? $value('regime_tributario') : [],
        ]);
    }

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self { return new self($data); }
    /** @return array<string,mixed> */
    public function toArray(): array { return $this->data; }
}
