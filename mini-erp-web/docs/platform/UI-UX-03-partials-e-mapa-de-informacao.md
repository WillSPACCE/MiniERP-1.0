# UI-UX-03 — partials e mapa de informação

O layout do Control-Plane aceita `?view=partial`. Nesse modo, autenticação, autorização, resolução do tenant e serviços continuam iguais, mas o shell global não é renderizado. O Modal Empresa carrega somente uma aba por vez via `fetch`; PDFs e DANFE continuam podendo usar iframe.

- Geral: identidade comercial e CNPJ.
- Central Fiscal: padrões tributários e subtabs fiscais.
- Fiscal: cadastro do emitente.
- Certificado Digital: certificado A1, diagnóstico e teste local.
- NF-e / NFC-e: séries e CSC aplicável.
- Usuários: acessos da empresa.
- Banco de Dados: inspeção read-only.
- Ambiente: tenant, banco, schema, runtime e timestamps técnicos.
- Prontidão: checklist consolidado.

Novas áreas administrativas extensas devem separar controller/serviço da apresentação, oferecer partial sem shell e usar loading local. Segredos nunca entram em cache de interface; após salvar, o cache da aba é invalidado.
