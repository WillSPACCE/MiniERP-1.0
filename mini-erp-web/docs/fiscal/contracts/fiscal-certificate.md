# Contrato de certificado fiscal

Escopo canônico: `tenant_id + establishment_id`. Um certificado ativo deve ter PKCS#12 válido, identidade igual ao estabelecimento, validade corrente, arquivo privado e segredo AES-256-GCM recuperável. A senha nunca é persistida ou exibida.

Substituição: validar e armazenar novo material, então em transação desativar o atual, inserir o novo e auditar. Falha anterior ao commit preserva o atual. Histórico não é apagado. Desativação exige PlatformAdmin, CSRF e motivo; assinatura só consulta certificado `active=1`. Exportação e remoção definitiva são bloqueadas.
