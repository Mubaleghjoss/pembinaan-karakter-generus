#!/usr/bin/env bash
# Focused guardrails for staging deploy safety; run without a staging host.
set -euo pipefail

script="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/deploy-staging.sh"

[ -x "$script" ] || { echo "deploy-staging.sh must be executable" >&2; exit 1; }
bash -n "$script"

grep -Fq 'flock -n 9' "$script"
grep -Fq 'validate_release_source' "$script"
grep -Fq 'ensure_runtime_group_write_access' "$script"
grep -Fq 'runtime_paths_are_group_writable' "$script"
grep -Fq 'sudo -n /usr/local/sbin/pkgenerus-staging-admin fix-permissions' "$script"
if grep -Fq 'command -v pkgenerus-staging-admin' "$script"; then
    echo "staging deploy must use the approved absolute permission helper via sudo -n" >&2
    exit 1
fi
grep -Fq "the release was not activated" "$script"
if grep -Eq '(^|[[:space:];])chgrp([[:space:];]|$)' "$script"; then
    echo "staging deploy must not change runtime file groups as the deploy user" >&2
    exit 1
fi

permission_line="$(grep -nF 'ensure_runtime_group_write_access' "$script" | tail -n 1 | cut -d: -f1)"
activation_line="$(grep -nF 'ln -sfn "$release_dir" "$APP_ROOT/current.next"' "$script" | cut -d: -f1)"
[ "$permission_line" -lt "$activation_line" ] || {
    echo "runtime permission repair must complete before release activation" >&2
    exit 1
}

grep -Fq 'current.next' "$script"
grep -Fq 'smoke_url "$STAGING_URL/up" "200"' "$script"
grep -Fq 'smoke_url "$STAGING_URL/login" "200 302"' "$script"
grep -Fq 'smoke_url "$STAGING_URL/tracer-bacaan-quran" "200 302"' "$script"
grep -Fq 'No built CSS asset found.' "$script"
grep -Fq 'No built JavaScript asset found.' "$script"
grep -Fq 'No public storage image found; storage image smoke check skipped.' "$script"
grep -Fq '[ "$candidate_real" = "$current_release" ]' "$script"

if grep -Eq 'artisan[[:space:]]+(migrate|migrate:|db:|schema:)' "$script"; then
    echo "staging deploy must not run database commands" >&2
    exit 1
fi

echo "deploy-staging static checks passed"
