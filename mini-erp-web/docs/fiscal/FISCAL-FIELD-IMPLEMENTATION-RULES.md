# Regras de implementação de campos fiscais

Toda task fiscal de Pessoa, Produto, Fornecedor, Saída ou Entrada deve entregar, para cada campo:

`Origem → UI/configuração/regra → backend → banco → SELECT/edição → teste de round-trip → tag XML → obrigatoriedade → snapshot`

Origens permitidas: `USER_INPUT`, `CONFIGURATION`, `DERIVED`, `CALCULATED`, `TAX_RULE`, `SECRET` e `SEFAZ_RESPONSE`. Nenhuma tag pode ficar sem uma dessas origens.

- `USER_INPUT` e `CONFIGURATION` exigem campo funcional quando houver manutenção humana.
- `DERIVED` e `CALCULATED` não podem virar input manual.
- `TAX_RULE` pertence ao motor tributário, não ao cadastro básico.
- `SECRET` usa armazenamento e acesso próprios, nunca formulário comum, log ou texto puro.
- `SEFAZ_RESPONSE` é imutável e não editável.
- Dados usados em documento autorizado devem ser copiados para snapshot; o documento não pode depender do cadastro atual.
- Snapshot é obrigatório quando o valor integrar um documento fiscal autorizado.
- `IMPLEMENTED_DATA` exige UI/configuração concreta, backend, coluna e round-trip. Não significa XML gerado.
- Campo visual persistente sem contrato e teste de cobertura deve falhar na suíte.

Checklist mínimo de revisão: isolamento por `tenant_id`, validação backend, opcionais/condicionais, mapa XML, ausência de duplicação no MAIN e nenhuma promoção indevida de readiness.
