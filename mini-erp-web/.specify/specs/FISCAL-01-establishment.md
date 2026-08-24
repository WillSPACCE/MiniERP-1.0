# FISCAL-01 — Cadastro fiscal completo de Empresa/Estabelecimento

Status: implementado e aceito fisicamente no tenant local 14; rollout permanece explícito e pendente nos demais tenants.

- Fonte fiscal: `establishments` no data-plane.
- Escopo canônico: `tenant_id`.
- Control-plane: identidade, lifecycle e bootstrap inicial somente.
- Superfícies: Painel Empresa → Cadastro fiscal e ERP Configuração → Empresa.
- Readiness: separado do lifecycle e `INCOMPLETE` nesta entrega.
- Fora do escopo: certificado, CSC, XML, XSD, NFePHP, SEFAZ, DANFE, tributação e filiais.
