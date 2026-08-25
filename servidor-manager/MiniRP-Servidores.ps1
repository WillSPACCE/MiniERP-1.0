param(
    [switch]$StatusOnly,
    [switch]$StartAll
)

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

$script:XamppPath = 'C:\xampp'
$script:ProjectPath = Split-Path -Parent $PSScriptRoot
$script:WebPath = Join-Path $script:ProjectPath 'mini-erp-web'
$script:PublicPath = Join-Path $script:WebPath 'public'
$script:PhpPath = Join-Path $script:XamppPath 'php\php.exe'
$script:PhpPidFile = Join-Path $PSScriptRoot '.minirp-php.pid'
$script:AppUrl = 'http://127.0.0.1:8000/'
$script:PhpPort = 8000

function Get-PortProcess {
    param([int]$Port)

    $connection = Get-NetTCPConnection -State Listen -LocalPort $Port -ErrorAction SilentlyContinue |
        Select-Object -First 1
    if ($null -eq $connection) { return $null }
    return Get-Process -Id $connection.OwningProcess -ErrorAction SilentlyContinue
}

function Get-ServiceState {
    param([ValidateSet('Apache', 'MySQL', 'PHP')][string]$Name)

    switch ($Name) {
        'Apache' { $process = Get-PortProcess -Port 80 }
        'MySQL'  { $process = Get-PortProcess -Port 3306 }
        'PHP'    { $process = Get-PortProcess -Port $script:PhpPort }
    }

    if ($null -eq $process) {
        return [pscustomobject]@{ Running = $false; Pid = $null; ProcessName = $null }
    }
    return [pscustomobject]@{
        Running = $true
        Pid = $process.Id
        ProcessName = $process.ProcessName
    }
}

function Wait-ForState {
    param([string]$Name, [bool]$Running, [int]$Seconds = 12)

    $limit = (Get-Date).AddSeconds($Seconds)
    do {
        if ((Get-ServiceState -Name $Name).Running -eq $Running) { return $true }
        Start-Sleep -Milliseconds 300
    } while ((Get-Date) -lt $limit)
    return $false
}

function Start-XamppService {
    param([ValidateSet('Apache', 'MySQL')][string]$Name)

    if ((Get-ServiceState -Name $Name).Running) { return "$Name já estava ligado." }
    $batch = if ($Name -eq 'Apache') { 'apache_start.bat' } else { 'mysql_start.bat' }
    $path = Join-Path $script:XamppPath $batch
    if (-not (Test-Path $path)) { throw "Arquivo não encontrado: $path" }

    Start-Process -FilePath 'cmd.exe' -ArgumentList @('/c', $path) -WorkingDirectory $script:XamppPath -WindowStyle Hidden
    if (-not (Wait-ForState -Name $Name -Running $true)) {
        throw "$Name não respondeu na porta esperada. Confira se a porta está livre."
    }
    return "$Name ligado com sucesso."
}

function Start-PhpServer {
    if ((Get-ServiceState -Name 'PHP').Running) { return 'Servidor PHP já estava ligado.' }
    if (-not (Test-Path $script:PhpPath)) { throw "PHP não encontrado em $script:PhpPath" }
    if (-not (Test-Path $script:PublicPath)) { throw "Pasta pública não encontrada: $script:PublicPath" }

    $arguments = @('-S', "0.0.0.0:$script:PhpPort", '-t', $script:PublicPath)
    $process = Start-Process -FilePath $script:PhpPath -ArgumentList $arguments -WorkingDirectory $script:WebPath -WindowStyle Hidden -PassThru
    Set-Content -LiteralPath $script:PhpPidFile -Value $process.Id -Encoding ascii
    if (-not (Wait-ForState -Name 'PHP' -Running $true)) {
        throw 'O servidor PHP não conseguiu abrir a porta 8000.'
    }
    return 'Servidor PHP ligado com sucesso.'
}

function Stop-PhpServer {
    $state = Get-ServiceState -Name 'PHP'
    if (-not $state.Running) { return 'Servidor PHP já estava desligado.' }

    # Só encerra a porta 8000 se ela realmente pertencer ao PHP.
    if ($state.ProcessName -notlike 'php*') {
        throw "A porta 8000 pertence a $($state.ProcessName); ela não foi encerrada por segurança."
    }
    Stop-Process -Id $state.Pid -ErrorAction Stop
    Remove-Item -LiteralPath $script:PhpPidFile -Force -ErrorAction SilentlyContinue
    [void](Wait-ForState -Name 'PHP' -Running $false -Seconds 5)
    return 'Servidor PHP desligado.'
}

