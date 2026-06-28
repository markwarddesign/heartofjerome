<?php
/**
 * ──────────────────────────────────────────────────────────────────────────
 *  The Heart of Jerome — configuration TEMPLATE
 * ──────────────────────────────────────────────────────────────────────────
 *  1. Copy this file to  `config.php`  (same folder).
 *  2. Replace every value marked  CHANGE_ME  with your real settings.
 *  3. Upload `config.php` to the server.  It is gitignored, so your secrets
 *     never reach the repository.
 *
 *  For LOCAL development you can instead drop a `config.local.php` next to this
 *  file (also gitignored) — anything it defines overrides the values below, so
 *  you never have to edit the production `config.php` to run on your machine.
 * ──────────────────────────────────────────────────────────────────────────
 */

// Local overrides load first and win (constants can only be defined once).
$__local = __DIR__ . '/config.local.php';
if (is_file($__local)) {
    require $__local;
}

// ── 1. DATABASE ────────────────────────────────────────────────────────────
//    hPanel → Databases → MySQL Databases (note the name / user / password).
defined('DB_HOST') || define('DB_HOST', 'localhost');                 // Hostinger = 'localhost'
defined('DB_NAME') || define('DB_NAME', 'CHANGE_ME_database_name');   // e.g. u1234567_jerome
defined('DB_USER') || define('DB_USER', 'CHANGE_ME_database_user');
defined('DB_PASS') || define('DB_PASS', 'CHANGE_ME_database_pass');
defined('DB_PORT') || define('DB_PORT', 3306);

// ── 2. EMAIL ───────────────────────────────────────────────────────────────
//    MAIL_TRANSPORT:
//      'mail' — PHP's built-in mail() (simplest; needs NO mailbox/login —
//               just set MAIL_FROM_EMAIL to an address on YOUR domain).
//      'smtp' — authenticated SMTP (most reliable for Gmail); needs SMTP_USER/PASS.
//      'auto' — use SMTP if its credentials are filled in, otherwise mail().
defined('MAIL_TRANSPORT') || define('MAIL_TRANSPORT', 'mail');

//    The "From" address. MUST be on your own domain or it gets dropped/spam-filed.
defined('MAIL_FROM_EMAIL') || define('MAIL_FROM_EMAIL', 'noreply@CHANGE_ME.com');
defined('MAIL_FROM_NAME')  || define('MAIL_FROM_NAME', 'The Heart of Jerome');

//    Only needed if MAIL_TRANSPORT is 'smtp' (or 'auto' with a real mailbox).
defined('SMTP_HOST')   || define('SMTP_HOST', 'smtp.hostinger.com');
defined('SMTP_PORT')   || define('SMTP_PORT', 465);                   // 465 = SSL, 587 = TLS
defined('SMTP_SECURE') || define('SMTP_SECURE', 'ssl');              // 'ssl' for 465, 'tls' for 587
defined('SMTP_USER')   || define('SMTP_USER', 'kindness@CHANGE_ME.com');
defined('SMTP_PASS')   || define('SMTP_PASS', 'CHANGE_ME_mailbox_pass');

//    Who gets notified of each new submission.
defined('TEAM_RECIPIENTS') || define('TEAM_RECIPIENTS', [
    ['email' => 'davidmbernice@gmail.com', 'name' => 'Dave Davis'],
    ['email' => 'pastor@jeromebbc.com',    'name' => 'Tim Knutson'],
]);

//    Also send the submitter a thank-you confirmation? (true / false)
defined('SEND_CONFIRMATION') || define('SEND_CONFIRMATION', true);

// ── 3. SITE ────────────────────────────────────────────────────────────────
defined('SITE_URL') || define('SITE_URL', 'https://CHANGE_ME.com');   // no trailing slash
defined('DEBUG')    || define('DEBUG', false);                        // keep false in production

//    Password for the admin page at /api/admin.php — make it strong.
defined('ADMIN_PASSWORD') || define('ADMIN_PASSWORD', 'CHANGE_ME_admin_password');

//    Origins allowed to POST to the API (CSRF-lite). Add your live domain.
defined('ALLOWED_ORIGINS') || define('ALLOWED_ORIGINS', [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://heartofjerome.test',
    'https://CHANGE_ME.com',
]);

// ── 4. CONSTANTS (same on every environment — no need to change) ────────────
const SITE_NAME      = 'The Heart of Jerome';
const GOAL           = 2500;   // acts-of-kindness goal for July 4, 2026
const STARTING_COUNT = 1248;   // real-world acts tallied before launch (counter = this + logged acts)

const MAX_UPLOAD_BYTES   = 10 * 1024 * 1024;  // 10 MB
const ALLOWED_UPLOAD_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'webm'];

// ── (no edits needed below) ─────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
date_default_timezone_set('America/Boise'); // Jerome, Idaho (Mountain Time)
