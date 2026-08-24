<?php
declare(strict_types=1);

use MiniErp\Repositories\PlatformAdminRepository;

require_once __DIR__ . '/_context.php';
require_once __DIR__ . '/_layout.php';

[$pdo, , , $identity] = requireAuthorizedPlatformContext();
$repo = new PlatformAdminRepository($pdo);

if (empty($_SESSION['platform_account_csrf'])) {
    $_SESSION['platform_account_csrf'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals((string) $_SESSION['platform_account_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'CSRF inválido.';
    } else {
        $record = $repo->findByEmail($identity->getEmail());
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!$record || !password_verify($current, $record['password_hash'])) {
            $error = 'Senha atual inválida.';
        } elseif (!hash_equals($new, $confirm)) {
            $error = 'As novas senhas não conferem.';
        } elseif (strlen($new) < 12 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/\d/', $new)) {
            $error = 'A nova senha não atende à política.';
        } else {
            $repo->changePassword($identity->getUserId(), password_hash($new, PASSWORD_DEFAULT), 'SELF_SERVICE');
            session_regenerate_id(true);
            $message = 'Senha alterada.';
        }
    }
}

renderPlatformStart($identity, 'Minha conta', 'Minha conta');
?>
<div class="page-title">
    <div>
        <h1>Minha conta</h1>
        <p>Administrador: <?= platformEscape($identity->getName()) ?> · <?= platformEscape($identity->getRole()) ?></p>
    </div>
</div>
<?php if ($message): ?>
    <p class="message success"><?= platformEscape($message) ?></p>
<?php endif; ?>
<?php if ($error): ?>
    <p class="message warning"><?= platformEscape($error) ?></p>
<?php endif; ?>
<form method="post" class="panel form-grid">
    <input type="hidden" name="csrf_token" value="<?= platformEscape($_SESSION['platform_account_csrf']) ?>">
    <label>Senha atual
        <input type="password" name="current_password" required>
    </label>
    <label>Nova senha
        <input type="password" name="new_password" required>
    </label>
    <label>Confirmar nova senha
        <input type="password" name="confirm_password" required>
    </label>
    <button class="btn">Alterar senha</button>
</form>
<?php renderPlatformEnd();

