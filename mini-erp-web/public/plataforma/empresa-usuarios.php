<?php

declare(strict_types=1);

require_once __DIR__ . '/_tenant_users.php';
[$identity, $tenant, $service, $administrativeContext] = requireTenantUserContext();
$tenantId = $administrativeContext->getSelectedTenantId();
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        requirePlatformUserCsrf();
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;
        $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
        $service->setStatus($administrativeContext, $userId, $status);
        header('Location: /plataforma/empresa-usuarios.php?id=' . $tenantId . '&status_changed=1'); exit;
    } catch (DomainException|InvalidArgumentException $exception) { $error = $exception->getMessage(); }
}
$users = $service->list($administrativeContext);
renderPlatformStart($identity, 'Usuários', 'Empresas → ' . ($tenant['nome_fantasia'] ?? '') . ' → Usuários');
?>
<div class="page-title"><div><p class="eyebrow">Empresa: <?= platformEscape($tenant['nome_fantasia']) ?></p><h1>Usuários</h1><p class="muted">Tenant #<?= $tenantId ?> · Status: <?= platformEscape($tenant['status']) ?></p></div><a class="btn" href="/plataforma/empresa-usuario-novo.php?id=<?= $tenantId ?>">+ Novo usuário</a></div>
<?php if ($error !== ''): ?><p class="message error" role="alert"><?= platformEscape($error) ?></p><?php endif; ?>
<?php if (isset($_GET['created'])): ?><p class="message success">Usuário criado com sucesso.</p><?php endif; ?><?php if (isset($_GET['updated'])): ?><p class="message success">Usuário atualizado com sucesso.</p><?php endif; ?><?php if (isset($_GET['password'])): ?><p class="message success">Senha redefinida com sucesso.</p><?php endif; ?><?php if (isset($_GET['status_changed'])): ?><p class="message success">Status do usuário atualizado.</p><?php endif; ?>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= platformEscape($user['nome']) ?></td><td><?= platformEscape($user['email']) ?></td><td><?= ($user['role'] ?? '') === 'admin' ? 'Administrador da empresa' : 'Usuário' ?></td><td><span class="badge <?= ($user['status'] ?? '') === 'ativo' ? 'badge-active' : 'badge-archived' ?>"><?= platformEscape($user['status']) ?></span></td><td><div class="actions"><a class="btn small secondary" href="/plataforma/empresa-usuario-editar.php?id=<?= $tenantId ?>&amp;user_id=<?= (int) $user['id'] ?>">Editar</a><a class="btn small ghost" href="/plataforma/empresa-usuario-senha.php?id=<?= $tenantId ?>&amp;user_id=<?= (int) $user['id'] ?>">Redefinir senha</a><form method="post"><input type="hidden" name="csrf_token" value="<?= platformEscape($_SESSION['platform_user_csrf']) ?>"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><input type="hidden" name="status" value="<?= ($user['status'] ?? '') === 'ativo' ? 'inativo' : 'ativo' ?>"><button class="btn small <?= ($user['status'] ?? '') === 'ativo' ? 'danger' : '' ?>" type="submit"><?= ($user['status'] ?? '') === 'ativo' ? 'Desativar' : 'Ativar' ?></button></form></div></td></tr><?php endforeach; ?><?php if ($users === []): ?><tr><td colspan="5">Nenhum usuário cadastrado para esta empresa.</td></tr><?php endif; ?></tbody></table></div></section><p><a href="/plataforma/empresa.php?id=<?= $tenantId ?>">← Voltar para a empresa</a></p>
<?php renderPlatformEnd(); ?>
