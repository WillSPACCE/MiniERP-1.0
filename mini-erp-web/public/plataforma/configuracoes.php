<?php
declare(strict_types=1);

use MiniErp\Repositories\{PlatformAdminRepository, PlatformServerSettingsRepository};

require_once __DIR__.'/_context.php';
[$pdo,,, $identity] = requireAuthorizedPlatformContext();
require_once __DIR__.'/_layout.php';

$role = $identity->getRole();
$canEdit = in_array($role, ['SUPER_ADMIN','DATABASE_ADMIN'], true);
$repo = new PlatformServerSettingsRepository($pdo);
$_SESSION['platform_server_settings_csrf'] ??= bin2hex(random_bytes(32));
$success=''; $error='';
if (($_SERVER['REQUEST_METHOD']??'GET') === 'POST') {
    try {
        if (!$canEdit) throw new DomainException('Seu perfil possui acesso somente para consulta.');
        if (!hash_equals((string)$_SESSION['platform_server_settings_csrf'], (string)($_POST['csrf_token']??''))) throw new DomainException('Sessão expirada. Atualize a página.');
        $repo->save($_POST, $identity->getUserId());
        (new PlatformAdminRepository($pdo))->audit($identity->getUserId(),'SERVER_SETTINGS_UPDATED','platform','server',$_SERVER['REMOTE_ADDR']??null,['keys'=>array_keys($_POST)]);
        $success='Configurações de infraestrutura salvas.';
    } catch (Throwable $e) { $error=$e->getMessage(); }
}
$settings=$repo->all();
$cfg=require __DIR__.'/../../config.php'; $db=$cfg['db']??[];
$dbOk=false; try{$pdo->query('SELECT 1');$dbOk=true;}catch(Throwable){}
$cloudflared=$settings['cloudflared_path'];
$cloudflaredFromPath=$cloudflared==='cloudflared' ? trim((string)@shell_exec(PHP_OS_FAMILY==='Windows'?'where.exe cloudflared 2>NUL':'command -v cloudflared 2>/dev/null')) : '';
$cloudflaredFound=$cloudflared!=='' && (is_file($cloudflared) || $cloudflaredFromPath!=='');
$tokenConfigured=getenv($settings['cloudflare_token_env'])!==false && trim((string)getenv($settings['cloudflare_token_env']))!=='';
$backupWritable=is_dir($settings['backup_root']) && is_writable($settings['backup_root']);
$futureHostname=trim($settings['planned_subdomain'].'.'.$settings['planned_domain'],'.');
$domainPrepared=$settings['planned_domain']!=='';
$cloudflarePrepared=$settings['cloudflare_account_id']!==''&&$settings['cloudflare_zone_id']!=='';
$tunnelPrepared=$settings['cloudflare_tunnel_id']!==''&&$tokenConfigured;
$e=static fn($v)=>platformEscape($v);
renderPlatformStart($identity,'Configurações','Infraestrutura e servidores');
?>
<div class="page-title"><div><p class="eyebrow">Control-Plane</p><h1>Configurações</h1><p class="muted">Infraestrutura, publicação externa, backups e manutenção.</p></div></div>
<?php if($success):?><p class="message success"><?=$e($success)?></p><?php endif?><?php if($error):?><p class="message error"><?=$e($error)?></p><?php endif?>
<section class="server-health-grid" aria-label="Estado dos serviços">
 <article class="server-health-card"><span class="server-dot <?=$dbOk?'ok':'bad'?>"></span><div><small>Banco principal</small><strong><?=$dbOk?'Conectado':'Indisponível'?></strong><span><?=$e(($db['host']??'—').':'.($db['port']??'—'))?></span></div></article>
 <article class="server-health-card"><span class="server-dot <?=$cloudflaredFound?'ok':'warn'?>"></span><div><small>Cloudflare Tunnel</small><strong><?=$cloudflaredFound?'Executável configurado':'Revisar caminho'?></strong><span><?=$tokenConfigured?'Token disponível no ambiente':'Token não exposto/configurado'?></span></div></article>
 <article class="server-health-card"><span class="server-dot <?=$backupWritable?'ok':'warn'?>"></span><div><small>Backups</small><strong><?=$backupWritable?'Diretório gravável':'Diretório pendente'?></strong><span><?=$e($settings['backup_root'])?></span></div></article>
 <article class="server-health-card"><span class="server-dot ok"></span><div><small>Runtime</small><strong>PHP <?=PHP_VERSION?></strong><span><?=$e(PHP_OS_FAMILY)?> · <?=date_default_timezone_get()?></span></div></article>
