#!/usr/bin/env bash
# Focused guardrails for staging deploy safety; run without a staging host.
set -euo pipefail

script="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/deploy-staging.sh"

[ -x "$script" ] || { echo "deploy-staging.sh must be executable" >&2; exit 1; }
bash -n "$script"

grep -Fq 'flock -n 9' "$script"
grep -Fq 'validate_release_source' "$script"
grep -Fq 'ensure_www_data_runtime_access' "$script"
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
