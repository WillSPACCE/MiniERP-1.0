<?php

declare(strict_types=1);

function renderFiscalPreview(array $document): void
{
    $model = (string) ($document['totals']['model'] ?? '55');
    $issuer = $document['issuer_snapshot'] ?? [];
    $recipient = $document['recipient_snapshot'] ?? [];
    $totals = $document['totals'] ?? [];
    $payment = $document['payment_snapshot'] ?? [];
    $transport = $document['transport_snapshot'] ?? [];
    $compact = $model === '65';
    $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    ?>
    <section class="fiscal-preview <?= $compact ? 'preview-65' : 'preview-55' ?>">
        <div class="preview-watermark">SEM VALOR FISCAL</div>
        <header>
            <b><?= $compact ? 'PRÉVIA DANFC-e — MODELO 65' : 'PRÉVIA DANFE — MODELO 55' ?></b><br>
            <?= $compact ? 'PRÉVIA NFC-e — NÃO TRANSMITIDA À SEFAZ' : 'PRÉVIA / ESPELHO — NÃO TRANSMITIDO À SEFAZ' ?>
            <?php if (!empty($document['source_order_id'])): ?><br>Pedido #<?= (int) $document['source_order_id'] ?> · Espelho v<?= (int) ($document['snapshot_version'] ?? 1) ?><?php endif; ?>
            <?php if (!$compact): ?><br>Tipo: <?= $escape(($totals['operation_type'] ?? 'EXIT') === 'ENTRY' ? 'Entrada' : 'Saída') ?> · Natureza: <?= $escape($totals['operation_nature'] ?? 'Não informada') ?> · Data: <?= $escape($totals['operation_date'] ?? '') ?><?php endif; ?>
        </header>
        <div class="preview-grid">
            <div>
                <h3>Emitente</h3>
                <b><?= $escape($issuer['legal_name'] ?? $issuer['trade_name'] ?? 'Não informado') ?></b><br>
                CNPJ <?= $escape($issuer['tax_id'] ?? '') ?> · IE <?= $escape($issuer['state_registration'] ?? '') ?><br>
                <?= $escape(trim(($issuer['street'] ?? '') . ' ' . ($issuer['number'] ?? ''))) ?>
                <?= $escape(trim(($issuer['district'] ?? '') . ' ' . ($issuer['zip_code'] ?? ''))) ?><br>
                <?= $escape(($issuer['city_name'] ?? '') . '/' . ($issuer['state'] ?? '')) ?>
                <?= !empty($issuer['phone']) ? ' · ' . $escape($issuer['phone']) : '' ?>
            </div>
            <div>
                <h3><?= $compact ? 'Consumidor' : 'Destinatário' ?></h3>
                <b><?= $escape($recipient['nome'] ?? $recipient['legal_name'] ?? 'Não identificado') ?></b><br>
                CPF/CNPJ <?= $escape($recipient['cpf_cnpj'] ?? $recipient['tax_id'] ?? '') ?>
                <?= !empty($recipient['inscricao_estadual']) ? ' · IE ' . $escape($recipient['inscricao_estadual']) : '' ?><br>
                <?= $escape($recipient['endereco'] ?? $recipient['street'] ?? '') ?>
                <?= $escape($recipient['municipio'] ?? $recipient['city_name'] ?? '') ?>
                <?= !empty($recipient['uf']) ? '/' . $escape($recipient['uf']) : '' ?><br>
                Chave de acesso: <b>Ainda não gerada</b><br>
                Protocolo: não disponível
            </div>
        </div>
        <table>
            <thead><tr><th>Produto</th><?php if (!$compact): ?><th>NCM</th><th>CST</th><th>CFOP</th><?php endif; ?><th>Qtd</th><th>UN</th><th>Unit.</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach (($document['items'] ?? []) as $item):
                $product = json_decode($item['product_snapshot_json'] ?? '{}', true) ?: [];
                $tax = json_decode($item['tax_resolution_json'] ?? 'null', true) ?: [];
                ?>
                <tr>
                    <td><?= $escape(trim(($product['codigo'] ?? '') . ' ' . ($product['nome'] ?? ''))) ?></td>
                    <?php if (!$compact): ?><td><?= $escape($product['ncm'] ?? '') ?></td><td><?= $escape($tax['icms']['cst'] ?? $tax['icms']['csosn'] ?? 'Pendente') ?></td><td><?= $escape($tax['cfop'] ?? 'Pendente') ?></td><?php endif; ?>
                    <td><?= $escape($item['quantity_commercial'] ?? '') ?></td><td><?= $escape($product['unidade'] ?? 'UN') ?></td>
                    <td><?= $escape($item['unit_value_commercial'] ?? $item['unit_price'] ?? '') ?></td><td><?= $escape($item['net_total'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="preview-totals">
            Itens <?= count($document['items'] ?? []) ?> · Produtos <?= $escape($totals['products'] ?? '') ?> ·
            Desconto <?= $escape($totals['discount'] ?? '') ?> · Frete <?= $escape($totals['freight'] ?? '') ?> ·
            Outras despesas <?= $escape($totals['other'] ?? '') ?> · <b>Total <?= $escape($totals['grand'] ?? '') ?></b>
        </div>
        <?php if (!$compact): ?>
            <h3>Cálculo dos impostos</h3>
            <p><?= empty($totals['taxes']) ? 'TRIBUTAÇÃO PENDENTE — valores não calculados não são exibidos como zero.' : $escape(json_encode($totals['taxes'], JSON_UNESCAPED_UNICODE)) ?></p>
            <h3>Transporte</h3><p><?= empty($transport) ? 'Não informado no snapshot.' : $escape(json_encode($transport, JSON_UNESCAPED_UNICODE)) ?></p>
            <h3>Dados adicionais</h3><p><?= $escape($totals['notes'] ?? $document['additional_information'] ?? 'Sem informações adicionais no snapshot.') ?></p>
        <?php endif; ?>
        <h3>Pagamento</h3><p><?= empty($payment) ? 'Não informado no snapshot.' : $escape(json_encode($payment, JSON_UNESCAPED_UNICODE)) ?></p>
        <?php if ($compact): ?><div class="qr-unavailable">QR Code disponível após geração fiscal da NFC-e.</div><?php endif; ?>
        <?php if (!empty($document['pending'])): ?><div class="message warning"><b>TRIBUTAÇÃO PENDENTE</b><ul><?php foreach ($document['pending'] as $pending): ?><li><?= $escape($pending) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="preview-actions">
            <button class="btn primary" onclick="window.print()">Imprimir Prévia</button>
            <button disabled>Ver Chave</button><button disabled>Copiar Chave</button><button disabled>Baixar XML</button>
            <small>Disponível após geração do XML fiscal.</small>
        </div>
    </section>
    <?php
}
