<?php

declare(strict_types=1);

namespace MiniErp\Fiscal;

use RuntimeException;

final readonly class FiscalDocumentDTO
{
    public function __construct(
        public int $documentId, public int $tenantId, public string $model,
        public array $issuer, public array $recipient, public array $items,
        public array $totals, public array $payment, public array $transport
    ) {}

    public static function fromSnapshots(array $document, int $tenantId): self
    {
        if (($document['status'] ?? '') !== 'FISCAL_READY') throw new RuntimeException('Somente documento FISCAL_READY pode compor o DTO fiscal.');
        $items = [];
        foreach (($document['items'] ?? []) as $item) {
            $tax = json_decode($item['tax_resolution_json'] ?? 'null', true);
            if (!is_array($tax) || $tax === []) throw new RuntimeException('Item sem FiscalTaxResolution congelada.');
            $items[] = ['product' => json_decode($item['product_snapshot_json'] ?? '{}', true, 512, JSON_THROW_ON_ERROR), 'tax' => $tax, 'values' => $item];
        }
        if ($items === []) throw new RuntimeException('Documento fiscal sem itens congelados.');
        return new self((int) $document['id'], $tenantId, (string) ($document['totals']['model'] ?? ''), $document['issuer_snapshot'] ?? [], $document['recipient_snapshot'] ?? [], $items, $document['totals'] ?? [], $document['payment_snapshot'] ?? [], $document['transport_snapshot'] ?? []);
    }
}
