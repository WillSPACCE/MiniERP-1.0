<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';
$repo = new Repository();

$message = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $user = $repo->findUsuarioByEmail($email);
    if (!$user) {
        $message = 'Nenhum usuário encontrado com este e-mail.';
    } else {
    // gerar código numérico de 6 dígitos e persistir
    $code = (string) random_int(100000, 999999);
    $repo->createPasswordReset($email, $code);
    // enviar por e-mail
    try {
      require_once __DIR__ . '/../app/Mailer.php';
      $m = new Mailer();
      $m->sendPasswordReset($email, $code);
      $message = 'Código enviado para seu e-mail. Verifique sua caixa de entrada.';
    } catch (Throwable $e) {
      $message = 'Erro ao enviar e-mail, tente novamente mais tarde.';
    }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Esqueci minha senha — MiniERPWeb</title>
  <link rel="stylesheet" href="/assets/style.css">
  <link rel="stylesheet" href="/assets/login.css">
</head>
<body class="page-ready">
  <main class="login-shell">
    <div class="container auth-container">
      <div class="form-card">
        <h1>Recuperar senha</h1>
        <p>Digite o e-mail da sua conta. Você receberá um código por e-mail para redefinir a senha.</p>
        <?php if($message): ?>
          <div class="alert" role="status"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" action="/forgot.php" class="auth-form">
          <label> E-mail
            <input type="email" name="email" placeholder="seu@exemplo.com" required>
          </label>
          <div class="form-actions" style="justify-content:space-between;">
            <a href="/login.php" class="ghost" style="color:#1565c0;text-decoration:none;align-self:center;">Voltar ao login</a>
            <button class="btn primary" type="submit">Enviar código</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</body>
</html>
