# VPS Production Deployment

This is an approval-gated VPS deployment design for `pkgenerus.my.id`. It keeps production isolated from `/var/www/pkgenerus-staging`, does not put secrets in Git, and deliberately does not run migrations.

## Status and safety boundaries

- Production runtime root: `/var/www/pkgenerus`
- Release layout: `releases/<git-sha>`, `shared/.env`, `shared/storage`, and atomic `current` symlink
- The GitHub workflow runs tests first, then waits for the GitHub Environment named `production` to be approved.
- The production runtime is owned by `hermesadmin:www-data`, matching the existing VPS deployment pattern. GitHub Actions connects as `hermesadmin`; application files must not require broad sudo access during deployment.
- No production `.env` is generated or copied by the workflow. No database is created and no migration is run automatically.

## One-time VPS prerequisites

An administrator must create the runtime root once and grant ownership to `hermesadmin:www-data`. This requires privileged access and is intentionally not automated:

```bash
sudo install -d -o hermesadmin -g www-data -m 2775 /var/www/pkgenerus.my.id
sudo -u hermesadmin mkdir -p /var/www/pkgenerus.my.id/releases \
  /var/www/pkgenerus.my.id/shared/storage/app/public \
  /var/www/pkgenerus.my.id/shared/storage/app/private \
  /var/www/pkgenerus.my.id/shared/storage/framework/cache/data \
  /var/www/pkgenerus.my.id/shared/storage/framework/sessions \
  /var/www/pkgenerus.my.id/shared/storage/framework/views \
  /var/www/pkgenerus.my.id/shared/storage/logs
sudo -u hermesadmin chmod 2775 /var/www/pkgenerus.my.id/shared/storage /var/www/pkgenerus.my.id/shared/storage/logs
```

Create `/var/www/pkgenerus.my.id/shared/.env` manually with real production-only values. Generate `APP_KEY` once on the server and retain it permanently. Set at least `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://pkgenerus.my.id`, the separate production database values, mail/VAPID values when used, and production cookie/Sanctum settings. Keep the file owned by `hermesadmin:www-data` and mode `640` or stricter.

## New database prerequisite

The production database name, database server, and administrative credential have not been verified. Do not infer them from staging. An authorized database administrator may adapt this template after selecting an explicit, unused name and a strong password outside the shell history:

```sql
CREATE DATABASE `pkgenerus_production` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pkgenerus_production'@'127.0.0.1' IDENTIFIED BY 'GENERATE_AND_STORE_A_SECRET_OUTSIDE_GIT';
GRANT ALL PRIVILEGES ON `pkgenerus_production`.* TO 'pkgenerus_production'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Before executing it, confirm that the chosen database and user do not already exist and match the actual database host authentication model. Migrations require separate explicit approval after the application can connect to this new database:

```bash
cd /var/www/pkgenerus.my.id/current
/usr/bin/php artisan migrate --force
```

## GitHub Environment and secrets

Create a GitHub Environment exactly named `production`, configure required reviewers, and add these environment secrets:

- `DEPLOY_HOST`: production VPS hostname or IP
- `DEPLOY_PORT`: SSH port, normally `22`
- `DEPLOY_USER`: set to `hermesadmin` for this VPS
- `DEPLOY_SSH_KEY`: private key for that account
- `DEPLOY_KNOWN_HOSTS`: pinned `ssh-keyscan` output verified out-of-band

The workflow at `.github/workflows/production-deploy.yml` must stay uncommitted until reviewed. It runs `composer install`, `npm ci`, the frontend build, and Laravel tests in GitHub Actions; the approved deployment uploads a new release, builds it on the VPS, caches Laravel configuration/routes/views, and atomically changes `current`. It will fail safely if the shared `.env` is absent. It does not restart services and does not run migrations.

## Nginx and scheduler

`deploy/vps/nginx/pkgenerus.production.conf.template` is intentionally disabled and uses placeholders for the confirmed local port and PHP-FPM socket. Do not enable it, edit staging, change TLS, or reload Nginx until DNS, certificate paths, upstream proxy topology, and the PHP-FPM socket are verified. Validate the final server-wide Nginx configuration with a privileged `nginx -t` before any reload.

`deploy/vps/cron/pkgenerus-schedule.cron.template` is intentionally disabled. The application schedules notifications every five minutes and cleanup hourly; a single Laravel `schedule:run` cron each minute is sufficient. Before installing it, check the `hermesadmin` crontab, `/etc/cron.d`, and systemd timers for an existing PKGenerus scheduler. Do not add queue workers unless queue workload and existing supervision are verified.

## Post-deploy checks

After the first approved deployment, verify without exposing secrets:

```bash
readlink -f /var/www/pkgenerus.my.id/current
cd /var/www/pkgenerus.my.id/current && /usr/bin/php artisan about --only=environment
curl -fsS -o /dev/null -w '%{http_code}\n' https://pkgenerus.my.id/
```

Keep at least one known-good release for rollback. A rollback is a deliberate atomic symlink change to that release only after verifying its shared environment compatibility.
