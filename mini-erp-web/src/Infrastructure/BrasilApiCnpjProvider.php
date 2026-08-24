<?php
declare(strict_types=1);

namespace MiniErp\Infrastructure;

use MiniErp\Contracts\CnpjLookupProviderContract;
use MiniErp\Services\CnpjLookupException;
use MiniErp\Services\CnpjLookupResult;

final class BrasilApiCnpjProvider implements CnpjLookupProviderContract
{
    private readonly ?\Closure $transport;
    public function __construct(
        private readonly int $connectTimeoutSeconds = 5,
        private readonly int $timeoutSeconds = 8,
        ?\Closure $transport = null,
    ) {
        $this->transport = $transport;
    }

    public function lookup(string $cnpj): ?CnpjLookupResult
    {
        $url = sprintf('https://brasilapi.com.br/api/cnpj/v1/%s', rawurlencode($cnpj));
        if ($this->transport !== null) {
            $http = ($this->transport)($url, $this->connectTimeoutSeconds, $this->timeoutSeconds);
            $response = $http['body'] ?? false; $status = (int)($http['status'] ?? 0); $error = (string)($http['error'] ?? '');
        } else {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('A extensao cURL do PHP nao esta disponivel.');
        }

        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException('Nao foi possivel iniciar a consulta de CNPJ.');
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERAGENT => 'MiniERP/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        }

        if ($response === false) {
            $reason = str_contains(strtolower($error), 'timed out') ? 'CNPJ_SERVICE_TIMEOUT' : 'CNPJ_SERVICE_UNAVAILABLE';
            throw new CnpjLookupException($reason, 'Falha de comunicacao com a BrasilAPI.');
        }
        if ($status === 404) {
            return null;
        }
        if ($status === 429) {
            throw new CnpjLookupException('CNPJ_RATE_LIMIT', 'Limite de consultas da BrasilAPI atingido.');
        }
        if ($status < 200 || $status >= 300) {
            throw new CnpjLookupException('CNPJ_SERVICE_UNAVAILABLE', 'BrasilAPI indisponivel.');
        }

        $data = json_decode($response, true);
        if (!is_array($data) || $data === []) {
            throw new CnpjLookupException('CNPJ_PROVIDER_INVALID_RESPONSE', 'A BrasilAPI retornou uma resposta invalida.');
        }
        return CnpjLookupResult::fromBrasilApi($data);
    }
}
