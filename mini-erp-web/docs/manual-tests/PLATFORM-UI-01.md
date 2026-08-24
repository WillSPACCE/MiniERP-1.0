# PLATFORM-UI-01 — Teste manual de UTF-8 e UI do Control-Plane

## Objetivo

Validar que o painel administrativo do Control-Plane exibe textos corretamente em UTF-8 e mantém uma identidade visual consistente.

## Pré-requisito

- Servidor PHP ativo em `mini-erp-web`;
- Banco principal do Control-Plane disponível;
- Login do Platform Admin válido pela rota `/plataforma/login.php`.

## Fluxo de teste

### 1) Login

1. Acessar `/plataforma/login.php`.
2. Confirmar que a mensagem aparece em português correto e sem caracteres corrompidos.
3. Verificar que o botão e labels estão legíveis e sem sequências `Ã`/`Â`.

### 2) Dashboard

1. Acessar `/plataforma/` após autenticar.
2. Confirmar que o header, a sidebar e a breadcrumb exibem texto sem mojibake.
3. Verificar que o layout permanece consistente em desktop e em largura menor.

### 3) Operações multitenant

1. Acessar `/plataforma/operacoes-multitenant.php`.
2. Confirmar títulos e mensagens: `Operações Multi-tenant`, `Confirmação`, `Backup`, `Execução sequencial`, etc.
3. Validar que o formulário `SIMULAR` e `EXECUTAR OPERAÇÃO` aparecem corretos.

### 4) Minha conta

1. Acessar `/plataforma/minha-conta.php`.
2. Confirmar a mensagem de senha e os labels sem caracteres quebrados.

### 5) Banco de dados

1. Acessar a tela de banco de dados de uma empresa.
2. Validar que `Compatibilidade observada`, `Diferenças classificadas`, `Índices` e `Dados paginados` aparecem corretamente.

## Critério de aprovação

- Nenhuma string com sequência `Ã`, `Â` ou `â€` em páginas do painel;
- Todo o HTML em UTF-8;
- Layout responsivo consistente;
- Sem regressão funcional na autenticação e na navegação do Control-Plane.
