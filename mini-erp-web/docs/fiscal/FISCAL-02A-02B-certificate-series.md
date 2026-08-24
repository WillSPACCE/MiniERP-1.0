# FISCAL-02A/02B — certificado A1 e séries por estabelecimento

Implementação em 2026-08-21. O certificado pertence ao par canônico `tenant_id + establishment_id`. PKCS#12 é validado offline, limitado a 5 MB, tem identidade comparada fail-closed ao `tax_id` do estabelecimento e é armazenado fora de `public/`. A senha nunca entra no banco: `LocalEncryptedSecretStorage` usa AES-256-GCM com `FISCAL_SECRET_KEY` externa (mínimo 32 caracteres) e referência opaca persistida.

Metadados persistidos: referência privada, nome, SHA-256, fingerprint, subject, issuer, serial, tax ID, validade, status e referência do segredo. Download permanece desabilitado até existir política de reautenticação forte e auditoria específica.

Séries reutilizam `fiscal_series` e `fiscal_number_reservations`. O escopo é tenant, estabelecimento, modelo, série e ambiente; produção é recusada. Alterações são travadas por `FOR UPDATE`, não podem reduzir contador após reserva e geram `fiscal_series_audit` com usuário e motivo. `FiscalNumberAllocator` continua sendo a autoridade de `nNF`, com transação e constraints únicas.

Readiness diferencia `certificate_ready`, `homologation_ready=false` e `production_ready=false`. NFC-e apenas registra o gap futuro de CSC/idCSC; nenhum valor fictício foi criado. Não houve chamada SEFAZ, protocolo, autorização ou QR Code.
