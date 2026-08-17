<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
chdir(__DIR__ . '/..');
// Simula POST de login
$_GET = ['page' => 'login'];
$_POST = ['action' => 'login', 'email' => 'admin@localhost', 'senha' => 'admin'];
// limpa sessão anterior
if (session_status() === PHP_SESSION_ACTIVE) session_unset();
// Simula variáveis de servidor que o index.php espera
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/login.php';
include 'public/index.php';
// Após include, verifica se a sessão foi criada
var_dump($_SESSION);
