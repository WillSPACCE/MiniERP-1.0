<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();
$authenticated = !empty($_SESSION['erp_user_id']) && !empty($_SESSION['erp_tenant_id']);
header('Location: ' . ($authenticated ? '/?page=dashboard' : '/erp/login.php'));
exit;
