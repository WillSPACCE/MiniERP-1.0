<?php
declare(strict_types=1);

use MiniErp\Repositories\PlatformAdminRepository;

require_once __DIR__.'/_context.php';
require_once __DIR__.'/_layout.php';
[$connection,,,$identity]=requireAuthorizedPlatformContext();
$repository=new PlatformAdminRepository($connection);
$_SESSION['platform_tech_csrf']??=bin2hex(random_bytes(32));
$error='';

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        if($identity->getRole()!=='SUPER_ADMIN')throw new DomainException('Somente SUPER_ADMIN pode administrar contas técnicas globais.');
        if(!hash_equals((string)$_SESSION['platform_tech_csrf'],(string)($_POST['csrf_token']??'')))throw new DomainException('Sessão expirada.');
        $action=(string)($_POST['action']??'create');
        $targetId=(int)($_POST['target_id']??0);
        $actorId=$identity->getUserId();
        $ip=$_SERVER['REMOTE_ADDR']??null;

        if($action!=='create'){
            if($targetId<1||$targetId===$actorId)throw new DomainException('Esta conta não pode ser alterada por esta ação.');
            $target=$repository->findById($targetId);
            if(!$target||$target['role']!=='GLOBAL_TECH')throw new DomainException('Conta técnica global não encontrada.');
            if($action==='toggle_status'){
                $repository->setTechnicalUserActive($targetId,(int)($_POST['active']??0)===1,$actorId,$ip);
                header('Location: /plataforma/tecnicos.php?status_changed=1');exit;
            }
            if($action==='reset_password'){
                $password=(string)($_POST['password']??'');$confirmation=(string)($_POST['password_confirmation']??'');
                if($password!==$confirmation)throw new InvalidArgumentException('A confirmação da senha não confere.');
                if(strlen($password)<10||!preg_match('/[A-Za-z]/',$password)||!preg_match('/[0-9]/',$password))throw new InvalidArgumentException('A senha deve ter 10 caracteres ou mais, com letra e número.');
                $repository->resetTechnicalUserPassword($targetId,password_hash($password,PASSWORD_DEFAULT),$actorId,$ip);
                header('Location: /plataforma/tecnicos.php?password_reset=1');exit;
            }
            if($action==='delete'){
                $repository->deleteTechnicalUser($targetId,$actorId,$ip);
                header('Location: /plataforma/tecnicos.php?deleted=1');exit;
            }
            throw new DomainException('Ação administrativa inválida.');
        }

        $name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));$password=(string)($_POST['password']??'');
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Nome e e-mail válidos são obrigatórios.');
        if(strlen($password)<10||!preg_match('/[A-Za-z]/',$password)||!preg_match('/[0-9]/',$password))throw new InvalidArgumentException('A senha deve ter 10 caracteres ou mais, com letra e número.');
        if($repository->findByEmail($email))throw new DomainException('Este e-mail já está cadastrado no Control-Plane.');
        $repository->create($name,$email,password_hash($password,PASSWORD_DEFAULT),'GLOBAL_TECH','PLATFORM_UI');
        header('Location: /plataforma/tecnicos.php?created=1');exit;
    }catch(DomainException|InvalidArgumentException|RuntimeException $exception){$error=$exception->getMessage()==='TECHNICAL_USER_NOT_FOUND'?'Conta técnica global não encontrada.':$exception->getMessage();}
}

$users=$repository->listTechnicalUsers();
renderPlatformStart($identity,'Técnicos globais','Técnicos e testers');
?>
<div class="page-title"><div><p class="eyebrow">Acesso mestre auditável</p><h1>Técnicos globais</h1><p class="muted">Estas contas acessam o Control-Plane e todas as empresas atuais e futuras.</p></div></div>
<?php if(isset($_GET['created'])):?><p class="message success">Conta técnica global criada.</p><?php elseif(isset($_GET['status_changed'])):?><p class="message success">Status da conta técnica atualizado.</p><?php elseif(isset($_GET['password_reset'])):?><p class="message success">Senha da conta técnica redefinida.</p><?php elseif(isset($_GET['deleted'])):?><p class="message success">Conta técnica excluída.</p><?php endif;?>
<?php if($error!==''):?><p class="message error" role="alert"><?=platformEscape($error)?></p><?php endif;?>

<?php if($identity->getRole()==='SUPER_ADMIN'):?>
<section class="panel"><h2>Nova chave técnica</h2><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tech_csrf'])?>"><input type="hidden" name="action" value="create"><label>Nome<input name="name" required maxlength="150"></label><label>E-mail<input type="email" name="email" required maxlength="190"></label><label>Senha inicial<input type="password" name="password" required minlength="10" autocomplete="new-password"></label><button class="btn" type="submit">Criar técnico global</button></form></section>
<?php endif;?>

<section class="panel"><h2>Contas com acesso global</h2><div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Status</th><th>Último login</th><th>Ações</th></tr></thead><tbody>
<?php foreach($users as $user):?>
<tr><td><?=platformEscape($user['name'])?></td><td><?=platformEscape($user['email'])?></td><td><?=platformEscape($user['role'])?></td><td><span class="badge <?= $user['active'] ? 'badge-active' : 'badge-archived' ?>"><?= $user['active'] ? 'Ativo' : 'Bloqueado' ?></span></td><td><?=platformEscape($user['last_login_at']??'Nunca')?></td><td>
<?php if ($identity->getRole()==='SUPER_ADMIN' && $user['role']==='GLOBAL_TECH' && ((int) $user['id'] !== $identity->getUserId())): ?>
<div class="actions tech-actions">
<form method="post"<?= $user['active'] ? ' onsubmit="return confirm(\'Bloquear o acesso desta conta técnica?\');"' : '' ?>><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tech_csrf'])?>"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="target_id" value="<?=(int)$user['id']?>"><input type="hidden" name="active" value="<?= $user['active'] ? '0' : '1' ?>"><button class="btn small <?= $user['active'] ? 'danger' : 'secondary' ?>" type="submit"><?= $user['active'] ? 'Bloquear' : 'Desbloquear' ?></button></form>
<details><summary class="btn small ghost">Resetar senha</summary><form method="post" class="tech-password-form"><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tech_csrf'])?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="target_id" value="<?=(int)$user['id']?>"><label>Nova senha<input type="password" name="password" minlength="10" required autocomplete="new-password"></label><label>Confirmar senha<input type="password" name="password_confirmation" minlength="10" required autocomplete="new-password"></label><button class="btn small" type="submit">Confirmar nova senha</button></form></details>
<form method="post" onsubmit="return confirm('Excluir definitivamente esta conta técnica global?');"><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tech_csrf'])?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="target_id" value="<?=(int)$user['id']?>"><button class="btn small danger" type="submit">Excluir</button></form>
</div>
<?php else:?><span class="muted">Conta protegida</span><?php endif;?>
</td></tr>
<?php endforeach;?>
</tbody></table></div></section>
<?php renderPlatformEnd(); ?>
