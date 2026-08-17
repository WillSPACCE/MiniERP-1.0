<?php
// Página de entrada dedicada. O formulário envia para index.php que processa a ação 'login'.
?>
<?php
// mostra mensagens de erro simples quando redirecionado de `index.php`
$loginError = '';
if (!empty($_GET['error'])) {
    $err = $_GET['error'];
    if ($err === 'invalid') {
        $loginError = 'Credenciais inválidas. Verifique usuário e senha.';
    } elseif ($err === 'inactive') {
        $loginError = 'Usuário inativo. Entre em contato com o administrador.';
        } elseif ($err === 'unverified') {
            $loginError = 'E-mail não verificado. Verifique sua caixa de entrada.';
    }
}
if (!empty($_GET['registered'])) {
    $loginError = 'Conta criada. Verifique seu e-mail para confirmar o acesso.';
}
// If tenant resolved in session, load its data for display
$tenantDisplay = null;
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';
$repo = new Repository();
if (!empty($_SESSION['tenant_id'])) {
    $tenantDisplay = $repo->findCompany((int)$_SESSION['tenant_id']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Mini ERP</title>
    <!-- Favicons and manifest -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/Favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/Favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/Favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/images/Favicon/favicon.ico">
    <link rel="manifest" href="/assets/images/site.webmanifest">
    <meta name="theme-color" content="#1e88e5">
    <meta name="description" content="Acesse o Mini ERP - sistema de gestão">
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="/assets/login.css">
    <script src="/assets/login.js" defer></script>
</head>
<body class="page-ready">
    <main class="login-shell">
        <div class="container" id="container">
            <div class="form-container sign-up-container">
                <form method="POST" action="/?page=save_usuario">
                    <input type="hidden" name="action" value="save_usuario">
                    <h1>Criar Conta</h1>
                    <div class="social-container">
                        <a href="#" class="social">f</a>
                        <a href="#" class="social">G</a>
                        <a href="#" class="social">in</a>
                    </div>
                    <span>ou use seu e-mail para registro</span>
                    <input type="text" name="nome" placeholder="Nome" required />
                    <input type="email" name="email" placeholder="Email" required />
                    <input type="password" name="senha" placeholder="Senha" required />
                    <input type="hidden" name="role" value="user">
                    <button type="submit" class="btn primary" id="signupBtnForm">Registrar</button>
                </form>
            </div>
            <div class="form-container sign-in-container">
                <form method="POST" action="/?page=login">
                    <input type="hidden" name="action" value="login">
                    <h1>Entrar</h1>
                    <?php if($loginError): ?>
                        <div class="login-error" role="alert" style="color:#c62828;margin-bottom:12px;font-weight:700;"><?php echo htmlspecialchars($loginError); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($tenantDisplay)): ?>
                        <div style="margin-bottom:8px;font-weight:700;">Empresa: <?= htmlspecialchars($tenantDisplay['nome_fantasia'] ?? $tenantDisplay['apelido'] ?? '') ?></div>
                        <?php if (!empty($tenantDisplay['logo'])): ?>
                            <div style="margin-bottom:8px;"><img src="<?= htmlspecialchars($tenantDisplay['logo']) ?>" alt="Logo" style="max-height:48px;"></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="social-container">
                        <a href="#" class="social">f</a>
                        <a href="#" class="social">G</a>
                        <a href="#" class="social">in</a>
                    </div>
                    <span>ou use sua conta</span>
                    <input type="email" name="email" placeholder="Email" required />
                    <div class="password-wrap">
                        <input type="password" name="senha" placeholder="Senha" required aria-describedby="togglePassword">
                        <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar senha">👁️</button>
                    </div>
                    <a href="/forgot.php" class="forgot-link">Esqueceu sua senha?</a>
                    <button type="submit" class="btn primary" id="loginBtn">Entrar</button>
                </form>
            </div>
            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h1>Bem de volta!</h1>
                        <p>Para manter-se conectado, faça login com suas informações pessoais</p>
                        <button class="ghost" id="signIn">Entrar</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <a href="/" title="MiniERPWeb" class="overlay-logo-link">
                            <img src="/assets/images/logo_login.png" alt="MiniERPWeb" class="overlay-logo" onerror="this.style.display='none'">
                        </a>
                        <p>Insira seus dados pessoais e comece agora</p>
                        <button class="ghost" id="signUp">Registrar</button>
                    </div>
                </div>
            </div>
            <footer class="login-footer" aria-hidden="false">
                <div class="footer-brand">
                    <a href="/" title="MiniERPWeb">
                        <img src="/assets/images/logo_login.png" alt="MiniERPWeb" class="login-footer-logo" onerror="this.style.display='none'">
                    </a>
                </div>
                <div class="footer-dev">Desenvolvido por <a class="dev-link" href="https://willspacce.netlify.app/" target="_blank" rel="noopener noreferrer">DEV Willyan Martins</a></div>
            </footer>
        </div>
    </main>
</body>
</html>
