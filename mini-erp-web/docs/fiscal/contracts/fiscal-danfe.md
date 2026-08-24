# Contrato fiscal DANFE

- Entrada: `artifact_id` e tenant autenticado.
- Estados aceitos: `XSD_VALID_OFFLINE`, `SIGNED`, `READY`, `AUTHORIZED`.
- Documento suportado: NF-e modelo 55.
- Saída: bytes `%PDF`, nome seguro, SHA-256 e estado de cache.
- Cache key: artifact id + SHA do XML + versão sped-da + checksum do logo.
- Erros estáveis: `DANFE_ARTIFACT_NOT_FOUND`, `DANFE_ARTIFACT_FILE_MISSING`, `DANFE_ARTIFACT_INTEGRITY_FAILED`, `DANFE_XML_INVALID`, `DANFE_UNSUPPORTED_MODEL`, `DANFE_RENDER_FAILED`.