function Stop-XamppService {
    param([ValidateSet('Apache', 'MySQL')][string]$Name)

    if (-not (Get-ServiceState -Name $Name).Running) { return "$Name já estava desligado." }
    if ($Name -eq 'Apache') {
        $tool = Join-Path $script:XamppPath 'apache\bin\httpd.exe'
        $arguments = @('-k', 'shutdown')
    } else {
        $tool = Join-Path $script:XamppPath 'mysql\bin\mysqladmin.exe'
        $arguments = @('--user=root', 'shutdown')
    }
    if (-not (Test-Path $tool)) { throw "Ferramenta não encontrada: $tool" }
    $process = Start-Process -FilePath $tool -ArgumentList $arguments -WorkingDirectory $script:XamppPath -WindowStyle Hidden -Wait -PassThru
    if (-not (Wait-ForState -Name $Name -Running $false -Seconds 10)) {
        throw "$Name não desligou. Pode haver uma configuração ou senha diferente no XAMPP."
    }
    return "$Name desligado."
}

if ($StartAll) {
    Write-Output (Start-XamppService -Name 'Apache')
    Write-Output (Start-XamppService -Name 'MySQL')
    Write-Output (Start-PhpServer)
    exit 0
}

if ($StatusOnly) {
    foreach ($name in @('Apache', 'MySQL', 'PHP')) {
        $state = Get-ServiceState -Name $name
        Write-Output ("{0}: {1}{2}" -f $name, $(if ($state.Running) { 'LIGADO' } else { 'DESLIGADO' }), $(if ($state.Pid) { " (PID $($state.Pid))" } else { '' }))
    }
    exit 0
}

[System.Windows.Forms.Application]::EnableVisualStyles()
$form = New-Object System.Windows.Forms.Form
$form.Text = 'MiniRP - Gerenciador de Servidores'
$form.Size = New-Object System.Drawing.Size(590, 485)
$form.StartPosition = 'CenterScreen'
$form.FormBorderStyle = 'FixedSingle'
$form.MaximizeBox = $false
$form.BackColor = [System.Drawing.Color]::FromArgb(245, 247, 250)
$form.Font = New-Object System.Drawing.Font('Segoe UI', 10)

$title = New-Object System.Windows.Forms.Label
$title.Text = 'MiniRP pronto para trabalhar'
$title.Location = New-Object System.Drawing.Point(25, 20)
$title.Size = New-Object System.Drawing.Size(520, 35)
$title.Font = New-Object System.Drawing.Font('Segoe UI Semibold', 18)
$form.Controls.Add($title)

$subtitle = New-Object System.Windows.Forms.Label
$subtitle.Text = 'Ligue tudo com um clique e acompanhe o estado dos servidores.'
$subtitle.Location = New-Object System.Drawing.Point(28, 60)
$subtitle.Size = New-Object System.Drawing.Size(520, 25)
$subtitle.ForeColor = [System.Drawing.Color]::DimGray
$form.Controls.Add($subtitle)

$rows = @{}
$rowY = 105
foreach ($name in @('Apache', 'MySQL', 'PHP')) {
    $label = New-Object System.Windows.Forms.Label
    $label.Text = if ($name -eq 'PHP') { 'MiniRP (PHP :8000)' } else { $name }
    $label.Location = New-Object System.Drawing.Point(32, $rowY)
    $label.Size = New-Object System.Drawing.Size(210, 30)
    $label.Font = New-Object System.Drawing.Font('Segoe UI Semibold', 11)
    $form.Controls.Add($label)

    $status = New-Object System.Windows.Forms.Label
    $status.Text = 'VERIFICANDO'
    $status.TextAlign = 'MiddleCenter'
    $status.Location = New-Object System.Drawing.Point(250, ($rowY - 2))
    $status.Size = New-Object System.Drawing.Size(125, 30)
    $status.BackColor = [System.Drawing.Color]::LightGray
    $form.Controls.Add($status)

    $button = New-Object System.Windows.Forms.Button
    $button.Location = New-Object System.Drawing.Point(395, ($rowY - 4))
    $button.Size = New-Object System.Drawing.Size(145, 34)
    $button.FlatStyle = 'Flat'
    $button.Tag = $name
    $form.Controls.Add($button)
    $rows[$name] = @{ Status = $status; Button = $button }
    $rowY += 50
}

