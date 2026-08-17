# MINI ERP WEB — MEMÓRIA DO PROJETO

> Este arquivo é a memória técnica permanente do projeto.
> TODOS os agentes devem lê-lo antes de fazer alterações importantes
> e atualizá-lo depois de concluir qualquer tarefa relevante.
> Nunca apagar informações importantes sem explicar. Nunca inventar informações.

Última atualização: [a definir] — nenhuma etapa de desenvolvimento foi iniciada ainda.

---

## 1. Objetivo

Construir um Mini ERP Web (Enterprise Resource Planning) simples, funcional e organizado,
cobrindo os processos essenciais de uma pequena empresa: cadastro de clientes, produtos,
estoque, vendas e financeiro, com um dashboard para visão geral do negócio.

## 2. Objetivo de aprendizado

Este projeto também existe como projeto de estudo/portfólio. Ele deve ensinar, na prática:

- PHP (procedural e orientado a objetos)
- MySQL e modelagem de banco de dados relacional
- Desenvolvimento frontend (HTML, CSS, JavaScript)
- Arquitetura de aplicações web (camadas, responsabilidades, organização de pastas)
- Segurança em aplicações PHP/MySQL
- Boas práticas de desenvolvimento de sistemas ERP

## 3. Stack

- PHP: [a definir]
- MySQL: [a definir]
- PDO (acesso ao banco)
- HTML
- CSS
- JavaScript
- Bootstrap: [a definir se será utilizado]
- XAMPP (ambiente local)
- Apache
- MySQL Workbench (administração visual do banco)
- VS Code (editor de código)

> Atualizar esta lista sempre que uma tecnologia for adicionada ou removida.

## 4. Ambiente

- Sistema operacional: [a definir]
- Versão do PHP: [a definir]
- Versão do MySQL: [a definir]
- Versão do projeto: [a definir]
- Caminho local do projeto: [a definir] (sugestão: C:\xampp\htdocs\mini-erp)
- URL local: [a definir] (sugestão: http://localhost/mini-erp)

> Não inventar versões. Registrar somente quando informadas pelo usuário.

## 5. Arquitetura

Estrutura planejada (ainda não criada):

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

> Se a arquitetura mudar durante o desenvolvimento, atualizar esta seção.

## 6. Estado atual

### Concluído

- Estrutura de documentação e memória do projeto (memory.md, visual-memory.md, README.md)

### Em andamento

- Nenhuma etapa de desenvolvimento iniciada ainda.

### Próximo

- Aguardando definição do usuário sobre qual etapa iniciar (ex.: estrutura de pastas do
  código, banco de dados, autenticação).

### Bloqueios

- Nenhum no momento.

## 7. Funcionalidades

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

> Atualizar esta lista conforme o projeto evoluir.

## 8. Banco de dados

Nenhuma tabela criada ainda.

Quando uma tabela for criada, documentar aqui seguindo o modelo:

```
nome_da_tabela

Finalidade:
[explicar para que serve a tabela]

Campos principais:
[listar campos relevantes]

Chave primária:
[campo]

Foreign keys:
[campo -> tabela.campo]

Relacionamentos:
[explicar relação com outras tabelas]
```

## 9. Regras de negócio

Nenhuma regra implementada ainda.

Regra de referência para o fluxo de vendas (a ser implementada futuramente):

Quando uma venda é finalizada:

1. validar cliente
2. validar produtos
3. verificar estoque
4. calcular valores
5. criar venda
6. criar itens
7. atualizar estoque
8. registrar movimentação
9. gerar financeiro
10. confirmar transaction

## 10. Segurança

Medidas planejadas para o projeto (a implementar):

- PDO com Prepared Statements
- password_hash / password_verify para senhas
- Proteção CSRF
- Validação de dados de entrada
- Controle de autorização (permissões)
- Gerenciamento seguro de sessão

> Atualizar esta seção com as medidas efetivamente implementadas, à medida que forem criadas.

## 11. Decisões técnicas

```
Data: [a definir]
Decisão: Utilizar PDO para acesso ao banco de dados.
Motivo: Permitir Prepared Statements e uma conexão mais segura com o MySQL.
Impacto: Todo acesso a dados deverá passar pela camada de Models/Services usando PDO.
```

> Sempre registrar novas decisões neste formato (Data / Decisão / Motivo / Impacto).
> Quando uma decisão mudar, registrar: decisão antiga, nova decisão e motivo da mudança.

## 12. Padrões de código

A definir à medida que o código for criado. Deve cobrir:

- nomes de variáveis
- nomes de classes
- nomes de métodos
- organização de arquivos
- tratamento de erros
- padrão de acesso ao banco
- padrão de validação
- padrão de comentários (ver README.md, seção "Comentários no código")

## 13. Problemas conhecidos

Nenhum problema registrado até o momento.

## 14. Próximos passos

- Definir com o usuário qual será a primeira etapa real de desenvolvimento após a
  documentação inicial (estrutura de pastas do código, banco de dados ou autenticação).
