# FISCAL-02C — fechamento operacional

Certificados A1 são administrados por estabelecimento no Control-Plane. Upload e substituição validam completamente o novo PKCS#12 antes da ativação; a troca de metadados é transacional e o anterior permanece inativo no storage/histórico. Desativação exige motivo, remove o certificado do readiness e impede o signer de recuperá-lo. Remoção física e exportação permanecem indisponíveis.

Status derivados pelo backend: não configurado, válido, próximo do vencimento, expirado e desativado. A UI mostra fingerprint, titular, emissor, tax ID, serial, validade, dias restantes, upload e ator. O teste é integralmente offline.

Séries aceitam apenas 55/65 e `tpAmb=2`. Toda criação, edição e ativação/desativação exige motivo e grava before/after em `fiscal_series_audit`. Depois da primeira reserva, contador e ambiente são imutáveis. O allocator trava primeiro a série e usa constraints únicas; 21 processos concorrentes comprovaram sequências 1–7 independentes em 55/1, 55/12 e 65/1.

`homologation_ready` e `production_ready` continuam falsos. CSC, QR Code, certificado real, download e SEFAZ estão fora do escopo.
