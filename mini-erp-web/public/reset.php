<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';
$repo = new Repository();

// fluxo: aceitar tanto token via GET (link) quanto código via formulário (email + code)
$token = trim((string)($_GET['token'] ?? ''));
$message = '';
$valid = false;

if ($token !== '') {
  $row = $repo->findPasswordResetByToken($token);
  if ($row) {
    $valid = true;
  } else {
    $message = 'Token inválido ou expirado.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim((string)($_POST['email'] ?? ''));
  $code = trim((string)($_POST['code'] ?? ''));
  $senha = (string)($_POST['senha'] ?? '');
  $senha2 = (string)($_POST['senha2'] ?? '');

  if ($senha === '' || $senha !== $senha2) {
    $message = 'Senhas não conferem ou estão vazias.';
  } else {
    // se vier email+code, busca por email+token
    if ($email !== '' && $code !== '') {
      $row = $repo->findPasswordResetByEmailAndToken($email, $code);
      if ($row) {
        if ($repo->consumePasswordReset($code, $senha)) {
          header('Location: /login.php');
          exit;
        } else {
          $message = 'Não foi possível redefinir a senha. Tente novamente.';
        }
      } else {
        $message = 'Código inválido ou expirado.';
      }
    } elseif (!empty($_POST['token'])) {
      $token = trim((string)($_POST['token'] ?? ''));
      if ($repo->consumePasswordReset($token, $senha)) {
        header('Location: /login.php');
        exit;
      } else {
        $message = 'Não foi possível redefinir a senha (token inválido).';
      }
    } else {
      $message = 'Dados insuficientes para redefinir a senha.';
    }
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Redefinir senha — MiniERPWeb</title>
  <link rel="stylesheet" href="/assets/style.css">
  <link rel="stylesheet" href="/assets/login.css">
</head>
<body class="page-ready">
  <main class="login-shell">
    <div class="container auth-container">
      <div class="form-card">
        <h1>Redefinir senha</h1>
        <?php if($message): ?><div class="alert" role="alert"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if(!$valid): ?>
          <div>Token inválido ou expirado. Solicite uma nova redefinição em <a href="/forgot.php">Recuperar senha</a>.</div>
        <?php else: ?>
          <form method="POST" action="/reset.php" class="auth-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label> Nova senha
              <input type="password" name="senha" placeholder="Nova senha" required>
            </label>
            <label> Repita a nova senha
              <input type="password" name="senha2" placeholder="Repita a nova senha" required>
            </label>
            <div class="form-actions" style="justify-content:space-between;">
              <a href="/login.php" class="ghost" style="color:#1565c0;text-decoration:none;align-self:center;">Voltar ao login</a>
              <button class="btn primary" type="submit">Redefinir</button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </main>
</body>
</html>
