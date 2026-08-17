# MiniERP Constitution

Esta Constituição define princípios, padrões e restrições permanentes que governam o design, desenvolvimento e manutenção do projeto MiniERP. Ela representa o estado-alvo e os compromissos do projeto; onde o código atual divergir, a Constituição define o destino desejado — a migração para este estado será feita gradualmente e de forma reversível.

## Princípios Centrais

1. Arquitetura e Separação de Responsabilidades
- Padrão obrigatório: Controller → Service → Repository.
- Controllers: responsáveis somente por protocolo HTTP (parsing de request, verificação de autenticação/CSRF básica, seleção de resposta) e delegação a Services.
- Services: contêm regras de negócio, orquestram transações e coordenam múltiplos repositórios; recebem dados validados.
- Repositories: responsáveis exclusivamente por persistência e consultas parametrizadas; não devem conter lógica de negócio nem regras fiscais.
- Database: único responsável pelo gerenciamento de conexões e configuração de acesso ao banco.

2. PHP e Qualidade de Código
- Alvo mínimo: PHP 8.0+ (preferir 8.1+). Usar tipagem em parâmetros e retornos sempre que possível.
- Usar `declare(strict_types=1)` onde aplicável.
- Seguir PSR-12; criar e usar `composer.json` e autoload PSR-4 (migrar `app/` para `src/` progressivamente).
- Código deve ser simples, legível e testável; métodos pequenos e responsabilidade única.

3. Acesso a Banco de Dados e PDO
- PDO é o padrão de acesso; prepared statements obrigatórios para todo dado externo.
- `Database::getConnection()` (ou provedor central equivalente) é o único caminho para obter `PDO`; proíbe-se `new PDO(...)` espalhado.
- Não interpolar identificadores SQL diretamente a partir de input; usar whitelists validadas quando necessário.
- Operações multi-step devem usar transações atômicas com rollback em erro.

4. Migrations e Schema
- Migrações versionadas e idempotentes em `migrations/`; migrações só são aplicadas por runner explícito.
- Não executar alterações estruturais automaticamente durante o bootstrap de produção.
- Separar seeds de desenvolvimento de seeds de produção; documentar impacto das migrations.

5. Multi-tenant e Isolamento
- O projeto exige isolamento rigoroso entre tenants/empresas.
- Definir e documentar uma estratégia oficial de multi-tenancy; recomendação inicial: tenant por coluna `tenant_id` (DB por-tenant somente quando justificável e documentado).
- Implementar `TenantContext` como fonte única do tenant atual; services e repositories devem obter tenant via esse contexto, nunca diretamente de `$_SESSION`.
- Toda operação de leitura/gravação deve garantir escopo do tenant (filtro `tenant_id` ou uso da conexão do tenant). Acesso cruzado entre tenants é proibido.

6. Autenticação e Autorização
- Usar `password_hash()` e `password_verify()` para senhas; políticas de senha documentadas.
- Regenerar session id no login; configurar cookies com `HttpOnly`, `Secure` e `SameSite` apropriados.
- Implementar controle de acesso baseado em roles/permissions em Services ou middleware; Controllers apenas verificam permissões e delegam.

7. Segurança Aplicacional
- SQL Injection: sempre mitigado por prepared statements; nunca concatenar input em SQL.
- XSS: escapar toda saída HTML por padrão (helpers centralizados para escape).
- CSRF: tokens anti-CSRF obrigatórios para formulários que alteram estado.
- Não expor stack traces, erros internos ou segredos ao usuário final em produção.
- Segredos e credenciais devem viver fora do VCS (variáveis de ambiente); `config.php` pode ler env vars.

8. Regras Fiscais (domínio)
- Regras fiscais (CFOP, CST, CSOSN, NCM, cálculos de impostos) NÃO pertencem ao Repository.
- Criar camada/facade `FiscalService` (ou similar) para encapsular regras fiscais testáveis e auditáveis.
- Toda mudança fiscal deve ser rastreável, documentada e coberta por testes automatizados que comprovem o comportamento legal/esperado.

9. Validação de Dados
- Validação completa em Services ou validators dedicados; Repositories assumem dados validados.
- Validadores reutilizáveis para CPF/CNPJ, formatos fiscais, datas e limites monetários.

10. Tratamento e Registro de Erros
- Adotar logger compatível PSR-3; erros técnicos registrados em logs com níveis apropriados e rotação.
- Exceções específicas para classes de erro; Controllers transformam exceções em respostas amigáveis ao usuário e logam detalhes técnicos.

11. Testes e Qualidade
- Mudanças significativas devem incluir testes unitários e/ou de integração que comprovem comportamento.
- Pipeline de CI deve executar lint e testes antes de merge.
- Refatorações devem preservar comportamento atual; escrever testes que documentem o comportamento legado antes de alterar.

12. Organização de Pastas e Arquivos
- Estrutura alvo (migrar gradualmente):
	- `public/` — entradas públicas e assets
	- `src/Controllers/`, `src/Services/`, `src/Repositories/`, `src/Models/`
	- `config/` ou `config.php` (lendo env vars)
	- `database/` — `schema.sql`, `seeds.sql`
	- `migrations/`
	- `scripts/`, `docs/`, `tests/`, `logs/`

13. APIs e Integrações
- Versionar APIs (ex.: `/api/v1/`) e manter contrato documentado (OpenAPI/Swagger). Separar autenticação web de tokens API.

14. Compatibilidade e Manutenção
- Priorizar compatibilidade; breaking changes apenas em major releases com documentação e plano de migração.
- Usar feature flags para rollout de mudanças com risco.

15. Git, Commits e Processo
- Mensagens: Conventional Commits.
- Branches: `main` (produção), `develop` (integração), `feature/*`, `hotfix/*`.
- PRs: revisão por pelo menos 1 reviewer; CI verde antes do merge.

16. Documentação Técnica
- Documentar arquitetura, decisões (ADRs) e procedimentos de migração em `docs/` e `docs/decisions/`.
- README deve conter setup mínimo (XAMPP), env vars e como rodar migrations localmente.

17. GitHub Copilot
- Copilot é um assistente: sugestões exigem revisão humana.
- Não aceitar código gerado automaticamente sem entendimento explícito do impacto (especialmente regras fiscais e segurança).

18. Refatoração de Código Legado
- Refatorar em passos pequenos e reversíveis; manter wrappers compatíveis quando for necessário alterar contratos públicos.
- Antes de alterar funcionalidades críticas, escrever testes que capturem comportamento atual.

19. Critérios de Implementação Concluída
- Testes automatizados pertinentes presentes e CI verde.
- PR revisado e aprovado.
- Documentação atualizada (se aplica).
- Migrações adicionadas e verificadas em ambiente de staging.
- Checagens de segurança básicas atendidas (prepared statements, CSRF, escape de saída).

## Governança
- Esta Constituição tem precedência sobre práticas ad-hoc do projeto.
- Emendas requerem um documento de decisão (ADR), revisão e registro em `docs/decisions/` com data e motivação.
- Onde o código atual conflitar com a Constituição, a Constituição define o estado alvo; a migração para esse estado será tratada por specifications e planos separados.

**Versão**: 1.0 | **Ratificado**: 2026-08-17 | **Última Emenda**: 2026-08-17
