<?php declare(strict_types=1); ?>
<section class="md-shell xml-catalog" data-xml-catalog data-csrf="<?=htmlspecialchars((string)($_SESSION['erp_fiscal_csrf']??''))?>">
  <header class="md-page-head"><div><p class="eyebrow">Cadastros</p><h2>Cadastro por XML</h2><p>Leia uma NF-e para cadastrar a empresa como cliente ou fornecedor e distribuir produtos e impostos aos seus cadastros.</p></div></header>
  <nav class="md-tabs" aria-label="Cadastros"><a href="?page=cadastro&tab=pessoas">Pessoas</a><a href="?page=cadastro&tab=produtos">Produtos</a><a href="?page=cadastro&tab=cfops">CFOPs</a><a class="active" href="?page=cadastro&tab=xml">Cadastro por XML</a></nav>
  <div class="xml-upload-card">
    <label class="xml-drop"><svg class="erp-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5Z"/><path d="M14 2v6h6M12 18v-6M9 15l3-3 3 3"/></svg><strong>Selecionar XML da NF-e</strong><span>Arquivo XML, máximo de 10 MB</span><input type="file" accept=".xml,text/xml,application/xml" data-xml-file></label>
    <div class="xml-page-message" role="status" aria-live="polite" hidden></div>
  </div>
  <section class="xml-analysis" data-xml-analysis hidden>
    <div class="xml-analysis-head"><div><span>Análise concluída</span><h3 data-xml-title></h3></div><button type="button" class="btn secondary" data-change-xml>Trocar XML</button></div>
    <div class="xml-company-card" data-xml-company></div>
    <fieldset class="xml-role-choice"><legend>Onde cadastrar esta empresa?</legend><label><input type="radio" name="xml_party_type" value="fornecedor" checked><span><strong>Fornecedor</strong><small>Será mostrado em Cadastro → Pessoas, com vínculo de fornecedor.</small></span></label><label><input type="radio" name="xml_party_type" value="cliente"><span><strong>Cliente</strong><small>Será mostrado em Cadastro → Pessoas, com vínculo de cliente.</small></span></label></fieldset>
    <div class="xml-preview-grid"><article><span>Produtos encontrados</span><strong data-xml-products-count>0</strong></article><article><span>Produtos novos</span><strong data-xml-new-count>0</strong></article><article><span>CFOP do XML</span><strong data-xml-cfop>—</strong></article><article><span>Total da nota</span><strong data-xml-total>—</strong></article></div>
    <div class="xml-table-wrap"><table><thead><tr><th>Código</th><th>Produto</th><th>NCM</th><th>CFOP</th><th>ICMS</th><th>PIS</th><th>COFINS</th><th>Destino</th></tr></thead><tbody data-xml-products></tbody></table></div>
    <footer class="xml-register-footer"><p>Nenhuma nota ou movimentação de estoque será criada.</p><button type="button" class="btn primary" data-register-xml>Cadastrar empresa, produtos e impostos</button></footer>
  </section>
</section>
<script src="<?=htmlspecialchars($assetUrl('xml-catalog.js'))?>"></script>
