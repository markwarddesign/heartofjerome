<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=utf-8');

/** Send a JSON response and exit. */
function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Method not allowed.']);
}

/* Same-origin guard (CSRF-lite). If an Origin header is present it must be allowed.
 * Skipped in DEBUG (local dev), where Vite's port may vary. */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (!DEBUG && $origin !== '' && !in_array($origin, ALLOWED_ORIGINS, true)) {
    respond(403, ['ok' => false, 'message' => 'Forbidden origin.']);
}

/* Honeypot — bots fill hidden fields. Pretend success. */
if (!empty($_POST['website'])) {
    respond(200, ['ok' => true, 'total' => total_acts()]);
}

/* ---- Gather + validate ---- */
$num         = filter_var(trim($_POST['num_acts'] ?? '1'), FILTER_VALIDATE_INT);
$name        = trim($_POST['name'] ?? '');
$email       = trim($_POST['email'] ?? '');
$description = trim($_POST['description'] ?? '');
$loggedIdaho = !empty($_POST['logged_idaho']) && $_POST['logged_idaho'] !== 'false';

$errors = [];
if ($num === false || $num < 1 || $num > 1000) {
    $errors['num_acts'] = 'Enter a whole number between 1 and 1000.';
    $num = 1;
}
if ($email === '') {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (mb_strlen($name) > 100) {
    $errors['name'] = 'Name is too long.';
}
if (mb_strlen($description) > 2000) {
    $errors['description'] = 'Please keep the description under 2000 characters.';
}

/* ---- Optional upload ---- */
$photo_path = '';
if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['photo'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $errors['photo'] = 'Upload failed. Please try a smaller file.';
    } elseif ($f['size'] > MAX_UPLOAD_BYTES) {
        $errors['photo'] = 'File is larger than 10MB.';
    } else {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_UPLOAD_EXT, true)) {
            $errors['photo'] = 'Unsupported file type.';
        } else {
            $dir = __DIR__ . '/../uploads';
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $safe = bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $dir . '/' . $safe)) {
                $photo_path = 'uploads/' . $safe;
            } else {
                $errors['photo'] = 'Could not save the file.';
            }
        }
    }
}

if ($errors) {
    respond(422, ['ok' => false, 'errors' => $errors, 'message' => 'Please fix the highlighted fields.']);
}

/* ---- Persist ---- */
insert_act([
    'num_acts'     => $num,
    'name'         => $name,
    'email'        => $email,
    'description'  => $description,
    'photo_path'   => $photo_path,
    'logged_idaho' => $loggedIdaho,
    'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
]);
$total = total_acts();

/* ---- Emails (no-op locally where TEAM_RECIPIENTS is empty) ---- */
$displayName = $name !== '' ? $name : 'A neighbor';
$desc        = $description !== '' ? $description : '(no description provided)';
$photoLine   = $photo_path !== '' ? rtrim(SITE_URL, '/') . '/' . $photo_path : '';

$teamText = "A new act of kindness was logged on " . SITE_NAME . ".\n\n"
    . "Acts: {$num}\nName: " . ($name !== '' ? $name : '(not given)') . "\nEmail: {$email}\n"
    . "Also logged at IdahoKindness.com: " . ($loggedIdaho ? 'Yes' : 'No') . "\n"
    . "Description:\n{$desc}\n" . ($photoLine ? "\nPhoto/Video: {$photoLine}\n" : '')
    . "\nNew community total: " . number_format($total) . " of " . number_format(GOAL) . "\n";
$teamHtml = render_email('New act of kindness logged',
    '<p style="margin:0 0 16px"><strong>' . esc($displayName) . '</strong> just logged <strong>' . $num
        . '</strong> act' . ($num > 1 ? 's' : '') . ' of kindness.</p>'
    . email_row('Email', esc($email))
    . email_row('Also logged at IdahoKindness.com', $loggedIdaho ? 'Yes' : 'No')
    . email_row('Description', nl2br(esc($desc)))
    . ($photoLine ? email_row('Photo / Video', '<a href="' . esc($photoLine) . '">' . esc($photoLine) . '</a>') : '')
    . '<p style="margin:20px 0 0;font-size:18px">New community total: <strong>' . number_format($total)
        . '</strong> of ' . number_format(GOAL) . '</p>');
send_mail(TEAM_RECIPIENTS, "New act of kindness logged — {$num} from {$displayName}", $teamHtml, $teamText, $email, $name ?: null);

if (SEND_CONFIRMATION && $email !== '') {
    $confText = "Thank you" . ($name !== '' ? ', ' . $name : '') . "!\n\n"
        . "Your act of kindness has been recorded as part of The Heart of Jerome — Jerome's contribution "
        . "to America250 and Idaho's HCR 22 resolution.\n\n"
        . "ONE MORE STEP: please also log this act at https://www.idahokindness.com/ so Jerome is counted "
        . "in the statewide effort.\n\nTogether we've now recorded " . number_format($total) . " of "
        . number_format(GOAL) . " acts toward our July 4, 2026 goal.\n\nWith gratitude,\nThe Heart of Jerome";
    $confHtml = render_email('Thank you for your kindness!',
        '<p style="margin:0 0 16px">Thank you' . ($name !== '' ? ', ' . esc($name) : '')
            . '! Your act of kindness has been etched into Jerome\'s living history as part of '
            . '<strong>America250</strong> and Idaho\'s HCR&nbsp;22 resolution.</p>'
        . '<div style="background:#cae6ff;border-radius:10px;padding:16px 20px;margin:20px 0">'
        . '<p style="margin:0;color:#244a64"><strong>One more step:</strong> please also log this act at '
        . '<a href="https://www.idahokindness.com/" style="color:#244a64;font-weight:bold">IdahoKindness.com</a> '
        . 'so Jerome is counted in the statewide effort.</p></div>'
        . '<p style="margin:16px 0 0">Together we\'ve now recorded <strong>' . number_format($total)
            . '</strong> of ' . number_format(GOAL) . ' acts toward our July&nbsp;4,&nbsp;2026 goal.</p>');
    send_mail([['email' => $email, 'name' => $name]], 'Thank you for your kindness — The Heart of Jerome', $confHtml, $confText);
}

respond(200, ['ok' => true, 'total' => $total]);


/* ---------- helpers ---------- */
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function email_row(string $label, string $value): string {
    return '<p style="margin:0 0 10px"><span style="display:block;font-size:12px;text-transform:uppercase;'
        . 'letter-spacing:.06em;color:#906f6b">' . $label . '</span>' . $value . '</p>';
}
function render_email(string $heading, string $inner): string {
    return '<!DOCTYPE html><html><body style="margin:0;background:#fdf9f3;font-family:Arial,Helvetica,sans-serif;color:#1c1c18">'
        . '<div style="max-width:560px;margin:0 auto;padding:24px">'
        . '<div style="background:#b20112;color:#fff;border-radius:12px 12px 0 0;padding:20px 24px">'
        . '<div style="font-size:18px;font-weight:bold;font-style:italic">' . esc(SITE_NAME) . '</div></div>'
        . '<div style="background:#fff;border:1px solid #e5bdb9;border-top:none;border-radius:0 0 12px 12px;padding:24px">'
        . '<h1 style="font-size:24px;margin:0 0 16px;color:#1c1c18">' . esc($heading) . '</h1>' . $inner
        . '<p style="margin:24px 0 0;font-size:12px;color:#906f6b">Honoring our heritage, cultivating our future.</p>'
        . '</div></div></body></html>';
}
