# Cobertura de campos do runtime

| Tela | Campos auditados | Backend | Tabela | CREATE | READ | UPDATE | Status |
|---|---|---|---|---|---|---|---|
| Pessoas | 49 campos funcionais | PersonFiscalData/Repository | `clientes` | sim | sim | sim | testado por field coverage |
| Produtos | 20 campos funcionais | ProductFiscalData/Repository | `produtos` | sim | sim | sim | testado por field coverage |
| CFOP | `codigo`,`descricao`,`natureza`,`aplicacao`,`status` | Repository | `cfops` | sim | sim | sim | corrigido banco dedicado |
| Fornecedor | 15 campos visuais | Repository | `fornecedores` | sim | sim | sim | legado preservado |
| Transportadora | 15 campos visuais | Repository | `transportadoras` | sim | sim | sim | legado preservado |
| Motorista | 7 campos visuais | Repository | `motoristas` | sim | sim | sim | legado preservado |
| Empresa | campos do contrato Establishment | EstablishmentService | `establishments` | sim | sim | sim | fonte fiscal canônica |
| Usuários | identidade, papel e status | serviços tenant | `usuarios` | sim | sim | sim | PlatformAdmin não alterado |
| Pedido | 25 campos de cabeçalho/itens | FiscalOperationRepository | `fiscal_orders/items` | sim | sim | sim | totais recalculados |

Total auditado por contratos e testes: 156 campos/controles funcionais. Campos financeiros de parcelas e volumes/pesos permanecem backlogs declarados, não falsamente marcados como persistidos.
