# FISCAL-06B — Numeração, chave e artefatos XML

Estado em 2026-08-21: fundações implementadas; geração de XML bloqueada em modo fail-closed. O PHP 8.2 possui DOM/XML/OpenSSL, porém Composer não está instalado e `nfephp-org/sped-nfe` não pôde ser instalado, travado e validado contra os schemas 2026. Não existe builder XML improvisado, botão de preparação habilitado, XML fiscal produzido ou migration aplicada.

Foram implementados: configuração aditiva de séries com homologação (`environment=2`) como default, reserva transacional/idempotente com `SELECT ... FOR UPDATE`, chave alfanumérica/DV segundo NT Conjunta 2025.001 e NT 2026.004, DTO originado de snapshots e storage fora de `public/` com referência interna e SHA-256.

Uma reserva consumida permanece `RESERVED` se a etapa seguinte falhar e não retorna à sequência. O gap deve ser auditado e poderá exigir inutilização em task futura. Produção, certificado, assinatura, QR Code, transmissão e protocolo estão fora do escopo.

Próximo desbloqueio: instalar o Composer por distribuição oficial, validar versão exata de `sped-nfe` com PHP 8.2, RTC/IBS/CBS e CNPJ alfanumérico, gerar `composer.lock`, incorporar schemas oficiais versionados e somente então implementar o builder/XSD.
