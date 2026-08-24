# PLATFORM-01-T02 — Teste Manual

## 1. Pré-requisitos

- XAMPP com MariaDB ativo.
- Projeto em `C:\xampp\htdocs\MiniRP\mini-erp-web`.
- PHP em `C:\xampp\php\php.exe`.
- Navegador com cookies habilitados.
- Identidade ativa no MAIN. Temporariamente, `admin@localhost` com senha padrão `admin` é aceito; outras identidades precisam de senha conhecida e ID incluído em `PLATFORM_ADMIN_USER_IDS`.

## 2. Como iniciar

No Prompt de Comando:

```bat
cd /d C:\xampp\htdocs\MiniRP\mini-erp-web
start-platform-server.bat
```

O iniciador configura a autorização transitória para o processo, inicia o PHP com `public` como document root e abre automaticamente o login do painel.

## 3. Login e URL

1. Abra `http://localhost:8000/plataforma/login.php`.
2. Durante a compatibilidade temporária, use `admin@localhost` e a senha padrão autorizada. Para outra identidade, inicie o batch com seu ID como argumento e use a senha persistida correspondente.
3. O dashboard abrirá em `http://localhost:8000/plataforma/`.

## 4. Cadastrar empresa fictícia

1. No módulo Empresas, clique em **Nova empresa**.
2. Preencha, ajustando o sufixo se os valores já existirem:

```text
Razão social: Empresa Teste T02 2026 Ltda
Nome fantasia: Empresa Teste T02 2026
CNPJ: 98.765.432/0001-10
Slug: empresa-teste-t02-2026
```

3. Clique em **Salvar empresa**.

Resultado esperado:

- retorno à lista;
- mensagem “Empresa cadastrada e aguardando provisionamento”;
- novo `tenant_id` gerado pelo banco;
- status `cadastrada`;
- bloqueio legado “Não bloqueada”;
- nenhum banco dedicado criado.

## 5. Localizar e editar

1. Localize a empresa pelo nome, CNPJ ou slug na tabela.
2. Clique em **Editar**.
3. Altere o nome fantasia para `Empresa Teste T02 Atualizada`.
4. Altere o slug para `empresa-teste-t02-atualizada`.
5. Clique em **Salvar alterações**.

Resultado esperado: mensagem de atualização e novos valores na lista. `tenant_id`, status e bloqueio permanecem iguais.

## 6. Testar CNPJ duplicado

1. Clique em **Nova empresa**.
2. Use outro nome e slug, mas repita `98.765.432/0001-10`.
3. Salve.

Resultado esperado: cadastro recusado com “CNPJ já cadastrado para outra empresa”. Nenhuma empresa é sobrescrita.

## 7. Testar slug duplicado

1. Use um CNPJ fictício diferente de 14 dígitos e repita `empresa-teste-t02-atualizada`.
2. Salve.

Resultado esperado: cadastro recusado com “Slug já cadastrado para outra empresa”.

## 8. Confirmar ausência de banco dedicado

No cliente SQL local, execute somente leitura:

```sql
SELECT id, cnpj, slug, status, db_name
FROM mini_erp.tenants
WHERE cnpj = '98765432000110';

SELECT SCHEMA_NAME
FROM information_schema.SCHEMATA
WHERE SCHEMA_NAME LIKE 'mini_erp_tenant_%'
ORDER BY SCHEMA_NAME;
```

Resultado esperado: a empresa aparece com `db_name = NULL`; a lista de schemas é exatamente a mesma de antes do teste. T02 não cria banco.

## 9. Confirmar isolamento e ERP legado

1. Em outra aba, abra `http://localhost:8000/login.php`.
2. Use o ERP legado normalmente com uma empresa já provisionada.
3. Volte ao painel e edite a empresa fictícia.
4. Confirme que o tenant selecionado no ERP não mudou.

## 10. Validação visual completa

```text
LOGIN
  → PAINEL DA PLATAFORMA
  → EMPRESAS
  → NOVA EMPRESA
  → SALVAR
  → EMPRESA APARECE NA LISTA
  → EDITAR
  → SALVAR
  → ALTERAÇÃO APARECE NA LISTA
```

Também deve permanecer verdadeiro: **NENHUM BANCO DA EMPRESA FOI CRIADO AINDA**.

## 11. Limpeza opcional — SOMENTE TESTE LOCAL

O painel não oferece exclusão, pois isso pertence a outra task. Se a remoção do dado artificial for indispensável, faça backup e confirme visualmente o registro exato antes de qualquer remoção.

```sql
START TRANSACTION;

SELECT id, razao_social, nome_fantasia, cnpj, slug, status, db_name
FROM mini_erp.tenants
WHERE cnpj = '98765432000110';

-- Execute somente após confirmar que é exatamente o registro fictício deste roteiro:
DELETE FROM mini_erp.tenants
WHERE cnpj = '98765432000110'
  AND slug = 'empresa-teste-t02-atualizada'
  AND db_name IS NULL;

SELECT ROW_COUNT() AS removidos;
```

Se `removidos` for exatamente `1`, finalize conscientemente com `COMMIT;`. Em qualquer dúvida, use `ROLLBACK;`. Nunca adapte esse procedimento para dados reais.

## 12. Checklist

- [ ] login do painel funciona
- [ ] Nova empresa aparece
- [ ] tenant_id é gerado automaticamente
- [ ] empresa começa como cadastrada
- [ ] empresa pode ser editada
- [ ] CNPJ duplicado é recusado
- [ ] slug duplicado é recusado
- [ ] status/bloqueio/db_name não podem ser editados
- [ ] nenhum banco dedicado foi criado
- [ ] sessão do ERP não mudou
- [ ] ERP legado continua funcionando

## 13. Resultado manual

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
