<div align="center">

# ❤️ The Heart of Jerome

### Our Home for Kindness

A community kindness-counter for **Jerome, Idaho** — part of **America250** and Idaho's
**HCR 22** resolution. The goal: foster **2,500 acts of kindness** in the Magic Valley by
**July 4, 2026**.

Residents log their acts of kindness, every submission emails the team leads and a
thank-you to the submitter, and a live counter ticks toward the goal.

![Stack](https://img.shields.io/badge/frontend-React%20%2B%20Vite-0b7285)
![Stack](https://img.shields.io/badge/backend-PHP%208-7b5bb6)
![DB](https://img.shields.io/badge/database-MySQL-005f87)
![Host](https://img.shields.io/badge/hosting-Hostinger-673de6)

</div>

---

## ✨ Features

- **Live kindness counter** — reads the real running total from the database.
- **Log Your Act form** — name (optional), email, description, optional photo/video upload, and an
  "also logged at IdahoKindness.com" checkbox.
- **Transactional email** — every submission notifies the team **and** sends the submitter a
  thank-you (with a reminder to log at IdahoKindness.com). Works via plain PHP `mail()` *or*
  authenticated SMTP.
- **Admin dashboard** (`/api/admin.php`) — password-protected, with **Table** and **Gallery**
  views, a detail/lightbox modal (full text + image/video), and **CSV export**.
- **Polished, accessible UI** — hand-built design system, JS-driven mobile drawer, reduced-motion
  support, no UI framework bloat.
- **Zero-dependency backend** — no Composer; a small custom SMTP client and PDO are all it needs.

---

## 🧱 Tech stack

| Layer | Choice | Why |
| --- | --- | --- |
| **Frontend** | React + Vite, React Router | Fast, modern SPA; builds to plain static files |
| **Styling** | Hand-written CSS + design tokens | No Tailwind/UI-kit; small and bespoke |
| **Backend** | PHP 8 (no framework, no Composer) | Runs natively on Hostinger shared hosting |
| **Database** | MySQL (PDO) | Free on Hostinger; one simple table |
| **Email** | PHP `mail()` or custom SMTP client | No external mail dependency |
| **Hosting** | Hostinger shared hosting | Static files + PHP both run out of the box |

> **Why not Node?** The frontend compiles to static files, and the only server-side logic is a
> couple of PHP endpoints. Hostinger shared hosting runs PHP + MySQL natively — a Node backend
> would need a pricier VPS for no benefit here.

---

## 📁 Project structure

```
heartofjerome/
├── app/                      # React (Vite) frontend — source
│   ├── public/
│   │   ├── logo.svg          # brand logo
│   │   ├── .htaccess         # SPA routing + security + cache headers (→ web root)
│   │   └── LocalValetDriver.php   # local-only: makes Valet serve the SPA + /api
│   ├── src/
│   │   ├── components/        # Header (mobile drawer), Footer, Icon
│   │   ├── pages/             # Home, Ideas, Log
│   │   ├── styles/            # tokens.css + app.css
│   │   ├── api.js             # fetch helpers (/api/count.php, /api/submit.php)
│   │   └── App.jsx            # routes + shared counter context
│   └── vite.config.js         # builds to ../public_html; proxies /api in dev
│
├── api/                       # PHP backend (uploaded as-is)
│   ├── config.sample.php      # ← copy to config.php and fill in
│   ├── config.secret.php      # your real secrets (gitignored; create on server)
│   ├── db.php                 # PDO connection + counter helpers
│   ├── mailer.php             # mail() + custom SMTP client
│   ├── count.php              # GET  → { total, goal, starting }
│   ├── submit.php             # POST → validate, save, email
│   ├── admin.php              # password-protected dashboard
│   └── sql/schema.sql         # one-time database setup
│
├── public_html/              # BUILD OUTPUT (gitignored) — what you deploy
├── uploads/                  # submitted media (local dev scratch)
└── DEPLOY.md                 # step-by-step Hostinger guide
```

---

## 🚀 Local development

**Prerequisites:** Node 18+, PHP 8+, MySQL (e.g. [DBngin](https://dbngin.com/)). Optional:
[Laravel Valet](https://laravel.com/docs/valet) for the `.test` domain.

### 1. Install & configure

```bash
cd app && npm install && cd ..
cp api/config.sample.php api/config.php       # then edit if needed
```

For local dev, create `api/config.local.php` (gitignored) to point at your local MySQL and turn
off real email:

```php
<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'heartofjerome');
define('DB_USER', 'root');
define('DB_PASS', '');
define('TEAM_RECIPIENTS', []);     // don't email anyone locally
define('SEND_CONFIRMATION', false);
define('DEBUG', true);
```

### 2. Create the database

```bash
mysql -u root -h 127.0.0.1 -e "CREATE DATABASE heartofjerome"
mysql -u root -h 127.0.0.1 heartofjerome < api/sql/schema.sql
```

### 3. Run it — two options

**A) Vite dev server (hot reload):**

```bash
php -S 127.0.0.1:8790            # API, from the repo root
npm --prefix app run dev          # app on http://localhost:5173 (proxies /api → 8790)
```

**B) Production-like via Valet** (serves the built `public_html` at `heartofjerome.test`):

```bash
npm --prefix app run build
# Valet serves public_html via LocalValetDriver.php (SPA routing + /api + uploads)
```

---

## ⚙️ Configuration

`config.php` is **tracked but secret-free** — your real credentials go in a gitignored
**`config.secret.php`** (copy `config.sample.php`), so deploys never overwrite or wipe them.
`config.php` fills in safe defaults for anything you don't set. Key settings:

| Setting | What it does |
| --- | --- |
| `DB_*` | MySQL connection |
| `MAIL_TRANSPORT` | `'mail'` (PHP mail, no login) · `'smtp'` (authenticated) · `'auto'` |
| `MAIL_FROM_EMAIL` | **Must be an address on your own domain** or mail gets dropped/spam-filed |
| `TEAM_RECIPIENTS` | Who gets notified of each submission |
| `STARTING_COUNT` | Real-world acts counted before launch (see [the counter](#-the-counter)) |
| `GOAL` | The target (2,500) |
| `ADMIN_PASSWORD` | Login for `/api/admin.php` |
| `DEBUG` | Keep `false` in production |

> 🔒 Real secrets live in **`config.secret.php`** (gitignored). Never put credentials in the tracked `config.php`.

---

## 📧 Email

Two transports, switchable with one line (`MAIL_TRANSPORT`):

- **`mail`** — simplest. No mailbox or login required; just set `MAIL_FROM_EMAIL` to an address on
  your domain (e.g. `noreply@heartofjerome.com`).
- **`smtp`** — most reliable for Gmail recipients. Create a mailbox in hPanel and set `SMTP_USER` /
  `SMTP_PASS`.

> First emails often land in **spam** — mark them "not spam" once and future ones inbox.

---

## 🔐 Admin dashboard

Visit **`https://yourdomain.com/api/admin.php`** and log in with `ADMIN_PASSWORD`.

- **Table** view (compact, with thumbnails) and **Gallery** view (image cards).
- Click any entry → **detail modal** with the full text and a large image / playable video.
- **Export CSV** of every submission.
- `noindex`, session-based login, brute-force slow-down, and login is blocked while the password is
  still the default.

---

## 📊 The counter

```
Public total  =  STARTING_COUNT  +  SUM(num_acts logged through the site)
```

`STARTING_COUNT` (in `config.php`) represents acts tallied **before** the site went live — bump it
any time to reflect offline events. The counter reads live from the database on every page load and
degrades gracefully to `STARTING_COUNT` if the DB is briefly unavailable.

---

## 🌐 Deploy to Hostinger

Full walkthrough in **[DEPLOY.md](DEPLOY.md)**. In short:

```bash
cd app && npm run build          # outputs to ../public_html
```

1. Create a MySQL database (hPanel) and run `api/sql/schema.sql` in phpMyAdmin.
2. Copy `api/config.sample.php` → `api/config.php` and fill in real values.
3. Upload **the contents of `public_html/`** + the **`api/`** folder into `public_html`.
4. Make an `uploads/` folder (writable, `755`). Enable free SSL.

**Updating later:** frontend change → `npm run build`, re-upload `public_html/`. Backend change →
re-upload the single PHP file. The entry `index.html` is sent with `Cache-Control: no-cache`, so
new builds are picked up immediately.

---

## 🧹 Maintenance notes

- **View / export submissions:** `/api/admin.php` (or phpMyAdmin → `kindness_acts`).
- **Reset the counter:** `TRUNCATE kindness_acts;` (keeps `STARTING_COUNT`).
- **Adjust the baseline or goal:** edit `STARTING_COUNT` / `GOAL` in `config.php`.

---

## 🤝 Credits

Built for the **Heart of Jerome** kindness initiative — honoring our heritage, cultivating our
future. Brand: Newsreader + Public Sans, warm cream & America-red.

Team leads: **Dave Davis** · **Tim Knutson** · Statewide effort: [IdahoKindness.com](https://www.idahokindness.com/)
