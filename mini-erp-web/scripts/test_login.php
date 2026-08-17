<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
chdir(__DIR__ . '/..');
$_GET = ['page' => 'login'];
$_POST = [];
include 'public/index.php';
