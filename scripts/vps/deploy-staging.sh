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
readonly LOCK_FILE="$APP_ROOT/.deploy-staging.lock"

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

validate_release_source() {
    local entry metadata path expected actual

    # A SHA-named directory may be left behind by an interrupted deploy. Reuse it
    # only when every tracked source file still exactly matches that commit.
    while IFS= read -r -d '' entry; do
        metadata="${entry%%$'\t'*}"
        path="${entry#*$'\t'}"
        set -- $metadata
        expected="$3"
        [ -f "$release_dir/$path" ] || fail "Incomplete release source: $path"
        actual="$(git hash-object "$release_dir/$path")"
        [ "$actual" = "$expected" ] || fail "Release source does not match commit: $path"
    done < <(git ls-tree -r -z "$release_sha")
}

runtime_paths_are_group_writable() {
    local runtime_dir

    for runtime_dir in "$release_dir/bootstrap/cache" "$SHARED_DIR/storage"; do
        if find -P "$runtime_dir" -type d \( ! -perm -g+w -o ! -perm -g+x \) -print -quit | grep -q . \
            || find -P "$runtime_dir" -type f ! -perm -g+w -print -quit | grep -q .; then
            return 1
        fi
    done
}

ensure_runtime_group_write_access() {
    local runtime_dir chmod_failed=0

    for runtime_dir in "$release_dir/bootstrap/cache" "$SHARED_DIR/storage"; do
        mkdir -p "$runtime_dir" || fail "Cannot create runtime directory: $runtime_dir"
    done

    runtime_paths_are_group_writable && return

    # Only adjust mode bits where needed; the deploy user must not change file groups.
    for runtime_dir in "$release_dir/bootstrap/cache" "$SHARED_DIR/storage"; do
        if ! find -P "$runtime_dir" \( -type d \( ! -perm -g+w -o ! -perm -g+x \) -o -type f ! -perm -g+w \) -exec chmod g+rwX {} +; then
            chmod_failed=1
        fi
    done

    runtime_paths_are_group_writable && return

    if command -v pkgenerus-staging-admin >/dev/null 2>&1; then
        echo "INFO=Runtime paths need privileged permission repair; invoking pkgenerus-staging-admin fix-permissions." >&2
        if ! pkgenerus-staging-admin fix-permissions; then
            fail "Privileged permission repair failed. Run 'pkgenerus-staging-admin fix-permissions' and retry; the release was not activated."
        fi
        runtime_paths_are_group_writable && return
        fail "Runtime paths are not group-writable after privileged repair. Run 'pkgenerus-staging-admin fix-permissions' and verify its policy; the release was not activated."
    fi

    if [ "$chmod_failed" -eq 1 ]; then
        fail "Runtime paths need privileged permission repair, but pkgenerus-staging-admin is unavailable. Run 'pkgenerus-staging-admin fix-permissions' and retry; the release was not activated."
    fi
    fail "Runtime paths are not group-writable, but pkgenerus-staging-admin is unavailable. Run 'pkgenerus-staging-admin fix-permissions' and retry; the release was not activated."
}

smoke_url() {
    local url="$1" allowed_statuses="$2" status

    status="$(curl --silent --show-error --max-time 20 --output /dev/null --write-out '%{http_code}' "$url")"
    case " $allowed_statuses " in
        *" $status "*) ;;
        *) fail "Unexpected HTTP $status for $url (expected: $allowed_statuses)" ;;
    esac
}

[ "$(pwd -P)" = "$EXPECTED_REPO" ] || fail "Run only from $EXPECTED_REPO."
[ "$(git rev-parse --show-toplevel)" = "$EXPECTED_REPO" ] || fail "Unexpected Git repository."
[ "$(git branch --show-current)" = "develop" ] || fail "Only the develop branch may be deployed to staging."
# Local agent instructions and dependencies are intentionally never released by git archive.
if ! git diff --quiet -- . ':(exclude)AGENTS.md' \
    || ! git diff --cached --quiet -- . ':(exclude)AGENTS.md' \
    || git ls-files --others --exclude-standard | grep -qvE '^(AGENTS\.md\.before-hermes-lock|vendor(/|$))'; then
    fail "Refusing dirty or uncommitted release source."
