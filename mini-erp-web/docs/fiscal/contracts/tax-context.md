# Contrato FiscalTaxContext

Value object imutável, sem `$_POST`, `$_SESSION`, PDO ou Repository. O serviço de aplicação monta o contexto a partir de dados já autorizados.

| Origem | Dados consumidos |
|---|---|
| Establishment | `tenantId`, `establishmentId`, `crt`, `originState` |
| Person | `recipientType`, `destinationState`, `ieIndicator`, `countryCode` |
| Product | `ncm`, `cest`, `productOrigin`, `taxBenefitCode` |
| Operation | `direction`, `model`, `purpose`, `finalConsumer`, `icmsContributor`, `presence`, `operationDate`, quantidade, preço e dica de CFOP |

O tenant e banco nunca são resolvidos pelo motor. Produto incompleto deve ser identificado antes por `ProductFiscalCompleteness`.
