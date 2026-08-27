<?php
declare(strict_types=1);
namespace MiniErp\Services;

use MiniErp\Repositories\FiscalOperationRepository;
use PDO;
use RuntimeException;

final class IssuedOrderManagementService
{
    public function __construct(private PDO $pdo, private FiscalOperationRepository $orders) {}

    public function clone(int $orderId, int $actor): int
    {
        $source = $this->orders->orderWithTransport($orderId);
        $header = [
            'tipo'=>$source['operation_type']==='ENTRY'?'entrada':'saida', 'establishment_id'=>$source['establishment_id'],
            'cliente_id'=>$source['person_id'], 'fornecedor_id'=>$source['person_id'], 'codigo_interno'=>trim((string)$source['internal_code']).'-CLONE',
            'data_venda'=>date('Y-m-d'), 'operation_nature'=>$source['operation_nature'], 'fiscal_model'=>$source['fiscal_model'],
            'purpose'=>$source['purpose'], 'final_consumer'=>$source['final_consumer'], 'presence_indicator'=>$source['presence_indicator'],
            'condicao_pagamento'=>$source['payment_condition'], 'payment_method'=>$source['payment_method'], 'vencimento'=>$source['first_due_date'],
            'observacoes'=>$source['notes'], 'vendedor_id'=>$source['seller_id'], 'transportadora_id'=>$source['carrier_id'],
            'motorista_id'=>$source['driver_id'], 'freight_mode'=>$source['freight_mode'], 'desconto_valor'=>$source['discount_amount'],
            'frete'=>$source['freight_amount'], 'seguro'=>$source['insurance_amount'], 'outras_despesas'=>$source['other_amount'],
            'vehicle_plate'=>$source['vehicle_plate']??'', 'vehicle_state'=>$source['vehicle_state']??'', 'vehicle_rntc'=>$source['vehicle_rntc']??'',
            'volume_quantity'=>$source['volume_quantity']??'', 'volume_species'=>$source['volume_species']??'', 'volume_brand'=>$source['volume_brand']??'',
            'volume_numbering'=>$source['volume_numbering']??'', 'gross_weight'=>$source['gross_weight']??'', 'net_weight'=>$source['net_weight']??'',
        ];
        $this->orders->assertOrderParties($header);
        $items = array_map(static fn(array $item): array => ['produto_id'=>$item['id'], 'quantidade'=>$item['quantity'], 'preco_unitario'=>$item['unit_price'], 'desconto'=>$item['discount_amount']], $source['items']);
        $clone = $this->orders->saveOrderWithTransport(0, $header, $items, $actor);
        error_log("ORDER_CLONED tenant={$this->orders->tenantId()} source={$orderId} clone={$clone} actor={$actor}");
        return $clone;
    }

    /** False means an identical repeated request found the order already deleted. */
    public function delete(int $orderId, int $actor): bool
    {
        return $this->orders->transaction(function () use ($orderId, $actor): bool {
            $exists = $this->pdo->prepare('SELECT id FROM fiscal_orders WHERE id=? AND tenant_id=? FOR UPDATE');
            $exists->execute([$orderId, $this->orders->tenantId()]);
            if (!$exists->fetchColumn()) {
                error_log("ORDER_DELETE_IDEMPOTENT tenant={$this->orders->tenantId()} order={$orderId} actor={$actor}");
                return false;
            }
            $docs = $this->pdo->prepare('SELECT id,status FROM fiscal_documents WHERE tenant_id=? AND source_order_id=? FOR UPDATE');
            $docs->execute([$this->orders->tenantId(), $orderId]);
            $documents = $docs->fetchAll(PDO::FETCH_ASSOC);
            if ($documents) {
                $ids = array_column($documents, 'id');
                $reservations = $this->pdo->prepare('SELECT COUNT(*) FROM fiscal_number_reservations WHERE tenant_id=? AND fiscal_document_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
                $reservations->execute([$this->orders->tenantId(), ...$ids]);
                $code = (int)$reservations->fetchColumn() > 0 ? 'ORDER_HAS_FISCAL_RESERVATION' : 'ORDER_HAS_FISCAL_DOCUMENT';
                error_log("ORDER_DELETE_BLOCKED tenant={$this->orders->tenantId()} order={$orderId} code={$code} actor={$actor}");
                throw new RuntimeException($code);
            }
            $mirrors = $this->pdo->prepare('SELECT COUNT(*) FROM fiscal_mirrors WHERE tenant_id=? AND source_order_id=?');
            $mirrors->execute([$this->orders->tenantId(), $orderId]);
            if ((int)$mirrors->fetchColumn() > 0) throw new RuntimeException('ORDER_HAS_DEPENDENCIES');
            $this->pdo->prepare('DELETE FROM fiscal_order_items WHERE order_id=?')->execute([$orderId]);
            $deleted = $this->pdo->prepare('DELETE FROM fiscal_orders WHERE id=? AND tenant_id=?');
            $deleted->execute([$orderId, $this->orders->tenantId()]);
            if ($deleted->rowCount() !== 1) throw new RuntimeException('ORDER_DELETE_CONCURRENT');
            error_log("ORDER_DELETED tenant={$this->orders->tenantId()} order={$orderId} actor={$actor}");
            return true;
        });
    }
}
