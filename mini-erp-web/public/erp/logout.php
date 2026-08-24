<?php
declare(strict_types=1);
if(session_status()===PHP_SESSION_NONE)session_start();
if($_SERVER['REQUEST_METHOD']!=='POST'||!hash_equals((string)($_SESSION['erp_logout_csrf']??''),(string)($_POST['csrf_token']??''))){http_response_code(405);exit('Método ou token inválido.');}
$slug = strtolower(trim((string) ($_SESSION['erp_tenant_slug'] ?? '')));
unset($_SESSION['erp_user_id'],$_SESSION['erp_tenant_id'],$_SESSION['erp_tenant_slug'],$_SESSION['user_id'],$_SESSION['tenant_id'],$_SESSION['current_company_id'],$_SESSION['erp_login_csrf'],$_SESSION['erp_logout_csrf']);
session_regenerate_id(true); header('Location: /login.php' . ($slug !== '' ? '?empresa=' . rawurlencode($slug) : '')); exit;
