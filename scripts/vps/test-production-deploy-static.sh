#!/usr/bin/env bash
# Verify production uploads a fresh Vite build before activating a release.
set -euo pipefail

repository_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
workflow="$repository_root/.github/workflows/production-deploy.yml"
deploy_script="$repository_root/scripts/vps/deploy-release.sh"
verifier="$repository_root/scripts/vps/verify-vite-manifest-assets.mjs"

bash -n "$deploy_script"
test -f "$verifier"

python3 - "$workflow" "$deploy_script" <<'PY'
import sys
from pathlib import Path

workflow = Path(sys.argv[1]).read_text()
deploy_script = Path(sys.argv[2]).read_text()
deploy_job = workflow.split('  deploy:\n', 1)[-1]

required_workflow_steps = [
    'actions/setup-node@v4',
    'npm ci --no-audit --no-fund',
    'npm run build',
    'node scripts/vps/verify-vite-manifest-assets.mjs public/build',
    'rsync -az --delete',
]
positions = []
for step in required_workflow_steps:
    position = deploy_job.find(step)
    if position < 0:
        raise SystemExit(f'production deploy job is missing: {step}')
    positions.append(position)

if positions != sorted(positions):
    raise SystemExit('production workflow must verify a fresh Vite build before rsync uploads the release')

upload_block = deploy_job[positions[-1]:deploy_job.find('      - name: Build and activate release', positions[-1])]
if "--exclude='/public/build/'" in upload_block or "--exclude='/public/build'" in upload_block:
    raise SystemExit('production workflow must upload public/build')

if 'node scripts/vps/verify-vite-manifest-assets.mjs public/build' not in deploy_script:
    raise SystemExit('release activation must verify manifest assets after its server build')
PY

echo "production deploy static checks passed"
