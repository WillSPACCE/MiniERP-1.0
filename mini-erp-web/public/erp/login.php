<?php

declare(strict_types=1);

$slug = strtolower(trim(is_string($_GET['empresa'] ?? null) ? $_GET['empresa'] : ''));
$destination = '/login.php' . ($slug !== '' ? '?empresa=' . rawurlencode($slug) : '');
header('Location: ' . $destination, true, 302);
exit;
