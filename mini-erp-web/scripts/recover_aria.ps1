# recover_aria.ps1 — Script seguro para backup + tentativa de reparo Aria (XAMPP)
# Use: execute em PowerShell elevado (Run as Administrator).
# Atenção: faça backup completo antes de prosseguir.

# Verifica se está como Administrador
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Execute este script como Administrador (Run as Administrator). Saindo."
    exit 1
}

# Ajuste se seu XAMPP estiver em outro caminho
$xamppRoot = 'C:\xampp'
$dataDir = Join-Path $xamppRoot 'mysql\data'
$binDir = Join-Path $xamppRoot 'mysql\bin'

if (-not (Test-Path $dataDir)) {
    Write-Error "Diretório de dados não encontrado: $dataDir"
    exit 1
}

# Confirmação do usuário
Write-Host "Diretório de dados detectado: $dataDir" -ForegroundColor Cyan
$null = Read-Host "Confirme que fez backup externo e pressione Enter para continuar (Ctrl+C para abortar)"

# Cria backup (usa robocopy para velocidade)
$timestamp = Get-Date -Format 'yyyyMMddHHmmss'
$backupDir = Join-Path $env:TEMP ("mysql_data_backup_$timestamp")
Write-Host "Criando backup para: $backupDir" -ForegroundColor Yellow
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null

# Robocopy copy (retorna código do robocopy; ignore códigos que indicam sucesso parcial)
$robocopyCmd = "robocopy `"$dataDir`" `"$backupDir`" /MIR /R:3 /W:5"
Write-Host "Executando: $robocopyCmd"
Invoke-Expression $robocopyCmd
Write-Host "Backup concluído (verifique $backupDir)." -ForegroundColor Green

# Move arquivos aria_log* para .bak (não exclui)
Get-ChildItem -Path $dataDir -Filter 'aria_log*' -File -ErrorAction SilentlyContinue | ForEach-Object {
    $src = $_.FullName
    $dest = $_.FullName + '.bak'
    try {
        Move-Item -Path $src -Destination $dest -Force
        Write-Host "Movido: $($_.Name) -> $(Split-Path $dest -Leaf)"
    } catch {
        Write-Warning "Falha ao mover $($_.Name): $_"
    }
}

# Localiza aria_chk
$ariaChk = Join-Path $binDir 'aria_chk.exe'
if (-not (Test-Path $ariaChk)) {
    Write-Warning "aria_chk.exe não encontrado em $ariaChk. Se não existir, instale ferramentas MariaDB ou use XAMPP com utilitários.";
    Write-Host "Após mover os aria_log* tente iniciar o MySQL pelo XAMPP Control Panel." -ForegroundColor Yellow
    exit 0
}

# Repara tabelas Aria (.MAI/.MAD)
$ariaFiles = Get-ChildItem -Path $dataDir -Include '*.MAI','*.MAD' -File -ErrorAction SilentlyContinue
if ($ariaFiles.Count -eq 0) {
    Write-Host "Nenhum arquivo Aria (.MAI/.MAD) encontrado em $dataDir." -ForegroundColor Yellow
} else {
    foreach ($f in $ariaFiles) {
        Write-Host "Reparando Aria: $($f.Name)" -ForegroundColor Cyan
        try {
            & $ariaChk -r $f.FullName 2>&1 | ForEach-Object { Write-Host $_ }
        } catch {
            Write-Warning "aria_chk retornou erro para $($f.Name): $_"
        }
    }
}

Write-Host "Operação concluída. Agora tente iniciar o MySQL pelo XAMPP Control Panel e cole o conteúdo de $xamppRoot\mysql\data\mysql_error.log se ainda houver falha." -ForegroundColor Green
Write-Host "Observação: se o erro persistir com 'Could not open mysql.plugin table', verifique os arquivos em $dataDir\mysql e considere restaurar esses arquivos do backup." -ForegroundColor Yellow

# Opcional: comando sugerido para mysql_upgrade (descomente se souber o que faz)
# & (Join-Path $binDir 'mysql_upgrade.exe') -u root --force

exit 0