fi
[ -f composer.lock ] || fail "composer.lock is required."
[ -f package-lock.json ] || fail "package-lock.json is required."
[ -f "$SHARED_DIR/.env" ] || fail "Missing staging shared environment."
[ -d "$SHARED_DIR/storage" ] || fail "Missing staging shared storage."

# Serialize staging deploys when flock is installed, without making it a hard host dependency.
if command -v flock >/dev/null 2>&1; then
    exec 9>"$LOCK_FILE"
    flock -n 9 || fail "Another staging deployment is already running."
else
    echo "WARN=flock unavailable; staging deployment is not serialized." >&2
fi

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

if [ -d "$release_dir" ]; then
    validate_release_source
else
    umask 022
    mkdir -p "$release_dir"
    # Archive only committed source: local .env, vendor, and node_modules never enter a release.
    git archive "$release_sha" | tar -x -C "$release_dir"
    validate_release_source
fi

[ -f "$release_dir/artisan" ] || fail "Release is not a Laravel application."
[ -f "$release_dir/composer.lock" ] || fail "Release composer.lock is missing."
[ -f "$release_dir/package-lock.json" ] || fail "Release package-lock.json is missing."
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
test "$(readlink -f "$release_dir/.env")" = "$SHARED_DIR/.env"
test "$(readlink -f "$release_dir/storage")" = "$SHARED_DIR/storage"
test "$(readlink -f "$release_dir/public/storage")" = "$SHARED_DIR/storage/app/public"
ensure_runtime_group_write_access

cd "$release_dir"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
ensure_runtime_group_write_access

ln -sfn "$release_dir" "$APP_ROOT/current.next"
mv -Tf "$APP_ROOT/current.next" "$CURRENT_LINK"
activated=1

# Validate the active release before considering the atomic swap successful.
test "$(readlink -f "$CURRENT_LINK")" = "$release_dir"
smoke_url "$STAGING_URL/up" "200"
smoke_url "$STAGING_URL/login" "200 302"
smoke_url "$STAGING_URL/tracer-bacaan-quran" "200 302"

css_asset="$(find "$release_dir/public/build" -type f -name '*.css' -print -quit)"
js_asset="$(find "$release_dir/public/build" -type f -name '*.js' -print -quit)"
[ -n "$css_asset" ] || fail "No built CSS asset found."
[ -n "$js_asset" ] || fail "No built JavaScript asset found."
smoke_url "$STAGING_URL${css_asset#"$release_dir/public"}" "200"
smoke_url "$STAGING_URL${js_asset#"$release_dir/public"}" "200"

storage_image="$(find "$SHARED_DIR/storage/app/public" -type f \( -iname '*.png' -o -iname '*.jpg' -o -iname '*.jpeg' -o -iname '*.gif' -o -iname '*.webp' \) ! -name '* *' -print -quit)"
if [ -n "$storage_image" ]; then
    smoke_url "$STAGING_URL/storage/${storage_image#"$SHARED_DIR/storage/app/public/"}" "200"
else
    echo "INFO=No public storage image found; storage image smoke check skipped."
fi

# Keep the active release, its predecessor, and up to four newer inactive releases.
current_release="$(readlink -f "$CURRENT_LINK")"
retained_inactive=0
mapfile -t releases < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | cut -d' ' -f2-)
for candidate in "${releases[@]:-}"; do
    candidate_real="$(readlink -f "$candidate")"
    if [ "$candidate_real" = "$current_release" ] || { [ -n "$previous" ] && [ "$candidate_real" = "$previous" ]; }; then
        continue
    fi
    if [ "$retained_inactive" -lt 4 ]; then
        retained_inactive=$((retained_inactive + 1))
        continue
    fi
    rm -rf -- "$candidate"
done

echo "CURRENT=$current_release"
echo "STATUS=SUCCESS"