$startAll = New-Object System.Windows.Forms.Button
$startAll.Text = 'LIGAR TUDO E ABRIR'
$startAll.Location = New-Object System.Drawing.Point(30, 270)
$startAll.Size = New-Object System.Drawing.Size(510, 48)
$startAll.BackColor = [System.Drawing.Color]::FromArgb(28, 125, 84)
$startAll.ForeColor = [System.Drawing.Color]::White
$startAll.FlatStyle = 'Flat'
$startAll.Font = New-Object System.Drawing.Font('Segoe UI Semibold', 12)
$form.Controls.Add($startAll)

$openButton = New-Object System.Windows.Forms.Button
$openButton.Text = 'Abrir MiniRP no navegador'
$openButton.Location = New-Object System.Drawing.Point(30, 330)
$openButton.Size = New-Object System.Drawing.Size(245, 38)
$form.Controls.Add($openButton)

$stopAll = New-Object System.Windows.Forms.Button
$stopAll.Text = 'Desligar tudo'
$stopAll.Location = New-Object System.Drawing.Point(295, 330)
$stopAll.Size = New-Object System.Drawing.Size(245, 38)
$form.Controls.Add($stopAll)

$message = New-Object System.Windows.Forms.Label
$message.Text = 'Pronto.'
$message.Location = New-Object System.Drawing.Point(30, 385)
$message.Size = New-Object System.Drawing.Size(510, 40)
$message.ForeColor = [System.Drawing.Color]::DimGray
$form.Controls.Add($message)

function Update-Display {
    foreach ($name in @('Apache', 'MySQL', 'PHP')) {
        $state = Get-ServiceState -Name $name
        $row = $rows[$name]
        if ($state.Running) {
            $row.Status.Text = 'LIGADO'
            $row.Status.BackColor = [System.Drawing.Color]::FromArgb(190, 235, 211)
            $row.Status.ForeColor = [System.Drawing.Color]::FromArgb(15, 95, 58)
            $row.Button.Text = 'Desligar'
        } else {
            $row.Status.Text = 'DESLIGADO'
            $row.Status.BackColor = [System.Drawing.Color]::FromArgb(245, 205, 205)
            $row.Status.ForeColor = [System.Drawing.Color]::FromArgb(145, 35, 35)
            $row.Button.Text = 'Ligar'
        }
    }
}

function Invoke-Safely {
    param([scriptblock]$Action)
    try {
        $form.UseWaitCursor = $true
        $message.Text = 'Aguarde...'
        $form.Refresh()
        $result = & $Action
        $message.Text = ($result -join '  ')
    } catch {
        $message.Text = "Erro: $($_.Exception.Message)"
        [System.Windows.Forms.MessageBox]::Show($_.Exception.Message, 'MiniRP - Atenção', 'OK', 'Warning') | Out-Null
    } finally {
        $form.UseWaitCursor = $false
        Update-Display
    }
}

foreach ($name in @('Apache', 'MySQL', 'PHP')) {
    $rows[$name].Button.Add_Click({
        $serviceName = $this.Tag
        Invoke-Safely {
            if ((Get-ServiceState -Name $serviceName).Running) {
                if ($serviceName -eq 'PHP') { Stop-PhpServer } else { Stop-XamppService -Name $serviceName }
            } else {
                if ($serviceName -eq 'PHP') { Start-PhpServer } else { Start-XamppService -Name $serviceName }
            }
        }
    })
}

$startAll.Add_Click({
    Invoke-Safely {
        $results = @()
        $results += Start-XamppService -Name 'Apache'
        $results += Start-XamppService -Name 'MySQL'
        $results += Start-PhpServer
        Start-Process $script:AppUrl
        return $results
    }
})

$openButton.Add_Click({ Start-Process $script:AppUrl })
$stopAll.Add_Click({
    $answer = [System.Windows.Forms.MessageBox]::Show('Deseja desligar PHP, Apache e MySQL?', 'Confirmar', 'YesNo', 'Question')
    if ($answer -ne 'Yes') { return }
    Invoke-Safely {
        $results = @()
        $results += Stop-PhpServer
        $results += Stop-XamppService -Name 'Apache'
        $results += Stop-XamppService -Name 'MySQL'
        return $results
    }
})

$timer = New-Object System.Windows.Forms.Timer
$timer.Interval = 2500
$timer.Add_Tick({ Update-Display })
$timer.Start()
Update-Display
[void]$form.ShowDialog()
$timer.Stop()
