@echo off
setlocal

cd /d "%~dp0"

set "PLATFORM_PHP=C:\xampp\php\php.exe"
set "PLATFORM_URL=http://localhost:8000/plataforma/login.php"

if not exist "%PLATFORM_PHP%" (
    echo ERRO: PHP do XAMPP nao encontrado em "%PLATFORM_PHP%".
    exit /b 1
)

echo Iniciando o servidor do Painel da Plataforma...
echo URL: %PLATFORM_URL%
echo Autenticacao: platform_admin_users no banco MAIN.
echo Se ainda nao houver administrador, execute bin\create-platform-admin.php.

start "MiniERP Platform Server" "%PLATFORM_PHP%" -S 0.0.0.0:8000 -t "%~dp0public"

timeout /t 2 /nobreak >nul
start "" "%PLATFORM_URL%"

echo Painel aberto no navegador.
echo Para encerrar o servidor, feche a janela "MiniERP Platform Server".

endlocal
