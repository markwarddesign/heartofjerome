<?php
/**
 * Simple password-protected admin view for The Heart of Jerome.
 * Visit:  https://yourdomain.com/api/admin.php
 * Set ADMIN_PASSWORD in config.php first.
 */
require_once __DIR__ . '/db.php';
session_start();

function adm_e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* ---- Logout ---- */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

$authed = !empty($_SESSION['hoj_admin']);
$loginError = '';

/* ---- Login ---- */
if (!$authed && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (str_contains(ADMIN_PASSWORD, 'CHANGE_ME')) {
        $loginError = 'Admin password not set yet — edit ADMIN_PASSWORD in config.php.';
    } elseif (hash_equals(ADMIN_PASSWORD, (string) ($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['hoj_admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        usleep(500000); // slow down brute force
        $loginError = 'Incorrect password.';
    }
}

/* ---- Gate ---- */
if (!$authed) {
    render_login($loginError);
    exit;
}

$pdo = db();

/* ---- Send test emails (to verify notifications + confirmation work) ---- */
$testResult = null;
if (($_POST['action'] ?? '') === 'testmail') {
    require_once __DIR__ . '/mailer.php';
    require_once __DIR__ . '/email_template.php';
    $to = trim($_POST['test_email'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $testResult = ['ok' => false, 'msg' => 'Enter a valid email address to send the test to.'];
    } else {
        $note = '<p style="margin:0 0 12px;background:#fff3cd;padding:8px 12px;border-radius:6px;color:#664d03">'
              . 'This is a <strong>TEST</strong> from the admin panel — not a real submission.</p>';
        $okAdmin = send_mail(
            [['email' => $to, 'name' => 'Admin']],
            'TEST — New act of kindness (admin notification)',
            email_layout('New act of kindness logged', $note . '<p>This is the email your admins receive whenever someone logs an act. On a real submission it goes to: <strong>' . adm_e(implode(', ', array_map(fn($r) => $r['email'], TEAM_RECIPIENTS))) . '</strong>.</p>'),
            "TEST admin notification from " . SITE_NAME
        );
        $okConfirm = send_mail(
            [['email' => $to, 'name' => 'Submitter']],
            'TEST — Thank you for your kindness',
            email_layout('Thank you for your kindness!', $note . '<p>This is the thank-you confirmation a submitter receives after logging an act.</p>'),
            "TEST confirmation from " . SITE_NAME
        );
        $testResult = [
            'ok'  => $okAdmin && $okConfirm,
            'to'  => $to,
            'admin'   => $okAdmin,
            'confirm' => $okConfirm,
            'transport' => defined('MAIL_TRANSPORT') ? MAIL_TRANSPORT : 'auto',
        ];
    }
}

/* ---- CSV export (all rows) ---- */
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kindness-acts-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'date', 'acts', 'name', 'email', 'description', 'logged_idahokindness', 'photo', 'ip'], ',', '"', '');
    if ($pdo) {
        foreach ($pdo->query('SELECT * FROM kindness_acts ORDER BY id DESC') as $r) {
            fputcsv($out, [
                $r['id'], $r['created_at'], $r['num_acts'], $r['name'], $r['email'],
                $r['description'], $r['logged_idaho'] ? 'yes' : 'no', $r['photo_path'], $r['ip_address'],
            ], ',', '"', '');
        }
    }
    fclose($out);
    exit;
}

/* ---- Data ---- */
$perPage = 50;
$page    = max(1, (int) ($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$view    = $_GET['view'] ?? 'table';
if (!in_array($view, ['table', 'gallery'], true)) {
    $view = 'table';
}

$totalRows   = 0;
$loggedActs  = 0;
$rows        = [];
$dbError     = null;

if ($pdo) {
    try {
        $totalRows  = (int) $pdo->query('SELECT COUNT(*) FROM kindness_acts')->fetchColumn();
        $loggedActs = (int) $pdo->query('SELECT COALESCE(SUM(num_acts),0) FROM kindness_acts')->fetchColumn();
        $stmt = $pdo->prepare('SELECT * FROM kindness_acts ORDER BY id DESC LIMIT :lim OFFSET :off');
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        $dbError = $e->getMessage();
    }
} else {
    $dbError = 'Could not connect to the database.';
}

$publicTotal = STARTING_COUNT + $loggedActs;
$totalPages  = max(1, (int) ceil($totalRows / $perPage));

// Map of this page's entries for the detail/lightbox modal (populated client-side).
$entriesById = [];
foreach ($rows as $r) {
    $url = $r['photo_path'] ? '/' . ltrim($r['photo_path'], '/') : null;
    $ext = $url ? strtolower(pathinfo($url, PATHINFO_EXTENSION)) : '';
    $entriesById[(int) $r['id']] = [
        'acts'    => (int) $r['num_acts'],
        'date'    => date('M j, Y g:i a', strtotime($r['created_at'])),
        'name'    => $r['name'],
        'email'   => $r['email'],
        'desc'    => $r['description'],
        'logged'  => (bool) $r['logged_idaho'],
        'media'   => $url,
        'isVideo' => in_array($ext, ['mp4', 'mov', 'webm'], true),
    ];
}

admin_header();
?>
<div class="bar">
  <div class="stats">
    <div class="stat"><span><?= number_format($publicTotal) ?></span>Public total (incl. baseline)</div>
    <div class="stat"><span><?= number_format($totalRows) ?></span>Submissions</div>
    <div class="stat"><span><?= number_format($loggedActs) ?></span>Acts logged here</div>
    <div class="stat"><span><?= number_format(GOAL) ?></span>Goal</div>
  </div>
  <div class="actions">
    <div class="viewtoggle" role="tablist" aria-label="View">
      <a class="<?= $view === 'table' ? 'on' : '' ?>" href="admin.php?view=table">▤ Table</a>
      <a class="<?= $view === 'gallery' ? 'on' : '' ?>" href="admin.php?view=gallery">▦ Gallery</a>
    </div>
    <a class="btn" href="admin.php?export=csv">Export CSV</a>
    <a class="btn btn--ghost" href="admin.php?logout=1">Log out</a>
  </div>
</div>

<!-- Email test -->
<details class="mailtest" <?= $testResult ? 'open' : '' ?>>
  <summary>✉️ Test the email notifications</summary>
  <p class="mailtest__hint">
    Sends a copy of both the <strong>admin notification</strong> and the <strong>submitter thank-you</strong>
    to one address so you can confirm they arrive (check spam too).
  </p>
  <form method="post" class="mailtest__form">
    <input type="hidden" name="action" value="testmail">
    <input type="email" name="test_email" placeholder="you@example.com" required>
    <button type="submit" class="btn">Send test emails</button>
  </form>
  <?php if ($testResult): ?>
    <?php if (!empty($testResult['msg'])): ?>
      <p class="mailtest__result err"><?= adm_e($testResult['msg']) ?></p>
    <?php else: ?>
      <ul class="mailtest__result <?= $testResult['ok'] ? 'good' : 'bad' ?>">
        <li>Admin notification → <?= adm_e($testResult['to']) ?>: <strong><?= $testResult['admin'] ? 'sent ✓' : 'failed ✗' ?></strong></li>
        <li>Thank-you confirmation → <?= adm_e($testResult['to']) ?>: <strong><?= $testResult['confirm'] ? 'sent ✓' : 'failed ✗' ?></strong></li>
        <li class="muted">Transport: <?= adm_e($testResult['transport']) ?> — if "failed", check MAIL_FROM_EMAIL / MAIL_TRANSPORT in config. If "sent" but nothing arrives, check the spam folder.</li>
      </ul>
    <?php endif; ?>
  <?php endif; ?>
</details>

<?php if ($dbError): ?>
  <p class="err">Database error: <?= adm_e($dbError) ?></p>
<?php elseif (!$rows): ?>
  <p class="empty">No submissions yet.</p>
<?php elseif ($view === 'gallery'): ?>
  <div class="gallery">
    <?php foreach ($rows as $r): ?>
      <article class="gcard" data-entry="<?= (int) $r['id'] ?>" tabindex="0" role="button" aria-label="View entry">
        <div class="gmedia"><?= media_html($r['photo_path'], 'gallery') ?></div>
        <div class="gbody">
          <div class="ghead">
            <span class="gacts"><?= (int) $r['num_acts'] ?> act<?= (int) $r['num_acts'] === 1 ? '' : 's' ?></span>
            <span class="gdate"><?= adm_e(date('M j, Y', strtotime($r['created_at']))) ?></span>
          </div>
          <div class="gname"><?= $r['name'] ? adm_e($r['name']) : '<span class="muted">Anonymous</span>' ?></div>
          <a class="gemail" href="mailto:<?= adm_e($r['email']) ?>"><?= adm_e($r['email']) ?></a>
          <?php if ($r['description']): ?><p class="gdesc"><?= nl2br(adm_e($r['description'])) ?></p><?php endif; ?>
          <?php if ($r['logged_idaho']): ?><span class="gtag">✓ IdahoKindness</span><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="tablewrap">
    <table>
      <thead>
        <tr><th>#</th><th>Date</th><th>Acts</th><th>Name</th><th>Email</th><th>Description</th><th>IdahoK.</th><th>Media</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr class="rowlink" data-entry="<?= (int) $r['id'] ?>">
            <td class="muted"><?= (int) $r['id'] ?></td>
            <td class="nowrap"><?= adm_e(date('M j, Y g:i a', strtotime($r['created_at']))) ?></td>
            <td class="num"><?= (int) $r['num_acts'] ?></td>
            <td><?= $r['name'] ? adm_e($r['name']) : '<span class="muted">—</span>' ?></td>
            <td><a href="mailto:<?= adm_e($r['email']) ?>"><?= adm_e($r['email']) ?></a></td>
            <td class="desc"><?php if ($r['description']): ?><div class="clamp2"><?= adm_e($r['description']) ?></div><?php else: ?><span class="muted">—</span><?php endif; ?></td>
            <td class="center"><?= $r['logged_idaho'] ? '✓' : '<span class="muted">—</span>' ?></td>
            <td><?= media_html($r['photo_path'], 'table') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php if (!$dbError && $rows && $totalPages > 1): ?>
  <nav class="pager">
    <?php if ($page > 1): ?><a href="admin.php?view=<?= $view ?>&page=<?= $page - 1 ?>">← Newer</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a href="admin.php?view=<?= $view ?>&page=<?= $page + 1 ?>">Older →</a><?php endif; ?>
  </nav>
<?php endif; ?>

<!-- Detail / lightbox modal -->
<div class="modal" id="entryModal" hidden>
  <div class="modal__backdrop" data-close></div>
  <div class="modal__box" role="dialog" aria-modal="true" aria-label="Submission detail">
    <button class="modal__close" data-close aria-label="Close">✕</button>
    <div class="modal__media" id="mMedia" hidden></div>
    <div class="modal__body">
      <div class="modal__head">
        <span class="gacts" id="mActs"></span>
        <span class="gdate" id="mDate"></span>
      </div>
      <div class="gname" id="mName"></div>
      <a class="gemail" id="mEmail"></a>
      <div id="mIdaho"></div>
      <p class="modal__desc" id="mDesc"></p>
    </div>
  </div>
</div>

<script>
  const ENTRIES = <?= json_encode($entriesById, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
  const modal = document.getElementById('entryModal');
  const mMedia = document.getElementById('mMedia');

  function openEntry(id) {
    const e = ENTRIES[id];
    if (!e) return;
    if (e.media) {
      mMedia.innerHTML = e.isVideo
        ? '<video src="' + e.media + '" controls autoplay playsinline></video>'
        : '<img src="' + e.media + '" alt="">';
      mMedia.hidden = false;
    } else {
      mMedia.innerHTML = '';
      mMedia.hidden = true;
    }
    document.getElementById('mActs').textContent = e.acts + ' act' + (e.acts === 1 ? '' : 's');
    document.getElementById('mDate').textContent = e.date;
    document.getElementById('mName').textContent = e.name || 'Anonymous';
    const em = document.getElementById('mEmail');
    em.textContent = e.email; em.href = 'mailto:' + e.email;
    document.getElementById('mIdaho').innerHTML = e.logged ? '<span class="gtag">✓ Also logged at IdahoKindness</span>' : '';
    document.getElementById('mDesc').textContent = e.desc || '';
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }
  function closeEntry() {
    modal.hidden = true;
    mMedia.innerHTML = ''; // stop any playing video
    document.body.style.overflow = '';
  }

  document.addEventListener('click', (ev) => {
    if (ev.target.closest('[data-close]')) { closeEntry(); return; }
    if (ev.target.closest('#entryModal')) return;          // clicks inside the open modal behave normally
    if (ev.target.closest('a')) return;                    // let email links work
    const row = ev.target.closest('[data-entry]');
    if (row) openEntry(row.getAttribute('data-entry'));
  });
  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape' && !modal.hidden) closeEntry();
  });
  // Keyboard activation for gallery cards (role=button)
  document.addEventListener('keydown', (ev) => {
    if ((ev.key === 'Enter' || ev.key === ' ') && ev.target.matches('[data-entry][role=button]')) {
      ev.preventDefault();
      openEntry(ev.target.getAttribute('data-entry'));
    }
  });
</script>

<?php
admin_footer();


/* ============================ media ============================ */
function media_html(?string $path, string $context): string
{
    if (!$path) {
        return $context === 'gallery' ? '<div class="noimg">No photo</div>' : '<span class="muted">—</span>';
    }
    $url = '/' . ltrim($path, '/');
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $isVideo = in_array($ext, ['mp4', 'mov', 'webm'], true);

    // No links here — clicking the row/card opens the detail/lightbox modal.
    if ($isVideo) {
        return $context === 'gallery'
            ? '<video class="gvid" src="' . adm_e($url) . '" muted preload="metadata"></video><span class="playbadge">▶</span>'
            : '<span class="vidbadge" title="Video">▶</span>';
    }
    $cls = $context === 'gallery' ? 'gimg' : 'thumb';
    return '<img class="' . $cls . '" src="' . adm_e($url) . '" loading="lazy" alt="">';
}

/* ============================ views ============================ */
function render_login(string $error): void
{
    admin_header(true);
    ?>
    <form class="login" method="post">
      <h1>Admin</h1>
      <p class="sub">The Heart of Jerome</p>
      <?php if ($error): ?><p class="err"><?= adm_e($error) ?></p><?php endif; ?>
      <label for="pw">Password</label>
      <input id="pw" type="password" name="password" autofocus autocomplete="current-password">
      <button type="submit" class="btn btn--block">Sign in</button>
    </form>
    <?php
    admin_footer();
}

function admin_header(bool $minimal = false): void
{
    ?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Admin — The Heart of Jerome</title>
<style>
  :root{--red:#b20112;--ink:#1c1c18;--soft:#5c403d;--paper:#fdf9f3;--paper2:#f1ede7;--line:#e5bdb9;--white:#fff}
  *{box-sizing:border-box}
  body{margin:0;font-family:'Public Sans',system-ui,-apple-system,Segoe UI,sans-serif;background:var(--paper);color:var(--ink);line-height:1.5}
  a{color:var(--red)}
  .wrap{max-width:1100px;margin:0 auto;padding:1.5rem}
  .topbar{background:var(--white);border-bottom:1px solid var(--line)}
  .topbar .wrap{display:flex;align-items:center;justify-content:space-between;padding-block:1rem}
  .topbar h1{font-family:Georgia,serif;font-style:italic;color:var(--red);font-size:1.4rem;margin:0}
  .bar{display:flex;flex-wrap:wrap;gap:1rem;justify-content:space-between;align-items:center;margin-bottom:1.25rem}
  .stats{display:flex;flex-wrap:wrap;gap:1.5rem}
  .stat{font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:var(--soft)}
  .stat span{display:block;font-size:1.7rem;font-weight:800;color:var(--red);font-family:Georgia,serif;letter-spacing:0}
  .actions{display:flex;gap:.5rem}
  .btn{display:inline-flex;align-items:center;justify-content:center;background:var(--red);color:#fff;text-decoration:none;font-weight:700;padding:.6rem 1.1rem;border-radius:.5rem;border:none;cursor:pointer;font-size:1rem}
  .btn--ghost{background:var(--paper2);color:var(--soft)}
  .btn--block{width:100%;padding:.85rem;font-size:1.1rem}
  .tablewrap{overflow-x:auto;border:1px solid var(--line);border-radius:.75rem;background:var(--white)}
  table{width:100%;border-collapse:collapse;font-size:.9rem}
  th,td{text-align:left;padding:.7rem .8rem;border-bottom:1px solid #f0e6e4;vertical-align:top}
  th{background:var(--paper2);font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--soft);position:sticky;top:0}
  tr:last-child td{border-bottom:none}
  .num,.center{text-align:center}
  .nowrap{white-space:nowrap}
  .muted{color:#a99}
  .desc{max-width:30rem}
  .clamp2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  /* clickable rows */
  .rowlink{cursor:pointer}
  .rowlink:hover td{background:#fbf4f3}
  /* view toggle */
  .actions{align-items:center}
  .viewtoggle{display:inline-flex;border:1px solid var(--line);border-radius:.5rem;overflow:hidden}
  .viewtoggle a{padding:.55rem .85rem;text-decoration:none;color:var(--soft);font-weight:600;font-size:.9rem;background:var(--white)}
  .viewtoggle a+a{border-left:1px solid var(--line)}
  .viewtoggle a.on{background:var(--red);color:#fff}
  /* table thumbnails */
  .thumb{width:52px;height:52px;object-fit:cover;border-radius:.35rem;border:1px solid var(--line);display:block}
  .vidbadge{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:.35rem;background:#1c1c18;color:#fff;font-size:1rem}
  /* gallery */
  .gallery{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem}
  .gcard{background:var(--white);border:1px solid var(--line);border-radius:.75rem;overflow:hidden;display:flex;flex-direction:column;cursor:pointer;transition:box-shadow .15s,transform .15s}
  .gcard:hover{box-shadow:0 8px 22px rgba(92,64,61,.16);transform:translateY(-2px)}
  .gcard:focus-visible{outline:2px solid var(--red);outline-offset:2px}
  .gmedia{position:relative;aspect-ratio:4/3;background:var(--paper2);display:flex;align-items:center;justify-content:center;overflow:hidden}
  .gimg,.gvid{width:100%;height:100%;object-fit:cover;display:block}
  .playbadge{position:absolute;width:3rem;height:3rem;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;pointer-events:none}
  .noimg{color:#bbaaa8;font-size:.85rem}
  .gbody{padding:.9rem 1rem;display:flex;flex-direction:column;gap:.25rem}
  .ghead{display:flex;justify-content:space-between;align-items:baseline}
  .gacts{font-weight:800;color:var(--red);font-family:Georgia,serif;font-size:1.1rem}
  .gdate{font-size:.78rem;color:var(--soft)}
  .gname{font-weight:700}
  .gemail{font-size:.85rem;text-decoration:none}
  .gdesc{margin:.4rem 0 0;font-size:.9rem;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden}
  .gtag{margin-top:.5rem;align-self:flex-start;font-size:.72rem;background:#cae6ff;color:#244a64;padding:.2rem .55rem;border-radius:1rem;font-weight:700}
  /* detail / lightbox modal */
  .modal[hidden]{display:none}
  .modal{position:fixed;inset:0;z-index:1000;display:flex;align-items:center;justify-content:center;padding:1.25rem}
  .modal__backdrop{position:absolute;inset:0;background:rgba(28,28,24,.62)}
  .modal__box{position:relative;background:var(--white);border-radius:1rem;width:100%;max-width:640px;max-height:92vh;overflow:auto;box-shadow:0 30px 70px rgba(0,0,0,.35)}
  .modal__close{position:absolute;top:.6rem;right:.6rem;z-index:2;width:2.3rem;height:2.3rem;border:none;border-radius:50%;background:rgba(255,255,255,.92);color:#1c1c18;font-size:1.05rem;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2)}
  .modal__media{background:#0d0d0c;display:flex;align-items:center;justify-content:center;max-height:62vh;overflow:hidden}
  .modal__media[hidden]{display:none}
  .modal__media img,.modal__media video{max-width:100%;max-height:62vh;object-fit:contain;display:block}
  .modal__body{padding:1.25rem 1.5rem 1.5rem}
  .modal__head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.15rem}
  .modal__desc{margin:.85rem 0 0;white-space:pre-wrap;line-height:1.6;color:var(--ink);max-height:38vh;overflow:auto}
  /* email test */
  .mailtest{background:var(--white);border:1px solid var(--line);border-radius:.75rem;padding:.4rem 1rem;margin-bottom:1.25rem}
  .mailtest summary{cursor:pointer;font-weight:700;padding:.5rem 0}
  .mailtest__hint{color:var(--soft);font-size:.9rem;margin:.25rem 0 .75rem}
  .mailtest__form{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:.5rem}
  .mailtest__form input{flex:1;min-width:200px;padding:.6rem .8rem;border:1px solid var(--line);border-radius:.5rem;font-size:1rem}
  .mailtest__result{list-style:none;padding:.75rem 1rem;margin:.5rem 0 .75rem;border-radius:.5rem;background:var(--paper2);font-size:.92rem}
  .mailtest__result li{margin:.2rem 0}
  .mailtest__result.good{background:#e6f4ea}
  .mailtest__result.bad{background:#ffdad6}
  .pager{display:flex;gap:1.5rem;align-items:center;justify-content:center;margin-top:1.25rem;color:var(--soft)}
  .pager a{font-weight:700;text-decoration:none}
  .empty{text-align:center;color:var(--soft);padding:3rem;background:var(--white);border:1px solid var(--line);border-radius:.75rem}
  .err{background:#ffdad6;color:#93000a;border:1px solid #f0b3ad;padding:.7rem 1rem;border-radius:.5rem}
  .login{max-width:22rem;margin:4rem auto;background:var(--white);border:1px solid var(--line);border-radius:1rem;padding:2rem}
  .login h1{font-family:Georgia,serif;font-style:italic;color:var(--red);margin:0}
  .login .sub{color:var(--soft);margin:.25rem 0 1.5rem}
  .login label{display:block;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--soft);font-weight:700;margin-bottom:.35rem}
  .login input{width:100%;padding:.7rem .9rem;font-size:1rem;border:1px solid var(--line);border-radius:.5rem;margin-bottom:1rem}
  .login input:focus{outline:none;border-color:var(--red);box-shadow:0 0 0 3px rgba(178,1,18,.18)}
</style></head><body>
<?php if (!$minimal): ?>
<div class="topbar"><div class="wrap"><h1>The Heart of Jerome</h1><span style="color:#5c403d;font-size:.85rem">Kindness Log — Admin</span></div></div>
<?php endif; ?>
<div class="wrap">
<?php
}

function admin_footer(): void
{
    echo "</div></body></html>";
}
