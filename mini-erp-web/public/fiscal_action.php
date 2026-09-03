<?php
declare(strict_types=1);

use MiniErp\Fiscal\TaxRuleResolver;
use MiniErp\Repositories\{FiscalArtifactRepository,FiscalDocumentEventRepository,FiscalOperationRepository,MariaDbTaxRuleRepository};
use MiniErp\Services\{CreateInternalFiscalDocumentService,FiscalLocalPipelineFactory};

require_once __DIR__ . '/../vendor/autoload.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$tenantId = (int)($_SESSION['erp_tenant_id'] ?? $_SESSION['tenant_id'] ?? 0);
$userId = (int)($_SESSION['erp_user_id'] ?? $_SESSION['user_id'] ?? 0);
if ($tenantId < 1 || $userId < 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error_code' => 'UNAUTHENTICATED', 'error_message' => 'Sessão expirada.']);
    exit;
}
try {
    $policyMain=(new \MiniErp\Infrastructure\ControlPlaneConnectionFactory(__DIR__.'/../config.php'))->create();
    $fiscalPolicy=(new \MiniErp\Repositories\TenantAccessPolicyRepository($policyMain))->effectiveForTenant($tenantId);
    if (empty($_SESSION['erp_global_admin_id']) && (($fiscalPolicy['access_mode']??'FULL')!=='FULL'||empty($fiscalPolicy['can_issue_fiscal']))) {
        http_response_code(403);echo json_encode(['success'=>false,'error_code'=>'TENANT_FISCAL_BLOCKED','error_message'=>'Emissão fiscal bloqueada pelo Painel da Plataforma.'],JSON_UNESCAPED_UNICODE);exit;
    }
} catch (Throwable $policyError) { error_log('FISCAL_POLICY_READ_FAILED tenant='.$tenantId.' type='.get_class($policyError)); }
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') === 'cross-site') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error_code' => 'REQUEST_NOT_ALLOWED', 'error_message' => 'Origem da requisição não permitida.']);
    exit;
}
$csrfSession = (string)($_SESSION['erp_fiscal_csrf'] ?? '');
$csrfCookie = (string)($_COOKIE['erp_fiscal_csrf'] ?? '');
$csrfPosted = (string)($_POST['csrf_token'] ?? '');
$validCsrfCookie = $csrfSession !== '' && $csrfCookie !== '' && hash_equals($csrfSession, $csrfCookie);
$validCsrfPost = $csrfSession !== '' && $csrfPosted !== '' && hash_equals($csrfSession, $csrfPosted);
if (!$validCsrfCookie && !$validCsrfPost) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error_code' => 'CSRF_INVALID', 'error_message' => 'Token CSRF fiscal inválido.']);
    exit;
}
session_write_close(); // libera a mesma sessão antes do trabalho concorrente; a proteção principal permanece server-side

