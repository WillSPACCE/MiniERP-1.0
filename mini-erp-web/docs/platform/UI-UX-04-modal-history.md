# Histórico do Modal Empresa

O modal mantém uma pilha própria de estados `{tab, subtab}`. Os botões internos Voltar e Avançar percorrem essa pilha sem desfazer campos. A URL usa `company`, `tab` e `subtab`; `popstate` restaura a localização enquanto o modal estiver aberto. Quando não houver `company` na URL, o comportamento normal é fechar o workspace, respeitando a proteção de alterações não salvas.

Somente o painel de conteúdo rola. Header, navegação principal, breadcrumb, subnavegação fiscal e footer permanecem no shell. Valores e posição de scroll são guardados por estado durante a sessão do modal.
