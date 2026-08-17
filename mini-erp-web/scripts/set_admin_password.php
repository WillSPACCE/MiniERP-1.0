<?php
require __DIR__ . '/../app/Database.php';
$pdo = Database::getConnection();
	$hash = password_hash('admin', PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE email = :email');
$stmt->execute(['senha' => $hash, 'email' => 'admin@localhost']);
	echo "Senha do admin atualizada para 'admin'\n";
