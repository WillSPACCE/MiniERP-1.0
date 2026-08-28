using System.Diagnostics;
using System.Drawing.Drawing2D;
using System.Net.Sockets;
using System.Text.RegularExpressions;
using System.Security.Cryptography.X509Certificates;

namespace MiniRPServidores;

internal static class Program
{
    [STAThread]
    private static void Main()
    {
        ApplicationConfiguration.Initialize();
        Application.Run(new MainForm());
    }
}

internal sealed class ServerDefinition
{
    public required string Key { get; init; }
    public required string Name { get; init; }
    public required string Description { get; init; }
    public required int Port { get; init; }
}

internal sealed class ServerManager
{
    private readonly string xampp = @"C:\xampp";
    private readonly string projectRoot;
    private readonly string webRoot;

    public ServerManager()
    {
        // O executável fica em servidor-manager; assim ele encontra o projeto sem configuração manual.
        var executableFolder = AppContext.BaseDirectory.TrimEnd(Path.DirectorySeparatorChar);
        projectRoot = Directory.GetParent(executableFolder)?.FullName ?? executableFolder;
        if (!Directory.Exists(Path.Combine(projectRoot, "mini-erp-web")))
        {
            projectRoot = Directory.GetParent(projectRoot)?.FullName ?? projectRoot;
        }
        webRoot = Path.Combine(projectRoot, "mini-erp-web");
    }

    public static async Task<bool> IsListeningAsync(int port, int timeoutMs = 350)
    {
        try
        {
            using var client = new TcpClient();
            using var timeout = new CancellationTokenSource(timeoutMs);
            await client.ConnectAsync("127.0.0.1", port, timeout.Token);
            return true;
        }
        catch { return false; }
    }

    public async Task<string> StartAsync(ServerDefinition server)
    {
        if (await IsListeningAsync(server.Port)) return $"{server.Name} já estava ligado.";

        switch (server.Key)
        {
            case "apache":
                StartHidden("cmd.exe", $"/c \"{Path.Combine(xampp, "apache_start.bat")}\"", xampp);
                break;
            case "mysql":
                StartHidden("cmd.exe", $"/c \"{Path.Combine(xampp, "mysql_start.bat")}\"", xampp);
                break;
            case "php":
                var php = Path.Combine(xampp, "php", "php.exe");
                var publicPath = Path.Combine(webRoot, "public");
                RequireFile(php, "PHP do XAMPP");
                if (!Directory.Exists(publicPath)) throw new DirectoryNotFoundException($"Pasta não encontrada: {publicPath}");
                StartHidden(php, $"-S 0.0.0.0:8000 -t \"{publicPath}\"", webRoot);
                break;
        }

        if (!await WaitForAsync(server.Port, true, 12))
            throw new InvalidOperationException($"{server.Name} não respondeu na porta {server.Port}.");
        return $"{server.Name} ligado.";
    }

    public async Task<string> StopAsync(ServerDefinition server)
    {
        if (!await IsListeningAsync(server.Port)) return $"{server.Name} já estava desligado.";

        switch (server.Key)
        {
            case "apache":
                var apache = Path.Combine(xampp, "apache", "bin", "httpd.exe");
                RequireFile(apache, "Apache");
                await RunAndWaitAsync(apache, "-k shutdown", xampp);
                break;
            case "mysql":
                var mysqlAdmin = Path.Combine(xampp, "mysql", "bin", "mysqladmin.exe");
                RequireFile(mysqlAdmin, "MySQL Admin");
                await RunAndWaitAsync(mysqlAdmin, "--user=root shutdown", xampp);
                break;
            case "php":
                StopPhpOnPort();
                break;
        }

        if (!await WaitForAsync(server.Port, false, 10))
            throw new InvalidOperationException($"{server.Name} não desligou. Confira a configuração do XAMPP.");
        return $"{server.Name} desligado.";
    }

    private static void StopPhpOnPort()
    {
        var command = "Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue | " +
                      "ForEach-Object { $p = Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue; " +
                      "if ($p.ProcessName -like 'php*') { Stop-Process -Id $p.Id } }";
        StartHidden("powershell.exe", $"-NoProfile -WindowStyle Hidden -Command \"{command}\"", Environment.CurrentDirectory, true);
    }

    private static void RequireFile(string path, string description)
    {
        if (!File.Exists(path)) throw new FileNotFoundException($"{description} não encontrado em {path}");
    }

    private static void StartHidden(string file, string args, string folder, bool wait = false)
    {
        var process = Process.Start(new ProcessStartInfo(file, args)
        {
            WorkingDirectory = folder,
            UseShellExecute = false,
            CreateNoWindow = true,
            WindowStyle = ProcessWindowStyle.Hidden
        }) ?? throw new InvalidOperationException($"Não foi possível iniciar {file}.");
        if (wait) process.WaitForExit(5000);
    }

