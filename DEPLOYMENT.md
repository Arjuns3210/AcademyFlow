# AcademyFlow — Deployment Guide

## Server Requirements

- **PHP 8.2+** (developed and tested on 8.3). Required extensions: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `dom`, `filter`, `hash`, `session`, `gd` (image validation/dompdf), `zip` (database backups).
- **MySQL 8.0+** (developed and tested on 8.0.45).
- **Node.js + npm** — needed once at build time to compile frontend assets (Bootstrap, Chart.js). Not needed at runtime.
- A web server (Apache or nginx) with its document root pointed at `public/`, **not** the project root.
- Composer 2.x.

## Deploy Steps

Run in order, from the project root:

```bash
# 1. Get the code onto the server (see "Rollback" below for how this project
#    is not a git repo locally — however you get code to the server, keep
#    the previous release available until the new one is confirmed working).

# 2. Install PHP dependencies without dev tooling
composer install --no-dev --optimize-autoloader

# 3. Install and build frontend assets
npm ci
npm run build

# 4. Environment file — copy .env.example to .env and fill in real values
#    (DB credentials, APP_URL, MAIL_*, etc.), then:
php artisan key:generate   # only on a genuinely fresh install — never re-run
                           # this against an existing production .env, it
                           # invalidates all existing encrypted data/sessions

# 5. Database
php artisan migrate --force

# 6. Public storage symlink (uploaded logos/photos are served through this)
php artisan storage:link

# 7. Cache everything for production (config:cache in particular requires
#    every env() call to live inside config/*.php — confirmed true for this
#    app in Sprint 8D; if that ever changes, config:cache will misbehave
#    silently rather than error, so re-verify after any config-related change)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Confirm APP_DEBUG=false in .env before going live — stack traces must
#    never reach a real user. (Verified in Sprint 8D that Laravel's default
#    error handling actually honors this; nothing custom to configure.)
```

If a deploy changes `config/`, `routes/`, or any Blade view, re-run step 7's three cache commands — stale caches are a common source of "works locally, broken in production" bugs.

## Scheduler (cron)

Two scheduled commands currently exist: `subscriptions:expire` (daily) and the database backup (`backup:run --only-db` + `backup:clean`, daily at 02:00/02:30 — see Sprint 8C). Both run through Laravel's scheduler, which needs exactly one cron entry on the server:

```
* * * * * cd /path/to/academyflow && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Worker — not currently required

As of Sprint 8D, nothing in the codebase dispatches a queued job or implements `ShouldQueue` (checked directly, not assumed) — PDF receipts, CSV/PDF report exports, and everything else run synchronously within the request. `QUEUE_CONNECTION=database` is configured but nothing depends on it, so **no queue worker or Supervisor process needs to run in production today**.

If a future change does add queued work (e.g., moving report export generation to a queue for large datasets), it will need a persistent worker — typically Supervisor running `php artisan queue:work --tries=3`, with `queue:restart` wired into the deploy steps above so workers pick up new code. Flagging this now so it isn't quietly forgotten if that day comes.

## Rollback

This project isn't a git repository locally, so "rollback" here means whatever release-management approach is used to get code onto the server (git tags, timestamped release directories, etc.) — keep the previous release intact and switchable-back-to until the new one is confirmed working, rather than deploying in place over it.

For the database specifically:
- **Before running `migrate --force`**, take a fresh manual backup: `php artisan backup:run --only-db`. The automated daily backup (Sprint 8C) covers routine recovery, but a deploy-time backup gives you a precise pre-migration snapshot.
- `php artisan migrate:rollback` exists and reverses the last migration batch, but it is **not** a substitute for the backup above — a rollback only undoes schema changes via each migration's `down()` method, it does not restore data that a migration may have altered or dropped along the way.