function fiscalRetryFingerprint(PDO $pdo, FiscalOperationRepository $operations, array $document): string
{
    $order=$operations->order((int)$document['source_order_id']);$issuer=[];$recipient=[];
    $s=$pdo->prepare('SELECT * FROM establishments WHERE tenant_id=? AND id=?');$s->execute([$operations->tenantId(),(int)$order['establishment_id']]);$issuer=$s->fetch(PDO::FETCH_ASSOC)?:[];
    $table=$order['operation_type']==='ENTRY'?'fornecedores':'clientes';$s=$pdo->prepare("SELECT * FROM {$table} WHERE id=?");$s->execute([(int)$order['person_id']]);$recipient=$s->fetch(PDO::FETCH_ASSOC)?:[];
    $settings=[];foreach(['establishment_fiscal_settings','establishment_cfop_defaults','establishment_icms_defaults','establishment_legacy_tax_defaults','establishment_rtc_defaults','tax_rule_versions','fiscal_series']as$table){try{$s=$pdo->prepare("SELECT * FROM {$table} WHERE tenant_id=? ORDER BY id");$s->execute([$operations->tenantId()]);$settings[$table]=$s->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable){$settings[$table]=[];}}
    return hash('sha256',json_encode(['source_order'=>(int)$document['source_order_id'],'order'=>$order,'issuer'=>$issuer,'recipient'=>$recipient,'settings'=>$settings],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
}

$documentId = 0;
try {
    $cfg = require __DIR__ . '/../config.php';
    $d = $cfg['db'];
    $main = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$d['database']};charset=utf8mb4", $d['username'], $d['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $s = $main->prepare('SELECT db_name FROM tenants WHERE id=?');
    $s->execute([$tenantId]);
    $db = (string)$s->fetchColumn();
    if (!preg_match('/^mini_erp_tenant_[1-9]\d*$/', $db)) throw new RuntimeException('TENANT_NOT_FOUND');
    $pdo = new PDO("mysql:host={$d['host']};port={$d['port']};dbname={$db};charset=utf8mb4", $d['username'], $d['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $operations = new FiscalOperationRepository($pdo, $tenantId);
    $action = (string)($_POST['fiscal_action'] ?? '');
    if(in_array($action,['retry','transmit'],true)&&!$validCsrfPost)throw new RuntimeException('CSRF_INVALID');
    if(in_array($action,['preview','finalize','mirror','note'],true)&&!in_array((string)($_POST['fiscal_model']??''),['55','65'],true))throw new RuntimeException('FISCAL_DOCUMENT_MODEL_UNSUPPORTED');
    if(in_array($action,['preview','finalize','mirror','note'],true))$_POST['operation_nature']=$operations->validatedOperationNature($_POST,(int)($_POST['order_id']??0));

    if ($action === 'preview') {
        if (($_POST['tipo'] ?? 'saida') === 'entrada') $_POST['cliente_id'] = $_POST['fornecedor_id'] ?? 0;
        $operations->assertOrderParties($_POST);
        $orderId=(int)($_POST['order_id']??0);
        $orderId=$operations->saveOrderWithTransport($orderId,$_POST,$_POST['itens']??[],$userId);
        echo json_encode(['success'=>true,'order_id'=>$orderId,'model'=>(string)$_POST['fiscal_model'],'model_source'=>'EXPLICIT','preserve_page'=>true,'danfe_url'=>'fiscal_danfe_preview.php?order_id='.$orderId,'notes_url'=>null,'error_code'=>null,'error_message'=>null],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
    } elseif ($action === 'finalize') {
        if (($_POST['tipo'] ?? 'saida') === 'entrada') $_POST['cliente_id'] = $_POST['fornecedor_id'] ?? 0;
        $operations->assertOrderParties($_POST);
        $token = strtolower(trim((string)($_POST['idempotency_key'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new RuntimeException('INVALID_IDEMPOTENCY_TOKEN');
        if ($existing = $operations->findDocumentByKey($token)) {
            echo json_encode(['success'=>true,'order_id'=>(int)$existing['source_order_id'],'document_id'=>(int)$existing['id'],'status'=>(string)$existing['status'],'danfe_url'=>null,'notes_url'=>'?page=fiscal_notes&highlight='.(int)$existing['id'],'error_code'=>null,'error_message'=>null],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
        }
        $orderId = (int)($_POST['order_id'] ?? 0);
        $orderId = $operations->saveOrderWithTransport($orderId, $_POST, $_POST['itens'] ?? [], $userId);
        $creator = new CreateInternalFiscalDocumentService($operations, new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo, $tenantId)));
        $document = $creator->create($orderId, $token, $userId);
        $documentId = (int)$document['id'];
        $events = new FiscalDocumentEventRepository($pdo, $tenantId);
        if (!$events->timeline($documentId)) $events->append($documentId, 'DOCUMENT_CREATED', 'SNAPSHOT', (string)$document['status'], (string)$document['status'], 'Pedido concluído com snapshot fiscal interno; sem reserva ou transmissão.', [], $userId);
        echo json_encode(['success'=>true,'order_id'=>$orderId,'document_id'=>$documentId,'status'=>(string)$document['status'],'danfe_url'=>null,'notes_url'=>'?page=fiscal_notes&highlight='.$documentId,'error_code'=>null,'error_message'=>null],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;
    } elseif ($action === 'transmit') {
        $documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT) ?: 0;
        $operations->document($documentId);
        $configuration = new \MiniErp\Repositories\FiscalConfigurationRepository($pdo, $tenantId);
        $provider = new \MiniErp\Fiscal\OperationalCertificateProvider(
            new \MiniErp\Fiscal\A1CertificateInspector(),
            new \MiniErp\Fiscal\PrivateCertificateStorage(dirname(__DIR__) . '/storage/fiscal/certificates'),
            new \MiniErp\Fiscal\LocalEncryptedSecretStorage(dirname(__DIR__) . '/storage/fiscal/secrets', \MiniErp\Fiscal\FiscalMasterKey::resolve(dirname(__DIR__))),
            $configuration
        );
        $transmission = new \MiniErp\Services\SefazAuthorizationService(
            $pdo, $tenantId,
            new FiscalArtifactRepository($pdo, $tenantId),
            new FiscalDocumentEventRepository($pdo, $tenantId),
            new \MiniErp\Fiscal\FiscalArtifactStorage(dirname(__DIR__) . '/storage/fiscal/artifacts'),
            $provider
        );
        $result = $transmission->transmitHomologation($documentId, $userId);
        echo json_encode(['success'=>true,'authorized'=>(bool)$result['authorized'],'document_id'=>$documentId,'artifact_id'=>(int)$result['artifact_id'],'status'=>(string)$result['status'],'cstat'=>(string)($result['cstat']??''),'protocol'=>(string)($result['protocol']??''),'message'=>(string)($result['reason']??($result['authorized']?'NF-e autorizada em homologação.':'Retorno processado pela SEFAZ.')),'notes_url'=>'/?page=fiscal_notes&highlight='.$documentId], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    } elseif ($action === 'retry') {
        $documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT) ?: 0;
        $document=$operations->document($documentId); // tenant/IDOR validation
        if(($document['status']??'')==='FISCAL_PENDING'){
            $token=fiscalRetryFingerprint($pdo,$operations,$document);
            $creator=new CreateInternalFiscalDocumentService($operations,new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo,$tenantId)));
            $fresh=$creator->create((int)$document['source_order_id'],$token,$userId);$documentId=(int)$fresh['id'];
        }
        $events = new FiscalDocumentEventRepository($pdo, $tenantId);
        $events->append($documentId, 'RETRY_REQUESTED', 'RETRY', 'PROCESSING', null, 'Nova tentativa solicitada.', [], $userId);
        $events->append($documentId, 'RETRY_STARTED', 'RETRY', 'PROCESSING', null, 'Pipeline local reiniciado.', [], $userId);
        try {
            $result = FiscalLocalPipelineFactory::create($pdo, $tenantId, dirname(__DIR__))->prepare($tenantId, $documentId, $userId);
            $events->append($documentId, 'RETRY_COMPLETED', 'RETRY', 'OK', null, 'Nova tentativa concluída.', [], $userId);
        } catch (Throwable $e) {
            $retryCode=\MiniErp\Services\FiscalErrorSanitizer::code($e);$events->append($documentId, 'RETRY_FAILED', 'RETRY', 'FAILED', $retryCode, \MiniErp\Services\FiscalErrorSanitizer::message($retryCode), [], $userId);
            throw $e;
        }
    } elseif ($action === 'mirror') {
        if (($_POST['tipo'] ?? 'saida') === 'entrada') $_POST['cliente_id'] = $_POST['fornecedor_id'] ?? 0;
        $operations->assertOrderParties($_POST);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $orderId = $operations->saveOrderWithTransport($orderId, $_POST, $_POST['itens'] ?? [], $userId);
        $mirrorToken = strtolower(trim((string)($_POST['idempotency_key'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $mirrorToken)) throw new RuntimeException('INVALID_IDEMPOTENCY_TOKEN');
        $mirrorId = $operations->createMirror($orderId, $userId, $mirrorToken);
        echo json_encode(['success' => true, 'document_id' => null, 'public_id' => 'MIRROR-' . $mirrorId, 'status' => 'ESPELHO_LOCAL', 'danfe_url' => '/fiscal_mirror.php?mirror_id=' . $mirrorId, 'notes_url' => '/?page=pedidos&tab=emitidos', 'error_code' => null, 'error_message' => null], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    } elseif ($action === 'note') {
        if (($_POST['tipo'] ?? 'saida') === 'entrada') $_POST['cliente_id'] = $_POST['fornecedor_id'] ?? 0;
        $operations->assertOrderParties($_POST);
        $token = strtolower(trim((string)($_POST['idempotency_key'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new RuntimeException('INVALID_IDEMPOTENCY_TOKEN');
        $existing = $operations->findDocumentByKey($token);
        if ($existing) {
            $documentId = (int)$existing['id'];
        } else {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $orderId = $operations->saveOrderWithTransport($orderId, $_POST, $_POST['itens'] ?? [], $userId);
            $creator = new CreateInternalFiscalDocumentService($operations, new TaxRuleResolver(new MariaDbTaxRuleRepository($pdo, $tenantId)));
            try {
                $document = $creator->create($orderId, $token, $userId);
            } catch (PDOException $e) {
                if ((string)$e->getCode() !== '23000') throw $e;
                $document = null;
                for ($attempt = 0; $attempt < 20 && $document === null; $attempt++) {
                    usleep(50000);
                    $document = $operations->findDocumentByKey($token);
                }
                if ($document === null) throw $e;
            }
            $documentId = (int)$document['id'];
            $pending = is_array($document['pending'] ?? null) ? $document['pending'] : (json_decode((string)($document['pending_json'] ?? '[]'), true) ?: []);
            if (!(new FiscalDocumentEventRepository($pdo, $tenantId))->timeline($documentId)) {
                (new FiscalDocumentEventRepository($pdo, $tenantId))->append($documentId, 'DOCUMENT_CREATED', 'DOCUMENT', (string)$document['status'], (string)$document['status'], $pending ? implode('; ', $pending) : 'Documento fiscal criado.', [], $userId);
            }
        }
        $result = FiscalLocalPipelineFactory::create($pdo, $tenantId, dirname(__DIR__))->prepare($tenantId, $documentId, $userId);
    } else {
        throw new RuntimeException('INVALID_ACTION');
    }

    $artifact = (new FiscalArtifactRepository($pdo, $tenantId))->findByDocumentVersion($documentId, (int)$operations->document($documentId)['document_version'], 'NFE');
    $artifactId = (int)($artifact['id'] ?? $result['artifact_id'] ?? 0);
    echo json_encode(['success' => true, 'document_id' => $documentId, 'public_id' => 'DOC-' . $documentId, 'status' => 'PENDING_TRANSMISSION', 'danfe_url' => $artifactId ? '/fiscal_danfe.php?artifact_id=' . $artifactId . '&mode=inline' : null, 'notes_url' => '/?page=fiscal_notes&highlight=' . $documentId, 'error_code' => null, 'error_message' => null], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(422);
    $concurrent = $e instanceof PDOException && in_array((string)$e->getCode(), ['40001', '1213'], true);
    $code = $concurrent ? 'REQUEST_CONCURRENT_RETRY' : \MiniErp\Services\FiscalErrorSanitizer::code($e);
    error_log('FISCAL_ACTION_FAILED tenant='.$tenantId.' document='.$documentId.' type='.get_class($e).' code='.$code);
    $message = $concurrent ? 'Outra requisição está processando o mesmo documento. Consulte a Central ou tente novamente.' : \MiniErp\Services\FiscalErrorSanitizer::message($code);
    echo json_encode(['success' => false, 'document_id' => $documentId ?: null, 'public_id' => $documentId ? 'DOC-' . $documentId : null, 'status' => $concurrent ? 'PROCESSING' : 'FAILED', 'danfe_url' => null, 'notes_url' => $documentId ? '/?page=fiscal_notes&highlight=' . $documentId : '/?page=fiscal_notes', 'error_code' => $code, 'error_message' => $message], JSON_UNESCAPED_UNICODE);
}
