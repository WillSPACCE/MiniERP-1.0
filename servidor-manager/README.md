# Aplicativo de servidores do MiniRP

Abra `dist\MiniRP Servidores.exe` com dois cliques. É um aplicativo Windows com janela
própria; não abre terminal nem depende do antigo arquivo `.bat`. O painel permite:

- ligar Apache, MySQL e o servidor PHP do MiniRP com um clique;
- abrir o MiniRP automaticamente no navegador;
- ver quais servidores estão ligados;
- ligar ou desligar cada servidor separadamente;
- desligar todos os servidores com confirmação.

O painel pressupõe o XAMPP instalado em `C:\xampp` e usa a porta `8000` para o MiniRP.

Para facilitar ainda mais, clique com o botão direito no arquivo `.exe`, escolha
`Enviar para` e depois `Área de trabalho (criar atalho)`.

## Diagnóstico rápido

No PowerShell, execute:

```powershell
powershell -ExecutionPolicy Bypass -File .\MiniRP-Servidores.ps1 -StatusOnly
```

Também é possível ligar tudo pelo terminal com `-StartAll`.

## Código-fonte

O aplicativo fica em `app` e usa C# com Windows Forms. Para gerar uma nova versão:

```powershell
dotnet publish .\app\MiniRP.Servidores.csproj -c Release -r win-x64 --self-contained false -p:PublishSingleFile=true -o .\dist
```
