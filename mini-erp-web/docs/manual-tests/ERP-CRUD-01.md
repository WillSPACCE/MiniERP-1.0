# Teste manual — ERP-CRUD-01 Clientes

## Preparação

1. Execute `start-platform-server.bat <ID_PLATFORM_ADMIN>` na pasta do projeto.
2. Abra `http://localhost:8000/plataforma/` e autentique o PlatformAdmin.
3. Encontre **Willyan Info** e clique em **Acessar ERP**.
4. Confirme `http://localhost:8000/login.php?empresa=willyaninfo`.
5. Entre com um usuário ativo vinculado ao tenant 14.
6. Confirme o dashboard histórico e abra `CADASTRO → Pessoas`.

## Cadastrar e listar

1. Preencha pelo menos Nome, CPF/CNPJ válido, CEP, Logradouro e Telefone Principal.
2. Preencha também e-mail, número, bairro, cidade, estado e demais campos desejados.
3. Clique em **Salvar**.
4. Confirme a mensagem de sucesso e o registro na tabela Pessoas.
5. Pressione F5; confirme que o registro permanece e não foi duplicado.
6. Digite nome, documento ou e-mail na pesquisa e clique em **Pesquisar**.
7. Confirme que somente os registros correspondentes são exibidos.

## Validar erros

1. Tente salvar sem Nome ou CPF/CNPJ e confirme mensagem amigável.
2. Tente telefone com menos de 10 dígitos e confirme recusa.
3. Confirme que os demais valores preenchidos continuam no formulário.

## Editar

1. Clique no ícone de lápis do cliente.
2. Confirme que os dados atuais preencheram o mesmo formulário.
3. Altere nome, telefone ou endereço e clique em **Salvar**.
4. Confirme o valor atualizado na lista e novamente após F5.

## Excluir

1. Clique no ícone de exclusão.
2. Confirme a caixa **Deseja remover esta pessoa?**.
3. Confirme a mensagem e ausência do registro, inclusive após F5.

## Banco e isolamento

1. Execute `SELECT DATABASE();` pela prova automatizada; deve retornar `mini_erp_tenant_14`.
2. Confira o cliente com SELECT somente em `mini_erp_tenant_14.clientes`.
3. Entre em outro tenant e pesquise o mesmo nome/documento; ele não deve aparecer.
4. Não envie `tenant_id`, `company_id` ou `db_name`; esses valores não fazem parte da autoridade do formulário.

## Automação controlada

Execute `C:\xampp\php\php.exe tests\ErpClientCrudIntegrationTest.php`. O teste informa banco, ID temporário, sequência SQL e `cleanup=confirmed`. Nenhum cliente artificial deve permanecer.
