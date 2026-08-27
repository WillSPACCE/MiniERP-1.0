<p align="center">
  <img src="mini-erp-web/public/assets/images/mini-erp-logo.png" alt="MiniERP" width="220">
</p>

<h1 align="center">MiniERP 1.0</h1>

<p align="center">
  ERP web multiempresa desenvolvido em PHP 8.2 e MariaDB, com pedidos comerciais,
  cadastros unificados e base fiscal para NF-e/NFC-e.
</p>

<p align="center">
  <img alt="PHP 8.2" src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white">
  <img alt="MariaDB" src="https://img.shields.io/badge/MariaDB-MySQL-003545?logo=mariadb&logoColor=white">
  <img alt="NF-e" src="https://img.shields.io/badge/NF--e%20%2F%20NFC--e-4.00-1565C0">
  <img alt="Status" src="https://img.shields.io/badge/status-em%20desenvolvimento-F59E0B">
</p>

> [!IMPORTANT]
> O projeto gera prévias locais de DANFE/DANFC-e sem certificado e sem comunicação
> com a SEFAZ. Transmissão fiscal real somente deve ser habilitada depois da configuração
> completa de empresa, série, ambiente, tributação e certificado A1.

## Visão geral

O MiniERP reúne a operação comercial e a preparação fiscal em uma aplicação web responsiva.
O projeto possui isolamento multi-tenant, persistência transacional e uma separação explícita
entre salvar um pedido, criar um documento fiscal interno, gerar uma prévia e transmitir à SEFAZ.

### Principais recursos

- autenticação de usuários e contexto seguro de empresa;
- isolamento de dados por tenant e banco dedicado;
- cadastro unificado de clientes, fornecedores, transportadoras e motoristas;
- cadastro de produtos, CFOP e dados tributários;
- criação e edição de pedidos de entrada e saída;
- itens, totais, frete, transportadora, veículo, volumes e pesos;
- listagem operacional de pedidos emitidos;
- Central de Notas com filtros, estados, detalhes e timeline;
- prévia DANFE modelo 55 em A4;
- prévia DANFC-e modelo 65 em formato compacto;
- XML fiscal armazenado com verificação SHA-256 quando disponível;
- configuração de estabelecimentos, séries fiscais e certificado A1;
- testes de integração, isolamento, persistência e fluxos HTTP reais.

## Fluxos importantes

### Salvar pedido

```text
Novo pedido
   ↓
Validação e CSRF
   ↓
Pedido + itens + transporte em transação
   ↓
COMMIT e leitura de confirmação
   ↓
Pedidos Emitidos
```

Salvar um pedido não emite nota, não reserva número fiscal e não chama a SEFAZ.

### Prévia fiscal

```text
Pedido ou documento interno
   ↓
FiscalDanfePreviewService
   ↓
XML técnico completo de prévia
   ↓
NFePHP/sped-da
   ↓
PDF em nova guia
```

- Modelo 55: `NFePHP\DA\NFe\Danfe`, papel A4.
- Modelo 65: `NFePHP\DA\NFe\Danfce`, papel compacto de 80 mm.
- Certificado A1: não exigido para prévia.
- SEFAZ: nenhuma chamada durante a prévia.

### Emissão e transmissão

A ação **Emitir / Transmitir** é separada da prévia. Ela permanece bloqueada quando faltar
certificado A1, série ativa de homologação ou preflight fiscal completo. O estado
`AUTHORIZED` nunca deve ser criado sem uma resposta real da SEFAZ.

## Tecnologias

| Camada | Tecnologias |
|---|---|
| Backend | PHP 8.2, PDO, Composer |
| Banco | MariaDB / MySQL |
| Frontend | HTML5, CSS, JavaScript |
| Fiscal | NFePHP `sped-nfe` e `sped-da` |
| Segurança | Sessão, CSRF, prepared statements, isolamento por tenant |
| Ambiente local | Apache e PHP pelo XAMPP |

## Requisitos

- Windows com XAMPP, ou ambiente equivalente com Apache/PHP;
- PHP `8.2+`;
- MariaDB ou MySQL;
- Composer;
- extensões PHP `dom`, `json`, `libxml`, `openssl`, `simplexml`, `soap` e `zlib`.

## Instalação local

### 1. Clonar o projeto

```powershell
cd C:\xampp\htdocs
git clone https://github.com/WillSPACCE/MiniERP-1.0.git MiniRP
cd MiniRP\mini-erp-web
```

### 2. Instalar dependências

```powershell
composer install
```

### 3. Configurar o banco

O arquivo `mini-erp-web/config.php` lê as variáveis abaixo e possui padrões adequados ao
XAMPP local:

| Variável | Padrão |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `mini_erp` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | vazio |
| `APP_TIMEZONE` | `America/Sao_Paulo` |

Crie o banco principal e aplique somente as migrations compatíveis com o estado atual.
Antes de qualquer migration em banco existente, faça um dump completo. O catálogo está em
[`mini-erp-web/migrations`](mini-erp-web/migrations).

