#!/usr/bin/env bash
# Immutable staging-only deploy. This script intentionally has no production override.
set -euo pipefail

export PATH="$HOME/.local/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

readonly EXPECTED_REPO="/home/hermesadmin/projects/pembinaan-karakter-generus"
readonly APP_ROOT="/var/www/pkgenerus-staging"
readonly SHARED_DIR="$APP_ROOT/shared"
readonly RELEASES_DIR="$APP_ROOT/releases"
readonly CURRENT_LINK="$APP_ROOT/current"
readonly STAGING_URL="https://staging.pkgenerus.my.id"

release_sha="${1:-}"
previous=""
release_dir=""
activated=0

fail() {
    echo "STATUS=FAILED"
    echo "ERROR=$*" >&2
    exit 1
}

rollback() {
    local status=$?
    if [ "$activated" -eq 1 ] && [ -n "$previous" ] && [ -d "$previous" ]; then
        ln -sfn "$previous" "$APP_ROOT/current.rollback"
        mv -Tf "$APP_ROOT/current.rollback" "$CURRENT_LINK"
        echo "ROLLBACK=$previous" >&2
    fi
    exit "$status"
}
trap rollback ERR

[ "$(pwd -P)" = "$EXPECTED_REPO" ] || fail "Run only from $EXPECTED_REPO."
[ "$(git rev-parse --show-toplevel)" = "$EXPECTED_REPO" ] || fail "Unexpected Git repository."
[ "$(git branch --show-current)" = "develop" ] || fail "Only the develop branch may be deployed to staging."
[ -z "$(git status --porcelain)" ] || fail "Refusing dirty or uncommitted source."
[ -f composer.lock ] || fail "composer.lock is required."
[ -f package-lock.json ] || fail "package-lock.json is required."
[ -f "$SHARED_DIR/.env" ] || fail "Missing staging shared environment."
[ -d "$SHARED_DIR/storage" ] || fail "Missing staging shared storage."

release_sha="${release_sha:-$(git rev-parse HEAD)}"
git rev-parse --verify "$release_sha^{commit}" >/dev/null 2>&1 || fail "Invalid commit SHA."
release_sha="$(git rev-parse "$release_sha^{commit}")"
git merge-base --is-ancestor "$release_sha" develop || fail "Commit is not reachable from develop."
release_dir="$RELEASES_DIR/$release_sha"

case "$release_dir" in
    "$APP_ROOT"/releases/[0-9a-f][0-9a-f]*) ;;
    *) fail "Unsafe release path." ;;
esac

previous="$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)"
echo "RELEASE=$release_sha"
echo "PREVIOUS=${previous:-none}"

if [ ! -d "$release_dir" ]; then
    umask 022
    mkdir -p "$release_dir"
    # Archive only committed source: local .env, vendor, and node_modules never enter a release.
    git archive "$release_sha" | tar -x -C "$release_dir"
fi

[ -f "$release_dir/artisan" ] || fail "Release is not a Laravel application."
[ ! -e "$release_dir/.env" ] || [ -L "$release_dir/.env" ] || fail "Release .env is unsafe."
[ ! -e "$release_dir/storage" ] || [ -L "$release_dir/storage" ] || fail "Release storage is unsafe."
[ ! -e "$release_dir/vendor" ] || fail "Release must not include vendor."
[ ! -e "$release_dir/node_modules" ] || fail "Release must not include node_modules."

for directory in app/public app/private framework/cache/data framework/sessions framework/views logs; do
    mkdir -p "$SHARED_DIR/storage/$directory"
done

ln -sfn "$SHARED_DIR/.env" "$release_dir/.env"
ln -sfn "$SHARED_DIR/storage" "$release_dir/storage"
ln -sfn "$SHARED_DIR/storage/app/public" "$release_dir/public/storage"

cd "$release_dir"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

ln -sfn "$release_dir" "$APP_ROOT/current.next"
mv -Tf "$APP_ROOT/current.next" "$CURRENT_LINK"
activated=1

# Validate the active release before considering the swap successful.
test "$(readlink -f "$CURRENT_LINK")" = "$release_dir"
curl --fail --silent --show-error --max-time 20 -o /dev/null "$STAGING_URL/login"

# Keep the active release plus the four newest inactive releases.
mapfile -t stale < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | awk 'NR > 5 {print $2}')
for candidate in "${stale[@]:-}"; do
    [ "$(readlink -f "$candidate")" = "$(readlink -f "$CURRENT_LINK")" ] || rm -rf -- "$candidate"
done

echo "CURRENT=$(readlink -f "$CURRENT_LINK")"
echo "STATUS=SUCCESS"
