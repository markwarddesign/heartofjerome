<?php
/**
 * TEMPORARY mail diagnostic.  Upload to api/, then visit:
 *   https://yourdomain.com/api/mailtest.php?token=jerome&to=you@gmail.com
 * It reports the resolved config and the EXACT result/error of a real mail() call.
 * ⚠️  DELETE this file from the server once email works.
 */
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if (($_GET['token'] ?? '') !== 'jerome') {
    http_response_code(403);
    echo json_encode(['error' => 'Add ?token=jerome to the URL.']);
    exit;
}

$report = [
    'config_source'          => defined('CONFIG_SOURCE') ? CONFIG_SOURCE : '(old config.php — redeploy)',
    'mail_from'              => MAIL_FROM_EMAIL,
    'mail_from_is_valid'     => (bool) filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL),
    'mail_from_placeholder'  => str_contains(MAIL_FROM_EMAIL, 'CHANGE_ME'),
    'mail_transport'         => defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'auto',
    'site_url'               => SITE_URL,
    'team_recipients'        => array_map(fn($r) => $r['email'], TEAM_RECIPIENTS),
    'send_confirmation'      => SEND_CONFIRMATION,
    'files_on_server' => [
        'mailer.php'         => is_file(__DIR__ . '/mailer.php'),
        'email_template.php' => is_file(__DIR__ . '/email_template.php'),
        'submit.php'         => is_file(__DIR__ . '/submit.php'),
    ],
    'php_mail_function_exists' => function_exists('mail'),
    'disable_functions'        => ini_get('disable_functions'),
    'sendmail_path'            => ini_get('sendmail_path'),
];

if (!empty($_GET['to'])) {
    $to = $_GET['to'];
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $report['send'] = ['error' => 'invalid ?to= address'];
    } elseif (!function_exists('mail')) {
        $report['send'] = ['error' => 'mail() is DISABLED on this server (see disable_functions) — you must use SMTP.'];
    } else {
        // 1) A bare, minimal mail() call — isolates whether mail() itself works.
        $headers = 'From: ' . MAIL_FROM_EMAIL . "\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8";
        error_clear_last();
        $okWithF = @mail($to, 'Heart of Jerome — mail() test (with -f)', '<p>Bare mail() test WITH envelope sender. If you got this, mail() delivers.</p>', $headers, '-f' . MAIL_FROM_EMAIL);
        $errWithF = error_get_last();

        error_clear_last();
        $okNoF = @mail($to, 'Heart of Jerome — mail() test (no -f)', '<p>Bare mail() test WITHOUT envelope sender.</p>', $headers);
        $errNoF = error_get_last();

        // 2) The REAL path — exactly what submit.php and the admin test use
        //    (send_mail → mail_via_php, with the full branded HTML + logo).
        require_once __DIR__ . '/mailer.php';
        require_once __DIR__ . '/email_template.php';
        $brandedHtml = email_layout('Thank you for your kindness!',
            '<p>This was sent through the <strong>real</strong> path (send_mail → mail_via_php) — the exact code a submission uses, with the full branded HTML + logo.</p>');
        error_clear_last();
        $realOk  = send_mail([['email' => $to, 'name' => 'Test']], 'Real-path test from The Heart of Jerome', $brandedHtml, 'Real-path test.');
        $realErr = error_get_last();

        $report['send'] = [
            'to' => $to,
            'bare_mail' => [
                'returned_with_f' => $okWithF,
                'error_with_f'    => $errWithF['message'] ?? null,
                'returned_no_f'   => $okNoF,
                'error_no_f'      => $errNoF['message'] ?? null,
            ],
            'real_branded_path' => [
                'returned' => $realOk,
                'error'    => $realErr['message'] ?? null,
            ],
            'how_to_read' => 'Check inbox AND spam for ALL test emails. If the BARE ones arrive but the "Real-path"/branded one does NOT, the rich HTML+logo is being rejected by Gmail for unauthenticated mail() — switch to SMTP. If none arrive, mail() delivery is broken entirely → SMTP.',
        ];
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
