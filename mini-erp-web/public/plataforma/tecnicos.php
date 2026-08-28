<?php
declare(strict_types=1);

use MiniErp\Repositories\PlatformAdminRepository;

require_once __DIR__.'/_context.php';
require_once __DIR__.'/_layout.php';
[$connection,,,$identity]=requireAuthorizedPlatformContext();
$repository=new PlatformAdminRepository($connection);
$_SESSION['platform_tech_csrf']??=bin2hex(random_bytes(32));$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    try{
        if($identity->getRole()!=='SUPER_ADMIN')throw new DomainException('Somente SUPER_ADMIN pode criar uma chave técnica global.');
        if(!hash_equals((string)$_SESSION['platform_tech_csrf'],(string)($_POST['csrf_token']??'')))throw new DomainException('Sessão expirada.');
        $name=trim((string)($_POST['name']??''));$email=strtolower(trim((string)($_POST['email']??'')));$password=(string)($_POST['password']??'');
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Nome e e-mail válidos são obrigatórios.');
        if(strlen($password)<10||!preg_match('/[A-Za-z]/',$password)||!preg_match('/[0-9]/',$password))throw new InvalidArgumentException('A senha deve ter 10 caracteres ou mais, com letra e número.');
        if($repository->findByEmail($email))throw new DomainException('Este e-mail já está cadastrado no Control-Plane.');
        $repository->create($name,$email,password_hash($password,PASSWORD_DEFAULT),'GLOBAL_TECH','PLATFORM_UI');
        header('Location: /plataforma/tecnicos.php?created=1');exit;
    }catch(DomainException|InvalidArgumentException $exception){$error=$exception->getMessage();}
}
$users=$repository->listTechnicalUsers();renderPlatformStart($identity,'Técnicos globais','Técnicos e testers');?>
<div class="page-title"><div><p class="eyebrow">Acesso mestre auditável</p><h1>Técnicos globais</h1><p class="muted">Estas contas acessam o Control-Plane e todas as empresas atuais e futuras.</p></div></div>
<?php if(isset($_GET['created'])):?><p class="message success">Conta técnica global criada.</p><?php endif;if($error!==''):?><p class="message error" role="alert"><?=platformEscape($error)?></p><?php endif;?>
<?php if($identity->getRole()==='SUPER_ADMIN'):?><section class="panel"><h2>Nova chave técnica</h2><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=platformEscape($_SESSION['platform_tech_csrf'])?>"><label>Nome<input name="name" required maxlength="150"></label><label>E-mail<input type="email" name="email" required maxlength="190"></label><label>Senha inicial<input type="password" name="password" required minlength="10" autocomplete="new-password"></label><button class="btn" type="submit">Criar técnico global</button></form></section><?php endif;?>
<section class="panel"><h2>Contas com acesso global</h2><div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Papel</th><th>Status</th><th>Último login</th></tr></thead><tbody><?php foreach($users as$user):?><tr><td><?=platformEscape($user['name'])?></td><td><?=platformEscape($user['email'])?></td><td><?=platformEscape($user['role'])?></td><td><?=$user['active']?'Ativo':'Inativo'?></td><td><?=platformEscape($user['last_login_at']??'Nunca')?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php renderPlatformEnd();
