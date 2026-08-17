<?php
$require = require __DIR__ . '/../app/Database.php';
require __DIR__ . '/../app/Repository.php';
$repo = new Repository();
$repo->saveCompany(['nome_fantasia' => 'Default Tenant', 'slug' => 'default-tenant']);
echo "Default tenant criado\n";
