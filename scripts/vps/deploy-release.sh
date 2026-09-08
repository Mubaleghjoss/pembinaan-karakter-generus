#!/usr/bin/env bash
# Activate a release prepared by the GitHub Actions production deployment job.
set -euo pipefail
export PATH="$HOME/.local/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

APP_ROOT="${APP_ROOT:-/var/www/pkgenerus.my.id}"
RELEASE_NAME="${1:?Usage: deploy-release.sh <release-name>}"
RELEASE_DIR="$APP_ROOT/releases/$RELEASE_NAME"
SHARED_DIR="$APP_ROOT/shared"

case "$RELEASE_NAME" in
    *[!A-Za-z0-9._-]* | '') echo "Invalid release name." >&2; exit 2 ;;
esac

[ -d "$RELEASE_DIR" ] || { echo "Release directory does not exist: $RELEASE_DIR" >&2; exit 1; }
[ -f "$RELEASE_DIR/artisan" ] || { echo "Release is not a Laravel application." >&2; exit 1; }
[ -f "$RELEASE_DIR/composer.lock" ] || { echo "composer.lock is required for an immutable deploy." >&2; exit 1; }
[ -f "$RELEASE_DIR/package-lock.json" ] || { echo "package-lock.json is required for an immutable deploy." >&2; exit 1; }
[ -f "$SHARED_DIR/.env" ] || { echo "Missing $SHARED_DIR/.env; provision the real production environment first." >&2; exit 1; }

for directory in \
    storage/app/public storage/app/private storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs; do
    mkdir -p "$SHARED_DIR/$directory"
done

cd "$RELEASE_DIR"
[ ! -e storage ] || { echo "Release storage must not be uploaded." >&2; exit 1; }
[ ! -e .env ] || { echo "Release .env must not be uploaded." >&2; exit 1; }
ln -s "$SHARED_DIR/storage" storage
ln -s "$SHARED_DIR/.env" .env
[ ! -e public/storage ] || {
    echo "Release public/storage must not be uploaded." >&2
    exit 1
}

ln -s "$SHARED_DIR/storage/app/public" public/storage

composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# The symlink swap keeps the preceding release live until this release is ready.
ln -sfn "$RELEASE_DIR" "$APP_ROOT/current.next"
mv -Tf "$APP_ROOT/current.next" "$APP_ROOT/current"

echo "Activated release: $RELEASE_NAME"
echo "Database migrations are intentionally not run by this script."
