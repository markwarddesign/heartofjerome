<?php
/**
 * SAMPLE config — copy this to `config.php` and fill in real values.
 * `config.php` is gitignored so secrets never reach the repo.
 */

$__local = __DIR__ . '/config.local.php';
if (is_file($__local)) {
    require $__local;
}

// ---- Database ----
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'CHANGE_ME_database_name');
defined('DB_USER') || define('DB_USER', 'CHANGE_ME_database_user');
defined('DB_PASS') || define('DB_PASS', 'CHANGE_ME_database_pass');
defined('DB_PORT') || define('DB_PORT', 3306);

// ---- Email ----
defined('SMTP_HOST')   || define('SMTP_HOST', 'smtp.hostinger.com');
defined('SMTP_PORT')   || define('SMTP_PORT', 465);
defined('SMTP_SECURE') || define('SMTP_SECURE', 'ssl');
defined('SMTP_USER')   || define('SMTP_USER', 'kindness@CHANGE_ME.com');
defined('SMTP_PASS')   || define('SMTP_PASS', 'CHANGE_ME_mailbox_pass');
defined('MAIL_FROM_EMAIL') || define('MAIL_FROM_EMAIL', 'noreply@CHANGE_ME.com');
defined('MAIL_FROM_NAME')  || define('MAIL_FROM_NAME', 'The Heart of Jerome');
defined('TEAM_RECIPIENTS') || define('TEAM_RECIPIENTS', [
    ['email' => 'davidmbernice@gmail.com', 'name' => 'Dave Davis'],
    ['email' => 'pastor@jeromebbc.com',    'name' => 'Tim Knutson'],
]);
defined('SEND_CONFIRMATION') || define('SEND_CONFIRMATION', true);
defined('MAIL_TRANSPORT')    || define('MAIL_TRANSPORT', 'auto'); // 'mail' | 'smtp' | 'auto'

// ---- Site ----
defined('SITE_URL') || define('SITE_URL', 'https://CHANGE_ME.com');
defined('DEBUG')    || define('DEBUG', false);
defined('ADMIN_PASSWORD') || define('ADMIN_PASSWORD', 'CHANGE_ME_admin_password');
defined('ALLOWED_ORIGINS') || define('ALLOWED_ORIGINS', [
    'http://localhost:5173', 'http://127.0.0.1:5173', 'http://heartofjerome.test', 'https://CHANGE_ME.com',
]);

const SITE_NAME      = 'The Heart of Jerome';
const GOAL           = 2500;
const STARTING_COUNT = 1248;
const MAX_UPLOAD_BYTES   = 10 * 1024 * 1024;
const ALLOWED_UPLOAD_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov', 'webm'];

error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
date_default_timezone_set('America/Boise');
