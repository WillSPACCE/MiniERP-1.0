# Teste manual — FISCAL-01C

## A. Empresa existente

1. Inicie o servidor e entre no Painel.
2. Abra Willyan Info e confira a aba Geral, lifecycle, banco e bloqueio somente leitura.
3. Confira o status do onboarding e abra Fiscal.
4. Verifique Identificação, Inscrições/Regime e Endereço fiscal.
5. Preencha somente dados reais conhecidos; não invente IE, CNAE ou CRT.
6. Salve, reabra e compare campo a campo.
7. Confira `mini_erp_tenant_14.establishments` no MariaDB.
8. Entre no ERP, abra Configuração → Empresa e confirme os mesmos valores.
9. Confirme readiness `INCOMPLETE`, certificado não configurado e ausência de CSC/emissão.

## B. Nova empresa exclusivamente de teste

1. Abra Nova Empresa e confira as quatro etapas do onboarding.
2. Preencha apenas Geral e salve; confirme o redirecionamento ao detalhe.
3. Verifique que Fiscal está pendente enquanto não há banco.
4. Provisione somente com autorização explícita.
5. Complete o estabelecimento e confira o status cadastral.
6. Remova conscientemente todos os recursos artificiais conforme o procedimento autorizado; não deixe tenant ou banco de teste órfão.

Nunca use dados fictícios no tenant 14 e não altere autenticação para facilitar o teste.
