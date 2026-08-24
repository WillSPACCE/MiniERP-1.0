# Teste manual — PLATFORM-01-T03

## Preparação

1. Abra um Prompt de Comando na raiz do projeto.
2. Execute `start-platform-server.bat <ID_REAL_AUTORIZADO>`. Durante a compatibilidade temporária de `admin@localhost`, o argumento pode ser omitido.
3. Acesse `http://localhost:8000/plataforma/` e autentique-se.

## Tenant 14 — willyan info

1. Localize `tenant_id = 14`, nome fantasia `willyan info`.
2. Confirme o estado `cadastrada`.
3. Confirme: Editar habilitado; Provisionar habilitado; Usuários desabilitado; Acessar ERP desabilitado; Bloquear desabilitado.
4. Clique em Provisionar.
5. Confirme que aparece apenas a mensagem de ação em implementação e que nenhuma alteração é executada.
6. Em ferramenta administrativa externa e somente leitura, confirme que `mini_erp_tenant_14` ainda não existe. Não crie o banco durante este teste.

## Outros estados

Se houver tenant ativo, observe que Editar, Usuários e Bloquear ficam disponíveis; Acessar ERP só fica disponível quando `db_name` estiver preenchido. Os links ainda são placeholders seguros. Para tenant bloqueado, confirme que ERP está desabilitado e Desbloquear aparece apenas como ação futura. Não altere status nem `blocked` manualmente.

## Checklist

- [ ] status visível
- [ ] ações refletem status
- [ ] tenant 14 aparece como cadastrada
- [ ] Provisionar disponível
- [ ] Usuários indisponível
- [ ] Acessar ERP indisponível
- [ ] nenhum banco criado
- [ ] nenhum usuário criado
- [ ] nenhum bloqueio real aplicado
- [ ] ERP legado continua funcionando

Data:

Testado por:

Ambiente:

Resultado:

Problemas encontrados:
