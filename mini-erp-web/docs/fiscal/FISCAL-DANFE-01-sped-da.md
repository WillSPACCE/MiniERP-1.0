# FISCAL-DANFE-01

O DANFE modelo 55 é renderizado por `nfephp-org/sped-da` v1.1.6 a partir do XML persistido em `fiscal_artifacts`. O serviço valida tenant, status, modelo, existência e SHA-256 antes de chamar `NFePHP\DA\NFe\Danfe`.

API usada: `new Danfe($xml)`, `printParameters('P', 'A4', 2, 2)`, `logoParameters()` opcional e `render()`.

XML offline permanece sem protocolo e o próprio sped-da aplica as marcas `NF-e NÃO PROTOCOLADA` e `SEM VALOR FISCAL`. Nenhum XML é alterado. O cache regenerável fica em `storage/fiscal/danfe`, fora de `public`.

O endpoint `/fiscal_danfe.php?artifact_id=ID&mode=inline|download` deriva o tenant exclusivamente da sessão ERP. Não aceita path, storage reference, banco ou tenant pela URL. SEFAZ não é chamada.
