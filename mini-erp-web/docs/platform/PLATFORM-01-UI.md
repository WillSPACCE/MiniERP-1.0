# Padrão visual do Painel da Plataforma

## Referência encontrada

O ERP usa CSS próprio em `public/assets/style.css`, sem Bootstrap. Sua identidade é baseada em Arial/Helvetica, fundo `#f4f7fb`, painéis brancos, azul primário `#2f6fed`, azul escuro `#204ea5`, texto `#1c2340`, bordas `#dfe6f1`, radius entre 8 e 12px e sombras azuis discretas. Também já possui cards, badges, tabelas com overflow, botões primário/secundário/ghost e navegação lateral responsiva.

## Aplicação no Control-Plane

`public/assets/platform.css` reutiliza esses tokens visuais sem importar lógica ou CSS operacional do ERP. O painel possui sidebar própria, header administrativo, cards derivados da listagem real, tabela responsiva, badges com texto e cor, botões semanticamente desabilitados e páginas de detalhe/confirmação.

Sidebar: Dashboard, Empresas, Usuários, Acessos, Auditoria e Configurações. Apenas Empresas é funcional; os demais itens indicam “Em breve”. O header identifica o Control-Plane, ambiente, PlatformAdmin e logout.

## Continuidade

Próximas telas devem reutilizar `platform.css`, manter labels explícitos, foco visível, mensagens com texto, ações futuras desabilitadas e tabelas dentro de `.table-wrap`. Não se deve importar estado de sessão, menus ou operações do data-plane apenas por semelhança visual.
