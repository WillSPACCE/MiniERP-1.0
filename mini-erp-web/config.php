<?php

// Arquivo de configuração central da aplicação.
// Aqui ficam as informações de conexão com o banco de dados e outros parâmetros globais.
return [
    'db' => [
        // Driver do banco: agora padrão é MySQL, conforme a configuração do XAMPP.
        'driver' => getenv('DB_DRIVER') ?: 'mysql',

        // Configurações do MySQL (usadas se o driver for mysql).
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_NAME') ?: 'mini_erp',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',

        // Caminho do arquivo SQLite local do projeto.
        'sqlite_path' => __DIR__ . '/database/mini_erp.sqlite',
    ],
    'mail' => [
        // Método: 'mail' para PHP mail(), ou 'disabled' para não enviar.
        'method' => getenv('MAIL_METHOD') ?: 'mail',
        'from' => getenv('MAIL_FROM') ?: 'no-reply@local',
        'reply_to' => getenv('MAIL_REPLYTO') ?: 'no-reply@local',
        // Para SMTP (não implementado por padrão aqui) deixamos placeholders
        'smtp_host' => getenv('SMTP_HOST') ?: '',
        'smtp_port' => getenv('SMTP_PORT') ?: '587',
        'smtp_user' => getenv('SMTP_USER') ?: '',
        'smtp_pass' => getenv('SMTP_PASS') ?: '',
    ],
];
