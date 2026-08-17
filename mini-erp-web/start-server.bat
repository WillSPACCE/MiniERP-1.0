@echo off
cd /d "%~dp0"
echo Iniciando PHP built-in server na porta 8000 (pasta public)...
start "PHP Server" "C:\xampp\php\php.exe" -S 0.0.0.0:8000 -t "%~dp0public"
echo URL para compartilhar: http://192.168.1.107:8000
echo http://192.168.1.107:8000 | clip
echo URL copiada para a area de transferencia.
pause
