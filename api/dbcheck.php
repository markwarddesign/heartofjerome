<?php
/**
 * TEMPORARY diagnostic — visit:  https://yourdomain.com/api/dbcheck.php?token=jerome
 * Reports DATABASE + EMAIL (SMTP) status so you can see why a submission isn't
 * saving or emailing. No passwords are exposed.
 *
 * ⚠️  DELETE THIS FILE from the server once everything works.
 */
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if (($_GET['token'] ?? '') !== 'jerome') {
    http_response_code(403);
    echo json_encode(['error' => 'Add ?token=jerome to the URL.']);
    exit;
}

$out = ['database' => check_db(), 'email' => check_email()];
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);


/* ------------------------------------------------------------------ */
function check_db(): array
{
    $r = [
        'using'  => ['DB_HOST' => DB_HOST, 'DB_PORT' => DB_PORT, 'DB_NAME' => DB_NAME, 'DB_USER' => DB_USER],
        'placeholder_unset' => has_changeme(DB_NAME) || has_changeme(DB_USER) || has_changeme(DB_PASS),
    ];
    if ($r['placeholder_unset']) {
        $r['verdict'] = 'STOP: config.php still has CHANGE_ME database values — fill in your real DB credentials.';
        return $r;
    }
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $r['connection'] = 'OK';
        try {
            $r['row_count'] = (int) $pdo->query('SELECT COUNT(*) FROM kindness_acts')->fetchColumn();
            $r['table_kindness_acts'] = 'exists';
            $r['verdict'] = 'All good — database reachable and table exists.';
        } catch (Throwable $e) {
            $r['table_kindness_acts'] = 'MISSING';
            $r['verdict'] = 'Connected, but the kindness_acts table is missing — run api/sql/schema.sql in THIS database.';
        }
    } catch (Throwable $e) {
        $r['connection'] = 'FAILED';
        $r['connection_error'] = $e->getMessage();
        $r['verdict'] = 'Could not connect — check DB_HOST / DB_NAME / DB_USER / DB_PASS in config.php.';
    }
    return $r;
}

function check_email(): array
{
    // Resolve effective transport the same way send_mail() does.
    $transport = defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'auto';
    if ($transport === 'auto') {
        $transport = (!has_changeme(SMTP_USER) && !has_changeme(SMTP_PASS)) ? 'smtp' : 'mail';
    }

    $r = [
        'transport'         => $transport,
        'mail_from'         => MAIL_FROM_EMAIL,
        'team_recipients'   => count(TEAM_RECIPIENTS),
        'send_confirmation' => SEND_CONFIRMATION,
    ];
    if (empty(TEAM_RECIPIENTS) && !SEND_CONFIRMATION) {
        $r['verdict'] = 'Email is DISABLED in this config (this is the default for LOCAL dev — config.local.php). On the live server it should be enabled.';
        return $r;
    }

    // PHP mail() path — nothing to log in to; just sanity-check the From address.
    if ($transport === 'mail') {
        if (has_changeme(MAIL_FROM_EMAIL)) {
            $r['verdict'] = 'STOP: using PHP mail() but MAIL_FROM_EMAIL is still CHANGE_ME — set it to an address on YOUR domain (e.g. kindness@yourdomain.com).';
        } else {
            $r['verdict'] = 'Using PHP mail() (no SMTP login needed). Submit a test act and check inboxes/spam. If nothing arrives, the host may need SMTP instead.';
        }
        return $r;
    }

    // SMTP path
    $r['using'] = ['SMTP_HOST' => SMTP_HOST, 'SMTP_PORT' => SMTP_PORT, 'SMTP_SECURE' => SMTP_SECURE, 'SMTP_USER' => SMTP_USER];
    if (has_changeme(SMTP_USER) || has_changeme(SMTP_PASS) || has_changeme(MAIL_FROM_EMAIL)) {
        $r['verdict'] = 'STOP: config.php still has CHANGE_ME email values — set SMTP_USER, SMTP_PASS, MAIL_FROM_EMAIL to your real mailbox.';
        return $r;
    }

    // Probe the SMTP login without sending anything.
    $secure = SMTP_SECURE === 'ssl';
    $remote = ($secure ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
    $errno = 0; $errstr = '';
    $conn = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$conn) {
        $r['connection'] = 'FAILED';
        $r['connection_error'] = "$errstr ($errno)";
        $r['verdict'] = 'Could not reach the SMTP server — check SMTP_HOST / SMTP_PORT / SMTP_SECURE.';
        return $r;
    }
    stream_set_timeout($conn, 15);
    $read = function () use ($conn) {
        $d = '';
        while (($line = fgets($conn, 515)) !== false) {
            $d .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $d;
    };
    $cmd = function ($c) use ($conn, $read) { fwrite($conn, $c . "\r\n"); return $read(); };

    $read(); // greeting
    if (!$secure && SMTP_SECURE === 'tls') {
        $cmd('EHLO localhost');
        $cmd('STARTTLS');
        @stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    }
    $cmd('EHLO localhost');
    $cmd('AUTH LOGIN');
    $cmd(base64_encode(SMTP_USER));
    $auth = $cmd(base64_encode(SMTP_PASS));
    @fwrite($conn, "QUIT\r\n");
    @fclose($conn);

    $r['connection'] = 'OK';
    $r['auth_ok'] = str_starts_with(trim($auth), '235');
    $r['verdict'] = $r['auth_ok']
        ? 'SMTP login OK — email should send.'
        : 'SMTP login FAILED — use the FULL mailbox address as SMTP_USER and its password. (' . trim($auth) . ')';
    return $r;
}

function has_changeme(string $v): bool { return str_contains($v, 'CHANGE_ME'); }
