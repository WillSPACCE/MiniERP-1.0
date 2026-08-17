<?php
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $cnpj = $_REQUEST['cnpj'] ?? '';
    if ($cnpj === '') {
        http_response_code(400);
        echo json_encode(['error' => 'cnpj_required']);
        exit;
    }
    $repo = new Repository();
    $data = $repo->fetchCnpjData($cnpj);
    if ($data === null) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }
    echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
