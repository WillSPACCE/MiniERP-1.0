<?php

class Mailer
{
    private array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../config.php';
        $this->config = $this->config['mail'] ?? [];
    }

    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        // Tenta usar PHPMailer via Composer se disponível e configurações SMTP estiverem presentes
        $composerAutoload = __DIR__ . '/../vendor/autoload.php';
        $useSmtp = !empty($this->config['smtp_host']);

        if (file_exists($composerAutoload) && $useSmtp) {
            try {
                require_once $composerAutoload;
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->SMTPDebug = 0;
                $mail->Host = $this->config['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['smtp_user'] ?? '';
                $mail->Password = $this->config['smtp_pass'] ?? '';
                $mail->SMTPSecure = $this->config['smtp_secure'] ?? 'tls';
                $mail->Port = (int)($this->config['smtp_port'] ?: 587);
                $mail->CharSet = 'UTF-8';
                $mail->setFrom($this->config['from'] ?? 'no-reply@local', 'MiniERPWeb');
                $mail->addAddress($to);
                $mail->Subject = $subject;
                if ($isHtml) {
                    $mail->isHTML(true);
                    $mail->Body = $body;
                } else {
                    $mail->Body = $body;
                }
                return $mail->send();
            } catch (Throwable $e) {
                // se PHPMailer falhar, cair para fallback
            }
        }

        // Fallback para mail() nativo (requer configuração sendmail/php.ini)
        // Se houver configuração SMTP, tente enviar via socket SMTP (sem Composer)
        if ($useSmtp) {
            try {
                return $this->sendViaSmtpSocket($to, $subject, $body, $isHtml);
            } catch (Throwable $e) {
                // fallback para mail()
            }
        }

        $from = $this->config['from'] ?? 'no-reply@local';
        $headers = [];
        if ($isHtml) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
        }
        $headers[] = 'From: ' . $from;
        $headers[] = 'Reply-To: ' . ($this->config['reply_to'] ?? $from);

        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }

    private function sendViaSmtpSocket(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        $host = $this->config['smtp_host'] ?? '';
        $port = (int)($this->config['smtp_port'] ?? 587);
        $user = $this->config['smtp_user'] ?? '';
        $pass = $this->config['smtp_pass'] ?? '';
        $secure = $this->config['smtp_secure'] ?? 'tls';
        if ($host === '') return false;

        $errno = 0; $errstr = '';
        $transport = ($secure === 'ssl') ? 'ssl://' : '';
        $fp = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 15);
        if (!$fp) return false;
        stream_set_timeout($fp, 15);

        $read = function() use ($fp) {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $send = function($cmd) use ($fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $greeting = $read();
        // EHLO
        $send('EHLO ' . gethostname());
        $read();

        // STARTTLS if needed
        if ($secure === 'tls') {
            $send('STARTTLS');
            $res = $read();
            if (strpos($res, '220') !== false) {
                stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                // EHLO again
                $send('EHLO ' . gethostname());
                $read();
            }
        }

        // Auth
        if ($user !== '') {
            $send('AUTH LOGIN'); $read();
            $send(base64_encode($user)); $read();
            $send(base64_encode($pass)); $read();
        }

        $from = $this->config['from'] ?? $user ?: 'no-reply@local';
        $send('MAIL FROM: <' . $from . '>'); $read();
        $send('RCPT TO: <' . $to . '>'); $read();
        $send('DATA'); $read();

        $headers = [];
        $headers[] = 'From: ' . $from;
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        if ($isHtml) {
            $headers[] = 'Content-type: text/html; charset=utf-8';
        }
        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $send($message);
        $read();
        $send('QUIT');
        fclose($fp);
        return true;
    }

    public function sendVerification(string $to, string $token): bool
    {
        $link = sprintf('%s/verify_email.php?token=%s', rtrim((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/'), $token);
        $subject = 'Confirme seu e-mail — MiniERPWeb';
        $body = '<p>Olá,</p>';
        $body .= '<p>Por favor confirme seu e-mail clicando no link abaixo:</p>';
        $body .= '<p><a href="' . htmlspecialchars($link) . '">Confirmar meu e-mail</a></p>';
        $body .= '<p>Se você não solicitou este registro, ignore esta mensagem.</p>';
        return $this->send($to, $subject, $body, true);
    }

    public function sendPasswordReset(string $to, string $code): bool
    {
        $link = sprintf('%s/reset.php', rtrim((isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''), '/'));
        $subject = 'Recuperação de senha — MiniERPWeb';
        $body = '<p>Você solicitou a recuperação de senha.</p>';
        $body .= '<p>Use o código abaixo para redefinir sua senha:</p>';
        $body .= '<h2 style="letter-spacing:4px;">' . htmlspecialchars($code) . '</h2>';
        $body .= '<p>Ou clique no link abaixo e insira o código:</p>';
        $body .= '<p><a href="' . htmlspecialchars($link) . '">Abrir página de redefinição</a></p>';
        $body .= '<p>Se você não solicitou, ignore esta mensagem.</p>';
        return $this->send($to, $subject, $body, true);
    }
}
