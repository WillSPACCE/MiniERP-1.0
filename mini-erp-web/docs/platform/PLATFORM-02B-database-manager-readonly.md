# Database Manager read-only

Primeira versão disponível no detalhe da empresa. O banco vem exclusivamente do MAIN após autorização PlatformAdmin. Lista tabelas, colunas, índices, FKs e até 50 registros por página (máximo 100), com tabela validada contra whitelist do schema.

Campos de senha, segredo, token, private key, PFX/certificado bruto e payload sensível são mascarados. Não há endpoint POST nem SQL de escrita. Associações históricas exibem banner de revisão. A comparação usa o template v1 como fonte.
