<?php
declare(strict_types=1);

namespace MiniErp\Adapters;

use DOMDocument;
use DOMXPath;
use NFePHP\Common\Certificate;
use NFePHP\Common\Soap\SoapCurl;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Tools;
use RuntimeException;

final class NfePhpSefazAuthorizationClient
{
    public function authorize(string $configJson, object $certificate, string $signedXml, string $batchId, int $timeoutSeconds = 30): array
    {
        if (!$certificate instanceof Certificate) throw new RuntimeException('SEFAZ_CERTIFICATE_ERROR');
        if (!preg_match('/^\d{1,15}$/', $batchId)) throw new RuntimeException('SEFAZ_BATCH_INVALID');

        $tools = new Tools($configJson, $certificate);
        $tools->model(55);
        $soap = new SoapCurl($certificate);
        $soap->disableSecurity(false);
        $soap->timeout(max(5, min(60, $timeoutSeconds)));
        $tools->loadSoapClass($soap);

        $response = $tools->sefazEnviaLote([$signedXml], $batchId, 1);
        $parsed = $this->parse($response);
        if (in_array($parsed['batch_cstat'], ['103', '105'], true) && $parsed['receipt'] !== '') {
            for ($attempt = 0; $attempt < 4 && in_array($parsed['batch_cstat'], ['103', '105'], true); $attempt++) {
                usleep(500000);
                $response = $tools->sefazConsultaRecibo($parsed['receipt'], 2);
                $parsed = $this->parse($response);
            }
        }
        if ($parsed['environment'] !== '' && $parsed['environment'] !== '2') throw new RuntimeException('SEFAZ_PRODUCTION_BLOCKED');
        if ($parsed['authorized']) $parsed['authorized_xml'] = Complements::toAuthorize($signedXml, $response);
        $parsed['response_xml'] = $response;
        return $parsed;
    }

    public function consult(string $configJson, object $certificate, string $accessKey, string $signedXml, int $timeoutSeconds = 30): array
    {
        if (!$certificate instanceof Certificate) throw new RuntimeException('SEFAZ_CERTIFICATE_ERROR');
        if (!preg_match('/^\d{44}$/', $accessKey)) throw new RuntimeException('SEFAZ_ACCESS_KEY_INVALID');
        $tools = new Tools($configJson, $certificate);
        $tools->model(55);
        $soap = new SoapCurl($certificate);
        $soap->disableSecurity(false);
        $soap->timeout(max(5, min(60, $timeoutSeconds)));
        $tools->loadSoapClass($soap);
        $response = $tools->sefazConsultaChave($accessKey, 2);
        $parsed = $this->parse($response);
        if ($parsed['environment'] !== '' && $parsed['environment'] !== '2') throw new RuntimeException('SEFAZ_PRODUCTION_BLOCKED');
        if ($parsed['authorized']) $parsed['authorized_xml'] = Complements::toAuthorize($signedXml, $response);
        $parsed['response_xml'] = $response;
        return $parsed;
    }

    private function parse(string $xml): array
    {
        $dom = new DOMDocument();
        if (!@$dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) throw new RuntimeException('SEFAZ_INVALID_RESPONSE');
        $xpath = new DOMXPath($dom);
        $value = static fn(string $expression): string => trim((string)$xpath->evaluate('string(' . $expression . ')'));
        $batchCode = $value('(//*[local-name()="retEnviNFe" or local-name()="retConsReciNFe" or local-name()="retConsSitNFe"])[1]/*[local-name()="cStat"]');
        $batchReason = $value('(//*[local-name()="retEnviNFe" or local-name()="retConsReciNFe" or local-name()="retConsSitNFe"])[1]/*[local-name()="xMotivo"]');
        $protocolCode = $value('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="cStat"]');
        $protocolReason = $value('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="xMotivo"]');
        $environment = $value('//*[local-name()="tpAmb"][1]');
        return [
            'batch_cstat' => $batchCode,
            'batch_reason' => $batchReason,
            'cstat' => $protocolCode !== '' ? $protocolCode : $batchCode,
            'reason' => $protocolReason !== '' ? $protocolReason : $batchReason,
            'protocol' => $value('//*[local-name()="protNFe"]/*[local-name()="infProt"]/*[local-name()="nProt"]'),
            'receipt' => $value('//*[local-name()="nRec"][1]'),
            'environment' => $environment,
            'authorized' => in_array($protocolCode, ['100', '150'], true),
            'denied' => in_array($protocolCode, ['110', '301', '302'], true),
            'processing' => in_array($batchCode, ['103', '105'], true),
        ];
    }
}
