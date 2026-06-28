# Deploying The Heart of Jerome to Hostinger

The site is a **React frontend** (built to static files) plus a **PHP + MySQL API**.
You build the frontend once, then upload the built files alongside the `api/` folder.

```
Repo layout
├── app/        React source (Vite)   → builds to public_html/
├── api/        PHP endpoints (count.php, submit.php) + config + DB schema
└── uploads/    created on the server for optional photo/video uploads
```

---

## 1. Build the frontend (on your computer)

```bash
cd app
npm install        # first time only
npm run build      # outputs the static site to public_html/
```

> Locally this same build is served at **http://heartofjerome.test** via Valet
> (a `LocalValetDriver.php` handles SPA routing + the `/api` calls). Just re-run
> `npm run build` and refresh. For live-reload development instead, run
> `npm --prefix app run dev` with `php -S 127.0.0.1:8790` from the repo root.

## 2. Create the database (hPanel, ~5 min)

1. hPanel → **Databases → MySQL Databases**. Create one; note the **name, user, password**.
2. Open **phpMyAdmin** → **SQL** tab → paste `api/sql/schema.sql` → **Go**.
   The `kindness_acts` table appears.

## 3. Create the email mailbox (hPanel, ~5 min)

1. hPanel → **Emails → Email Accounts**. Create e.g. `kindness@yourdomain.com`.
2. Sending must come **from your own domain** (Gmail/Yahoo are rejected as "From").

## 4. Configure `api/config.php`

Replace every `CHANGE_ME`:

| Setting | Value |
| --- | --- |
| `DB_NAME` / `DB_USER` / `DB_PASS` | from step 2 |
| `SMTP_USER` / `SMTP_PASS` | the mailbox from step 3 |
| `MAIL_FROM_EMAIL` | same as `SMTP_USER` |
| `SITE_URL` | your domain, e.g. `https://heartofjerome.com` (no trailing slash) |
| `ALLOWED_ORIGINS` | replace the `CHANGE_ME` entry with your domain |
| `STARTING_COUNT` | real-world acts already counted (currently 1248) |
| `TEAM_RECIPIENTS` | already Dave + Tim — change if needed |

`DB_HOST` stays `localhost` on Hostinger. (`config.local.php` is for local dev only —
do **not** upload it; it's gitignored.)

## 5. Upload

Into `public_html` upload:

- **the contents of the built `public_html/`** (index.html, assets/, logo.svg, .htaccess, …)
- **the whole `api/` folder**

(The `LocalValetDriver.php` in the build is harmless on Hostinger — it's only used
by Valet locally — but you can delete it from the server if you prefer.)

Resulting layout:

```
public_html/
├── index.html
├── assets/
├── logo.svg
├── .htaccess          ← SPA routing + security (from app/dist)
├── api/
│   ├── config.php     ← your edited credentials
│   ├── count.php
│   ├── submit.php
│   └── …
└── uploads/           ← create this, make it writable (chmod 755)
```

> The `.htaccess` (shipped in `app/dist/`) routes client-side React paths to
> `index.html` while leaving `/api/*` and real files alone.

## 6. Test

1. Visit your domain — the home page loads, counter shows.
2. **Log Your Act** → submit a test entry. Confirm: thank-you screen, counter rises,
   Dave & Tim get the email, you get the confirmation.
3. Enable free **SSL** in hPanel → **SSL** so the site is served over `https://`.

If email fails, set `DEBUG` to `true` in `api/config.php` temporarily, re-test, then
set it back to `false`.

---

## Updating the site later

- **Frontend change** → `cd app && npm run build`, re-upload `public_html/` contents.
- **Backend change** → edit the file in `api/` and re-upload just that file.
- **View submissions** → phpMyAdmin → `kindness_acts` → Browse (export to CSV there).
- **Adjust the counter** → change `STARTING_COUNT` in `api/config.php`.
