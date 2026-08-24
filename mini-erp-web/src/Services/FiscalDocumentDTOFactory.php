<?php

declare(strict_types=1);

namespace MiniErp\Services;

use MiniErp\Fiscal\FiscalDocumentDTO;
use RuntimeException;

final class FiscalDocumentDTOFactory
{
    public function create(array $document, int $tenantId, array $reservation = [], array $identity = []): FiscalDocumentDTO
    {
        if (($document['status'] ?? '') !== 'FISCAL_READY') {
            throw new RuntimeException('Somente documento FISCAL_READY pode compor o DTO fiscal.');
        }

        $issuer = $document['issuer_snapshot'] ?? [];
        $recipient = $document['recipient_snapshot'] ?? [];
        $payment = $document['payment_snapshot'] ?? [];
        $transport = $document['transport_snapshot'] ?? [];
        $totals = $document['totals'] ?? [];

        $items = [];
        foreach (($document['items'] ?? []) as $item) {
            $tax = json_decode((string) ($item['tax_resolution_json'] ?? 'null'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($tax) || $tax === []) {
                throw new RuntimeException('Item sem FiscalTaxResolution congelada.');
            }

            $product = json_decode((string) ($item['product_snapshot_json'] ?? '{}'), true, 512, JSON_THROW_ON_ERROR);
            $items[] = [
                'product' => is_array($product) ? $product : [],
                'tax' => $tax,
                'values' => $item,
            ];
        }

        if ($items === []) {
            throw new RuntimeException('Documento fiscal sem itens congelados.');
        }

        $model = (string) (($reservation['model'] ?? $totals['model'] ?? $identity['model'] ?? '55'));
        if ($model === '') {
            $model = (string) ($document['totals']['model'] ?? '55');
        }

        $totals['model'] = $model;
        $totals['operation_nature'] = $totals['operation_nature'] ?? ($document['operation_nature'] ?? 'VENDA');
        $totals['operation_type'] = $totals['operation_type'] ?? ($document['operation_type'] ?? 'EXIT');
        $totals['purpose'] = $this->purposeCode($totals['purpose'] ?? ($identity['purpose'] ?? 1));
        $totals['final_consumer'] = $totals['final_consumer'] ?? ($identity['final_consumer'] ?? 1);
        $totals['presence_indicator'] = $totals['presence_indicator'] ?? ($identity['presence_indicator'] ?? 1);

        $payment['method'] = $this->paymentCode((string)($payment['method'] ?? $document['payment_method'] ?? ''));
        $payment['amount'] = $payment['amount'] ?? (($totals['grand'] ?? '0.00'));
        $transport['freight_mode'] = $transport['freight_mode'] ?? 9;

        return new FiscalDocumentDTO(
            (int) ($document['id'] ?? 0),
            $tenantId,
            $model,
            $issuer,
            $recipient,
            $items,
            $totals,
            $payment,
            $transport,
        );
    }

    private function paymentCode(string $method):string
    {
        $value=strtoupper(trim($method));if(preg_match('/^\d{2}$/',$value))return$value;
        return match($value){'DINHEIRO'=>'01','CHEQUE'=>'02','CARTAO_CREDITO','CARTÃO DE CRÉDITO','CREDITO'=>'03','CARTAO_DEBITO','CARTÃO DE DÉBITO','DEBITO'=>'04','PIX'=>'17','BOLETO'=>'15','SEM_PAGAMENTO','SEM PAGAMENTO'=>'90',default=>throw new RuntimeException('PAYMENT_METHOD_INVALID: Forma de pagamento não possui código fiscal válido.')};
    }

    private function purposeCode(mixed $purpose):int
    {
        $value=strtoupper(trim((string)$purpose));
        if(in_array($value,['1','2','3','4','5','6'],true))return(int)$value;
        return match($value){'NORMAL'=>1,'COMPLEMENTAR','COMPLEMENTARY'=>2,'AJUSTE','ADJUSTMENT'=>3,'DEVOLUCAO','DEVOLUÇÃO','RETURN'=>4,'NOTA_DE_CREDITO','NOTA DE CRÉDITO','CREDIT'=>5,'NOTA_DE_DEBITO','NOTA DE DÉBITO','DEBIT'=>6,default=>throw new RuntimeException('DOCUMENT_PURPOSE_INVALID: Finalidade fiscal inválida.')};
    }
}