    private static async Task RunAndWaitAsync(string file, string args, string folder)
    {
        using var process = Process.Start(new ProcessStartInfo(file, args)
        {
            WorkingDirectory = folder,
            UseShellExecute = false,
            CreateNoWindow = true,
            WindowStyle = ProcessWindowStyle.Hidden
        }) ?? throw new InvalidOperationException($"Não foi possível executar {file}.");
        await process.WaitForExitAsync();
    }

    private static async Task<bool> WaitForAsync(int port, bool expected, int seconds)
    {
        var until = DateTime.UtcNow.AddSeconds(seconds);
        while (DateTime.UtcNow < until)
        {
            if (await IsListeningAsync(port) == expected) return true;
            await Task.Delay(300);
        }
        return false;
    }
}

internal sealed class PublicTunnelManager
{
    private Process? process;
    public string? PublicUrl { get; private set; }
    public string? BaseUrl { get; private set; }
    public bool Running => process is { HasExited: false } && PublicUrl is not null;

    public async Task<string> StartAsync()
    {
        if (Running) return PublicUrl!;
        if (!await ServerManager.IsListeningAsync(8000)) throw new InvalidOperationException("Ligue o servidor MiniRP antes de liberar o acesso online.");
        var folder=Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),"MiniRP","tools");Directory.CreateDirectory(folder);
        var executable=Path.Combine(folder,"cloudflared.exe");
        if(!File.Exists(executable)){
            using var client=new HttpClient();client.Timeout=TimeSpan.FromMinutes(3);
            var bytes=await client.GetByteArrayAsync("https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe");
            var temporary=executable+".download";await File.WriteAllBytesAsync(temporary,bytes);
            try{using var certificate=new X509Certificate2(X509Certificate.CreateFromSignedFile(temporary));if(!certificate.Subject.Contains("Cloudflare",StringComparison.OrdinalIgnoreCase))throw new InvalidOperationException("A assinatura digital do cloudflared não pertence à Cloudflare.");File.Move(temporary,executable,true);}catch{File.Delete(temporary);throw;}
        }
        process=new Process{StartInfo=new ProcessStartInfo(executable,"tunnel --no-autoupdate --url http://127.0.0.1:8000") {UseShellExecute=false,CreateNoWindow=true,RedirectStandardOutput=true,RedirectStandardError=true,WindowStyle=ProcessWindowStyle.Hidden}};
        var ready=new TaskCompletionSource<string>(TaskCreationOptions.RunContinuationsAsynchronously);
        DataReceivedEventHandler read=(_,e)=>{if(e.Data is null)return;var match=Regex.Match(e.Data,@"https://[a-z0-9-]+\.trycloudflare\.com",RegexOptions.IgnoreCase);if(match.Success){BaseUrl=match.Value;PublicUrl=BaseUrl+"/plataforma/";ready.TrySetResult(PublicUrl);}};
        process.OutputDataReceived+=read;process.ErrorDataReceived+=read;process.Start();process.BeginOutputReadLine();process.BeginErrorReadLine();
        var completed=await Task.WhenAny(ready.Task,Task.Delay(TimeSpan.FromSeconds(30)));
        if(completed!=ready.Task){Stop();throw new InvalidOperationException("O túnel não forneceu uma URL pública. Verifique sua conexão com a internet.");}
        return await ready.Task;
    }

    public void Stop(){try{if(process is {HasExited:false})process.Kill(true);}catch{}process?.Dispose();process=null;PublicUrl=null;BaseUrl=null;}
}

internal sealed class ServerCard : Panel
{
    private readonly Label status;
    private readonly Button action;
    public ServerDefinition Server { get; }
    public event EventHandler? ActionClicked;

    public ServerCard(ServerDefinition server)
    {
        Server = server;
        Size = new Size(650, 82);
        BackColor = Color.White;
        Padding = new Padding(18);

        var dot = new Label { Name = "dot", Text = "●", Font = new Font("Segoe UI", 18), Location = new Point(18, 21), Size = new Size(30, 35) };
        var name = new Label { Text = server.Name, Font = new Font("Segoe UI Semibold", 12), Location = new Point(58, 15), Size = new Size(250, 28) };
        var detail = new Label { Text = server.Description, ForeColor = Color.FromArgb(105, 115, 130), Location = new Point(59, 44), Size = new Size(310, 24) };
        status = new Label { TextAlign = ContentAlignment.MiddleCenter, Font = new Font("Segoe UI Semibold", 9), Location = new Point(390, 25), Size = new Size(105, 30) };
        action = new Button { Text = "Ligar", FlatStyle = FlatStyle.Flat, Cursor = Cursors.Hand, Location = new Point(510, 21), Size = new Size(112, 38), BackColor = Color.White };
        action.FlatAppearance.BorderColor = Color.FromArgb(210, 215, 222);
        action.Click += (_, e) => ActionClicked?.Invoke(this, e);
        Controls.AddRange([dot, name, detail, status, action]);
        Region = Region.FromHrgn(CreateRoundRectRgn(0, 0, Width, Height, 14, 14));
    }

