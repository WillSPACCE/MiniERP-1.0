<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use DOMDocument;
use RuntimeException;

final readonly class OfficialNfeXsdValidator
{
    public const VERSION = '010e-v1.02';
    public function __construct(private string $schemaRoot) {}
    public function validate(string $xml): void
    {
        $schema = rtrim($this->schemaRoot, '/\\') . '/nfe_v4.00.xsd';
        if (!is_file($schema)) throw new RuntimeException('XSD oficial NF-e não encontrado.');
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml, LIBXML_NONET) || !$dom->schemaValidate($schema)) {
            $messages = array_map(static fn (\LibXMLError $e): string => trim($e->message), libxml_get_errors());
            libxml_clear_errors();
            throw new RuntimeException('XML inválido no XSD ' . self::VERSION . ': ' . implode(' | ', array_unique($messages)));
        }
        libxml_clear_errors();
    }
}
