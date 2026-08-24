<?php
declare(strict_types=1);
function renderEstablishmentForm(array $data,string $csrfToken,string $action='save_establishment'):void
{
 $e=static fn(string $key,string $default=''):string=>htmlspecialchars((string)($data[$key]??$default),ENT_QUOTES,'UTF-8'); ?>
<form method="post" class="form-grid establishment-form">
<input type="hidden" name="action" value="<?= htmlspecialchars($action,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8') ?>">
<h3 class="form-section">Identificação</h3>
<label>CNPJ <small>Identificação do emitente.</small><span style="display:flex;gap:8px;align-items:center"><input id="establishment_tax_id" name="tax_id" maxlength="18" value="<?= $e('tax_id',$data['cnpj']??'') ?>" required><button type="button" id="btn-buscar-cnpj-estabelecimento" class="btn small">Buscar CNPJ</button></span></label>
<label>Razão social<input id="establishment_legal_name" name="legal_name" maxlength="150" value="<?= $e('legal_name',$data['razao_social']??'') ?>" required></label>
<label>Nome fantasia<input id="establishment_trade_name" name="trade_name" maxlength="150" value="<?= $e('trade_name',$data['nome_fantasia']??'') ?>"></label>
<h3 class="form-section">Inscrições</h3>
<label>Inscrição estadual <small>Inscrição Estadual do estabelecimento.</small><input name="state_registration" maxlength="30" value="<?= $e('state_registration') ?>" required></label>
<label>IEST <small>Somente quando aplicável.</small><input name="st_registration" maxlength="30" value="<?= $e('st_registration') ?>"></label>
<label>Inscrição municipal <small>Somente quando aplicável.</small><input name="municipal_registration" maxlength="30" value="<?= $e('municipal_registration') ?>"></label>
<label>CNAE <small>Atividade fiscal, quando aplicável.</small><input id="establishment_cnae" name="cnae" maxlength="10" value="<?= $e('cnae') ?>"></label>
<label>CRT <small>Regime tributário utilizado na emissão fiscal.</small><select name="tax_regime_code" required><?php foreach(['1'=>'Simples Nacional','2'=>'Simples Nacional — excesso de sublimite','3'=>'Regime Normal','4'=>'MEI'] as $code=>$label):?><option value="<?= $code ?>" <?= $e('tax_regime_code')===$code?'selected':'' ?>><?= $code ?> — <?= $label ?></option><?php endforeach;?></select></label>
<h3 class="form-section">Endereço</h3>
<label>CEP<input id="establishment_postal_code" name="postal_code" maxlength="9" value="<?= $e('postal_code',$data['cep']??'') ?>" required></label>
<label>Logradouro<input id="establishment_street" name="street" maxlength="150" value="<?= $e('street',$data['logradouro']??'') ?>" required></label>
<label>Número<input id="establishment_number" name="number" maxlength="20" value="<?= $e('number',$data['numero']??'') ?>" required></label>
<label>Complemento <small>Opcional.</small><input id="establishment_complement" name="complement" maxlength="100" value="<?= $e('complement',$data['complemento']??'') ?>"></label>
<label>Bairro<input id="establishment_district" name="district" maxlength="100" value="<?= $e('district',$data['bairro']??'') ?>" required></label>
<label>Código IBGE <small>Código oficial do município utilizado no XML da NF-e.</small><input id="establishment_city_ibge_code" name="city_ibge_code" inputmode="numeric" maxlength="7" value="<?= $e('city_ibge_code',$data['codigo_ibge']??'') ?>" required></label>
<label>Município<input id="establishment_city_name" name="city_name" maxlength="100" value="<?= $e('city_name',$data['municipio']??'') ?>" required></label>
<label>UF<input id="establishment_state" name="state" maxlength="2" value="<?= $e('state',$data['uf']??'') ?>" required></label>
<label>País<input name="country_name" maxlength="60" value="<?= $e('country_name','BRASIL') ?>"></label>
<label>Código do país<input name="country_code" maxlength="4" value="<?= $e('country_code','1058') ?>"></label>
<h3 class="form-section">Contato</h3>
<label>Telefone<input id="establishment_phone" name="phone" maxlength="30" value="<?= $e('phone',$data['telefone']??'') ?>"></label>
<label>E-mail <small>Opcional para o cadastro do emitente.</small><input id="establishment_email" type="email" name="email" maxlength="150" value="<?= $e('email') ?>"></label>
<h3 class="form-section">Prontidão cadastral</h3>
<label>Status do estabelecimento<select name="status"><option value="ativo" <?= $e('status','ativo')==='ativo'?'selected':'' ?>>Ativo</option><option value="inativo" <?= $e('status')==='inativo'?'selected':'' ?>>Inativo</option></select></label>
<div class="form-actions"><button class="btn primary" type="submit">Salvar cadastro fiscal</button></div>
</form><?php }
