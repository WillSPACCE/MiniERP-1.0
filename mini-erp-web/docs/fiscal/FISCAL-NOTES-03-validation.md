# FISCAL-NOTES-03 — validação final

- `FiscalDocumentDoubleSubmitHttpTest.php` inicia dois servidores HTTP independentes e envia dois POSTs simultâneos ao endpoint real `public/fiscal_action.php`, com a mesma sessão, tenant, Pedido e token.
- O resultado comprovado é um Documento, uma reserva, um nNF, um cNF, uma chave e um artifact vigente. Um terceiro POST reutiliza o resultado.
- A proteção combina CSRF, unique key do Documento, transação, `GET_LOCK` do pipeline e uniques da reserva/número/chave. Deadlock concorrente recebe `REQUEST_CONCURRENT_RETRY` sem mensagem SQL.
- Espelho usa o mesmo token dentro do snapshot imutável e `GET_LOCK`; double submit retorna a mesma versão.
- `FiscalDanfeLogoMatrixTest.php` cobre sem logo, PNG válida, indisponível, traversal, path absoluto, cross-tenant e invalidação de cache por checksum.
- Todos os bancos, certificados, artifacts, logos, PDFs e sessões da suíte são TEST_ONLY e removidos no `finally`.

Nenhuma chamada SEFAZ faz parte desta validação.