> [!WARNING]
> Não execute todas as migrations cegamente em uma base já utilizada. Algumas representam
> etapas históricas e exigem conferência de dependências e versão do schema.

### 4. Iniciar a aplicação

Opção A — Apache do XAMPP:

1. Inicie Apache e MySQL no painel do XAMPP.
2. Acesse `http://localhost/MiniRP/mini-erp-web/public/`.

Opção B — servidor PHP embutido:

```powershell
cd C:\xampp\htdocs\MiniRP\mini-erp-web
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
```

Depois acesse `http://127.0.0.1:8000/`.

## Como usar

### Cadastros

1. Entre na empresa correta.
2. Acesse **Cadastros → Pessoas** para clientes, fornecedores, transportadoras e motoristas.
3. Cadastre produtos com NCM e informações tributárias.
4. Configure CFOPs coerentes com entrada ou saída.

### Pedido de venda

1. Acesse **Pedidos → Saída → Novo pedido de venda**.
2. Selecione cliente, natureza/CFOP e modelo 55 ou 65.
3. Adicione produtos, quantidades e preços.
4. Preencha frete e transporte quando aplicável.
5. Clique em **Gravar**.
6. O pedido será exibido em **Pedidos Emitidos**.

### Prévia DANFE/DANFC-e

1. Abra **Pedidos Emitidos** ou a **Central de Notas**.
2. Abra o menu **Ações**.
3. Clique em **Prévia DANFE** ou **Prévia DANFC-e**.
4. O PDF será aberto em nova guia e a tela atual permanecerá aberta.

### Central de Notas

O menu respeita a disponibilidade de cada documento:

1. Prévia DANFE/DANFC-e;
2. visualizar e baixar XML, quando existir artifact;
3. Emitir/Transmitir, sujeito ao preflight e certificado;
4. tentar novamente;
5. timeline/eventos;
6. copiar chave, quando houver chave válida.

## Testes

Execute os testes a partir de `mini-erp-web`:

```powershell
C:\xampp\php\php.exe tests\FiscalXmlBuilderTest.php
C:\xampp\php\php.exe tests\FiscalDanfePreviewEndpointStaticTest.php
C:\xampp\php\php.exe tests\TenantIsolationTest.php
```

O teste HTTP cria registros `TEST_ONLY`, percorre o fluxo real e remove os dados criados:

```powershell
$env:RUN_ORDER_HTTP_TESTS='1'
C:\xampp\php\php.exe tests\OrderCrudHttpTest.php
Remove-Item Env:RUN_ORDER_HTTP_TESTS
```

Ele requer Apache e MariaDB ativos, além das fixtures esperadas no tenant de teste.

## Estrutura

```text
MiniRP/
├── README.md
└── mini-erp-web/
    ├── app/            # compatibilidade e acesso legado
    ├── database/       # templates de schema
    ├── docs/           # documentação técnica e operacional
    ├── migrations/     # evolução versionada do banco
    ├── public/         # entrypoints HTTP e assets
    ├── src/            # domínio, serviços, repositórios e infraestrutura
    ├── tests/          # testes unitários, integração e HTTP
    ├── composer.json
    └── config.php
```

## Segurança e dados locais

Nunca envie para o Git:

- `.env` e credenciais reais;
- certificados `.pfx` ou `.p12`;
- master keys e secrets fiscais;
- dumps SQL de clientes;
- sessões, cookies, logs, cache, PDFs ou XMLs operacionais;
- conteúdo de `storage`, `tmp`, `output` ou `backups`.

Os endpoints fiscais resolvem o tenant pela sessão autenticada. IDs de pedido, documento e
artifact são revalidados no banco do tenant para reduzir risco de IDOR.

## Estado do projeto

| Área | Estado |
|---|---|
| Autenticação e multi-tenant | Implementado |
| Pessoas e produtos | Implementado |
| Pedidos de entrada/saída | Implementado |
| Pedidos Emitidos | Implementado |
| Central de Notas | Implementado |
| Prévia DANFE 55 | Implementado |
| Prévia DANFC-e 65 | Implementado |
| XML e integridade SHA-256 | Implementado quando há artifact |
| Transmissão SEFAZ | Protegida / não presumir habilitada |
| Financeiro completo | Em evolução |

## Backup e recuperação do código

O snapshot funcional está preservado na branch:

```text
backup/functional-prompt-081-2026-08-27
```

Para abrir esse snapshot sem sobrescrever trabalho atual:

```powershell
git fetch origin
git switch -c recuperacao-081 origin/backup/functional-prompt-081-2026-08-27
```

## Licença

O `composer.json` identifica o projeto como proprietário. Consulte o autor antes de
redistribuir, sublicenciar ou utilizar comercialmente.

---

<p align="center">
  Desenvolvido por <a href="https://github.com/WillSPACCE">WillSPACCE</a>.
</p>
