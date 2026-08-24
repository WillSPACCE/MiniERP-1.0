# Contrato — configuração fiscal da empresa

Escopo obrigatório: `tenant_id + establishment_id`. Fonte de verdade: banco dedicado do tenant. Ambiente inicia em homologação; POST com produção é rejeitado independentemente do HTML. Percentuais aceitam vírgula/ponto e persistem como `DECIMAL(15,6)`; texto, NaN e formatos absurdos falham.

Precedência: (1) TaxRule específica/versionada; (2) produto/pessoa/contexto específico; (3) default da empresa; (4) ausência = `FISCAL_PENDING`. Defaults nunca mascaram falta de regra obrigatória. A configuração efetivamente resolvida deve integrar o snapshot na criação do documento; documentos existentes nunca consultam defaults atuais.
