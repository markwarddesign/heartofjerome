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
    <a class="btn" href="admin.php?export=csv">Export CSV</a>
    <a class="btn btn--ghost" href="admin.php?logout=1">Log out</a>
  </div>
</div>

<?php if ($dbError): ?>
  <p class="err">Database error: <?= adm_e($dbError) ?></p>
<?php elseif (!$rows): ?>
  <p class="empty">No submissions yet.</p>
<?php else: ?>
  <div class="tablewrap">
    <table>
      <thead>
        <tr><th>#</th><th>Date</th><th>Acts</th><th>Name</th><th>Email</th><th>Description</th><th>IdahoK.</th><th>Media</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="muted"><?= (int) $r['id'] ?></td>
            <td class="nowrap"><?= adm_e(date('M j, Y g:i a', strtotime($r['created_at']))) ?></td>
            <td class="num"><?= (int) $r['num_acts'] ?></td>
            <td><?= $r['name'] ? adm_e($r['name']) : '<span class="muted">—</span>' ?></td>
            <td><a href="mailto:<?= adm_e($r['email']) ?>"><?= adm_e($r['email']) ?></a></td>
            <td class="desc"><?= $r['description'] ? nl2br(adm_e($r['description'])) : '<span class="muted">—</span>' ?></td>
            <td class="center"><?= $r['logged_idaho'] ? '✓' : '<span class="muted">—</span>' ?></td>
            <td><?= $r['photo_path'] ? '<a href="/' . adm_e($r['photo_path']) . '" target="_blank">view</a>' : '<span class="muted">—</span>' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <nav class="pager">
      <?php if ($page > 1): ?><a href="admin.php?page=<?= $page - 1 ?>">← Newer</a><?php endif; ?>
      <span>Page <?= $page ?> of <?= $totalPages ?></span>
      <?php if ($page < $totalPages): ?><a href="admin.php?page=<?= $page + 1 ?>">Older →</a><?php endif; ?>
    </nav>
  <?php endif; ?>
<?php endif; ?>

<?php
admin_footer();


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
