# Mini ERP Web

## Sobre o projeto

O Mini ERP Web é um sistema de gestão empresarial simplificado (ERP), desenvolvido em
PHP e MySQL, cobrindo os processos essenciais de uma pequena empresa: clientes, produtos,
estoque, vendas, financeiro e um dashboard com visão geral do negócio.

## Objetivo

Este é um projeto de portfólio e de aprendizado. Ele existe para praticar, na prática,
PHP, MySQL, frontend, arquitetura de software, segurança e o desenvolvimento completo de
um sistema ERP — do banco de dados à interface.

## Tecnologias

- PHP
- MySQL
- PDO
- HTML
- CSS
- JavaScript
- Bootstrap *(a confirmar se será utilizado)*
- XAMPP
- Apache
- MySQL Workbench
- VS Code

## Requisitos

Antes de começar, instale:

- **XAMPP** — para rodar Apache, PHP e MySQL localmente
- **MySQL Workbench** — para administrar o banco de dados visualmente
- **VS Code** — editor de código
- **Navegador** (Chrome, Firefox, Edge, etc.)

## Instalação

### 1. Instalar XAMPP

Baixe e instale o XAMPP para o seu sistema operacional em https://www.apachefriends.org.
Durante a instalação, mantenha ao menos os módulos **Apache** e **MySQL** selecionados.

### 2. Iniciar Apache

Abra o painel de controle do XAMPP e clique em **Start** ao lado de **Apache**.
Isso liga o servidor web que vai processar os arquivos PHP do projeto.

### 3. Iniciar MySQL

No mesmo painel, clique em **Start** ao lado de **MySQL**.
Isso liga o servidor de banco de dados.

### 4. Criar o projeto

Coloque a pasta do projeto dentro do diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\mini-erp
```

### 5. Criar o banco de dados

1. Abra o **MySQL Workbench**.
2. Crie uma nova conexão apontando para o MySQL do XAMPP (host `127.0.0.1`, porta `3306`,
   usuário padrão `root`, sem senha, salvo se você tiver configurado uma).
3. Conecte-se e execute o script:
   ```
   database/schema.sql
   ```
   *(este arquivo ainda será criado quando o banco for modelado)*

### 6. Dados iniciais

Após criar as tabelas, execute o script de dados iniciais (dados de exemplo para testar
o sistema):

```
database/seeds.sql
```

*(este arquivo ainda será criado)*

### 7. Configuração

O projeto terá um arquivo de configuração de conexão com o banco (host, usuário, senha,
nome do banco).

**IMPORTANTE:** Nunca colocar senha real ou segredo no Git. Utilizar um arquivo de exemplo
(ex.: `config.example.php`) versionado, e um arquivo real (ex.: `config.php`) ignorado
pelo `.gitignore`.

## Como executar

1. Abrir o XAMPP
2. Iniciar o Apache
3. Iniciar o MySQL
4. Abrir o navegador
5. Acessar `localhost`
6. Acessar o Mini ERP em `http://localhost/mini-erp`

## Como testar

*(a detalhar conforme os testes forem criados na pasta `tests/`)*

De forma geral, cada funcionalidade deve ser testada:

- pelo navegador, testando o fluxo real da tela
- validando que os dados aparecem corretamente no MySQL Workbench após cada ação

## Estrutura do projeto

```
app/
config/
controllers/
models/
services/
views/
helpers/
middleware/

public/
assets/

database/

tests/
```