</section>
<form method="post" class="server-settings-form">
 <input type="hidden" name="csrf_token" value="<?=$e($_SESSION['platform_server_settings_csrf'])?>">
 <section class="panel settings-section domain-readiness"><div class="panel-header"><div><h2>Preparação do domínio futuro</h2><span class="muted">Preencha aos poucos. Nada será publicado enquanto o domínio e o túnel permanente não existirem.</span></div></div>
  <div class="domain-preview"><span>Endereço planejado</span><strong>https://<?=$e($futureHostname?:'app.seudominio.com.br')?></strong></div>
  <ol class="readiness-list">
   <li class="<?=$domainPrepared?'done':''?>"><span><?=$domainPrepared?'✓':'1'?></span><div><strong>Comprar e informar o domínio</strong><small><?=$domainPrepared?'Domínio planejado: '.$e($settings['planned_domain']):'Exemplo: minierpweb.com.br'?></small></div></li>
   <li class="<?=$cloudflarePrepared?'done':''?>"><span><?=$cloudflarePrepared?'✓':'2'?></span><div><strong>Adicionar o domínio à Cloudflare</strong><small>Depois copie o Account ID e o Zone ID.</small></div></li>
   <li class="<?=$tunnelPrepared?'done':''?>"><span><?=$tunnelPrepared?'✓':'3'?></span><div><strong>Criar um túnel permanente</strong><small>O token fica somente no servidor, em variável de ambiente.</small></div></li>
   <li><span>4</span><div><strong>Publicar e testar HTTPS</strong><small>Validar login, cookies, uploads, DANFE e acesso mobile antes da troca.</small></div></li>
  </ol>
  <div class="settings-grid">
   <label>Domínio que pretende comprar<input name="planned_domain" value="<?=$e($settings['planned_domain'])?>" placeholder="minierpweb.com.br"><small>Não inclua https:// nem caminhos.</small></label>
   <label>Subdomínio do sistema<input name="planned_subdomain" value="<?=$e($settings['planned_subdomain'])?>" placeholder="app"><small>Exemplos: app, painel ou erp.</small></label>
   <label>Empresa onde comprou o domínio<input name="domain_registrar" value="<?=$e($settings['domain_registrar'])?>" placeholder="Registro.br, Cloudflare Registrar..."></label>
   <label>E-mail técnico responsável<input type="email" name="infrastructure_contact_email" value="<?=$e($settings['infrastructure_contact_email'])?>" placeholder="tecnico@seudominio.com.br"></label>
  </div>
 </section>
 <section class="panel settings-section"><div class="panel-header"><div><h2>Publicação e Cloudflare</h2><span class="muted">O token permanece em variável de ambiente e nunca é salvo no banco.</span></div></div><div class="settings-grid">
  <label>URL pública<input type="url" name="public_base_url" value="<?=$e($settings['public_base_url'])?>" placeholder="https://erp.exemplo.com"></label>
  <label>Hostname Cloudflare<input name="cloudflare_hostname" value="<?=$e($settings['cloudflare_hostname'])?>" placeholder="erp.exemplo.com"></label>
  <label>ID ou nome do túnel<input name="cloudflare_tunnel_id" value="<?=$e($settings['cloudflare_tunnel_id'])?>" placeholder="mini-erp-production"></label>
  <label>Cloudflare Account ID<input name="cloudflare_account_id" value="<?=$e($settings['cloudflare_account_id'])?>" placeholder="Disponível no painel da Cloudflare"></label>
  <label>Cloudflare Zone ID<input name="cloudflare_zone_id" value="<?=$e($settings['cloudflare_zone_id'])?>" placeholder="Disponível na página do domínio"></label>
  <label>Caminho do cloudflared<input name="cloudflared_path" value="<?=$e($settings['cloudflared_path'])?>" placeholder="C:\\cloudflared\\cloudflared.exe"></label>
  <label>Variável do token<input name="cloudflare_token_env" value="<?=$e($settings['cloudflare_token_env'])?>" autocomplete="off"><small>Salve apenas o nome, por exemplo CLOUDFLARE_TUNNEL_TOKEN.</small></label>
  <label class="settings-toggle"><input type="checkbox" name="force_https" value="1" <?=$settings['force_https']==='1'?'checked':''?>><span><strong>Forçar HTTPS</strong><small>Recomendado quando o domínio permanente estiver ativo.</small></span></label>
  <label class="settings-toggle"><input type="checkbox" name="cloudflare_access_enabled" value="1" <?=$settings['cloudflare_access_enabled']==='1'?'checked':''?>><span><strong>Cloudflare Access</strong><small>Proteção adicional para o painel administrativo.</small></span></label>
 </div></section>
 <section class="panel settings-section"><div class="panel-header"><div><h2>Backups e continuidade</h2><span class="muted">Políticas administrativas do servidor.</span></div></div><div class="settings-grid">
  <label>Diretório de backups<input name="backup_root" value="<?=$e($settings['backup_root'])?>"></label>
  <label>Retenção em dias<input type="number" min="1" max="3650" name="backup_retention_days" value="<?=$e($settings['backup_retention_days'])?>"></label>
  <label class="settings-toggle"><input type="checkbox" name="maintenance_mode" value="1" <?=$settings['maintenance_mode']==='1'?'checked':''?>><span><strong>Modo de manutenção</strong><small>Deixa preparada a política de indisponibilidade controlada.</small></span></label>
  <label class="settings-wide">Mensagem de manutenção<textarea name="maintenance_message" rows="3" maxlength="500"><?=$e($settings['maintenance_message'])?></textarea></label>
 </div></section>
 <section class="panel settings-section readonly-settings"><div class="panel-header"><div><h2>Ambiente detectado</h2><span class="muted">Informações sensíveis permanecem ocultas e não podem ser alteradas nesta tela.</span></div></div><dl><div><dt>Banco</dt><dd><?=$e($db['database']??'—')?></dd></div><div><dt>Host</dt><dd><?=$e($db['host']??'—')?></dd></div><div><dt>Porta</dt><dd><?=$e($db['port']??'—')?></dd></div><div><dt>Servidor web</dt><dd><?=$e($_SERVER['SERVER_SOFTWARE']??'PHP local')?></dd></div></dl></section>
 <div class="settings-actions"><span class="muted"><?=$canEdit?'Alterações são registradas na auditoria.':'Seu perfil está em modo consulta.'?></span><button class="btn" type="submit" <?=$canEdit?'':'disabled'?>>Salvar configurações</button></div>
</form>
<?php renderPlatformEnd();
