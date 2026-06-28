<?php
require_once __DIR__ . '/config.php';

/**
 * Minimal, dependency-free SMTP client for transactional email.
 * Speaks just enough SMTP (EHLO, optional STARTTLS, AUTH LOGIN, MAIL/RCPT/DATA)
 * to send authenticated mail through Hostinger's SMTP server. No Composer, no
 * PHPMailer, no external files to upload.
 */
final class SmtpMailer
{
    private $conn;
    private string $lastError = '';

    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * @param array $recipients  list of ['email'=>..., 'name'=>...]
     */
    public function send(
        array $recipients,
        string $subject,
        string $htmlBody,
        string $textBody,
        ?string $replyToEmail = null,
        ?string $replyToName = null
    ): bool {
        if (!$recipients) {
            $this->lastError = 'No recipients.';
            return false;
        }

        $secure = SMTP_SECURE === 'ssl';
        $remote = ($secure ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
        $ctx = stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false],
        ]);

        $errno = 0; $errstr = '';
        $this->conn = @stream_socket_client($remote, $errno, $errstr, 25, STREAM_CLIENT_CONNECT, $ctx);
        if (!$this->conn) {
            $this->lastError = "Connection failed: $errstr ($errno)";
            return false;
        }
        stream_set_timeout($this->conn, 25);

        try {
            $this->expect('220');

            $host = $this->clientHostname();
            $this->cmd("EHLO $host", '250');

            // STARTTLS path (port 587)
            if (!$secure && SMTP_SECURE === 'tls') {
                $this->cmd('STARTTLS', '220');
                if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('TLS negotiation failed.');
                }
                $this->cmd("EHLO $host", '250');
            }

            // AUTH LOGIN
            $this->cmd('AUTH LOGIN', '334');
            $this->cmd(base64_encode(SMTP_USER), '334');
            $this->cmd(base64_encode(SMTP_PASS), '235');

            // Envelope
            $this->cmd('MAIL FROM:<' . MAIL_FROM_EMAIL . '>', '250');
            foreach ($recipients as $r) {
                $this->cmd('RCPT TO:<' . $r['email'] . '>', '25');
            }

            // Data
            $this->cmd('DATA', '354');
            $message = $this->buildMessage($recipients, $subject, $htmlBody, $textBody, $replyToEmail, $replyToName);
            $this->write($message . "\r\n.\r\n");
            $this->expect('250');

            $this->cmd('QUIT', '221');
            fclose($this->conn);
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            if (is_resource($this->conn)) {
                @fclose($this->conn);
            }
            return false;
        }
    }

    private function buildMessage(
        array $recipients,
        string $subject,
        string $htmlBody,
        string $textBody,
        ?string $replyToEmail,
        ?string $replyToName
    ): string {
        $to = implode(', ', array_map(
            fn($r) => $this->encodeName($r['name'] ?? '') . ' <' . $r['email'] . '>',
            $recipients
        ));
        $boundary = 'b_' . bin2hex(random_bytes(12));
        $date = date('r');
        $msgId = '<' . bin2hex(random_bytes(16)) . '@' . $this->domainFromEmail(MAIL_FROM_EMAIL) . '>';

        $headers = [];
        $headers[] = 'Date: ' . $date;
        $headers[] = 'From: ' . $this->encodeName(MAIL_FROM_NAME) . ' <' . MAIL_FROM_EMAIL . '>';
        $headers[] = 'To: ' . $to;
        if ($replyToEmail) {
            $headers[] = 'Reply-To: ' . $this->encodeName($replyToName ?? '') . ' <' . $replyToEmail . '>';
        }
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'Message-ID: ' . $msgId;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->dotStuff($textBody) . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $this->dotStuff($htmlBody) . "\r\n\r\n";
        $body .= "--$boundary--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    // --- SMTP plumbing ---------------------------------------------------

    private function cmd(string $command, string $expectedPrefix): void
    {
        $this->write($command . "\r\n");
        $this->expect($expectedPrefix);
    }

    private function write(string $data): void
    {
        if (fwrite($this->conn, $data) === false) {
            throw new RuntimeException('Failed writing to SMTP socket.');
        }
    }

    private function expect(string $prefix): string
    {
        $response = '';
        while (!feof($this->conn)) {
            $line = fgets($this->conn, 515);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // SMTP multiline: 4th char is '-' until the final line where it's ' '
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = substr($response, 0, strlen($prefix));
        if (strpos($response, $prefix) !== 0) {
            throw new RuntimeException('SMTP: expected ' . $prefix . ', got ' . trim($response));
        }
        return $response;
    }

    // --- helpers ---------------------------------------------------------

    private function clientHostname(): string
    {
        $h = $_SERVER['SERVER_NAME'] ?? gethostname() ?: 'localhost';
        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $h) ?: 'localhost';
    }

    private function domainFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? 'localhost';
    }

    private function encodeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }
        if (preg_match('/[^\x20-\x7E]/', $name)) {
            return '=?UTF-8?B?' . base64_encode($name) . '?=';
        }
        return '"' . str_replace('"', '', $name) . '"';
    }

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    /** Prevent a line starting with "." from terminating DATA early. */
    private function dotStuff(string $body): string
    {
        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        return preg_replace('/^\./m', '..', $body);
    }
}

/**
 * Send via PHP's built-in mail() — no SMTP login required.
 * Works on Hostinger as long as MAIL_FROM_EMAIL is an address on your domain.
 */
function mail_via_php(array $recipients, string $subject, string $html, string $text, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    if (!$recipients) {
        return false;
    }
    // Plain address list (no display names) — exactly like the bare test that delivered.
    $to = implode(', ', array_map(fn($r) => $r['email'], $recipients));

    // Headers identical to the bare mailtest that DELIVERED — no extra Date /
    // Message-ID (those can duplicate sendmail's own and get hard-rejected).
    $headers = [];
    $headers[] = 'From: ' . MAIL_FROM_EMAIL;       // plain, no display name
    if ($replyToEmail) {
        $headers[] = 'Reply-To: ' . $replyToEmail;
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';   // single-part, not multipart

    $encodedHeaders = implode("\r\n", $headers);

    // Prefer the envelope sender (-f) for SPF alignment; fall back without it.
    $ok = @mail($to, encode_subject($subject), $html, $encodedHeaders, '-f' . MAIL_FROM_EMAIL);
    if (!$ok) {
        $ok = @mail($to, encode_subject($subject), $html, $encodedHeaders);
    }
    if (!$ok && DEBUG) {
        error_log('PHP mail() failed for: ' . $to);
    }
    return $ok;
}

function encode_subject(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/**
 * Convenience wrapper used by submit.php. Picks the transport per MAIL_TRANSPORT.
 */
function send_mail(array $recipients, string $subject, string $html, string $text, ?string $replyToEmail = null, ?string $replyToName = null): bool
{
    $transport = defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'auto';
    if ($transport === 'auto') {
        $smtpReady = !str_contains(SMTP_USER, 'CHANGE_ME') && !str_contains(SMTP_PASS, 'CHANGE_ME');
        $transport = $smtpReady ? 'smtp' : 'mail';
    }

    if ($transport === 'mail') {
        return mail_via_php($recipients, $subject, $html, $text, $replyToEmail, $replyToName);
    }

    $mailer = new SmtpMailer();
    $ok = $mailer->send($recipients, $subject, $html, $text, $replyToEmail, $replyToName);
    if (!$ok && DEBUG) {
        error_log('SMTP error: ' . $mailer->lastError());
    }
    return $ok;
}