    public void SetState(bool running)
    {
        var dot = Controls.Find("dot", false)[0] as Label;
        if (dot != null) dot.ForeColor = running ? Color.FromArgb(24, 169, 104) : Color.FromArgb(210, 83, 83);
        status.Text = running ? "LIGADO" : "DESLIGADO";
        status.ForeColor = running ? Color.FromArgb(16, 120, 75) : Color.FromArgb(165, 55, 55);
        status.BackColor = running ? Color.FromArgb(224, 247, 236) : Color.FromArgb(253, 234, 234);
        action.Text = running ? "Desligar" : "Ligar";
    }

    [System.Runtime.InteropServices.DllImport("gdi32.dll")]
    private static extern IntPtr CreateRoundRectRgn(int left, int top, int right, int bottom, int width, int height);
}

internal sealed class MainForm : Form
{
    private readonly ServerManager manager = new();
    private readonly ServerDefinition[] servers =
    [
        new() { Key = "apache", Name = "Apache", Description = "Servidor web · porta 80", Port = 80 },
        new() { Key = "mysql", Name = "MySQL", Description = "Banco de dados · porta 3306", Port = 3306 },
        new() { Key = "php", Name = "MiniRP", Description = "Aplicação PHP · porta 8000", Port = 8000 }
    ];
    private readonly List<ServerCard> cards = [];
    private readonly Label feedback;
    private readonly Button startAll;
    private readonly PublicTunnelManager tunnel = new();
    private readonly Label publicUrl;
    private readonly Button online;
    private readonly Button copyUrl;
    private bool busy;

    public MainForm()
    {
        Text = "MiniRP · Servidores";
        ClientSize = new Size(720, 680);
        StartPosition = FormStartPosition.CenterScreen;
        FormBorderStyle = FormBorderStyle.FixedSingle;
        MaximizeBox = false;
        BackColor = Color.FromArgb(241, 244, 248);
        Font = new Font("Segoe UI", 10);
        Icon = LoadIcon();

        var header = new Panel { Dock = DockStyle.Top, Height = 112, BackColor = Color.FromArgb(21, 42, 66) };
        header.Controls.Add(new Label { Text = "MiniRP", ForeColor = Color.White, Font = new Font("Segoe UI Semibold", 22), Location = new Point(34, 20), Size = new Size(220, 42) });
        header.Controls.Add(new Label { Text = "Central de servidores", ForeColor = Color.FromArgb(185, 202, 219), Location = new Point(37, 67), Size = new Size(300, 25) });
        Controls.Add(header);

        var y = 132;
        foreach (var server in servers)
        {
            var card = new ServerCard(server) { Location = new Point(35, y) };
            card.ActionClicked += async (_, _) => await ToggleAsync(card);
            cards.Add(card);
            Controls.Add(card);
            y += 94;
        }

        startAll = new Button
        {
            Text = "▶  LIGAR TUDO E ABRIR MINIRP",
            Font = new Font("Segoe UI Semibold", 11), ForeColor = Color.White,
            BackColor = Color.FromArgb(25, 137, 92), FlatStyle = FlatStyle.Flat,
            Cursor = Cursors.Hand, Location = new Point(35, 423), Size = new Size(650, 48)
        };
        startAll.FlatAppearance.BorderSize = 0;
        startAll.Click += async (_, _) => await StartAllAsync();
        Controls.Add(startAll);

        var open = new Button { Text = "Abrir no navegador", Location = new Point(35, 482), Size = new Size(205, 38), FlatStyle = FlatStyle.Flat, Cursor = Cursors.Hand };
        open.Click += (_, _) => OpenBrowser();
        var stop = new Button { Text = "Desligar tudo", Location = new Point(250, 482), Size = new Size(180, 38), FlatStyle = FlatStyle.Flat, Cursor = Cursors.Hand };
        stop.Click += async (_, _) => await StopAllAsync();
        Controls.AddRange([open, stop]);

        var onlinePanel=new Panel{Location=new Point(35,532),Size=new Size(650,88),BackColor=Color.White,Padding=new Padding(12)};
        onlinePanel.Controls.Add(new Label{Text="Painel da Plataforma online",Font=new Font("Segoe UI Semibold",10),Location=new Point(14,10),Size=new Size(230,22)});
        publicUrl=new Label{Text="Desligado · nenhuma porta pública aberta",ForeColor=Color.FromArgb(105,115,130),Location=new Point(14,39),Size=new Size(355,30),AutoEllipsis=true};
        online=new Button{Text="Liberar online",Location=new Point(382,16),Size=new Size(122,38),FlatStyle=FlatStyle.Flat,Cursor=Cursors.Hand};online.Click+=async(_,_)=>await ToggleTunnelAsync();
        copyUrl=new Button{Text="Abrir / copiar",Location=new Point(514,16),Size=new Size(110,38),FlatStyle=FlatStyle.Flat,Enabled=false,Cursor=Cursors.Hand};copyUrl.Click+=(_,_)=>{if(tunnel.PublicUrl is not null){Clipboard.SetText(tunnel.PublicUrl);Process.Start(new ProcessStartInfo(tunnel.PublicUrl){UseShellExecute=true});}};
        onlinePanel.Controls.AddRange([publicUrl,online,copyUrl]);Controls.Add(onlinePanel);

        feedback = new Label { Text = "Verificando os servidores...", ForeColor = Color.FromArgb(95, 105, 120), Location = new Point(35, 635), Size = new Size(650, 28) };
        Controls.Add(feedback);

        var timer = new System.Windows.Forms.Timer { Interval = 3000 };
        timer.Tick += async (_, _) => { if (!busy) await RefreshStatusAsync(); };
        timer.Start();
        Shown += async (_, _) => await RefreshStatusAsync();
        FormClosed += (_, _) => tunnel.Stop();
    }

