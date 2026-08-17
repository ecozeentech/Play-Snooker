# Deploying Play Snooker to Hostinger Business Shared Hosting

This guide walks through deploying this Laravel 11 app to a **Hostinger Business** shared hosting plan (the minimum plan with SSH + Composer access). It uses SSH + Git, which is far less error-prone than uploading files over FTP.

> **Shared hosting limitations to know up front:** shared hosting has no Redis and cannot run a persistent process, so:
> - Cache/session/queue use the `database` driver instead of Redis (already the app's default).
> - Laravel Reverb (WebSockets) can't run — live odds/frame/bracket updates that normally push instantly will simply not broadcast; the UI still works and shows the last-saved values on page load/refresh. If you need real-time push later, either upgrade to a Hostinger VPS/Cloud plan and run `reverb:start` there, or point `BROADCAST_CONNECTION` at a hosted Pusher-compatible service.
> - Queued jobs (bulk tournament notifications, etc.) are drained by a Cron Job every minute instead of a long-running `queue:work` daemon (step 12).

## 0. Prerequisites

- A Hostinger **Business** (or higher) shared hosting plan — Premium doesn't include SSH.
- Your domain (`playsnooker.bet`) added/pointed to the Hostinger hosting account (hPanel → **Domains**).
- The GitHub repo for this project (already pushed — see the PR for this branch).
- [Composer](https://getcomposer.org) and Node.js installed **on your own computer** (only used to build the CSS/JS bundle before uploading — the server does not need Node).

## 1. Create the MySQL database

1. hPanel → **Databases → MySQL Databases**.
2. Create a database (e.g. `u123456789_playsnooker`) and a database user with a strong password; note both plus the password.
3. The host is almost always `127.0.0.1` (some plans use `localhost` — the DB creation page tells you).

## 2. Enable SSH access

1. hPanel → **Advanced → SSH Access** → toggle it on, set/confirm a password.
2. Note the **SSH host**, **port** (often `65002` on Hostinger, not `22`) and **username** shown on that page.
3. Connect from your computer:
   ```bash
   ssh -p 65002 u123456789@yourdomain.com
   ```

## 3. Set the PHP version & extensions

1. hPanel → **Advanced → PHP Configuration** (per-domain).
2. Set **PHP version to 8.3** (this app requires 8.2+).
3. Under "Extensions", make sure these are enabled: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `intl`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`. (Most are on by default; `intl`, `gd`, `bcmath` are the ones most commonly need turning on manually.)

## 4. Build the frontend assets locally

Shared hosting doesn't reliably have Node.js, so build the Vite bundle on your own machine and ship the compiled output:

```bash
git clone https://github.com/<your-org>/Play-Snooker.git
cd Play-Snooker
npm install
npm run build          # produces public/build/**
```

Then temporarily allow the build output into git so a plain `git clone` on the server includes it (skip this if you'd rather upload `public/build` separately over SFTP after step 5):

```bash
# one-time: stop ignoring the compiled assets for deployment
sed -i '' '/\/public\/build/d' .gitignore   # macOS; use `sed -i '/\/public\/build/d' .gitignore` on Linux
git add -A
git commit -m "chore: include built frontend assets for shared-hosting deploy"
git push
```

## 5. Clone the app onto the server (outside the web root)

Hostinger serves each domain from a `public_html` folder, but Laravel's entry point lives in a `public/` subfolder — so the app itself must live **next to**, not inside, `public_html`.

```bash
# you're already in your home dir after `ssh`; find your domain's folder:
cd ~/domains/yourdomain.com   # adjust to the path shown in hPanel > File Manager

git clone https://github.com/<your-org>/Play-Snooker.git playsnooker
cd playsnooker
```

## 6. Point the web root at the app's `public` folder

```bash
cd ~/domains/yourdomain.com
rm -rf public_html                 # back it up first if it has content you need
ln -s playsnooker/public public_html
```

If `ln -s` fails with "permission denied" or Hostinger recreates `public_html` automatically for your plan, use the fallback method instead: keep `public_html` as-is, delete everything inside it, copy the *contents* of `playsnooker/public/` into `public_html/`, then edit `public_html/index.php` so the two `require` lines point at `../playsnooker/vendor/autoload.php` and `../playsnooker/bootstrap/app.php`.

## 7. Install PHP dependencies

```bash
cd ~/domains/yourdomain.com/playsnooker
composer2 install --optimize-autoloader --no-dev
```

(Hostinger ships both Composer 1 and 2 — always use `composer2` explicitly.)

## 8. Configure `.env`

```bash
cp .env.hostinger.example .env
nano .env
```

Fill in at minimum:
- `APP_URL=https://playsnooker.bet`
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` from step 1
- Leave `BROADCAST_CONNECTION=log`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database` as shipped (no Redis/Reverb on shared hosting — see the note at the top)
- Optionally: `OPEN_EXCHANGE_RATES_APP_ID`, `STRIPE_*`, `COINBASE_COMMERCE_*`, `GOOGLE_*`/`FACEBOOK_*` if/when you wire those integrations up

Then generate the app key and push-notification keys:

```bash
php artisan key:generate
php artisan webpush:vapid
```

## 9. Run migrations & seed the database

```bash
php artisan migrate --force
php artisan db:seed --force
```

This creates the admin user (`admin@playsnooker.bet` / `password`), the shop catalog, achievements and currency/gateway settings. **Log in and change the admin password immediately** (step 13).

## 10. Storage permissions & the storage symlink

Hostinger disables PHP's `symlink()` function for security, so `php artisan storage:link` will fail — create the link manually instead:

```bash
chmod -R 775 storage bootstrap/cache
ln -s ~/domains/yourdomain.com/playsnooker/storage/app/public \
      ~/domains/yourdomain.com/playsnooker/public/storage
```

## 11. Cache the framework for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Re-run these three commands after every future deploy (a `composer.json` post-deploy script or a small `deploy.sh` you `ssh` in and run is a good habit).

## 12. Cron Jobs (scheduler + "queue worker")

hPanel → **Advanced → Cron Jobs** → add two jobs, both running every minute:

```
* * * * * cd /home/u123456789/domains/yourdomain.com/playsnooker && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/u123456789/domains/yourdomain.com/playsnooker && php artisan queue:work --stop-when-empty --tries=3 --timeout=50 >> /dev/null 2>&1
```

(Replace the path with the real one from `pwd` on the server.) The second line simulates a queue worker without a persistent process — it drains whatever's queued and exits every minute, which is enough for this app's job volume (bulk notifications, tournament shuffling, payout settlement).

## 13. Enable SSL & finish up

1. hPanel → **Security → SSL** → issue the free SSL certificate for your domain (usually automatic within a few minutes).
2. Visit `https://playsnooker.bet` — you should see the landing page.
3. Log in at `/admin` with `admin@playsnooker.bet` / `password`, then **immediately change that password** from your account settings (or Filament's user edit screen).
4. Register a normal player account and confirm the dashboard, shop, tournaments and practice-mode game engine all load.

## Updating an existing deployment

Whenever `main` gets new commits (new features, bug fixes, etc.), bring your live site up to date with these steps. This app changes fairly often (new migrations, new Blade/CSS/JS, new admin pages), so don't skip steps just because a past update didn't need them.

### 1. Back up first

Do this before every update — it takes two minutes and saves you if anything goes wrong:

- hPanel → **Files → Backups**, or manually export the database: hPanel → **Databases → phpMyAdmin** → select your database → **Export** → "Quick" → Go.
- Optionally zip the app folder too: `cd ~/domains/yourdomain.com && zip -r playsnooker-backup-$(date +%F).zip playsnooker -x 'playsnooker/vendor/*' -x 'playsnooker/node_modules/*'`.

### 2. Rebuild the frontend assets locally

Every update to this app may include Blade/CSS/JS changes, and the compiled bundle isn't committed to git by default — rebuild and re-include it before pulling on the server:

```bash
# on your own computer, in your local clone of the repo
git pull
npm install          # picks up any new/updated npm packages (e.g. @tailwindcss/typography)
npm run build         # regenerates public/build/** with fresh, correctly-hashed filenames

# re-include the build output for this deploy (same one-time trick as initial setup)
git add -A
git commit -m "chore: rebuild frontend assets for deploy"
git push
```

If you're not tracking `public/build` in git, instead upload the freshly-built `public/build` folder to the server over SFTP after step 4 below, replacing the old one entirely (don't merge old + new — stale hashed filenames left behind are harmless but pointless clutter).

### 3. Put the site in maintenance mode (recommended for anything beyond a trivial fix)

```bash
ssh -p 65002 u123456789@yourdomain.com
cd ~/domains/yourdomain.com/playsnooker
php artisan down --retry=60
```

### 4. Pull the update and reinstall dependencies

```bash
git pull
composer2 install --optimize-autoloader --no-dev
```

### 5. Run new database migrations

```bash
php artisan migrate --force
```

This is safe to run on every update even if there's nothing new to migrate — Laravel skips migrations that already ran. Never skip this step: forgetting it after an update that adds a column (e.g. the cue-appearance colors) causes "Unknown column" SQL errors on the pages that use it.

### 6. Re-check the storage symlink (needed for logo/favicon/ad-banner uploads)

If admins upload a logo, favicon, or ad banner image through `/admin`, those files are served through the `public/storage` symlink. Confirm it's still there (updates don't normally remove it, but it's worth a 5-second check):

```bash
ls -la public/storage
```

If it's missing, recreate it manually (Hostinger disables PHP's `symlink()`, so `php artisan storage:link` won't work — see step 10 of the initial setup guide above):

```bash
ln -s ~/domains/yourdomain.com/playsnooker/storage/app/public \
      ~/domains/yourdomain.com/playsnooker/public/storage
```

### 7. Clear and rebuild every cache

This is the step people most often forget, and the most common cause of "I updated but nothing changed" or new pages/admin sections 404ing:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

(`optimize:clear` clears the old config/route/view/event/compiled caches in one go before the four `*:cache` commands rebuild them fresh — running only the `*:cache` commands without clearing first can leave stale entries mixed in.)

### 8. Bring the site back up and verify

```bash
php artisan up
```

Then check, ideally in a private/incognito window (to avoid a stale service worker or browser cache hiding a problem):

- The homepage loads and shows the right title/branding.
- Log in as a normal player: dashboard, tournaments, shop, wallet, and `/play/practice` (the pool table should render and respond to drag input) all work.
- Log in as an admin: `/admin` loads, and if this update added new admin sections (e.g. **Platform Settings**, **Advertising → Ad Banners**), confirm they appear in the sidebar and open without errors.
- If you're a returning visitor and something looks stale (old logo, old layout), hard-refresh once (Ctrl/Cmd+Shift+R) or clear the site's service worker (DevTools → Application → Service Workers → Unregister) — this app caches pages/assets for offline practice mode, so a browser that visited before the update may show a cached copy until it re-fetches.

### Quick reference (once you've done the full walkthrough once)

```bash
# locally: rebuild assets and push
npm install && npm run build && git add -A && git commit -m "chore: rebuild assets" && git push

# on the server
ssh -p 65002 u123456789@yourdomain.com
cd ~/domains/yourdomain.com/playsnooker
php artisan down --retry=60
git pull
composer2 install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
php artisan up
```

## Troubleshooting: "the game board / nav dropdown / logout doesn't work"

If the pool table doesn't render, or nav links like Admin/Logout seem missing, it's almost always a **compiled frontend asset problem**, not a server-side bug — the whole UI (game engine, nav, dropdowns) is driven by one JS bundle in `public/build/`. Check in this order:

1. **Open the browser console** (F12 → Console tab) on the broken page and look for red errors. A `404` on `/build/assets/app-*.js` or `/build/manifest.json` means the compiled assets never made it to the server — re-do step 4 (rebuild locally, commit `public/build`, `git pull` on the server) and confirm with `ls ~/domains/yourdomain.com/playsnooker/public/build/assets/` over SSH that files actually exist there.
2. **Mixed-content blocking**: if `APP_URL` in `.env` is `http://` but the site is served over `https://` (or vice versa), browsers silently block the script and nothing on the page will be interactive. Make sure `APP_URL` exactly matches the scheme your site loads under, then `php artisan config:cache` again.
3. **Stale cached views**: after any deploy, always re-run `php artisan view:cache` (and `config:cache`/`route:cache`) — an old compiled Blade view can reference a `public/build` asset filename (Vite fingerprints every build with a new hash) that no longer exists after a newer build.
4. **Stale service worker**: this app registers a PWA service worker that caches pages/assets for offline practice mode. If you deployed a fix but a returning visitor's browser still shows the old broken version, have them hard-refresh (Ctrl/Cmd+Shift+R) once, or open DevTools → Application → Service Workers → Unregister, then reload. New visitors are unaffected.
5. As of this app's latest version, the game engine's Alpine.js component and the WebSocket (Echo/Reverb) connection are already hardened to not depend on script load order and to skip connecting when Reverb isn't configured (both were real bugs in an earlier build) — if you're deploying from an older checkout, `git pull` to pick these fixes up before troubleshooting further.
6. **Admin link not visible**: it only appears for the account that has `is_admin = true` (created by `AdminUserSeeder` as `admin@playsnooker.bet`) — it's a plain, always-visible button in the top nav (not hidden behind a dropdown), so if you're logged in as a regular player account you won't see it, by design. Log in as the admin account, or flip `is_admin` for another user via `php artisan tinker` / the Users table in phpMyAdmin.

## Known limitations on shared hosting (see also the README)

- **No live WebSocket broadcasting** (Reverb needs a persistent process) — live odds/frame/bracket pages fall back to their last-loaded state instead of updating instantly. Upgrading to a Hostinger VPS/Cloud plan (or any VPS) removes this limitation.
- **No Redis** — cache/session/queue run on MySQL via the `database` driver, which is fine at moderate traffic but won't scale as well as Redis under heavy load.
- Automated payment gateways (Stripe/Coinbase Commerce/Binance Pay) still need their webhook endpoints/SDKs wired up before going live with real payments — only the manual bank/crypto deposit-with-proof flow is fully functional out of the box.
