<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;
use RuntimeException;

final class FiscalXmlSigner
{
    public function signTestOnly(string $unsignedXml, string $pfxContent, string $password): array
    {
        if ($pfxContent === '' || $password === '') throw new RuntimeException('Credencial TEST_ONLY ausente.');
        $certificate = Certificate::readPfx($pfxContent, $password);
        $signed = Signer::sign($certificate, $unsignedXml, 'infNFe', 'Id', OPENSSL_ALGO_SHA1);
        if (trim($signed) !== '' && !str_starts_with(ltrim($signed), '<?xml')) {
            $signed = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . $signed;
        }
        if (!Signer::isSigned($signed, 'infNFe')) throw new RuntimeException('Assinatura XMLDSig TEST_ONLY inválida.');
        if ($this->canonicalInfNFe($unsignedXml) !== $this->canonicalInfNFe($signed)) throw new RuntimeException('A assinatura alterou o conteudo fiscal de infNFe.');
        $dom = new \DOMDocument();$dom->loadXML($signed);$reference=$dom->getElementsByTagName('Reference')->item(0)?->getAttribute('URI')??'';
        return ['xml'=>$signed,'status'=>'SIGNED_TEST_ONLY','unsigned_sha256'=>hash('sha256',$unsignedXml),'signed_sha256'=>hash('sha256',$signed),'reference'=>$reference,'subject'=>$certificate->getCompanyName()];
    }

    public function verify(string $signedXml): bool
    {
        try { return Signer::isSigned($signedXml, 'infNFe'); } catch (\Throwable) { return false; }
    }

    private function canonicalInfNFe(string $xml): string
    {
        $dom = new \DOMDocument();
        if (!$dom->loadXML($xml)) throw new RuntimeException('XML fiscal invalido.');
        $node = $dom->getElementsByTagName('infNFe')->item(0);
        if ($node === null) throw new RuntimeException('infNFe ausente no XML fiscal.');
        return (string) $node->C14N();
    }
}