- **app/** — responsável pela lógica da aplicação.
- **controllers/** — recebem requisições e coordenam as operações.
- **models/** — responsáveis pela comunicação com os dados quando aplicável.
- **services/** — contêm as regras de negócio.
- **views/** — interface (telas) do sistema.
- **config/** — configurações (banco, ambiente, etc.).
- **public/** — arquivos públicos (ponto de entrada, assets acessíveis pelo navegador).
- **database/** — scripts SQL (schema, seeds, migrações).
- **tests/** — testes do sistema.

## Banco de dados

Para visualizar e explorar o banco usando o **MySQL Workbench**:

- **Abrir tabela:** na aba Navigator, expanda o schema `mini_erp` (ou nome equivalente) →
  `Tables`, clique com o botão direito na tabela → *Select Rows - Limit 1000*.
- **Visualizar registros:** o resultado aparece em uma grid editável na área central.
- **Executar SELECT:** abra uma nova aba de SQL (`Ctrl+T`) e escreva a consulta.
- **INSERT/UPDATE:** podem ser feitos via SQL ou diretamente na grid de resultados
  (clicando duas vezes na célula).
- **JOIN:** usado para combinar dados de duas ou mais tabelas relacionadas.

## Primeiros comandos SQL

```sql
-- Listar todos os clientes
SELECT * FROM clientes;

-- Buscar um cliente pelo id
SELECT * FROM clientes WHERE id = 1;

-- Inserir um novo cliente
INSERT INTO clientes (nome, email) VALUES ('João Silva', 'joao@email.com');

-- Atualizar um cliente
UPDATE clientes SET email = 'novo@email.com' WHERE id = 1;

-- Relacionar vendas com clientes
SELECT vendas.id, clientes.nome
FROM vendas
JOIN clientes ON vendas.cliente_id = clientes.id;
```

*(exemplos ilustrativos — serão ajustados conforme as tabelas reais forem criadas)*

## Como estudar o projeto

Para aprender de verdade com este projeto, siga este ciclo em cada funcionalidade:

1. Ler o código
2. Executar e ver funcionando
3. Modificar algo pequeno
4. Quebrar de propósito (ver o erro acontecer)
5. Corrigir o erro
6. Testar novamente
7. Explicar com suas próprias palavras o que o código faz

## Fluxo da aplicação

```
Navegador
   ↓
Apache
   ↓
PHP
   ↓
Controller
   ↓
Service
   ↓
Model / Database
   ↓
MySQL
   ↓
Resposta
   ↓
Navegador
```

## Fluxos do ERP

```
Cliente
   ↓
Venda
   ↓
Itens
   ↓
Estoque
   ↓
Financeiro
```

## Segurança

Medidas de segurança planejadas/implementadas no projeto:

- PDO com Prepared Statements (proteção contra SQL Injection)
- `password_hash` / `password_verify` para senhas de usuários
- Proteção CSRF em formulários
- Validação de dados de entrada
- Controle de autorização (permissões por tipo de usuário)
- Gerenciamento seguro de sessão

*(esta seção será atualizada com o que for de fato implementado)*

## Funcionalidades

- [ ] Login
- [ ] Logout
- [ ] Usuários
- [ ] Permissões
- [ ] Clientes
- [ ] Categorias
- [ ] Produtos
- [ ] Estoque
- [ ] Vendas
- [ ] Financeiro
- [ ] Dashboard
- [ ] Relatórios

## Roadmap

1. Documentação inicial (memória técnica, memória visual, README) — **concluído**
2. Estrutura de pastas do código
3. Modelagem e criação do banco de dados
4. Autenticação (login/logout)
5. CRUDs base (clientes, produtos, categorias)
6. Estoque
7. Vendas
8. Financeiro
9. Dashboard
10. Relatórios

## Como contribuir

Ao alterar o projeto (mesmo sendo um projeto solo de estudo):

1. Ler `docs/memory.md` e `docs/visual-memory.md` antes de começar.
2. Seguir os padrões de código e visuais já definidos.
3. Não duplicar componentes ou lógica já existente.
4. Atualizar a documentação relevante ao concluir a mudança.

## Como criar uma nova funcionalidade

1. Entender o requisito
2. Analisar o banco de dados (tabelas existentes e necessárias)
3. Definir a regra de negócio
4. Implementar o backend
5. Implementar o frontend
6. Testar
7. Revisar segurança
8. Documentar
9. Atualizar `docs/memory.md` (e `docs/visual-memory.md` se houve mudança visual)

## Como explicar o projeto em uma entrevista

**P: Do que se trata o projeto?**
R: É um Mini ERP Web feito em PHP e MySQL, com módulos de clientes, produtos, estoque,
vendas e financeiro, criado para praticar arquitetura de sistemas e boas práticas de
desenvolvimento backend e frontend.

**P: Como você organizou a arquitetura?**
R: Em camadas — controllers recebem a requisição, services concentram as regras de
negócio, e models cuidam do acesso aos dados via PDO, mantendo cada responsabilidade
separada.

**P: Como você garantiu a segurança da aplicação?**
R: Usando PDO com Prepared Statements para evitar SQL Injection, hash de senhas com
`password_hash`, proteção CSRF nos formulários e validação de dados de entrada.

**P: Qual foi o maior desafio?**
R: *(responder com base na experiência real conforme o projeto avançar)*

---

## Comentários no código

Como este projeto é para aprendizado, todo código criado deve ter comentários
explicativos que ensinem, não apenas descrevam.

Para cada função importante, explicar:

- o que a função faz
- quais parâmetros recebe
- o que retorna
- por que existe
- quando é utilizada

Exemplo (PHPDoc):

```php
/**
 * Busca um cliente pelo ID.
 *
 * Esta função consulta o banco de dados utilizando PDO
 * e Prepared Statement.
 *
 * @param int $id ID do cliente.
 * @return array|null Retorna os dados do cliente ou null caso não seja encontrado.
 */
```

Outro exemplo, para autenticação:

```php
/**
 * Autentica um usuário utilizando email e senha.
 *
 * O sistema busca o usuário pelo email usando Prepared Statement
 * e depois verifica a senha através de password_verify().
 *
 * @param string $email
 * @param string $password
 * @return array|null
 */
```

Para SQL importante, explicar o objetivo da consulta:

```sql
-- Busca apenas produtos ativos
-- cujo estoque está abaixo do estoque mínimo.
SELECT * FROM produtos WHERE ativo = 1 AND estoque < estoque_minimo;
```

Não é necessário comentar cada linha (evitar comentários óbvios, como `// soma 1 com 1`).
O foco é explicar funções, classes, métodos, regras de negócio, consultas SQL importantes
e decisões que possam gerar dúvida.

### Regra de ensino ao criar código novo

- **Antes:** explicar o conceito envolvido.
- **Durante:** explicar o que o código está fazendo.
- **Depois:** explicar como testar.
- **Em seguida:** explicar como você poderia alterar aquele código sozinho.
