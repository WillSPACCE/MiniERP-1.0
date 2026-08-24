<?php
declare(strict_types=1);

use MiniErp\Infrastructure\BrasilApiCnpjProvider;
use MiniErp\Services\CnpjLookupService;
use MiniErp\Services\CnpjLookupException;

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['erp_user_id']) && empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['success' => false, 'error' => 'AUTH_REQUIRED']); exit;
}
try {
    $cnpj = (string) ($_GET['cnpj'] ?? '');
    if ($cnpj === '') {
        http_response_code(400);
        echo json_encode(['error' => 'cnpj_required']);
        exit;
    }
    $now = time();
    $_SESSION['cnpj_lookup_times'] = array_values(array_filter((array)($_SESSION['cnpj_lookup_times'] ?? []), static fn($time) => (int)$time > $now - 60));
    if (count($_SESSION['cnpj_lookup_times']) >= 10) throw new CnpjLookupException('CNPJ_RATE_LIMIT', 'Aguarde antes de consultar novamente.');
    $_SESSION['cnpj_lookup_times'][] = $now;
    $data = (new CnpjLookupService(new BrasilApiCnpjProvider(), __DIR__ . '/../storage/cache/cnpj'))->lookup($cnpj);
    if ($data === null) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'CNPJ_NOT_FOUND']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $data->toArray()], JSON_UNESCAPED_UNICODE);
} catch (CnpjLookupException $e) {
    $statuses = ['CNPJ_INVALID'=>400, 'CNPJ_NOT_FOUND'=>404, 'CNPJ_RATE_LIMIT'=>429, 'CNPJ_SERVICE_TIMEOUT'=>504, 'CNPJ_SERVICE_UNAVAILABLE'=>503, 'CNPJ_PROVIDER_INVALID_RESPONSE'=>502];
    http_response_code($statuses[$e->reason] ?? 500);
    echo json_encode(['success' => false, 'error' => $e->reason]);
} catch (Throwable $e) {
    error_log('CNPJ lookup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'provider_unavailable']);
}
