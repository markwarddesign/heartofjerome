<?php
/**
 * ──────────────────────────────────────────────────────────────────────────
 *  SECRETS template
 * ──────────────────────────────────────────────────────────────────────────
 *  Copy this file to  `config.secret.php`  (same folder) and fill in your real
 *  values. `config.secret.php` is gitignored, so it never reaches the repo and
 *  a GitHub/Hostinger deploy can never wipe it.
 *
 *  `config.php` reads whatever you define here and fills in safe defaults for
 *  anything you leave out — so you only need to list the values you want to set.
 *
 *  (For LOCAL development, use `config.local.php` instead — same idea.)
 * ──────────────────────────────────────────────────────────────────────────
 */

// ---- Database (hPanel → Databases → MySQL Databases) ----
define('DB_NAME', 'CHANGE_ME_database_name');   // e.g. u1234567_jerome
define('DB_USER', 'CHANGE_ME_database_user');
define('DB_PASS', 'CHANGE_ME_database_pass');

// ---- Email ----
define('MAIL_TRANSPORT', 'mail');                       // 'mail' (no login) | 'smtp' | 'auto'
define('MAIL_FROM_EMAIL', 'noreply@CHANGE_ME.com');     // MUST be an address on YOUR domain

// Admins who get the "new act of kindness" notification for every submission.
// Add or change names/emails here.
define('TEAM_RECIPIENTS', [
    ['email' => 'davidmbernice@gmail.com', 'name' => 'Dave Davis'],
    ['email' => 'pastor@jeromebbc.com',    'name' => 'Tim Knutson'],
    // ['email' => 'you@yourdomain.com',   'name' => 'You'],
]);

// Email the submitter a thank-you confirmation too?
define('SEND_CONFIRMATION', true);

// For SMTP only (skip if using 'mail'):
// define('SMTP_USER', 'kindness@CHANGE_ME.com');
// define('SMTP_PASS', 'CHANGE_ME_mailbox_pass');

// ---- Site ----
define('SITE_URL', 'https://CHANGE_ME.com');     // no trailing slash
define('DEBUG', false);                          // keep false in production
define('ADMIN_PASSWORD', 'CHANGE_ME_admin_password');

// Origins allowed to POST to the API — include your live domain.
define('ALLOWED_ORIGINS', [
    'https://CHANGE_ME.com',
    'https://www.CHANGE_ME.com',
]);