    private Icon? LoadIcon()
    {
        var path = Path.Combine(AppContext.BaseDirectory, "minirp.ico");
        return File.Exists(path) ? new Icon(path) : null;
    }

    private async Task RefreshStatusAsync()
    {
        foreach (var card in cards) card.SetState(await ServerManager.IsListeningAsync(card.Server.Port));
    }

    private async Task ToggleAsync(ServerCard card) => await RunBusyAsync(async () =>
    {
        var running = await ServerManager.IsListeningAsync(card.Server.Port);
        return running ? await manager.StopAsync(card.Server) : await manager.StartAsync(card.Server);
    });

    private async Task StartAllAsync() => await RunBusyAsync(async () =>
    {
        var messages = new List<string>();
        foreach (var server in servers) messages.Add(await manager.StartAsync(server));
        OpenBrowser();
        return string.Join("  ", messages);
    });

    private async Task StopAllAsync()
    {
        if (MessageBox.Show("Desligar o acesso online, MiniRP, Apache e MySQL?", "Confirmar", MessageBoxButtons.YesNo, MessageBoxIcon.Question) != DialogResult.Yes) return;
        await RunBusyAsync(async () =>
        {
            tunnel.Stop();UpdateTunnelUi();
            var messages = new List<string>();
            foreach (var server in servers.Reverse()) messages.Add(await manager.StopAsync(server));
            return string.Join("  ", messages);
        });
    }

    private async Task ToggleTunnelAsync()
    {
        if(tunnel.Running){tunnel.Stop();UpdateTunnelUi();feedback.Text="Acesso online desligado.";return;}
        await RunBusyAsync(async()=>{var url=await tunnel.StartAsync();UpdateTunnelUi();return "Acesso online liberado: "+url;});
    }

    private void UpdateTunnelUi(){publicUrl.Text=tunnel.PublicUrl??"Desligado · nenhuma porta pública aberta";online.Text=tunnel.Running?"Desligar online":"Liberar online";copyUrl.Enabled=tunnel.Running;}

    private async Task RunBusyAsync(Func<Task<string>> operation)
    {
        if (busy) return;
        busy = true;
        startAll.Enabled = false;
        UseWaitCursor = true;
        feedback.Text = "Aguarde, preparando os servidores...";
        try
        {
            feedback.Text = await operation();
            await RefreshStatusAsync();
        }
        catch (Exception ex)
        {
            feedback.Text = "Não foi possível concluir a operação.";
            MessageBox.Show(ex.Message, "MiniRP · Atenção", MessageBoxButtons.OK, MessageBoxIcon.Warning);
        }
        finally
        {
            UseWaitCursor = false;
            startAll.Enabled = true;
            busy = false;
        }
    }

    private static void OpenBrowser() => Process.Start(new ProcessStartInfo("http://127.0.0.1:8000/") { UseShellExecute = true });
}
