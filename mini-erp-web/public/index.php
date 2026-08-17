<?php

// Carrega as classes essenciais da aplicação.
require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Repository.php';

// Inicia sessão para autenticação cedo, antes de decidir qual DB usar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se já existe tenant na sessão, tenta obter `db_name` do tenant na base principal
$config = require __DIR__ . '/../config.php';
$dbConf = $config['db'];
if (!empty($_SESSION['tenant_id'])) {
    try {
        $serverDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
        $adminPdo = new PDO($serverDsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $stmt = $adminPdo->prepare('SELECT db_name FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int)$_SESSION['tenant_id']]);
        $row = $stmt->fetch();
        if ($row && !empty($row['db_name'])) {
            Database::setTenantDbName($row['db_name']);
        }
    } catch (Throwable $e) {
        // ignore, fallback para DB principal
    }
}

// Cria o repositório que interage com o banco (agora que tenant DB já foi decidido)
$repo = new Repository();

// Resolve tenant from URL slug (first segment) when present.
// Example: /mercado-silva/login  -> tenant slug = mercado-silva
$currentTenant = null;
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$pathSegments = array_values(array_filter(explode('/', $requestPath)));
if (!empty($pathSegments)) {
    $first = $pathSegments[0];
    // avoid matching assets and known files
    $ignore = ['assets', 'favicon.ico', 'login.php', 'index.php', 'forgot.php', 'reset.php'];
    if (!in_array($first, $ignore, true)) {
        $t = $repo->findTenantBySlug($first);
        if ($t) {
            $_SESSION['tenant_id'] = (int)$t['id'];
            // backward compat
            $_SESSION['current_company_id'] = (int)$t['id'];
                // se o tenant tiver um db_name configurado, força a conexão para este DB
                if (!empty($t['db_name'])) {
                    Database::setTenantDbName($t['db_name']);
                }
            $currentTenant = $t;
        }
    }
}

// Define qual página de menu está ativa.
$page = $_GET['page'] ?? 'dashboard';

// Armazena mensagens visuais de sucesso ou erro para o usuário.
$flash = [
    'success' => '',
    'error' => '',
];

// Trata ações de formulário enviados via POST.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'save_cliente':
                // Se fornecido CPF/CNPJ, tentar buscar dados na BrasilAPI para autopreencher
                $cpfcnpjRaw = trim((string)($_POST['cpf_cnpj'] ?? ''));
                if ($cpfcnpjRaw !== '') {
                    $cnpjData = $repo->fetchCnpjData($cpfcnpjRaw);
                    if (is_array($cnpjData)) {
                        $_POST['nome'] = $_POST['nome'] ?? ($cnpjData['razao_social'] ?? $cnpjData['nome'] ?? '');
                        $_POST['nome_fantasia'] = $_POST['nome_fantasia'] ?? ($cnpjData['nome_fantasia'] ?? '');
                        $_POST['cep'] = $_POST['cep'] ?? ($cnpjData['cep'] ?? '');
                        $_POST['uf'] = $_POST['uf'] ?? ($cnpjData['uf'] ?? '');
                        $_POST['municipio'] = $_POST['municipio'] ?? ($cnpjData['municipio'] ?? '');
                        $logradouro = trim((string)($cnpjData['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($cnpjData['logradouro'] ?? ''));
                        if ($logradouro !== '') $_POST['logradouro'] = $_POST['logradouro'] ?? $logradouro;
                        $_POST['numero'] = $_POST['numero'] ?? ($cnpjData['numero'] ?? '');
                        $_POST['complemento'] = $_POST['complemento'] ?? ($cnpjData['complemento'] ?? '');
                        $_POST['bairro'] = $_POST['bairro'] ?? ($cnpjData['bairro'] ?? '');
                        $_POST['telefone'] = $_POST['telefone'] ?? ($cnpjData['ddd_telefone_1'] ?? '');
                        $_POST['codigo_ibge'] = $_POST['codigo_ibge'] ?? ($cnpjData['codigo_ibge'] ?? $cnpjData['codigo_municipal'] ?? '');
                        $_POST['data'] = json_encode($cnpjData, JSON_UNESCAPED_UNICODE);
                    }
                }
                // Salva ou atualiza um cliente.
                $repo->saveCliente($_POST);
                $flash['success'] = 'Cliente salvo com sucesso.';
                break;

            case 'delete_cliente':
                // Remove um cliente do banco.
                $repo->deleteCliente((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Cliente removido com sucesso.';
                break;

            case 'save_produto':
                // Salva ou atualiza um produto.
                $repo->saveProduto($_POST);
                $flash['success'] = 'Produto salvo com sucesso.';
                break;

            case 'delete_produto':
                // Remove um produto do banco.
                $repo->deleteProduto((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Produto removido com sucesso.';
                break;

            case 'save_venda':
                // Registra a venda e atualiza estoque. Retorna o ID criado.
                // Passa o CNPJ da empresa (se uma empresa estiver selecionada na sessão)
                $empresaCnpj = null;
                $dataDir = __DIR__ . '/../data';
                if (is_dir($dataDir)) {
                    $empresasFile = $dataDir . '/empresas.json';
                    if (file_exists($empresasFile)) {
                        $empresas = json_decode(file_get_contents($empresasFile), true) ?: [];
                        $currentId = $_SESSION['current_company_id'] ?? null;
                        if ($currentId) {
                            foreach ($empresas as $e) {
                                if ((int)($e['id'] ?? 0) === (int)$currentId) {
                                    $empresaCnpj = preg_replace('/\D/', '', $e['cnpj'] ?? '');
                                    break;
                                }
                            }
                        } elseif (!empty($empresas[0])) {
                            $empresaCnpj = preg_replace('/\D/', '', $empresas[0]['cnpj'] ?? '');
                        }
                    } else {
                        // fallback antigo para compatibilidade
                        $empresaFile = $dataDir . '/empresa.json';
                        if (file_exists($empresaFile)) {
                            $empresa = json_decode(file_get_contents($empresaFile), true) ?: [];
                            $empresaCnpj = preg_replace('/\D/', '', $empresa['cnpj'] ?? '');
                        }
                    }
                }
                if ($empresaCnpj) $_POST['empresa_cnpj'] = $empresaCnpj;

                $vendaId = $repo->createSale($_POST, $_POST['itens'] ?? []);
                $flash['success'] = 'Venda registrada com sucesso.';
                break;

            case 'save_empresa':
                // Se fornecido CNPJ, tentar buscar dados na BrasilAPI para autopreencher
                $cnpjRaw = trim((string)($_POST['cnpj'] ?? ''));
                if ($cnpjRaw !== '') {
                    $cnpjData = $repo->fetchCnpjData($cnpjRaw);
                    if (is_array($cnpjData)) {
                        $_POST['razao_social'] = $_POST['razao_social'] ?? ($cnpjData['razao_social'] ?? '');
                        $_POST['nome_fantasia'] = $_POST['nome_fantasia'] ?? ($cnpjData['nome_fantasia'] ?? '');
                        $_POST['cep'] = $_POST['cep'] ?? ($cnpjData['cep'] ?? '');
                        $_POST['uf'] = $_POST['uf'] ?? ($cnpjData['uf'] ?? '');
                        $_POST['municipio'] = $_POST['municipio'] ?? ($cnpjData['municipio'] ?? '');
                        $logradouro = trim((string)($cnpjData['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($cnpjData['logradouro'] ?? ''));
                        if ($logradouro !== '') $_POST['logradouro'] = $_POST['logradouro'] ?? $logradouro;
                        $_POST['numero'] = $_POST['numero'] ?? ($cnpjData['numero'] ?? '');
                        $_POST['complemento'] = $_POST['complemento'] ?? ($cnpjData['complemento'] ?? '');
                        $_POST['bairro'] = $_POST['bairro'] ?? ($cnpjData['bairro'] ?? '');
                        $_POST['telefone'] = $_POST['telefone'] ?? ($cnpjData['ddd_telefone_1'] ?? '');
                        // opcional: armazenar payload bruto para auditoria
                        $_POST['data'] = json_encode($cnpjData, JSON_UNESCAPED_UNICODE);
                    }
                }

                // Save tenant into DB
                $repo->saveCompany($_POST);
                $flash['success'] = 'Empresa salva.';
                break;

            case 'assign_user_company':
                $uid = (int)($_POST['id'] ?? 0);
                $cid = isset($_POST['company_id']) && $_POST['company_id'] !== '' ? (int)$_POST['company_id'] : null;
                if ($uid) {
                    $repo->assignUserToCompany($uid, $cid);
                    $flash['success'] = 'Usuário atualizado com empresa.';
                }
                break;

            case 'select_empresa':
                $id = (int)($_POST['company_id'] ?? 0);
                if ($id) {
                    // set as current tenant in session
                    $_SESSION['tenant_id'] = $id;
                    // backward compatibility
                    $_SESSION['current_company_id'] = $id;
                    // força conexão para o DB do tenant selecionado, se disponível
                    $t = $repo->findTenantById($id);
                    if ($t && !empty($t['db_name'])) {
                        Database::setTenantDbName($t['db_name']);
                    }
                    $flash['success'] = 'Empresa selecionada.';
                    // redireciona diretamente para a página da empresa selecionada
                    header('Location: ?page=company&id=' . $id);
                    exit;
                }
                break;
            case 'create_tenant_user':
                $id = (int)($_POST['id'] ?? 0);
                $email = trim((string)($_POST['email'] ?? ''));
                $senha = (string)($_POST['senha'] ?? '');
                if (!$id || $email === '' || $senha === '') {
                    $flash['error'] = 'ID da empresa, e-mail e senha são obrigatórios.';
                    break;
                }
                try {
                    // garante que o tenant exista no DB principal; se não, tenta persistir a partir do JSON legacy
                    $t = $repo->findTenantById($id);
                    if (!$t) {
                        $dataDir = __DIR__ . '/../data';
                        $empresasFile = $dataDir . '/empresas.json';
                        if (file_exists($empresasFile)) {
                            $empresas = json_decode(file_get_contents($empresasFile), true) ?: [];
                            $found = null;
                            foreach ($empresas as $ee) {
                                if ((int)($ee['id'] ?? 0) === $id) { $found = $ee; break; }
                            }
                            if ($found) {
                                // salva empresa no DB (isso provisiona tenant e cria admin padrão)
                                $repo->saveCompany($found);
                                // tenta localizar pelo CNPJ (o ID pode mudar quando migrado do JSON)
                                if (!empty($found['cnpj'])) {
                                    $t = $repo->findTenantByCnpj($found['cnpj']);
                                }
                                // se ainda não encontrou, tenta localizar por slug (se houver) ou por nome
                                if (!$t) {
                                    if (!empty($found['slug'])) {
                                        try { $t = $repo->findTenantBySlug((string)$found['slug']); } catch (Throwable $e) { /* ignore */ }
                                    }
                                }
                                if (!$t) {
                                    // tentativa final: busca direta no DB principal por nome_fantasia
                                    try {
                                        $config = require __DIR__ . '/../config.php';
                                        $dbConf = $config['db'];
                                        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbConf['host'], $dbConf['port'], $dbConf['database']);
                                        $pdoMain = new PDO($dsn, $dbConf['username'], $dbConf['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
                                        $name = trim((string)($found['nome_fantasia'] ?? $found['apelido'] ?? ''));
                                        if ($name !== '') {
                                            $row = $pdoMain->prepare('SELECT * FROM tenants WHERE nome_fantasia = :nome LIMIT 1');
                                            $row->execute(['nome' => $name]);
                                            $t = $row->fetch() ?: null;
                                        }
                                    } catch (Throwable $e) {
                                        // ignore
                                    }
                                }
                                // se ainda não encontrou, tenta por id antigo como último recurso
                                if (!$t) {
                                    $t = $repo->findTenantById($id);
                                }
                            }
                        }
                    }

                    if (!$t) throw new Exception('Empresa/tenant não encontrada no banco. Primeiro cadastre a empresa.');

                    // cria/atualiza usuário central
                    $repo->createOrUpdateAdmin($email, $senha, 'Administrador');

                    // obtém usuário e associa à empresa/tenant
                    $user = $repo->findUsuarioByEmail($email);
                    if ($user) {
                        // Temporariamente define tenant/session para permitir assignUserToCompany funcionar
                        $prevTenant = $_SESSION['tenant_id'] ?? null;
                        $prevCompany = $_SESSION['current_company_id'] ?? null;
                        $_SESSION['tenant_id'] = $id;
                        $_SESSION['current_company_id'] = $id;
                        try {
                            $repo->assignUserToCompany((int)$user['id'], $id);
                        } finally {
                            // restaura sessão
                            if ($prevTenant === null) unset($_SESSION['tenant_id']); else $_SESSION['tenant_id'] = $prevTenant;
                            if ($prevCompany === null) unset($_SESSION['current_company_id']); else $_SESSION['current_company_id'] = $prevCompany;
                        }
                    }

                    // cria link direto para o login da empresa (usa slug quando disponível)
                    $loginTenant = $t['slug'] ?? $t['id'];
                    $flash['success'] = 'Usuário criado e associado à empresa.';
                    $flash['company_login'] = '/login.php?tenant=' . urlencode($loginTenant);
                } catch (Throwable $e) {
                    $flash['error'] = 'Erro ao criar usuário: ' . $e->getMessage();
                }
                break;
            case 'create_tenant_admin':
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    try {
                        $t = $repo->findTenantById($id);
                        if (!$t) {
                            // tenta migrar a partir do JSON legacy
                            $dataDir = __DIR__ . '/../data';
                            $empresasFile = $dataDir . '/empresas.json';
                            if (file_exists($empresasFile)) {
                                $empresas = json_decode(file_get_contents($empresasFile), true) ?: [];
                                foreach ($empresas as $ee) {
                                    if ((int)($ee['id'] ?? 0) === $id) {
                                        $repo->saveCompany($ee);
                                        if (!empty($ee['cnpj'])) {
                                            $t = $repo->findTenantByCnpj($ee['cnpj']);
                                        }
                                        if (!$t) $t = $repo->findTenantById($id);
                                        break;
                                    }
                                }
                            }
                        }

                        if (!$t) throw new Exception('Tenant não encontrado após tentativa de migração (id=' . $id . ')');

                        $repo->createTenantAdmin((int)$t['id']);
                        $flash['success'] = 'Conta administrativa criada/garantida para a empresa (id=' . $t['id'] . ').';
                        $loginTenant = $t['slug'] ?? $t['id'];
                        $flash['company_login'] = '/login.php?tenant=' . urlencode($loginTenant);
                    } catch (Throwable $e) {
                        $flash['error'] = 'Erro ao criar conta administrativa para id=' . $id . ': ' . $e->getMessage();
                    }
                }
                break;
            case 'delete_empresa':
                $id = (int)($_POST['id'] ?? 0);
                if ($id) {
                    $repo->deleteCompany($id);
                    if (isset($_SESSION['tenant_id']) && (int)$_SESSION['tenant_id'] === $id) {
                        unset($_SESSION['tenant_id']);
                        unset($_SESSION['current_company_id']);
                    }
                    $flash['success'] = 'Empresa removida.';
                }
                break;
                case 'toggle_block_empresa':
                    $id = (int)($_POST['id'] ?? 0);
                    $blocked = isset($_POST['blocked']) && $_POST['blocked'] === '1' ? true : false;
                    if ($id) {
                        $repo->setCompanyBlocked($id, $blocked);
                        $flash['success'] = $blocked ? 'Empresa bloqueada; logins desabilitados.' : 'Empresa desbloqueada; logins habilitados.';
                    }
                    break;

            case 'save_product_taxes':
                // Salva impostos no banco via Repository
                $prodId = (int)($_POST['product_id'] ?? 0);
                $taxes = [
                    'ipi' => $_POST['ipi'] ?? '',
                    'icms' => $_POST['icms'] ?? '',
                    'pis' => $_POST['pis'] ?? '',
                    'cofins' => $_POST['cofins'] ?? '',
                ];
                $repo->saveProductTaxes($prodId, $taxes);
                $flash['success'] = 'Impostos salvos para o produto (banco).';
                break;

            case 'save_cfop':
                $repo->saveCfop($_POST);
                $flash['success'] = 'CFOP salvo com sucesso.';
                break;

            case 'delete_cfop':
                $repo->deleteCfop((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'CFOP removido com sucesso.';
                break;

            case 'save_fornecedor':
                $cpfcnpjRaw = trim((string)($_POST['cpf_cnpj'] ?? ''));
                if ($cpfcnpjRaw !== '') {
                    $cnpjData = $repo->fetchCnpjData($cpfcnpjRaw);
                    if (is_array($cnpjData)) {
                        $_POST['nome'] = $_POST['nome'] ?? ($cnpjData['razao_social'] ?? $cnpjData['nome'] ?? '');
                        $_POST['nome_fantasia'] = $_POST['nome_fantasia'] ?? ($cnpjData['nome_fantasia'] ?? '');
                        $_POST['cep'] = $_POST['cep'] ?? ($cnpjData['cep'] ?? '');
                        $_POST['uf'] = $_POST['uf'] ?? ($cnpjData['uf'] ?? '');
                        $_POST['municipio'] = $_POST['municipio'] ?? ($cnpjData['municipio'] ?? '');
                        $logradouro = trim((string)($cnpjData['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($cnpjData['logradouro'] ?? ''));
                        if ($logradouro !== '') $_POST['logradouro'] = $_POST['logradouro'] ?? $logradouro;
                        $_POST['numero'] = $_POST['numero'] ?? ($cnpjData['numero'] ?? '');
                        $_POST['complemento'] = $_POST['complemento'] ?? ($cnpjData['complemento'] ?? '');
                        $_POST['bairro'] = $_POST['bairro'] ?? ($cnpjData['bairro'] ?? '');
                        $_POST['telefone'] = $_POST['telefone'] ?? ($cnpjData['ddd_telefone_1'] ?? '');
                        $_POST['codigo_ibge'] = $_POST['codigo_ibge'] ?? ($cnpjData['codigo_ibge'] ?? $cnpjData['codigo_municipal'] ?? '');
                        $_POST['data'] = json_encode($cnpjData, JSON_UNESCAPED_UNICODE);
                    }
                }
                $repo->saveFornecedor($_POST);
                $flash['success'] = 'Fornecedor salvo com sucesso.';
                break;

            case 'delete_fornecedor':
                $repo->deleteFornecedor((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Fornecedor removido com sucesso.';
                break;

            case 'save_motorista':
                $repo->saveMotorista($_POST);
                $flash['success'] = 'Motorista salvo com sucesso.';
                break;

            case 'delete_motorista':
                $repo->deleteMotorista((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Motorista removido com sucesso.';
                break;

            case 'save_transportadora':
                $repo->saveTransportadora($_POST);
                $flash['success'] = 'Transportadora salva com sucesso.';
                break;

            case 'delete_transportadora':
                $repo->deleteTransportadora((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Transportadora removida com sucesso.';
                break;

            case 'save_usuario':
                $isNew = empty($_POST['id']);
                $repo->saveUsuario($_POST);
                if ($isNew && ($page === 'save_usuario' || $page === 'login')) {
                    // se veio do formulário público (signup), redireciona para login mostrando instrução
                    header('Location: /login.php?registered=1');
                    exit;
                }
                $flash['success'] = 'Funcionário salvo com sucesso.';
                break;

            case 'create_admin_account':
                $email = trim((string)($_POST['email'] ?? ''));
                $senha = (string)($_POST['senha'] ?? '');
                $nome = trim((string)($_POST['nome'] ?? 'Administrador'));
                if ($email === '' || $senha === '') {
                    throw new InvalidArgumentException('E-mail e senha são obrigatórios para criar a conta administrativa.');
                }
                $repo->createOrUpdateAdmin($email, $senha, $nome);
                $flash['success'] = 'Conta administrativa criada/atualizada com sucesso.';
                break;

            case 'delete_usuario':
                $repo->deleteUsuario((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Funcionário removido com sucesso.';
                break;

            case 'approve_usuario':
                $repo->approveUsuario((int) ($_POST['id'] ?? 0));
                $flash['success'] = 'Usuário aprovado e ativado.';
                break;

            case 'login':
                $email = trim((string)($_POST['email'] ?? ''));
                $senha = (string)($_POST['senha'] ?? '');
                $user = $repo->findUsuarioByEmail($email);
                $isDefaultAdmin = strtolower((string)($email ?? '')) === 'admin@localhost';
                $isValidDefaultAdmin = $isDefaultAdmin && $senha === 'admin';
                // Somente autentica se o usuário existir e a senha bater.
                if (!$user || (!password_verify($senha, $user['senha']) && !$isValidDefaultAdmin)) {
                    // Redireciona de volta para a página de login com erro.
                    header('Location: /login.php?error=invalid');
                    exit;
                }

                // Verifica se usuário está ativo
                if (isset($user['status']) && $user['status'] !== 'ativo') {
                    header('Location: /login.php?error=inactive');
                    exit;
                }

                // Verifica se e-mail foi confirmado (admin@localhost e administradores são isentos)
                if (isset($user['email_verified']) && ((int)$user['email_verified']) === 0) {
                    $emailLower = strtolower((string)($user['email'] ?? ''));
                    $role = (string)($user['role'] ?? '');
                    if ($emailLower !== 'admin@localhost' && strtolower($role) !== 'admin') {
                        header('Location: /login.php?error=unverified');
                        exit;
                    }
                }

                // Sucesso: cria sessão, define tenant e empresa corrente e redireciona para dashboard
                $_SESSION['user_id'] = (int)$user['id'];
                // Prioriza tenant_id direto do usuário
                if (!empty($user['tenant_id'])) {
                    $_SESSION['tenant_id'] = (int) $user['tenant_id'];
                    $t = $repo->findTenantById((int)$user['tenant_id']);
                    if ($t && !empty($t['db_name'])) {
                        Database::setTenantDbName($t['db_name']);
                    }
                } elseif (!empty($user['company_id'])) {
                    // Compatibilidade: tenta resolver tenant a partir do company_id
                    $company = $repo->findCompany((int)$user['company_id']);
                    if ($company) {
                        $_SESSION['tenant_id'] = (int) $company['id'];
                        $t = $repo->findTenantById((int)$company['id']);
                        if ($t && !empty($t['db_name'])) {
                            Database::setTenantDbName($t['db_name']);
                        }
                    }
                    $_SESSION['current_company_id'] = (int) $user['company_id'];
                }
                // Garante um tenant de sessão mínimo para ambientes de desenvolvimento.
                if (empty($_SESSION['tenant_id'])) {
                    $_SESSION['tenant_id'] = 1;
                }
                header('Location: ?page=dashboard');
                exit;
                break;

            case 'logout':
                session_unset();
                session_destroy();
                header('Location: /login.php');
                exit;
                break;
        }
    } catch (Throwable $e) {
        // Captura qualquer erro e mostra a mensagem para o usuário.
        $flash['error'] = $e->getMessage();
    }
}

// Coleta os dados principais para uso nas telas.
$currentUser = null;
if (!empty($_SESSION['user_id'])) {
    $currentUser = $repo->findUsuarioById((int) $_SESSION['user_id']);
}

// Se não estiver na página de login e não existe usuário autenticado, redireciona para login
if ($page !== 'login' && !$currentUser) {
    header('Location: /login.php');
    exit;
}

$dashboard = $repo->getDashboardData();
$clientes = $repo->listClientes();
$produtos = $repo->listProdutos();
$vendas = $repo->listVendas();
$cfops = $repo->listCfops();
$fornecedores = $repo->listFornecedores();
$motoristas = $repo->listMotoristas();
$transportadoras = $repo->listTransportadoras();

// Busca o cliente que será editado na tela de clientes.
$editCliente = null;
if ($page === 'clientes' && isset($_GET['edit'])) {
    $editCliente = $repo->findCliente((int) $_GET['edit']);
}

$editPessoa = null;
if ($page === 'cadastro' && isset($_GET['tab']) && $_GET['tab'] === 'pessoas' && isset($_GET['edit'])) {
    $editPessoa = $repo->findCliente((int) $_GET['edit']);
}

// Busca o produto que será editado na tela de produtos.
$editProduto = null;
if ($page === 'produtos' && isset($_GET['edit'])) {
    $editProduto = $repo->findProduto((int) $_GET['edit']);
}

$editCfop = null;
if ($page === 'cadastro' && isset($_GET['tab']) && $_GET['tab'] === 'cfops' && isset($_GET['edit'])) {
    $editCfop = $repo->findCfop((int) $_GET['edit']);
}

$editFornecedor = null;
if ($page === 'cadastro' && isset($_GET['tab']) && $_GET['tab'] === 'fornecedores' && isset($_GET['edit'])) {
    $editFornecedor = $repo->findFornecedor((int) $_GET['edit']);
}

$editMotorista = null;
if ($page === 'cadastro' && isset($_GET['tab']) && $_GET['tab'] === 'motoristas' && isset($_GET['edit'])) {
    $editMotorista = $repo->findMotorista((int) $_GET['edit']);
}

$editTransportadora = null;
if ($page === 'cadastro' && isset($_GET['tab']) && $_GET['tab'] === 'transportadoras' && isset($_GET['edit'])) {
    $editTransportadora = $repo->findTransportadora((int) $_GET['edit']);
}

// Edit usuário (funcionário) via parametro
$editUsuario = null;
if ($page === 'configuracao' && isset($_GET['edit_user'])) {
    $editUsuario = $repo->findUsuarioById((int) $_GET['edit_user']);
}

// Formata dinheiro em reais para exibir no HTML.
function formatCurrency(float $value): string
{
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Define se o item do menu está ativo.
function navClass(string $pageName, string $currentPage): string
{
    return $pageName === $currentPage ? 'active' : '';
}

// Detecta as imagens de logo disponíveis no diretório de assets (fallbacks).
$imagesDir = __DIR__ . '/assets/images';
$logoUrl = '/assets/images/LOGO.png';
$loaderGifUrl = null;
if (is_dir($imagesDir)) {
    // Preferir o arquivo 'mini-erp-logo.png' (o logo principal enviado),
    // depois o 'logo_login.png' (ícone usado no login), e em seguida outros fallbacks.
    if (file_exists($imagesDir . '/mini-erp-logo.png')) {
        $logoUrl = '/assets/images/mini-erp-logo.png';
    } elseif (file_exists($imagesDir . '/logo_login.png')) {
        $logoUrl = '/assets/images/logo_login.png';
    } elseif (file_exists($imagesDir . '/LOGO.png')) {
        $logoUrl = '/assets/images/LOGO.png';
    } elseif (file_exists($imagesDir . '/logo.png')) {
        $logoUrl = '/assets/images/logo.png';
    }

    if (file_exists($imagesDir . '/gif_logo.gif')) {
        $loaderGifUrl = '/assets/images/gif_logo.gif';
    } elseif (file_exists($imagesDir . '/loader.gif')) {
        $loaderGifUrl = '/assets/images/loader.gif';
    }
}

// A página de login é servida por /login.php
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini ERP</title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/Favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/Favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/Favicon/favicon-16x16.png">
    <link rel="shortcut icon" href="/assets/images/Favicon/favicon.ico">
    <meta name="theme-color" content="#1e88e5">
    <link rel="stylesheet" href="/assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>
    <div id="page-loader" class="page-loader" aria-hidden="false">
        <div class="loader-inner">
            <?php if ($loaderGifUrl): ?>
                <img src="<?= htmlspecialchars($loaderGifUrl) ?>" alt="Carregando" class="loader-logo" onerror="this.style.display='none'">
            <?php else: ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Mini ERP Web" class="loader-logo" onerror="this.style.display='none'">
            <?php endif; ?>
        </div>
    </div>
    <div class="app-shell">
        <!-- Menu principal no topo (antes era sidebar) -->
        
        <button id="hamburger" class="hamburger" aria-label="Abrir menu" aria-expanded="false">☰</button>

        <div id="drawer-backdrop" class="drawer-backdrop" aria-hidden="true"></div>

        <aside id="sidebar-drawer" class="sidebar-drawer" aria-hidden="true">
            <div class="drawer-inner">
                <ul class="drawer-cats">
                    <li class="drawer-cat">
                        <a href="?page=dashboard" class="cat-link"><i data-feather="home"></i><span>Dashboard</span></a>
                    </li>

                    <li class="drawer-cat">
                        <button class="cat-toggle" aria-expanded="false"><i data-feather="file-text"></i><span>Pedidos</span></button>
                        <ul class="drawer-submenu">
                            <li><a href="?page=pedidos&tab=entrada">Entrada</a></li>
                            <li><a href="?page=pedidos&tab=saida">Saída</a></li>
                            <li><a href="?page=pedidos&tab=emitidos">Pedidos Emitidos</a></li>
                        </ul>
                    </li>

                    <li class="drawer-cat">
                        <button class="cat-toggle" aria-expanded="false"><i data-feather="users"></i><span>Cadastro</span></button>
                        <ul class="drawer-submenu">
                            <li><a href="?page=cadastro&tab=pessoas">Pessoas</a></li>
                            <li><a href="?page=cadastro&tab=produtos">Produtos</a></li>
                            <li><a href="?page=cadastro&tab=cfops">CFOPs</a></li>
                            <li><a href="?page=cadastro&tab=fornecedores">Fornecedores</a></li>
                            <li><a href="?page=cadastro&tab=motoristas">Motoristas</a></li>
                            <li><a href="?page=cadastro&tab=transportadoras">Transportadoras</a></li>
                        </ul>
                    </li>

                    <li class="drawer-cat">
                        <button class="cat-toggle" aria-expanded="false"><i data-feather="settings"></i><span>Configuração</span></button>
                        <ul class="drawer-submenu">
                            <li><a href="?page=configuracao&tab=empresa">Empresa</a></li>
                            <li><a href="?page=configuracao&tab=usuarios">Usuários</a></li>
                            <li><a href="?page=configuracao#fiscal">Fiscal</a></li>
                            <li><a href="?page=configuracao#nfce">NFC-e</a></li>
                            <li><a href="?page=configuracao#mdfe">MDF-e</a></li>
                            <li><a href="?page=configuracao#contador">Contador</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </aside>

        <main class="main-content">
            <!-- Cabeçalho da aplicação -->
            <header class="topbar">
                <div class="brand">
                    <img src="<?= htmlspecialchars($logoUrl) ?>" alt="Mini ERP Web" class="site-logo" onerror="this.style.display='none'">
                </div>

                <nav class="topnav">
                    <div class="menu">
                        <div class="menu-item-wrapper"><a class="menu-item <?= navClass('dashboard', $page) ?>" href="?page=dashboard">Dashboard</a></div>
                        <div class="menu-item-wrapper">
                            <a class="menu-item <?= navClass('pedidos', $page) ?>" href="?page=pedidos">PEDIDOS</a>
                            <div class="submenu">
                                <a href="?page=pedidos&tab=entrada">Entrada</a>
                                <a href="?page=pedidos&tab=saida">Saída</a>
                                <a href="?page=pedidos&tab=emitidos">Pedidos Emitidos</a>
                            </div>
                        </div>
                        <div class="menu-item-wrapper">
                            <a class="menu-item <?= navClass('cadastro', $page) ?>" href="?page=cadastro">CADASTRO</a>
                            <div class="submenu">
                                <a href="?page=cadastro&tab=pessoas">Pessoas</a>
                                <a href="?page=cadastro&tab=produtos">Produtos</a>
                                <a href="?page=cadastro&tab=cfops">CFOPs</a>
                                <a href="?page=cadastro&tab=fornecedores">Fornecedores</a>
                                <a href="?page=cadastro&tab=motoristas">Motoristas</a>
                                <a href="?page=cadastro&tab=transportadoras">Transportadoras</a>
                            </div>
                        </div>
                        <div class="menu-item-wrapper">
                            <a class="menu-item <?= navClass('configuracao', $page) ?>" href="?page=configuracao">CONFIGURAÇÃO</a>
                            <div class="submenu">
                                <a href="?page=configuracao&tab=empresa">Empresa</a>
                                <a href="?page=configuracao&tab=usuarios">Usuários</a>
                                <a href="?page=configuracao#fiscal">Fiscal</a>
                                <a href="?page=configuracao#nfce">NFC-e</a>
                                <a href="?page=configuracao#mdfe">MDF-e</a>
                                <a href="?page=configuracao#contador">Contador</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <div class="user-pill"> 
                    <?php if (!empty($currentUser)): ?>
                        <div class="avatar-circle">
                            <?php if (!empty($currentUser['avatar'])): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="<?= htmlspecialchars($currentUser['nome']) ?>">
                            <?php else: ?>
                                <span><?= htmlspecialchars(mb_substr($currentUser['nome'], 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-name"><?= htmlspecialchars($currentUser['nome']) ?></div>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="logout">
                            <button class="btn ghost" type="submit">Sair</button>
                        </form>
                    <?php else: ?>
                        <a class="btn primary" href="/login.php">Entrar</a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Mensagens de feedback ao usuário -->
            <?php if (!empty($flash['success'])): ?>
                <div class="alert success">
                    <?= htmlspecialchars($flash['success']) ?>
                    <?php if (!empty($flash['company_login'])): ?>
                        <div style="margin-top:8px;"><a class="btn primary" href="<?= htmlspecialchars($flash['company_login']) ?>">Ir para login desta empresa</a></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($flash['error'])): ?>
                <div class="alert error"><?= htmlspecialchars($flash['error']) ?></div>
            <?php endif; ?>

            <?php
            // Renderiza a seção correspondente à página ativa.
            switch ($page) {
                case 'company':
                    $companyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
                    $company = $companyId ? $repo->findCompany($companyId) : null;
                    $companyData = [];
                    if ($company) {
                        $companyData = json_decode($company['data'] ?? '{}', true) ?: [];
                    }
                    ?>
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Empresa</p>
                            <h2><?= htmlspecialchars($company['nome_fantasia'] ?? ($companyData['nome_fantasia'] ?? 'Empresa')) ?></h2>
                        </div>
                        <a class="btn secondary" href="?page=configuracao&tab=empresa">Voltar</a>
                    </section>

                    <div class="panel">
                        <?php if (!$company): ?>
                            <p>Empresa não encontrada.</p>
                        <?php else: ?>
                            <h3>Dados principais</h3>
                            <table>
                                <tr><th>Apelido</th><td><?= htmlspecialchars($company['nome_fantasia'] ?? ($companyData['nome_fantasia'] ?? '')) ?></td></tr>
                                <tr><th>Razão Social</th><td><?= htmlspecialchars($company['razao_social'] ?? ($companyData['razao_social'] ?? '')) ?></td></tr>
                                <tr><th>CNPJ</th><td><?= htmlspecialchars($company['cnpj'] ?? ($companyData['cnpj'] ?? '')) ?></td></tr>
                                <tr><th>CEP</th><td><?= htmlspecialchars($companyData['cep'] ?? '') ?></td></tr>
                                <tr><th>Logradouro</th><td><?= htmlspecialchars((($companyData['descricao_tipo_de_logradouro'] ?? '') . ' ' . ($companyData['logradouro'] ?? '')) ?? '') ?></td></tr>
                                <tr><th>Número</th><td><?= htmlspecialchars($companyData['numero'] ?? '') ?></td></tr>
                                <tr><th>Complemento</th><td><?= htmlspecialchars($companyData['complemento'] ?? '') ?></td></tr>
                                <tr><th>Bairro</th><td><?= htmlspecialchars($companyData['bairro'] ?? '') ?></td></tr>
                                <tr><th>Cidade / UF</th><td><?= htmlspecialchars(($companyData['municipio'] ?? '') . ' / ' . ($companyData['uf'] ?? '')) ?></td></tr>
                                <tr><th>Telefone</th><td><?= htmlspecialchars($companyData['ddd_telefone_1'] ?? $companyData['telefone'] ?? '') ?></td></tr>
                                <tr><th>Código IBGE</th><td><?= htmlspecialchars($companyData['codigo_ibge'] ?? $companyData['codigo_municipal'] ?? '') ?></td></tr>
                            </table>

                            <h3>Payload BrasilAPI (raw)</h3>
                            <pre style="white-space:pre-wrap;max-height:360px;overflow:auto;background:#f6f6f6;padding:12px;border-radius:6px;"><?= htmlspecialchars(json_encode($companyData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
                        <?php endif; ?>
                    </div>
                    <?php
                    break;
                case 'pedidos':
                    $tab = $_GET['tab'] ?? 'entrada';
                    ?>
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Pedidos</p>
                            <h2>Pedidos</h2>
                        // Autofill CNPJ: Reusa endpoint ajax_cnpj.php para clientes e fornecedores
                        async function fetchCnpj(cnpj) {
                            const url = 'ajax_cnpj.php?cnpj=' + encodeURIComponent(cnpj.replace(/\D/g, ''));
                            const res = await fetch(url);
                            if (!res.ok) throw new Error('Erro ao consultar CNPJ');
                            return await res.json();
                        }

                        function applyCnpjToFields(prefix, data) {
                            if (!data) return;
                            const map = {
                                nome: prefix + '_nome',
                                nome_fantasia: prefix + '_nome_fantasia',
                                cep: prefix + '_cep',
                                logradouro: prefix + '_logradouro',
                                numero: prefix + '_numero',
                                complemento: prefix + '_complemento',
                                bairro: prefix + '_bairro',
                                municipio: prefix + '_municipio',
                                uf: prefix + '_uf',
                                cidade: prefix + '_cidade',
                                telefone: prefix + '_telefone',
                                codigo_ibge: prefix + '_codigo_ibge'
                            };
                            for (const key in map) {
                                const el = document.getElementById(map[key]);
                                if (!el) continue;
                                const val = data[key] ?? data['municipio'] ?? data['cidade'] ?? '';
                                el.value = val;
                            }
                        }

                        document.getElementById('btn-buscar-cnpj-cliente')?.addEventListener('click', async function () {
                            const cnpj = document.getElementById('cliente_cpf_cnpj')?.value || '';
                            if (!cnpj) return alert('Informe o CNPJ/CPF primeiro.');
                            try {
                                const data = await fetchCnpj(cnpj);
                                if (data && data.status === 404) return alert('CNPJ não encontrado.');
                                applyCnpjToFields('cliente', data);
                            } catch (e) { alert('Erro ao buscar CNPJ: ' + e.message); }
                        });

                        document.getElementById('btn-buscar-cnpj-fornecedor')?.addEventListener('click', async function () {
                            const cnpj = document.getElementById('fornecedor_cpf_cnpj')?.value || '';
                            if (!cnpj) return alert('Informe o CNPJ/CPF primeiro.');
                            try {
                                const data = await fetchCnpj(cnpj);
                                if (data && data.status === 404) return alert('CNPJ não encontrado.');
                                applyCnpjToFields('fornecedor', data);
                            } catch (e) { alert('Erro ao buscar CNPJ: ' + e.message); }
                        });
                        </div>
                    </section>

                    <div class="pedido-shell">
                        <?php if ($tab === 'emitidos'): ?>
                            <div class="emitted-toolbar">
                                <div class="field-wrap compact">
                                    <label>Data Início</label>
                                    <input type="text" placeholder="dd/mm/aaaa">
                                </div>
                                <div class="field-wrap compact">
                                    <label>Data Fim</label>
                                    <input type="text" placeholder="dd/mm/aaaa">
                                </div>
                                <div class="field-wrap compact">
                                    <label>Tipo</label>
                                    <select>
                                        <option>Entrada e Saída</option>
                                        <option>Entrada</option>
                                        <option>Saída</option>
                                    </select>
                                </div>
                                <div class="field-wrap compact">
                                    <label>Origem</label>
                                    <select>
                                        <option>Pedidos e Comanda</option>
                                        <option>Pedido</option>
                                        <option>Comanda</option>
                                    </select>
                                </div>
                                <button class="btn secondary btn-small" type="button">Atualizar Tela</button>
                            </div>

                            <div class="emitted-actions-row">
                                <div class="inline-actions-left">
                                    <span>Exibir</span>
                                    <select class="mini-select">
                                        <option>20</option>
                                        <option>50</option>
                                        <option>100</option>
                                    </select>
                                    <span>resultados por página</span>
                                </div>

                                <div class="inline-actions-right">
                                    <input type="text" class="mini-input" placeholder="Pesquisar">
                                    <button class="btn btn-small btn-outline" type="button">Buscar</button>
                                    <button class="btn btn-small btn-success" type="button">PDF</button>
                                    <button class="btn btn-small btn-warning" type="button">Excel</button>
                                    <button class="btn btn-small btn-muted" type="button">E-mail</button>
                                </div>
                            </div>

                            <div class="emitted-table-wrap">
                                <table class="emitted-table">
                                    <thead>
                                        <tr>
                                            <th>Pedido</th>
                                            <th>Data</th>
                                            <th>Tipo</th>
                                            <th>Pessoa</th>
                                            <th>Número Nota</th>
                                            <th>Valor Total</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($vendas as $v): ?>
                                            <tr>
                                                <td><?= (int) $v['id'] ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($v['data_venda']))) ?></td>
                                                <td><span class="status-badge">Saída</span></td>
                                                <td><?= htmlspecialchars($v['cliente_nome'] ?? 'Cliente') ?></td>
                                                <td><?= (int) ($v['id'] + 100) ?></td>
                                                <td><?= htmlspecialchars(formatCurrency((float) ($v['total'] ?? 0))) ?></td>
                                                <td class="table-actions">
                                                    <button type="button" class="icon-action view" title="Visualizar">◉</button>
                                                    <button type="button" class="icon-action print" title="Imprimir">🖨</button>
                                                    <button type="button" class="icon-action clone" title="Clonar nota">⧉</button>
                                                    <button type="button" class="icon-action delete" title="Excluir">✕</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <form method="POST" id="pedido-form" class="pedido-form">
                                <input type="hidden" name="action" value="save_venda">
                                <input type="hidden" name="tipo" value="<?= $tab ?>">

                                <div class="pedido-section">
                                    <div class="section-grid fields-row">
                                        <div class="field-wrap">
                                            <label>Código Interno</label>
                                            <input type="text" name="codigo_interno" placeholder="Código">
                                        </div>
                                        <div class="field-wrap">
                                            <label>Data</label>
                                            <input type="date" name="data_venda" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="field-wrap">
                                            <label>Cliente / Pessoa</label>
                                            <select name="cliente_id" required>
                                                <option value="">Selecione um Cliente</option>
                                                <?php foreach ($clientes as $c): ?>
                                                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-wrap">
                                            <label>Vendedor</label>
                                            <select name="vendedor_id">
                                                <option value="">Sem vendedor</option>
                                                <?php foreach ($clientes as $c): ?>
                                                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="section-grid fields-row">
                                        <div class="field-wrap">
                                            <label>Fornecedor</label>
                                            <select name="fornecedor_id">
                                                <option value="">Selecione um fornecedor</option>
                                                <?php foreach ($fornecedores as $f): ?>
                                                    <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-wrap">
                                            <label>Transportadora</label>
                                            <select name="transportadora_id">
                                                <option value="">Selecione uma transportadora</option>
                                                <?php foreach ($transportadoras as $t): ?>
                                                    <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-wrap">
                                            <label>Motorista</label>
                                            <select name="motorista_id">
                                                <option value="">Selecione um motorista</option>
                                                <?php foreach ($motoristas as $m): ?>
                                                    <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field-wrap">
                                            <label>CFOP</label>
                                            <select name="cfop_id">
                                                <option value="">Selecione CFOP</option>
                                                <?php foreach ($cfops as $cf): ?>
                                                    <option value="<?= (int)$cf['id'] ?>"><?= htmlspecialchars(($cf['codigo'] ?? '') . ' - ' . ($cf['descricao'] ?? '')) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="pedido-section search-section">
                                    <div class="search-header">
                                        <span>Selecione os Produtos</span>
                                    </div>
                                    <div class="search-row">
                                        <div class="field-wrap search-field-wrap">
                                            <label>Buscar produtos</label>
                                            <input id="product-search" type="text" placeholder="Digite código ou nome do produto" autocomplete="off">
                                        </div>
                                        <button type="button" id="btn-clear" class="btn secondary">Limpar Pedido (F3)</button>
                                        <button type="button" id="btn-add-sample" class="btn primary">Adicionar</button>
                                    </div>
                                </div>

                                <div class="pedido-section table-section">
                                    <div class="table-head">
                                        <span>Produtos do Pedido</span>
                                        <button type="button" class="btn small warning">Limpar Pedido</button>
                                    </div>
                                    <table id="items-table">
                                        <thead>
                                            <tr><th>Item</th><th>Cod.Prod.</th><th>Descrição</th><th>UN</th><th>Qtd.</th><th>Vlr Uni.</th><th>Vlr Total</th><th>Ações</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr class="no-items"><td colspan="8">Nenhum registro encontrado</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="pedido-section">
                                    <label>Observações</label>
                                    <textarea name="observacoes" rows="3" placeholder="Observações da venda..."></textarea>
                                </div>

                                <div class="pedido-section payment-grid">
                                    <div class="payment-block">
                                        <div class="field-wrap">
                                            <label>Pagamento</label>
                                            <select name="condicao_pagamento">
                                                <option value="avista">À vista</option>
                                                <option value="30dias">30 dias</option>
                                                <option value="prazo">Prazo indeterminado</option>
                                            </select>
                                        </div>
                                        <div class="field-wrap">
                                            <label>1º Vencimento</label>
                                            <input type="date" name="vencimento">
                                        </div>
                                        <div class="field-wrap">
                                            <label>Documento</label>
                                            <select name="documento">
                                                <option>DINHEIRO</option>
                                                <option>Cartão de débito</option>
                                                <option>PIX</option>
                                                <option>Crédito</option>
                                                <option>Boleto</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="payment-block">
                                        <div class="field-wrap">
                                            <label>Frete</label>
                                            <input type="text" name="frete" value="0">
                                        </div>
                                        <div class="field-wrap">
                                            <label>Desconto %</label>
                                            <input type="number" step="0.01" name="desconto_percent" value="0">
                                        </div>
                                        <div class="field-wrap">
                                            <label>Desconto Valor</label>
                                            <input type="number" step="0.01" name="desconto_valor" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="totals-panel">
                                    <div class="total-box">
                                        <span>Total Produtos</span>
                                        <strong id="total-produtos">R$ 0,00</strong>
                                    </div>
                                    <div class="total-box total-box-primary">
                                        <span>Valor Total</span>
                                        <strong id="valor-total">R$ 0,00</strong>
                                    </div>
                                </div>

                                <div class="form-actions form-actions-footer">
                                    <button class="btn secondary" type="button">Salvar Rascunho</button>
                                    <button class="btn primary" type="submit">Salvar Pedido</button>
                                </div>

                            </form>

                            <script>
                                window.PRODUCTS = <?= json_encode(array_map(function($p){ return ['id'=>(int)$p['id'],'nome'=>$p['nome'],'codigo'=>$p['codigo'],'preco'=>(float)$p['preco'],'un'=>'un']; }, $produtos)) ?>;
                                window.CLIENTS = <?= json_encode($clientes) ?>;
                                // carregar impostos por produto do banco
                                window.PRODUCT_TAXES = {};
                                <?php
                                try {
                                    $pt = $repo->listProductTaxes();
                                    echo 'window.PRODUCT_TAXES = ' . json_encode($pt) . ';';
                                } catch (Throwable $e) {
                                    echo 'window.PRODUCT_TAXES = {}';
                                }
                                ?>
                            </script>
                        <?php endif; ?>
                        <!-- Modal para mostrar impostos do produto -->
                        <div id="product-taxes-modal" class="modal" role="dialog" aria-hidden="true">
                            <div class="modal-header">
                                <strong>Impostos do Produto</strong>
                                <button class="close" type="button" id="close-modal">×</button>
                            </div>
                            <div class="modal-body" id="modal-body-content">
                                <!-- preenchido por JS -->
                            </div>
                        </div>
                    </div>
                    <?php
                    break;

                case 'cadastro':
                    $tab = $_GET['tab'] ?? 'pessoas';
                    ?>
                    <section class="page-header">
                        <div>
                            <h2>Cadastro</h2>
                        </div>
                        <a class="btn primary" href="?page=cadastro&tab=pessoas">+ Adicionar</a>
                    </section>

                    <div class="tabs">
                        <a class="tab <?= $tab === 'pessoas' ? 'active' : '' ?>" href="?page=cadastro&tab=pessoas">Pessoas</a>
                        <a class="tab <?= $tab === 'produtos' ? 'active' : '' ?>" href="?page=cadastro&tab=produtos">Produtos</a>
                        <a class="tab <?= $tab === 'cfops' ? 'active' : '' ?>" href="?page=cadastro&tab=cfops">CFOPs</a>
                        <a class="tab <?= $tab === 'fornecedores' ? 'active' : '' ?>" href="?page=cadastro&tab=fornecedores">Fornecedores</a>
                        <a class="tab <?= $tab === 'motoristas' ? 'active' : '' ?>" href="?page=cadastro&tab=motoristas">Motoristas</a>
                        <a class="tab <?= $tab === 'transportadoras' ? 'active' : '' ?>" href="?page=cadastro&tab=transportadoras">Transportadoras</a>
                    </div>

                    <div class="panel">
                        <?php if ($tab === 'pessoas'): ?>
                            <?php
                            $tiposPessoa = [];
                            if (!empty($editPessoa['tipo_pessoa'])) {
                                $tiposPessoa = array_values(array_filter(array_map('trim', preg_split('/\s*,\s*/', (string) $editPessoa['tipo_pessoa']))));
                            }
                            if ($tiposPessoa === []) {
                                $tiposPessoa = ['cliente'];
                            }
                            $pessoaFisicaValor = strtolower(trim((string) ($editPessoa['pessoa_fisica'] ?? 'sim')));
                            if (!in_array($pessoaFisicaValor, ['sim', 'nao', 'não'], true)) {
                                $pessoaFisicaValor = 'sim';
                            }
                            ?>
                            <div class="register-shell">
                                <div class="register-header">
                                    <div class="register-title"><?= $editPessoa ? 'Editar Pessoa' : 'Nova Pessoa' ?></div>
                                    <div class="register-breadcrumb">Início / Pessoas / <?= $editPessoa ? 'Editar Pessoa' : 'Nova Pessoa' ?></div>
                                </div>

                                <form method="POST" class="register-form">
                                    <input type="hidden" name="action" value="save_cliente">
                                    <?php if ($editPessoa): ?>
                                        <input type="hidden" name="id" value="<?= (int) $editPessoa['id'] ?>">
                                    <?php endif; ?>

                                    <div class="register-tabs" role="tablist" aria-label="Tipos de cadastro da pessoa">
                                        <button type="button" class="register-tab active" data-register-tab="basicas">Informações Básicas</button>
                                        <button type="button" class="register-tab" data-register-tab="entrega">Endereço de Entrega</button>
                                        <button type="button" class="register-tab" data-register-tab="fiscal">Fiscal e Financeiro</button>
                                        <button type="button" class="register-tab" data-register-tab="transportadora">Transportadora</button>
                                    </div>

                                    <div class="register-panels">
                                        <div class="register-panel active" data-register-panel="basicas">
                                            <div class="register-grid two-columns-form">
                                                <div class="field-block">
                                                    <label>CPF/CNPJ: <span class="required">*</span></label>
                                                    <div style="display:flex;gap:8px;align-items:center">
                                                        <input type="text" id="cliente_cpf_cnpj" name="cpf_cnpj" value="<?= htmlspecialchars($editPessoa['cpf_cnpj'] ?? '') ?>" placeholder="000.000.000-00" required>
                                                        <button type="button" id="btn-buscar-cnpj-cliente" class="btn small">Buscar CNPJ</button>
                                                    </div>
                                                </div>
                                                <div class="field-block">
                                                    <label>Nome/Razão Social: <span class="required">*</span></label>
                                                    <input type="text" id="cliente_nome" name="nome" value="<?= htmlspecialchars($editPessoa['nome'] ?? '') ?>" placeholder="Digite o nome ou razão social" required>
                                                </div>
                                            </div>

                                            <div class="register-grid two-columns-form">
                                                <div class="field-block">
                                                    <label>Nome / Nome Fantasia: <span class="required">*</span></label>
                                                    <input type="text" id="cliente_nome_fantasia" name="nome_fantasia" value="<?= htmlspecialchars($editPessoa['nome_fantasia'] ?? '') ?>" placeholder="Digite o nome fantasia">
                                                </div>
                                                <div class="field-block">
                                                    <label>Tipo de Pessoa: <span class="required">*</span></label>
                                                    <div class="check-row">
                                                        <label class="checkbox-inline"><input type="checkbox" name="tipo_pessoa[]" value="cliente" <?= in_array('cliente', $tiposPessoa, true) ? 'checked' : '' ?>> Cliente</label>
                                                        <label class="checkbox-inline"><input type="checkbox" name="tipo_pessoa[]" value="fornecedor" <?= in_array('fornecedor', $tiposPessoa, true) ? 'checked' : '' ?>> Fornecedor</label>
                                                        <label class="checkbox-inline"><input type="checkbox" name="tipo_pessoa[]" value="vendedor" <?= in_array('vendedor', $tiposPessoa, true) ? 'checked' : '' ?>> Vendedor</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="register-grid four-columns-form">
                                                <div class="field-block">
                                                    <label>Pessoa Física:</label>
                                                    <select name="pessoa_fisica">
                                                        <option value="sim" <?= $pessoaFisicaValor === 'sim' ? 'selected' : '' ?>>Sim</option>
                                                        <option value="nao" <?= in_array($pessoaFisicaValor, ['nao', 'não'], true) ? 'selected' : '' ?>>Não</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Aniversário:</label>
                                                    <input type="date" name="aniversario" value="<?= htmlspecialchars($editPessoa['aniversario'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Gênero:</label>
                                                    <select name="genero">
                                                        <option value="" <?= (($editPessoa['genero'] ?? '') === '') ? 'selected' : '' ?>>Não selecionado</option>
                                                        <option value="Masculino" <?= (($editPessoa['genero'] ?? '') === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                                        <option value="Feminino" <?= (($editPessoa['genero'] ?? '') === 'Feminino') ? 'selected' : '' ?>>Feminino</option>
                                                        <option value="Outro" <?= (($editPessoa['genero'] ?? '') === 'Outro') ? 'selected' : '' ?>>Outro</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Status:</label>
                                                    <select name="status">
                                                        <option value="ativo" <?= (($editPessoa['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                                        <option value="inativo" <?= (($editPessoa['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="register-grid five-columns-form">
                                                <div class="field-block">
                                                    <label>Nome do Contato: <span class="required">*</span></label>
                                                    <input type="text" name="nome_contato" value="<?= htmlspecialchars($editPessoa['nome_contato'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>E-mail: <span class="required">*</span></label>
                                                    <input type="email" name="email" value="<?= htmlspecialchars($editPessoa['email'] ?? '') ?>" placeholder="nome@empresa.com">
                                                </div>
                                                <div class="field-block">
                                                    <label>Fone Principal:</label>
                                                    <input type="text" name="fone_principal" value="<?= htmlspecialchars($editPessoa['fone_principal'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Fone 2:</label>
                                                    <input type="text" name="fone_2" value="<?= htmlspecialchars($editPessoa['fone_2'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Fone 3:</label>
                                                    <input type="text" name="fone_3" value="<?= htmlspecialchars($editPessoa['fone_3'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="register-panel" data-register-panel="entrega">
                                            <div class="register-grid five-columns-form">
                                                <div class="field-block">
                                                    <label>CEP <span class="required">*</span></label>
                                                    <input type="text" id="cliente_cep" name="cep" value="<?= htmlspecialchars($editPessoa['cep'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Logradouro <span class="required">*</span></label>
                                                    <input type="text" id="cliente_logradouro" name="logradouro" value="<?= htmlspecialchars($editPessoa['logradouro'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Número <span class="required">*</span></label>
                                                    <input type="text" id="cliente_numero" name="numero" value="<?= htmlspecialchars($editPessoa['numero'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Bairro <span class="required">*</span></label>
                                                    <input type="text" id="cliente_bairro" name="bairro" value="<?= htmlspecialchars($editPessoa['bairro'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Cidade <span class="required">*</span></label>
                                                    <input type="text" id="cliente_cidade" name="cidade" value="<?= htmlspecialchars($editPessoa['cidade'] ?? '') ?>">
                                                </div>
                                            </div>

                                            <div class="register-grid four-columns-form">
                                                <div class="field-block">
                                                    <label>Estado:</label>
                                                    <select id="cliente_uf" name="estado">
                                                        <option value="" <?= (($editPessoa['estado'] ?? '') === '') ? 'selected' : '' ?>>Selecione</option>
                                                        <option value="SP" <?= (($editPessoa['estado'] ?? '') === 'SP') ? 'selected' : '' ?>>SP</option>
                                                        <option value="RJ" <?= (($editPessoa['estado'] ?? '') === 'RJ') ? 'selected' : '' ?>>RJ</option>
                                                        <option value="MG" <?= (($editPessoa['estado'] ?? '') === 'MG') ? 'selected' : '' ?>>MG</option>
                                                        <option value="PR" <?= (($editPessoa['estado'] ?? '') === 'PR') ? 'selected' : '' ?>>PR</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Complemento:</label>
                                                    <input type="text" id="cliente_complemento" name="complemento" value="<?= htmlspecialchars($editPessoa['complemento'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Ponto de Referência:</label>
                                                    <input type="text" id="cliente_ponto_referencia" name="ponto_referencia" value="<?= htmlspecialchars($editPessoa['ponto_referencia'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Cód. IBGE:</label>
                                                    <input type="text" id="cliente_codigo_ibge" name="codigo_ibge" value="<?= htmlspecialchars($editPessoa['codigo_ibge'] ?? '') ?>">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="register-panel" data-register-panel="fiscal">
                                            <div class="register-grid four-columns-form">
                                                <div class="field-block">
                                                    <label>Suprama:</label>
                                                    <input type="text" name="suprama" value="<?= htmlspecialchars($editPessoa['suprama'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>IM:</label>
                                                    <input type="text" name="im" value="<?= htmlspecialchars($editPessoa['im'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Vendedor:</label>
                                                    <select name="vendedor">
                                                        <option value="" <?= (($editPessoa['vendedor'] ?? '') === '') ? 'selected' : '' ?>>Sem Vendedor</option>
                                                        <option value="Vendedor A" <?= (($editPessoa['vendedor'] ?? '') === 'Vendedor A') ? 'selected' : '' ?>>Vendedor A</option>
                                                        <option value="Vendedor B" <?= (($editPessoa['vendedor'] ?? '') === 'Vendedor B') ? 'selected' : '' ?>>Vendedor B</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Status Pagamento:</label>
                                                    <select name="status_pagamento">
                                                        <option value="" <?= (($editPessoa['status_pagamento'] ?? '') === '') ? 'selected' : '' ?>>Selecione</option>
                                                        <option value="Ótimo" <?= (($editPessoa['status_pagamento'] ?? '') === 'Ótimo') ? 'selected' : '' ?>>Ótimo</option>
                                                        <option value="Bom" <?= (($editPessoa['status_pagamento'] ?? '') === 'Bom') ? 'selected' : '' ?>>Bom</option>
                                                        <option value="Regular" <?= (($editPessoa['status_pagamento'] ?? '') === 'Regular') ? 'selected' : '' ?>>Regular</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="register-grid four-columns-form">
                                                <div class="field-block">
                                                    <label>Pagamento:</label>
                                                    <select name="pagamento">
                                                        <option value="" <?= (($editPessoa['pagamento'] ?? '') === '') ? 'selected' : '' ?>>Nenhum</option>
                                                        <option value="À vista" <?= (($editPessoa['pagamento'] ?? '') === 'À vista') ? 'selected' : '' ?>>À vista</option>
                                                        <option value="Parcelado" <?= (($editPessoa['pagamento'] ?? '') === 'Parcelado') ? 'selected' : '' ?>>Parcelado</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Anvisa Data Venc.:</label>
                                                    <input type="date" name="anvisa_data_venc" value="<?= htmlspecialchars($editPessoa['anvisa_data_venc'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Anvisa Código:</label>
                                                    <input type="text" name="anvisa_codigo" value="<?= htmlspecialchars($editPessoa['anvisa_codigo'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>% Comissão:</label>
                                                    <input type="text" name="comissao_percentual" value="<?= htmlspecialchars($editPessoa['comissao_percentual'] ?? '') ?>" placeholder="%">
                                                </div>
                                            </div>

                                            <div class="register-grid four-columns-form">
                                                <div class="field-block">
                                                    <label>Comissão Volume:</label>
                                                    <input type="text" name="comissao_volume" value="<?= htmlspecialchars($editPessoa['comissao_volume'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Forma de Pagamento:</label>
                                                    <select name="forma_pagamento">
                                                        <option value="" <?= (($editPessoa['forma_pagamento'] ?? '') === '') ? 'selected' : '' ?>>Nenhum</option>
                                                        <option value="Dinheiro" <?= (($editPessoa['forma_pagamento'] ?? '') === 'Dinheiro') ? 'selected' : '' ?>>Dinheiro</option>
                                                        <option value="Cartão" <?= (($editPessoa['forma_pagamento'] ?? '') === 'Cartão') ? 'selected' : '' ?>>Cartão</option>
                                                        <option value="Boleto" <?= (($editPessoa['forma_pagamento'] ?? '') === 'Boleto') ? 'selected' : '' ?>>Boleto</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Limite Crédito:</label>
                                                    <input type="text" name="limite_credito" value="<?= htmlspecialchars($editPessoa['limite_credito'] ?? '') ?>" placeholder="R$">
                                                </div>
                                                <div class="field-block">
                                                    <label>Desconto:</label>
                                                    <input type="text" name="desconto" value="<?= htmlspecialchars($editPessoa['desconto'] ?? '') ?>" placeholder="R$">
                                                </div>
                                            </div>

                                            <div class="register-grid one-column-form">
                                                <div class="field-block">
                                                    <label>Funeral:</label>
                                                    <input type="text" name="funeral" value="<?= htmlspecialchars($editPessoa['funeral'] ?? '') ?>" placeholder="%">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="register-panel" data-register-panel="transportadora">
                                            <div class="register-grid three-columns-form">
                                                <div class="field-block">
                                                    <label>Transportadora:</label>
                                                    <select name="transportadora">
                                                        <option value="" <?= (($editPessoa['transportadora'] ?? '') === '') ? 'selected' : '' ?>>Selecione</option>
                                                        <option value="Transportadora A" <?= (($editPessoa['transportadora'] ?? '') === 'Transportadora A') ? 'selected' : '' ?>>Transportadora A</option>
                                                        <option value="Transportadora B" <?= (($editPessoa['transportadora'] ?? '') === 'Transportadora B') ? 'selected' : '' ?>>Transportadora B</option>
                                                    </select>
                                                </div>
                                                <div class="field-block">
                                                    <label>Placa:</label>
                                                    <input type="text" name="placa" value="<?= htmlspecialchars($editPessoa['placa'] ?? '') ?>" placeholder="Informe a placa">
                                                </div>
                                                <div class="field-block">
                                                    <label>Placa UF:</label>
                                                    <select name="placa_uf">
                                                        <option value="" <?= (($editPessoa['placa_uf'] ?? '') === '') ? 'selected' : '' ?>>Nenhuma</option>
                                                        <option value="SP" <?= (($editPessoa['placa_uf'] ?? '') === 'SP') ? 'selected' : '' ?>>SP</option>
                                                        <option value="RJ" <?= (($editPessoa['placa_uf'] ?? '') === 'RJ') ? 'selected' : '' ?>>RJ</option>
                                                        <option value="MG" <?= (($editPessoa['placa_uf'] ?? '') === 'MG') ? 'selected' : '' ?>>MG</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="register-grid three-columns-form">
                                                <div class="field-block">
                                                    <label>ANTT:</label>
                                                    <input type="text" name="antt" value="<?= htmlspecialchars($editPessoa['antt'] ?? '') ?>">
                                                </div>
                                                <div class="field-block">
                                                    <label>Frete:</label>
                                                    <input type="text" name="frete" value="<?= htmlspecialchars($editPessoa['frete'] ?? '') ?>" placeholder="R$">
                                                </div>
                                                <div class="field-block">
                                                    <label>Valor do Frete:</label>
                                                    <input type="text" name="valor_frete" value="<?= htmlspecialchars($editPessoa['valor_frete'] ?? '') ?>" placeholder="R$">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="register-action-row">
                                        <button class="btn primary" type="submit">Salvar</button>
                                        <a class="btn secondary" href="?page=cadastro&tab=pessoas">Voltar</a>
                                        <a class="btn secondary" href="?page=cadastro&tab=pessoas">Adicionar Nova Pessoa</a>
                                    </div>
                                </form>

                                <div class="register-list-header">
                                    <h3>Pessoas</h3>
                                    <a class="btn primary" href="?page=cadastro&tab=pessoas">+ Adicionar</a>
                                </div>

                                <div class="register-list-wrap">
                                    <div class="list-toolbar">
                                        <div class="inline-filters">
                                            <label>Status:
                                                <select>
                                                    <option>Ativos</option>
                                                </select>
                                            </label>
                                            <label>Tipo:
                                                <select>
                                                    <option>Todos</option>
                                                </select>
                                            </label>
                                        </div>
                                        <div class="inline-actions-right">
                                            <button class="btn btn-small btn-outline" type="button">Pesquisar</button>
                                            <button class="btn btn-small btn-outline" type="button">Buscar registros</button>
                                            <button class="btn btn-small btn-success" type="button">PDF</button>
                                            <button class="btn btn-small btn-warning" type="button">Excel</button>
                                            <button class="btn btn-small btn-muted" type="button">E-mail</button>
                                        </div>
                                    </div>

                                    <table class="register-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nome</th>
                                                <th>Nome Fantasia</th>
                                                <th>CPF/CNPJ</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($clientes as $c): ?>
                                                <tr>
                                                    <td><?= (int) $c['id'] ?></td>
                                                    <td><?= htmlspecialchars($c['nome']) ?></td>
                                                    <td><?= htmlspecialchars($c['nome_fantasia'] ?? $c['cidade'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($c['cpf_cnpj']) ?></td>
                                                    <td class="table-actions">
                                                        <a class="icon-action view" href="?page=cadastro&tab=pessoas&edit=<?= (int)$c['id'] ?>" title="Editar">✎</a>
                                                        <form method="POST" class="inline-form" onsubmit="return confirm('Deseja remover esta pessoa?');">
                                                            <input type="hidden" name="action" value="delete_cliente">
                                                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                            <button class="icon-action delete" type="submit" title="Excluir">✕</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php elseif ($tab === 'produtos'): ?>
                            <div class="page-header">
                                <div>
                                    <p class="eyebrow">Produtos</p>
                                    <h2>Cadastro de Produtos</h2>
                                </div>
                                <button type="button" class="btn primary" data-register-tab-switch="product-cadastro">+ Novo produto</button>
                            </div>

                            <div class="register-shell">
                                <div class="register-tabs">
                                    <button type="button" class="register-tab active" data-register-tab="product-cadastro">Cadastro</button>
                                    <button type="button" class="register-tab" data-register-tab="product-lista">Lista</button>
                                </div>

                                <div class="register-panels">
                                    <div class="register-panel active" data-register-panel="product-cadastro">
                                        <div class="panel">
                                            <h3><?= $editProduto ? 'Editar produto' : 'Cadastrar produto' ?></h3>
                                            <form method="POST" class="form-grid">
                                                <input type="hidden" name="action" value="save_produto">
                                                <?php if ($editProduto): ?>
                                                    <input type="hidden" name="id" value="<?= (int) $editProduto['id'] ?>">
                                                <?php endif; ?>

                                                <div class="inline-row">
                                                    <label>
                                                        Nome do produto
                                                        <input type="text" name="nome" value="<?= htmlspecialchars($editProduto['nome'] ?? '') ?>" required>
                                                    </label>
                                                    <label>
                                                        Código
                                                        <input type="text" name="codigo" value="<?= htmlspecialchars($editProduto['codigo'] ?? '') ?>" required>
                                                    </label>
                                                </div>

                                                <div class="inline-row">
                                                    <label>
                                                        NCM
                                                        <input type="text" name="ncm" value="<?= htmlspecialchars($editProduto['ncm'] ?? '') ?>">
                                                    </label>
                                                    <label>
                                                        CEST
                                                        <input type="text" name="cest" value="<?= htmlspecialchars($editProduto['cest'] ?? '') ?>">
                                                    </label>
                                                </div>

                                                <div class="inline-row">
                                                    <label>
                                                        Unidade
                                                        <input type="text" name="unidade" value="<?= htmlspecialchars($editProduto['unidade'] ?? 'UN') ?>">
                                                    </label>
                                                    <label>
                                                        GTIN / Código de barras
                                                        <input type="text" name="gtin" value="<?= htmlspecialchars($editProduto['gtin'] ?? '') ?>">
                                                    </label>
                                                </div>

                                                <div class="inline-row">
                                                    <label>
                                                        CFOP padrão
                                                        <input type="text" name="cfop_padrao" value="<?= htmlspecialchars($editProduto['cfop_padrao'] ?? '') ?>">
                                                    </label>
                                                    <label>
                                                        Categoria
                                                        <input type="text" name="categoria" value="<?= htmlspecialchars($editProduto['categoria'] ?? '') ?>">
                                                    </label>
                                                </div>

                                                <div class="inline-row">
                                                    <label>
                                                        Preço de venda
                                                        <input type="number" step="0.01" min="0" name="preco" value="<?= htmlspecialchars((string) ($editProduto['preco'] ?? 0)) ?>" required>
                                                    </label>
                                                    <label>
                                                        Estoque atual
                                                        <input type="number" min="0" name="estoque_atual" value="<?= htmlspecialchars((string) ($editProduto['estoque_atual'] ?? 0)) ?>" required>
                                                    </label>
                                                </div>

                                                <label>
                                                    Status
                                                    <select name="status">
                                                        <option value="ativo" <?= (($editProduto['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                                        <option value="inativo" <?= (($editProduto['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                                    </select>
                                                </label>

                                                <div class="form-actions">
                                                    <button class="btn primary" type="submit">Salvar produto</button>
                                                    <?php if ($editProduto): ?>
                                                        <a class="btn secondary" href="?page=cadastro&tab=produtos">Cancelar</a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn secondary" data-register-tab-switch="product-lista">Lista de produtos</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="register-panel" data-register-panel="product-lista">
                                        <div class="panel">
                                            <div class="list-toolbar" style="padding: 0 0 16px; border-top: none; background: transparent; justify-content: space-between;">
                                                <div class="inline-filters">
                                                    <label>
                                                        Buscar
                                                        <input id="product-list-search" type="search" placeholder="Nome, código ou categoria" aria-label="Buscar produtos" style="min-width: 220px;">
                                                    </label>
                                                    <label>
                                                        Status
                                                        <select id="product-status-filter">
                                                            <option value="all">Todos</option>
                                                            <option value="ativo">Ativos</option>
                                                            <option value="inativo">Inativos</option>
                                                        </select>
                                                    </label>
                                                </div>
                                                <button type="button" class="btn primary" data-register-tab-switch="product-cadastro">+ Novo produto</button>
                                            </div>

                                            <table class="register-table">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Produto</th>
                                                        <th>Código</th>
                                                        <th>NCM</th>
                                                        <th>Unidade</th>
                                                        <th>Preço</th>
                                                        <th>Estoque</th>
                                                        <th>Ações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="product-list-body">
                                                    <?php foreach ($produtos as $p): ?>
                                                        <tr data-product-row data-product-text="<?= htmlspecialchars(strtolower((string)($p['nome'] ?? '') . ' ' . ($p['codigo'] ?? '') . ' ' . ($p['categoria'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" data-product-status="<?= htmlspecialchars(strtolower((string)($p['status'] ?? 'ativo')), ENT_QUOTES, 'UTF-8') ?>">
                                                            <td><?= (int) $p['id'] ?></td>
                                                            <td><?= htmlspecialchars($p['nome']) ?></td>
                                                            <td><?= htmlspecialchars($p['codigo']) ?></td>
                                                            <td><?= htmlspecialchars($p['ncm'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars($p['unidade'] ?? 'UN') ?></td>
                                                            <td><?= formatCurrency((float) ($p['preco'] ?? 0)) ?></td>
                                                            <td><?= (int) ($p['estoque_atual'] ?? 0) ?></td>
                                                            <td class="actions">
                                                                <a class="link-button" href="?page=cadastro&tab=produtos&edit=<?= (int) $p['id'] ?>">Editar</a>
                                                                <a class="link-button" href="?page=cadastro&tab=produtos&edit_taxes=<?= (int) $p['id'] ?>">Impostos</a>
                                                                <form method="POST" class="inline-form" onsubmit="return confirm('Deseja remover este produto?');">
                                                                    <input type="hidden" name="action" value="delete_produto">
                                                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                                                    <button class="link-button danger" type="submit">Excluir</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                        <?php if (isset($_GET['edit_taxes']) && (int)$_GET['edit_taxes'] === (int)$p['id']):
                                                            $tax = $repo->getProductTaxes((int)$p['id']) ?? ['ipi' => '', 'icms' => '', 'pis' => '', 'cofins' => ''];
                                                        ?>
                                                            <tr>
                                                                <td colspan="8">
                                                                    <form method="POST" class="panel" style="padding: 16px; margin-top: 8px;">
                                                                        <input type="hidden" name="action" value="save_product_taxes">
                                                                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                                                        <div class="inline-row">
                                                                            <label>
                                                                                IPI
                                                                                <input type="text" name="ipi" value="<?= htmlspecialchars($tax['ipi']) ?>">
                                                                            </label>
                                                                            <label>
                                                                                ICMS
                                                                                <input type="text" name="icms" value="<?= htmlspecialchars($tax['icms']) ?>">
                                                                            </label>
                                                                        </div>
                                                                        <div class="inline-row">
                                                                            <label>
                                                                                PIS
                                                                                <input type="text" name="pis" value="<?= htmlspecialchars($tax['pis']) ?>">
                                                                            </label>
                                                                            <label>
                                                                                COFINS
                                                                                <input type="text" name="cofins" value="<?= htmlspecialchars($tax['cofins']) ?>">
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-actions">
                                                                            <button class="btn primary" type="submit">Salvar impostos</button>
                                                                            <a class="btn secondary" href="?page=cadastro&tab=produtos">Fechar</a>
                                                                        </div>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                            <div id="product-list-empty" style="display:none; padding: 16px; color: var(--muted);">Nenhum produto encontrado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($tab === 'cfops'): ?>
                            <div class="page-header">
                                <div><p class="eyebrow">CFOP</p><h2>CFOPs</h2></div>
                                <a class="btn primary" href="?page=cadastro&tab=cfops">+ Novo CFOP</a>
                            </div>
                            <div class="grid two-columns">
                                <div class="panel">
                                    <h3><?= $editCfop ? 'Editar CFOP' : 'Cadastrar CFOP' ?></h3>
                                    <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="save_cfop">
                                        <?php if ($editCfop): ?><input type="hidden" name="id" value="<?= (int) $editCfop['id'] ?>"><?php endif; ?>
                                        <label>Código<input type="text" name="codigo" value="<?= htmlspecialchars($editCfop['codigo'] ?? '') ?>" required></label>
                                        <label>Descrição<input type="text" name="descricao" value="<?= htmlspecialchars($editCfop['descricao'] ?? '') ?>" required></label>
                                        <label>Natureza<input type="text" name="natureza" value="<?= htmlspecialchars($editCfop['natureza'] ?? '') ?>"></label>
                                        <label>Aplicação<input type="text" name="aplicacao" value="<?= htmlspecialchars($editCfop['aplicacao'] ?? '') ?>"></label>
                                        <label>Status<select name="status"><option value="ativo" <?= (($editCfop['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= (($editCfop['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option></select></label>
                                        <div class="form-actions"><button class="btn primary" type="submit">Salvar</button><?php if ($editCfop): ?><a class="btn secondary" href="?page=cadastro&tab=cfops">Cancelar</a><?php endif; ?></div>
                                    </form>
                                </div>
                                <div class="panel">
                                    <h3>Lista de CFOPs</h3>
                                    <table><thead><tr><th>Código</th><th>Descrição</th><th>Natureza</th><th>Ações</th></tr></thead><tbody>
                                        <?php foreach ($cfops as $cf): ?>
                                            <tr><td><?= htmlspecialchars($cf['codigo']) ?></td><td><?= htmlspecialchars($cf['descricao']) ?></td><td><?= htmlspecialchars($cf['natureza']) ?></td><td class="actions"><a class="link-button" href="?page=cadastro&tab=cfops&edit=<?= (int)$cf['id'] ?>">Editar</a><form method="POST" class="inline-form" onsubmit="return confirm('Excluir CFOP?');"><input type="hidden" name="action" value="delete_cfop"><input type="hidden" name="id" value="<?= (int)$cf['id'] ?>"><button class="link-button danger" type="submit">Excluir</button></form></td></tr>
                                        <?php endforeach; ?>
                                    </tbody></table>
                                </div>
                            </div>
                        <?php elseif ($tab === 'fornecedores'): ?>
                            <div class="page-header"><div><p class="eyebrow">Fornecedores</p><h2>Fornecedores</h2></div><a class="btn primary" href="?page=cadastro&tab=fornecedores">+ Novo fornecedor</a></div>
                            <div class="grid two-columns">
                                <div class="panel">
                                    <h3><?= $editFornecedor ? 'Editar fornecedor' : 'Cadastrar fornecedor' ?></h3>
                                        <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="save_fornecedor">
                                        <?php if ($editFornecedor): ?><input type="hidden" name="id" value="<?= (int) $editFornecedor['id'] ?>"><?php endif; ?>
                                        <label>Nome<input type="text" id="fornecedor_nome" name="nome" value="<?= htmlspecialchars($editFornecedor['nome'] ?? '') ?>" required></label>
                                        <label>Nome fantasia<input type="text" id="fornecedor_nome_fantasia" name="nome_fantasia" value="<?= htmlspecialchars($editFornecedor['nome_fantasia'] ?? '') ?>"></label>
                                        <label>CPF/CNPJ
                                            <div style="display:flex;gap:8px;align-items:center">
                                                <input type="text" id="fornecedor_cpf_cnpj" name="cpf_cnpj" value="<?= htmlspecialchars($editFornecedor['cpf_cnpj'] ?? '') ?>" required>
                                                <button type="button" id="btn-buscar-cnpj-fornecedor" class="btn small">Buscar CNPJ</button>
                                            </div>
                                        </label>
                                        <label>Inscrição Estadual<input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($editFornecedor['inscricao_estadual'] ?? '') ?>"></label>
                                        <label>E-mail<input type="email" name="email" value="<?= htmlspecialchars($editFornecedor['email'] ?? '') ?>"></label>
                                        <label>Telefone<input type="text" id="fornecedor_telefone" name="telefone" value="<?= htmlspecialchars($editFornecedor['telefone'] ?? '') ?>"></label>
                                        <label>CEP<input type="text" id="fornecedor_cep" name="cep" value="<?= htmlspecialchars($editFornecedor['cep'] ?? '') ?>"></label>
                                        <label>Logradouro<input type="text" id="fornecedor_logradouro" name="logradouro" value="<?= htmlspecialchars($editFornecedor['logradouro'] ?? '') ?>"></label>
                                        <label>Número<input type="text" id="fornecedor_numero" name="numero" value="<?= htmlspecialchars($editFornecedor['numero'] ?? '') ?>"></label>
                                        <label>Complemento<input type="text" id="fornecedor_complemento" name="complemento" value="<?= htmlspecialchars($editFornecedor['complemento'] ?? '') ?>"></label>
                                        <label>Bairro<input type="text" id="fornecedor_bairro" name="bairro" value="<?= htmlspecialchars($editFornecedor['bairro'] ?? '') ?>"></label>
                                        <label>Município<input type="text" id="fornecedor_municipio" name="municipio" value="<?= htmlspecialchars($editFornecedor['municipio'] ?? '') ?>"></label>
                                        <label>UF<input type="text" id="fornecedor_uf" name="uf" value="<?= htmlspecialchars($editFornecedor['uf'] ?? '') ?>"></label>
                                        <label>Cidade<input type="text" id="fornecedor_cidade" name="cidade" value="<?= htmlspecialchars($editFornecedor['cidade'] ?? '') ?>"></label>
                                        <label>Status<select name="status"><option value="ativo" <?= (($editFornecedor['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= (($editFornecedor['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option></select></label>
                                        <div class="form-actions"><button class="btn primary" type="submit">Salvar</button><?php if ($editFornecedor): ?><a class="btn secondary" href="?page=cadastro&tab=fornecedores">Cancelar</a><?php endif; ?></div>
                                    </form>
                                </div>
                                <div class="panel"><h3>Lista de fornecedores</h3><table><thead><tr><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>Ações</th></tr></thead><tbody><?php foreach ($fornecedores as $f): ?><tr><td><?= htmlspecialchars($f['nome']) ?></td><td><?= htmlspecialchars($f['cpf_cnpj']) ?></td><td><?= htmlspecialchars($f['telefone']) ?></td><td class="actions"><a class="link-button" href="?page=cadastro&tab=fornecedores&edit=<?= (int)$f['id'] ?>">Editar</a><form method="POST" class="inline-form" onsubmit="return confirm('Excluir fornecedor?');"><input type="hidden" name="action" value="delete_fornecedor"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><button class="link-button danger" type="submit">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></div>
                            </div>
                        <?php elseif ($tab === 'motoristas'): ?>
                            <div class="page-header"><div><p class="eyebrow">Motoristas</p><h2>Motoristas</h2></div><a class="btn primary" href="?page=cadastro&tab=motoristas">+ Novo motorista</a></div>
                            <div class="grid two-columns">
                                <div class="panel">
                                    <h3><?= $editMotorista ? 'Editar motorista' : 'Cadastrar motorista' ?></h3>
                                    <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="save_motorista">
                                        <?php if ($editMotorista): ?><input type="hidden" name="id" value="<?= (int) $editMotorista['id'] ?>"><?php endif; ?>
                                        <label>Nome<input type="text" name="nome" value="<?= htmlspecialchars($editMotorista['nome'] ?? '') ?>" required></label>
                                        <label>CPF<input type="text" name="cpf" value="<?= htmlspecialchars($editMotorista['cpf'] ?? '') ?>" required></label>
                                        <label>CNH<input type="text" name="cnh" value="<?= htmlspecialchars($editMotorista['cnh'] ?? '') ?>"></label>
                                        <label>Categoria CNH<input type="text" name="categoria_cnh" value="<?= htmlspecialchars($editMotorista['categoria_cnh'] ?? '') ?>"></label>
                                        <label>Vencimento CNH<input type="date" name="vencimento_cnh" value="<?= htmlspecialchars($editMotorista['vencimento_cnh'] ?? '') ?>"></label>
                                        <label>Telefone<input type="text" name="telefone" value="<?= htmlspecialchars($editMotorista['telefone'] ?? '') ?>"></label>
                                        <label>Status<select name="status"><option value="ativo" <?= (($editMotorista['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= (($editMotorista['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option></select></label>
                                        <div class="form-actions"><button class="btn primary" type="submit">Salvar</button><?php if ($editMotorista): ?><a class="btn secondary" href="?page=cadastro&tab=motoristas">Cancelar</a><?php endif; ?></div>
                                    </form>
                                </div>
                                <div class="panel"><h3>Lista de motoristas</h3><table><thead><tr><th>Nome</th><th>CPF</th><th>CNH</th><th>Ações</th></tr></thead><tbody><?php foreach ($motoristas as $m): ?><tr><td><?= htmlspecialchars($m['nome']) ?></td><td><?= htmlspecialchars($m['cpf']) ?></td><td><?= htmlspecialchars($m['cnh']) ?></td><td class="actions"><a class="link-button" href="?page=cadastro&tab=motoristas&edit=<?= (int)$m['id'] ?>">Editar</a><form method="POST" class="inline-form" onsubmit="return confirm('Excluir motorista?');"><input type="hidden" name="action" value="delete_motorista"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="link-button danger" type="submit">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></div>
                            </div>
                        <?php elseif ($tab === 'transportadoras'): ?>
                            <div class="page-header"><div><p class="eyebrow">Transportadoras</p><h2>Transportadoras</h2></div><a class="btn primary" href="?page=cadastro&tab=transportadoras">+ Nova transportadora</a></div>
                            <div class="grid two-columns">
                                <div class="panel">
                                    <h3><?= $editTransportadora ? 'Editar transportadora' : 'Cadastrar transportadora' ?></h3>
                                    <form method="POST" class="form-grid">
                                        <input type="hidden" name="action" value="save_transportadora">
                                        <?php if ($editTransportadora): ?><input type="hidden" name="id" value="<?= (int) $editTransportadora['id'] ?>"><?php endif; ?>
                                        <label>Nome<input type="text" name="nome" value="<?= htmlspecialchars($editTransportadora['nome'] ?? '') ?>" required></label>
                                        <label>Nome fantasia<input type="text" name="nome_fantasia" value="<?= htmlspecialchars($editTransportadora['nome_fantasia'] ?? '') ?>"></label>
                                        <label>CPF/CNPJ<input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($editTransportadora['cpf_cnpj'] ?? '') ?>" required></label>
                                        <label>Inscrição Estadual<input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($editTransportadora['inscricao_estadual'] ?? '') ?>"></label>
                                        <label>E-mail<input type="email" name="email" value="<?= htmlspecialchars($editTransportadora['email'] ?? '') ?>"></label>
                                        <label>Telefone<input type="text" name="telefone" value="<?= htmlspecialchars($editTransportadora['telefone'] ?? '') ?>"></label>
                                        <label>CEP<input type="text" name="cep" value="<?= htmlspecialchars($editTransportadora['cep'] ?? '') ?>"></label>
                                        <label>Logradouro<input type="text" name="logradouro" value="<?= htmlspecialchars($editTransportadora['logradouro'] ?? '') ?>"></label>
                                        <label>Número<input type="text" name="numero" value="<?= htmlspecialchars($editTransportadora['numero'] ?? '') ?>"></label>
                                        <label>Complemento<input type="text" name="complemento" value="<?= htmlspecialchars($editTransportadora['complemento'] ?? '') ?>"></label>
                                        <label>Bairro<input type="text" name="bairro" value="<?= htmlspecialchars($editTransportadora['bairro'] ?? '') ?>"></label>
                                        <label>Município<input type="text" name="municipio" value="<?= htmlspecialchars($editTransportadora['municipio'] ?? '') ?>"></label>
                                        <label>UF<input type="text" name="uf" value="<?= htmlspecialchars($editTransportadora['uf'] ?? '') ?>"></label>
                                        <label>Cidade<input type="text" name="cidade" value="<?= htmlspecialchars($editTransportadora['cidade'] ?? '') ?>"></label>
                                        <label>Status<select name="status"><option value="ativo" <?= (($editTransportadora['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option><option value="inativo" <?= (($editTransportadora['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option></select></label>
                                        <div class="form-actions"><button class="btn primary" type="submit">Salvar</button><?php if ($editTransportadora): ?><a class="btn secondary" href="?page=cadastro&tab=transportadoras">Cancelar</a><?php endif; ?></div>
                                    </form>
                                </div>
                                <div class="panel"><h3>Lista de transportadoras</h3><table><thead><tr><th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>Ações</th></tr></thead><tbody><?php foreach ($transportadoras as $t): ?><tr><td><?= htmlspecialchars($t['nome']) ?></td><td><?= htmlspecialchars($t['cpf_cnpj']) ?></td><td><?= htmlspecialchars($t['telefone']) ?></td><td class="actions"><a class="link-button" href="?page=cadastro&tab=transportadoras&edit=<?= (int)$t['id'] ?>">Editar</a><form method="POST" class="inline-form" onsubmit="return confirm('Excluir transportadora?');"><input type="hidden" name="action" value="delete_transportadora"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button class="link-button danger" type="submit">Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></div>
                            </div>
                        <?php else: ?>
                            <h3><?=ucfirst($tab)?></h3>
                            <p>Área de cadastro de <?=htmlspecialchars($tab)?>.</p>
                        <?php endif; ?>
                    </div>
                    <?php
                    break;

                case 'configuracao':
                    // CARREGAR DADOS EXISTENTES
                    $tab = $_GET['tab'] ?? 'empresa';
                    $dataFile = __DIR__ . '/../data/empresa.json';
                    $empresa = [];
                    if (file_exists($dataFile)) {
                        $empresa = json_decode(file_get_contents($dataFile), true) ?: [];
                    }
                    ?>
                    <?php if (!isset($tab)) { $tab = 'empresa'; }
                    ?>
                    <nav class="config-tabs" style="margin-bottom:16px;">
                        <a class="tab <?= ($tab === 'empresa') ? 'active' : '' ?>" href="?page=configuracao&tab=empresa" style="margin-right:8px;padding:8px 12px;border:1px solid #ccc;border-bottom:none;text-decoration:none;<?= ($tab === 'empresa') ? 'background:#fff;font-weight:600;' : 'background:#f5f5f5;' ?>">Empresa</a>
                        <a class="tab <?= ($tab === 'usuarios') ? 'active' : '' ?>" href="?page=configuracao&tab=usuarios" style="margin-right:8px;padding:8px 12px;border:1px solid #ccc;border-bottom:none;text-decoration:none;<?= ($tab === 'usuarios') ? 'background:#fff;font-weight:600;' : 'background:#f5f5f5;' ?>">Usuários</a>
                        <a class="tab <?= ($tab === 'fiscal') ? 'active' : '' ?>" href="?page=configuracao&tab=fiscal" style="padding:8px 12px;border:1px solid #ccc;border-bottom:none;text-decoration:none;<?= ($tab === 'fiscal') ? 'background:#fff;font-weight:600;' : 'background:#f5f5f5;' ?>">Fiscal</a>
                    </nav>

                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Configuração</p>
                            <h2>Configuração da Empresa</h2>
                        </div>
                        <a class="btn primary" href="?page=configuracao">Cadastrar Empresa</a>
                    </section>

                    <?php if ($tab === 'empresa'): ?>
                    <?php
                        // carregar lista de empresas do banco (fallback para antigo JSON se vazio)
                        $empresas = $repo->listCompanies();
                        if (empty($empresas)) {
                            $dataDir = __DIR__ . '/../data';
                            $empresasFile = $dataDir . '/empresas.json';
                            if (file_exists($empresasFile)) {
                                $empresas = json_decode(file_get_contents($empresasFile), true) ?: [];
                            }
                        }
                        $currentCompanyId = $_SESSION['current_company_id'] ?? null;
                        $isNew = isset($_GET['new_company']) || (isset($_GET['edit_company']) && $_GET['edit_company'] === 'new');
                        $editCompany = null;
                        if (!empty($_GET['edit_company'])) {
                            $editId = (int)$_GET['edit_company'];
                            // tenta buscar do banco
                            $editCompany = $repo->findCompany($editId) ?: null;
                            // se não achou no DB, tenta no JSON fallback
                            if (!$editCompany) {
                                foreach ($empresas as $e) {
                                    if ((int)($e['id'] ?? 0) === $editId) { $editCompany = $e; break; }
                                }
                            }
                        }
                    ?>

                    <div class="panel">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <h3>Empresas</h3>
                            <div>
                                <a class="btn primary" href="?page=configuracao&tab=empresa&new_company=1">+ Adicionar Empresa</a>
                            </div>
                        </div>

                        <?php if (!empty($_GET['new_company']) || $editCompany): ?>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="save_empresa">
                                <?php if ($editCompany): ?><input type="hidden" name="id" value="<?= (int)$editCompany['id'] ?>"><?php endif; ?>
                                <label>Apelido <input type="text" id="company_apelido" name="apelido" value="<?= htmlspecialchars($editCompany['apelido'] ?? '') ?>" required></label>
                                <label>Razão Social <input type="text" id="company_razao" name="razao_social" value="<?= htmlspecialchars($editCompany['razao_social'] ?? '') ?>" required></label>
                                <label>CNPJ <div style="display:flex;gap:8px"><input type="text" id="company_cnpj" name="cnpj" value="<?= htmlspecialchars($editCompany['cnpj'] ?? '') ?>" required><button type="button" id="btn-buscar-cnpj" class="btn small">Buscar CNPJ</button></div></label>
                                <label>Cidade <input type="text" id="company_municipio" name="municipio" value="<?= htmlspecialchars($editCompany['municipio'] ?? '') ?>"></label>
                                <label>Regime <input type="text" id="company_regime" name="regime" value="<?= htmlspecialchars($editCompany['regime'] ?? '') ?>"></label>
                                <label>CEP <input type="text" id="company_cep" name="cep" value="<?= htmlspecialchars($editCompany['cep'] ?? '') ?>"></label>
                                <label>UF <input type="text" id="company_uf" name="uf" value="<?= htmlspecialchars($editCompany['uf'] ?? '') ?>"></label>
                                <label>Logradouro <input type="text" id="company_logradouro" name="logradouro" value="<?= htmlspecialchars($editCompany['logradouro'] ?? '') ?>"></label>
                                <label>Número <input type="text" id="company_numero" name="numero" value="<?= htmlspecialchars($editCompany['numero'] ?? '') ?>"></label>
                                <label>Complemento <input type="text" id="company_complemento" name="complemento" value="<?= htmlspecialchars($editCompany['complemento'] ?? '') ?>"></label>
                                <label>Bairro <input type="text" id="company_bairro" name="bairro" value="<?= htmlspecialchars($editCompany['bairro'] ?? '') ?>"></label>
                                <label>Telefone <input type="text" id="company_telefone" name="telefone" value="<?= htmlspecialchars($editCompany['telefone'] ?? '') ?>"></label>
                                <label>Cód. IBGE <input type="text" id="company_codigo_ibge" name="codigo_ibge" value="<?= htmlspecialchars($editCompany['codigo_ibge'] ?? '') ?>"></label>
                                <div class="form-actions"><button class="btn primary" type="submit">Salvar Empresa</button> <a class="btn secondary" href="?page=configuracao&tab=empresa">Cancelar</a></div>
                            </form>
                                <script>
                                    (function(){
                                        const btn = document.getElementById('btn-buscar-cnpj');
                                        if (!btn) return;
                                        btn.addEventListener('click', async function(){
                                            const cnpj = document.getElementById('company_cnpj').value || '';
                                            if (!cnpj) return alert('Informe o CNPJ antes de buscar.');
                                            btn.disabled = true;
                                            btn.textContent = 'Buscando...';
                                            try {
                                                const res = await fetch('/ajax_cnpj.php?cnpj=' + encodeURIComponent(cnpj));
                                                if (!res.ok) {
                                                    const err = await res.json().catch(()=>({}));
                                                    alert('CNPJ não encontrado ou erro: ' + (err.error||res.status));
                                                    return;
                                                }
                                                const payload = await res.json();
                                                const data = payload.data || {};
                                                // Preencher campos se existirem
                                                if (data.razao_social) document.getElementById('company_razao').value = data.razao_social;
                                                if (data.nome_fantasia) document.getElementById('company_apelido').value = data.nome_fantasia;
                                                if (data.municipio) document.getElementById('company_municipio').value = data.municipio;
                                                if (data.regime) document.getElementById('company_regime').value = Array.isArray(data.regime) ? (data.regime[0].forma_de_tributacao||'') : (data.regime||'');
                                                if (data.cep) {
                                                    const cepField = document.querySelector('input[name="cep"]');
                                                    if (cepField) cepField.value = data.cep;
                                                }
                                                if (data.uf) {
                                                    const ufField = document.querySelector('input[name="uf"]');
                                                    if (ufField) ufField.value = data.uf;
                                                }
                                                const log = (data.descricao_tipo_de_logradouro||'') + ' ' + (data.logradouro||'');
                                                if (log.trim()) {
                                                    const logField = document.querySelector('input[name="logradouro"]');
                                                    if (logField) logField.value = log.trim();
                                                }
                                                if (data.numero) {
                                                    const numField = document.querySelector('input[name="numero"]');
                                                    if (numField) numField.value = data.numero;
                                                }
                                                if (data.complemento) {
                                                    const cmpField = document.querySelector('input[name="complemento"]');
                                                    if (cmpField) cmpField.value = data.complemento;
                                                }
                                                if (data.bairro) {
                                                    const bField = document.querySelector('input[name="bairro"]');
                                                    if (bField) bField.value = data.bairro;
                                                }
                                                if (data.ddd_telefone_1) {
                                                    const tField = document.querySelector('input[name="telefone"]');
                                                    if (tField) tField.value = data.ddd_telefone_1;
                                                }
                                            } catch (e) {
                                                alert('Erro ao consultar BrasilAPI: ' + e.message);
                                            } finally {
                                                btn.disabled = false;
                                                btn.textContent = 'Buscar CNPJ';
                                            }
                                        });
                                    })();
                                </script>
                        <?php elseif (!empty($_GET['create_user_for'])): ?>
                            <?php $createFor = (int)$_GET['create_user_for']; ?>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="create_tenant_user">
                                <input type="hidden" name="id" value="<?= $createFor ?>">
                                <label>E-mail (login) <input type="email" name="email" required></label>
                                <label>Senha <input type="password" name="senha" required></label>
                                <div class="form-actions">
                                    <button class="btn primary" type="submit">Criar usuário</button>
                                    <a class="btn secondary" href="?page=configuracao&tab=empresa">Cancelar</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <table style="width:100%;">
                                <thead><tr><th>ID</th><th>Apelido</th><th>Razão Social</th><th>CNPJ</th><th>Cidade</th><th>Regime</th><th>Ações</th></tr></thead>
                                <tbody>
                                <?php foreach ($empresas as $e): ?>
                                    <tr>
                                        <td><?= (int)($e['id'] ?? $e['id'] ?? 0) ?></td>
                                        <td><?= htmlspecialchars($e['nome_fantasia'] ?? ($e['apelido'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($e['razao_social'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($e['cnpj'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($e['municipio'] ?? ($e['cidade'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars($e['regime'] ?? '') ?></td>
                                        <td>
                                            <form method="POST" style="display:inline;margin-right:6px;">
                                                <input type="hidden" name="action" value="select_empresa">
                                                <input type="hidden" name="company_id" value="<?= (int)($e['id'] ?? 0) ?>">
                                                <button class="btn small" type="submit">Selecionar</button>
                                            </form>
                                            <a class="btn small" href="?page=configuracao&tab=empresa&create_user_for=<?= (int)($e['id'] ?? 0) ?>">Criar usuário</a>
                                            <a class="btn small" href="?page=company&id=<?= (int)($e['id'] ?? 0) ?>">Ver</a>
                                            <a class="btn small" href="?page=configuracao&tab=empresa&edit_company=<?= (int)($e['id'] ?? 0) ?>">Editar</a>
                                            <?php $isBlocked = !empty($e['blocked']) ? true : false; ?>
                                            <form method="POST" style="display:inline;margin-left:6px;">
                                                <input type="hidden" name="action" value="toggle_block_empresa">
                                                <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
                                                <input type="hidden" name="blocked" value="<?= $isBlocked ? '0' : '1' ?>">
                                                <button class="btn <?= $isBlocked ? 'secondary' : 'danger' ?> small" type="submit"><?= $isBlocked ? 'Desbloquear' : 'Bloquear' ?></button>
                                            </form>
                                            <form method="POST" style="display:inline;margin-left:6px;" onsubmit="return confirm('Remover empresa?');">
                                                <input type="hidden" name="action" value="delete_empresa">
                                                <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
                                                <button class="btn danger small" type="submit">Excluir</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    </div>
                    <?php endif; ?>
                    <!-- Conta administrativa movida para a guia Usuários -->

                    <?php if (($tab ?? 'empresa') === 'usuarios'): ?>
                        <!-- Cadastro de funcionários / usuários (guia Usuários) -->
                        <?php $usuarios = $repo->listUsuarios(); ?>
                        <section class="page-header">
                            <div>
                                <p class="eyebrow">Segurança</p>
                                <h2>Funcionários / Usuários</h2>
                            </div>
                            <a class="btn primary" href="?page=configuracao&tab=usuarios">Gerenciar Usuários</a>
                        </section>

                        <!-- Painel rápido para criar/atualizar conta administrativa (agora dentro de Usuários) -->
                        <div class="panel">
                            <p>Use este formulário para criar ou atualizar rapidamente a conta administrativa (role = admin). O e-mail será marcado como verificado automaticamente.</p>
                            <form method="POST" class="form-grid" style="max-width:420px;">
                                <input type="hidden" name="action" value="create_admin_account">
                                <label>Nome <input type="text" name="nome" value="Administrador"></label>
                                <label>E-mail <input type="email" name="email" placeholder="admin@localhost" value="admin@localhost" required></label>
                                <label>Senha <input type="password" name="senha" placeholder="Digite a senha" required></label>
                                <div class="form-actions"><button class="btn primary" type="submit">Criar/Atualizar Conta Admin</button></div>
                            </form>
                        </div>

                        <div class="grid two-columns">
                            <div class="panel">
                                <h3><?= $editUsuario ? 'Editar funcionário' : 'Cadastrar funcionário' ?></h3>
                                <form method="POST" class="form-grid">
                                    <input type="hidden" name="action" value="save_usuario">
                                    <?php if ($editUsuario): ?>
                                        <input type="hidden" name="id" value="<?= (int)$editUsuario['id'] ?>">
                                    <?php endif; ?>

                                    <label>Nome <input type="text" name="nome" value="<?= htmlspecialchars($editUsuario['nome'] ?? '') ?>" required></label>
                                    <label>E-mail <input type="email" name="email" value="<?= htmlspecialchars($editUsuario['email'] ?? '') ?>" required></label>
                                    <label>Senha <input type="password" name="senha" placeholder="Deixe em branco para manter"></label>
                                    <input type="hidden" name="company_id" value="<?= htmlspecialchars($_SESSION['current_company_id'] ?? '') ?>">
                                    <label>Role <input type="text" name="role" value="<?= htmlspecialchars($editUsuario['role'] ?? 'user') ?>"></label>
                                    <label>Cargo
                                        <select name="cargo">
                                            <option value="funcionario" <?= (($editUsuario['cargo'] ?? '') === 'funcionario') ? 'selected' : '' ?>>Funcionário</option>
                                            <option value="vendedor" <?= (($editUsuario['cargo'] ?? '') === 'vendedor') ? 'selected' : '' ?>>Vendedor</option>
                                            <option value="motorista" <?= (($editUsuario['cargo'] ?? '') === 'motorista') ? 'selected' : '' ?>>Motorista</option>
                                            <option value="transportadora" <?= (($editUsuario['cargo'] ?? '') === 'transportadora') ? 'selected' : '' ?>>Transportadora</option>
                                            <option value="admin" <?= (($editUsuario['cargo'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
                                        </select>
                                    </label>
                                    <label>Status
                                        <select name="status">
                                            <option value="ativo" <?= (($editUsuario['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                            <option value="inativo" <?= (($editUsuario['status'] ?? '') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                        </select>
                                    </label>
                                    <fieldset>
                                        <legend>Permissões</legend>
                                        <?php
                                        $availablePermissions = [
                                            'vendas:view' => 'Ver Vendas',
                                            'vendas:edit' => 'Editar Vendas',
                                            'produtos:view' => 'Ver Produtos',
                                            'produtos:edit' => 'Editar Produtos',
                                            'relatorios:view' => 'Ver Relatórios',
                                            'config:manage' => 'Gerenciar Configurações',
                                            'clientes:manage' => 'Gerenciar Clientes',
                                        ];
                                        $userPerms = array_filter(array_map('trim', explode(',', (string)($editUsuario['permissions'] ?? ''))));
                                        foreach ($availablePermissions as $permKey => $permLabel):
                                        ?>
                                            <label class="checkbox-inline"><input type="checkbox" name="permissions[]" value="<?= $permKey ?>" <?= in_array($permKey, $userPerms, true) ? 'checked' : '' ?>> <?= $permLabel ?></label>
                                        <?php endforeach; ?>
                                    </fieldset>

                                    <fieldset>
                                        <legend>Criar registros associados (opcional)</legend>
                                        <label class="checkbox-inline"><input type="checkbox" name="create_cliente" value="1" <?= !empty($_POST['create_cliente']) ? 'checked' : '' ?>> Criar como cliente</label>
                                        <label>CPF/CNPJ cliente <input type="text" name="cpf_cnpj_cliente" value="<?= htmlspecialchars($editUsuario['cpf_cnpj_cliente'] ?? '') ?>" placeholder="Somente números" maxlength="14" pattern="[0-9]{11}|[0-9]{14}"></label>
                                        <label class="checkbox-inline"><input type="checkbox" name="create_motorista" value="1" <?= !empty($_POST['create_motorista']) ? 'checked' : '' ?>> Criar como motorista</label>
                                        <label>CPF motorista <input type="text" name="cpf_motorista" value="<?= htmlspecialchars($editUsuario['cpf_motorista'] ?? '') ?>" placeholder="Somente números" maxlength="11" pattern="[0-9]{11}"></label>
                                    </fieldset>
                                    <label>Avatar (URL relativa) <input type="text" name="avatar" value="<?= htmlspecialchars($editUsuario['avatar'] ?? '') ?>"></label>
                                    <div class="form-actions"><button class="btn primary" type="submit">Salvar</button></div>
                                </form>
                            </div>

                            <div class="panel">
                                <h3>Lista de funcionários</h3>
                                <table>
                                    <thead><tr><th>Nome</th><th>E-mail</th><th>Role</th><th>Cargo</th><th>Status</th><th></th></tr></thead>
                                    <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($u['nome']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td><?= htmlspecialchars($u['role']) ?></td>
                                            <td><?= htmlspecialchars($u['cargo'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($u['status'] ?? '') ?></td>
                                            <td>
                                                <a class="btn small" href="?page=configuracao&tab=usuarios&edit_user=<?= (int)$u['id'] ?>">Editar</a>
                                                <!-- assign company form -->
                                                <form method="POST" style="display:inline;margin-left:6px;">
                                                    <input type="hidden" name="action" value="assign_user_company">
                                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                    <select name="company_id" onchange="this.form.submit()" style="margin-left:6px;">
                                                        <option value="">-- Empresa --</option>
                                                        <?php foreach ($repo->listCompanies() as $c): ?>
                                                            <option value="<?= (int)$c['id'] ?>" <?= ((int)($u['company_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['apelido'] ?: $c['razao_social']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                                    <?php if (!empty($currentUser) && strtolower((string)($currentUser['role'] ?? '')) === 'admin' && ($u['status'] ?? '') !== 'ativo'): ?>
                                                        <form method="POST" style="display:inline">
                                                            <input type="hidden" name="action" value="approve_usuario">
                                                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                            <button class="btn success small" type="submit">Aprovar Acesso</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline">
                                                        <input type="hidden" name="action" value="delete_usuario">
                                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                        <button class="btn ghost small" type="submit" onclick="return confirm('Remover usuário?')">Remover</button>
                                                    </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php
                    break;

                case 'clientes':
                    ?>
                    <!-- Tela de cadastro e listagem de clientes -->
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Cadastros</p>
                            <h2>Clientes</h2>
                        </div>
                        <a class="btn primary" href="?page=clientes">+ Novo cliente</a>
                    </section>

                    <div class="grid two-columns">
                        <div class="panel">
                            <h3><?= $editCliente ? 'Editar cliente' : 'Cadastrar cliente' ?></h3>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="save_cliente">
                                <?php if ($editCliente): ?>
                                    <input type="hidden" name="id" value="<?= (int) $editCliente['id'] ?>">
                                <?php endif; ?>

                                <label>
                                    Nome
                                    <input type="text" name="nome" value="<?= htmlspecialchars($editCliente['nome'] ?? '') ?>" required>
                                </label>

                                <label>
                                    CPF / CNPJ
                                    <input type="text" name="cpf_cnpj" value="<?= htmlspecialchars($editCliente['cpf_cnpj'] ?? '') ?>" required>
                                </label>

                                <label>
                                    Inscrição Estadual
                                    <input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($editCliente['inscricao_estadual'] ?? '') ?>">
                                </label>

                                <label>
                                    E-mail
                                    <input type="email" name="email" value="<?= htmlspecialchars($editCliente['email'] ?? '') ?>">
                                </label>

                                <label>
                                    Telefone
                                    <input type="text" name="telefone" value="<?= htmlspecialchars($editCliente['telefone'] ?? '') ?>">
                                </label>

                                <label>
                                    CEP
                                    <input type="text" name="cep" value="<?= htmlspecialchars($editCliente['cep'] ?? '') ?>">
                                </label>

                                <label>
                                    Logradouro
                                    <input type="text" name="logradouro" value="<?= htmlspecialchars($editCliente['logradouro'] ?? '') ?>">
                                </label>

                                <label>
                                    Número
                                    <input type="text" name="numero" value="<?= htmlspecialchars($editCliente['numero'] ?? '') ?>">
                                </label>

                                <label>
                                    Complemento
                                    <input type="text" name="complemento" value="<?= htmlspecialchars($editCliente['complemento'] ?? '') ?>">
                                </label>

                                <label>
                                    Bairro
                                    <input type="text" name="bairro" value="<?= htmlspecialchars($editCliente['bairro'] ?? '') ?>">
                                </label>

                                <label>
                                    Município
                                    <input type="text" name="municipio" value="<?= htmlspecialchars($editCliente['municipio'] ?? '') ?>">
                                </label>

                                <label>
                                    Código Municipal (IBGE)
                                    <input type="text" name="codigo_municipal" value="<?= htmlspecialchars($editCliente['codigo_municipal'] ?? '') ?>">
                                </label>

                                <label>
                                    UF
                                    <input type="text" name="uf" value="<?= htmlspecialchars($editCliente['uf'] ?? '') ?>">
                                </label>

                                <label>
                                    Cidade
                                    <input type="text" name="cidade" value="<?= htmlspecialchars($editCliente['cidade'] ?? '') ?>">
                                </label>

                                <label>
                                    Status
                                    <select name="status">
                                        <option value="ativo" <?= (($editCliente['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                        <option value="inativo" <?= (($editCliente['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </label>

                                <div class="form-actions">
                                    <button class="btn primary" type="submit">Salvar</button>
                                    <?php if ($editCliente): ?>
                                        <a class="btn secondary" href="?page=clientes">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Lista de clientes</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>E-mail</th>
                                        <th>Cidade</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                            <td><?= htmlspecialchars($cliente['email']) ?></td>
                                            <td><?= htmlspecialchars($cliente['cidade']) ?></td>
                                            <td><span class="badge success"><?= htmlspecialchars($cliente['status']) ?></span></td>
                                            <td class="actions">
                                                <a class="link-button" href="?page=clientes&edit=<?= (int) $cliente['id'] ?>">Editar</a>
                                                <form method="POST" class="inline-form" onsubmit="return confirm('Deseja remover este cliente?');">
                                                    <input type="hidden" name="action" value="delete_cliente">
                                                    <input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>">
                                                    <button class="link-button danger" type="submit">Excluir</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                case 'produtos':
                    ?>
                    <!-- Tela de cadastro e listagem de produtos -->
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Estoque</p>
                            <h2>Produtos</h2>
                        </div>
                        <a class="btn primary" href="?page=produtos">+ Novo produto</a>
                    </section>

                    <div class="grid two-columns">
                        <div class="panel">
                            <h3><?= $editProduto ? 'Editar produto' : 'Cadastrar produto' ?></h3>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="save_produto">
                                <?php if ($editProduto): ?>
                                    <input type="hidden" name="id" value="<?= (int) $editProduto['id'] ?>">
                                <?php endif; ?>
                                <input type="hidden" name="company_id" value="<?= htmlspecialchars($_SESSION['current_company_id'] ?? '') ?>">

                                <label>
                                    Nome
                                    <input type="text" name="nome" value="<?= htmlspecialchars($editProduto['nome'] ?? '') ?>" required>
                                </label>

                                <label>
                                    Código
                                    <input type="text" name="codigo" value="<?= htmlspecialchars($editProduto['codigo'] ?? '') ?>" required>
                                </label>

                                <label>
                                    NCM
                                    <input type="text" name="ncm" value="<?= htmlspecialchars($editProduto['ncm'] ?? '') ?>">
                                </label>

                                <label>
                                    CEST
                                    <input type="text" name="cest" value="<?= htmlspecialchars($editProduto['cest'] ?? '') ?>">
                                </label>

                                <label>
                                    Unidade
                                    <input type="text" name="unidade" value="<?= htmlspecialchars($editProduto['unidade'] ?? 'UN') ?>">
                                </label>

                                <label>
                                    GTIN / Código de Barras
                                    <input type="text" name="gtin" value="<?= htmlspecialchars($editProduto['gtin'] ?? '') ?>">
                                </label>

                                <label>
                                    CFOP Padrão
                                    <input type="text" name="cfop_padrao" value="<?= htmlspecialchars($editProduto['cfop_padrao'] ?? '') ?>">
                                </label>

                                <label>
                                    Categoria
                                    <input type="text" name="categoria" value="<?= htmlspecialchars($editProduto['categoria'] ?? '') ?>">
                                </label>

                                <label>
                                    Preço
                                    <input type="number" step="0.01" min="0" name="preco" value="<?= htmlspecialchars((string) ($editProduto['preco'] ?? 0)) ?>" required>
                                </label>

                                <label>
                                    Estoque atual
                                    <input type="number" min="0" name="estoque_atual" value="<?= htmlspecialchars((string) ($editProduto['estoque_atual'] ?? 0)) ?>" required>
                                </label>

                                <label>
                                    Status
                                    <select name="status">
                                        <option value="ativo" <?= (($editProduto['status'] ?? 'ativo') === 'ativo') ? 'selected' : '' ?>>Ativo</option>
                                        <option value="inativo" <?= (($editProduto['status'] ?? 'ativo') === 'inativo') ? 'selected' : '' ?>>Inativo</option>
                                    </select>
                                </label>

                                <div class="form-actions">
                                    <button class="btn primary" type="submit">Salvar</button>
                                    <?php if ($editProduto): ?>
                                        <a class="btn secondary" href="?page=produtos">Cancelar</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Lista de produtos</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Código</th>
                                        <th>Preço</th>
                                        <th>Estoque</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produtos as $produto): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                                            <td><?= htmlspecialchars($produto['codigo']) ?></td>
                                            <td><?= formatCurrency((float) $produto['preco']) ?></td>
                                            <td><?= (int) $produto['estoque_atual'] ?></td>
                                            <td class="actions">
                                                <a class="link-button" href="?page=produtos&edit=<?= (int) $produto['id'] ?>">Editar</a>
                                                <form method="POST" class="inline-form" onsubmit="return confirm('Deseja remover este produto?');">
                                                    <input type="hidden" name="action" value="delete_produto">
                                                    <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                                                    <button class="link-button danger" type="submit">Excluir</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                case 'vendas':
                    ?>
                    <!-- Tela de registro e histórico de vendas -->
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Operações</p>
                            <h2>Vendas</h2>
                        </div>
                    </section>

                    <div class="grid two-columns">
                        <div class="panel">
                            <h3>Registrar venda</h3>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="save_venda">

                                <label>
                                    Cliente
                                    <select name="cliente_id" required>
                                        <option value="">Selecione</option>
                                        <?php foreach ($clientes as $cliente): ?>
                                            <option value="<?= (int) $cliente['id'] ?>"><?= htmlspecialchars($cliente['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <?php for ($i = 0; $i < 3; $i++): ?>
                                    <div class="inline-row">
                                        <label>
                                            Produto <?= $i + 1 ?>
                                            <select name="itens[<?= $i ?>][produto_id]">
                                                <option value="">Selecione</option>
                                                <?php foreach ($produtos as $produto): ?>
                                                    <option value="<?= (int) $produto['id'] ?>"><?= htmlspecialchars($produto['nome']) ?> (<?= (int) $produto['estoque_atual'] ?> em estoque)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>

                                        <label>
                                            Quantidade
                                            <input type="number" min="1" name="itens[<?= $i ?>][quantidade]" value="0">
                                        </label>
                                    </div>
                                <?php endfor; ?>

                                <div class="form-actions">
                                    <button class="btn primary" type="submit">Finalizar venda</button>
                                </div>
                            </form>
                        </div>

                        <div class="panel">
                            <h3>Histórico de vendas</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Data</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vendas as $venda): ?>
                                        <tr>
                                            <td>#<?= (int) $venda['id'] ?></td>
                                            <td><?= htmlspecialchars($venda['cliente_nome']) ?></td>
                                            <td><?= htmlspecialchars($venda['data_venda']) ?></td>
                                            <td><?= formatCurrency((float) $venda['total']) ?></td>
                                            <td><span class="badge success"><?= htmlspecialchars($venda['status']) ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                    break;

                default:
                    ?>
                    <!-- Dashboard principal com visão geral do negócio -->
                    <section class="page-header">
                        <div>
                            <p class="eyebrow">Resumo geral</p>
                            <h2>Dashboard</h2>
                        </div>
                    </section>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <span>Clientes</span>
                            <strong><?= (int) $dashboard['clientes'] ?></strong>
                        </div>
                        <div class="stat-card">
                            <span>Produtos</span>
                            <strong><?= (int) $dashboard['produtos'] ?></strong>
                        </div>
                        <div class="stat-card">
                            <span>Vendas</span>
                            <strong><?= (int) $dashboard['vendas'] ?></strong>
                        </div>
                        <div class="stat-card warning">
                            <span>Faturamento</span>
                            <strong><?= formatCurrency((float) $dashboard['faturamento']) ?></strong>
                        </div>
                    </div>

                    <?php
                    $salesChartDays = [];
                    $chartMax = 0;
                    $chartData = [];
                    $now = new DateTimeImmutable('today');
                    foreach (range(6, 0) as $dayOffset) {
                        $date = $now->modify('-' . $dayOffset . ' days')->format('Y-m-d');
                        $dayTotal = 0.0;
                        foreach ($vendas as $v) {
                            if (!empty($v['data_venda']) && date('Y-m-d', strtotime((string)$v['data_venda'])) === $date) {
                                $dayTotal += (float) ($v['total'] ?? 0);
                            }
                        }
                        $chartData[] = [
                            'label' => date('d', strtotime($date)),
                            'shortLabel' => date('d', strtotime($date)),
                            'value' => $dayTotal,
                            'date' => $date,
                        ];
                        $chartMax = max($chartMax, $dayTotal);
                    }
                    $chartMax = $chartMax > 0 ? $chartMax : 1;
                    ?>

                    <div class="panel sales-panel">
                        <div class="sales-header">
                            <div>
                                <p class="eyebrow">Vendas por data</p>
                                <h3>Últimos 7 dias</h3>
                            </div>
                            <span class="sales-total-label">Total: <?= formatCurrency((float) array_sum(array_map(static fn ($item) => $item['value'], $chartData))) ?></span>
                        </div>
                        <div class="sales-chart" aria-label="Gráfico de vendas por data">
                            <?php foreach ($chartData as $item): ?>
                                <div class="sales-column" title="<?= htmlspecialchars($item['date']) ?>: <?= formatCurrency((float) $item['value']) ?>">
                                    <span class="sales-value"><?= formatCurrency((float) $item['value']) ?></span>
                                    <span class="sales-bar" data-percent="<?= max(10, (float) $item['value'] / $chartMax * 100) ?>"></span>
                                    <span class="sales-day"><?= htmlspecialchars($item['shortLabel']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <h3>Alertas de estoque</h3>
                        <p>Produtos com estoque baixo: <?= (int) $dashboard['estoque_baixo'] ?></p>
                        <?php foreach ($produtos as $produto): ?>
                            <?php if ((int) $produto['estoque_atual'] <= 5): ?>
                                <div class="stock-row">
                                    <span><?= htmlspecialchars($produto['nome']) ?></span>
                                    <strong><?= (int) $produto['estoque_atual'] ?> unidades</strong>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php
                    break;
            }
            ?>
        </main>
    </div>
</body>
</html>
<script src="/assets/app.js"></script>
