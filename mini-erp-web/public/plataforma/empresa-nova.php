<?php
declare(strict_types=1);
use MiniErp\Repositories\PlatformTenantRepository;
use MiniErp\Services\{CreatePlatformTenantService,FlashFormState,PlatformTenantData};
require_once __DIR__.'/_context.php';require_once __DIR__.'/_layout.php';
[$connection,,$authorizer,$identity]=requireAuthorizedPlatformContext();
$_SESSION['platform_tenant_csrf']??=bin2hex(random_bytes(32));
$values=['razao_social'=>'','nome_fantasia'=>'','cnpj'=>'','slug'=>''];$error='';
$state=FlashFormState::consume($_SESSION);if($state){$values=array_merge($values,$state['old_input']);$error=(string)(reset($state['errors'])?:'Revise os campos.');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 try{if(!hash_equals((string)$_SESSION['platform_tenant_csrf'],(string)($_POST['csrf_token']??'')))throw new RuntimeException('Sessão do formulário expirada. Recarregue a página.');$created=(new CreatePlatformTenantService(new PlatformTenantRepository($connection),$authorizer))->create($identity,PlatformTenantData::fromArray($_POST));$_SESSION['platform_tenant_csrf']=bin2hex(random_bytes(32));header('Location: /plataforma/empresa.php?id='.(int)$created['tenant_id'].'&created=1');exit;}
 catch(Throwable $e){$message=$e instanceof DomainException||$e instanceof InvalidArgumentException||$e instanceof RuntimeException?$e->getMessage():'Não foi possível cadastrar a empresa com segurança.';FlashFormState::store($_SESSION,'platform_tenant_create',$_POST,['_form'=>$message]);header('Location: /plataforma/empresa-nova.php');exit;}
}
renderPlatformStart($identity,'Nova empresa','Empresas → Nova empresa');?>
<div class="page-title"><div><p class="eyebrow">Onboarding empresarial e fiscal</p><h1>Nova empresa</h1><p class="muted">Cadastre a identidade administrativa. O sistema guiará as próximas etapas.</p></div></div>
<?php if($error!==''):?><p class="message error" role="alert"><?=platformEscape($error)?></p><?php endif;?>
<section class="panel"><h2>Etapa 1 — Identificação</h2><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tenant_csrf'])?>"><label>Razão social<input id="platform_razao_social" name="razao_social" maxlength="255" required value="<?=platformEscape($values['razao_social'])?>"></label><label>Nome fantasia<input id="platform_nome_fantasia" name="nome_fantasia" maxlength="255" required value="<?=platformEscape($values['nome_fantasia'])?>"></label><label>CNPJ<span style="display:flex;gap:8px"><input id="platform_cnpj" name="cnpj" maxlength="18" required value="<?=platformEscape($values['cnpj'])?>"><button type="button" id="btn-buscar-cnpj-plataforma" class="btn small">Consultar CNPJ</button></span></label><label>Slug <small>(opcional; normalizado pelo servidor)</small><input name="slug" maxlength="255" value="<?=platformEscape($values['slug'])?>"></label><div class="form-actions"><button class="btn" type="submit">Salvar e continuar</button><a class="btn secondary" href="/plataforma/">Cancelar</a></div></form></section>
<?php renderPlatformEnd(); ?>
