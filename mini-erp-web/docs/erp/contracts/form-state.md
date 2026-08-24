# Contrato de estado de formulário

Em falha de gravação, `FlashFormState` filtra o POST, armazena `old_input` e erros na sessão e aplica POST/Redirect/GET. O estado é consumido uma vez no GET seguinte.

Strings, selects, radios, checkboxes, textareas e arrays aninhados são preservados. Nunca são preservados: senha/password, senha de certificado, CSRF/token, PFX/P12 e `FISCAL_SECRET_KEY`. Uploads não fazem parte do POST preservado.

O resumo de erro aparece no topo. Formulários podem associar erros a campos específicos conforme suas validações evoluírem; o input nunca deve ser limpo por um erro em outro campo.
