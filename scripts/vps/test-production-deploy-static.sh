#!/usr/bin/env bash
# Verify production uploads a fresh Vite build and reloads PHP-FPM after activation.
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

activation_step = '      - name: Build, activate, and reload PHP-FPM'
activation_position = deploy_job.find(activation_step)
if activation_position < positions[-1]:
    raise SystemExit('production workflow must activate only after uploading the verified Vite build')

upload_block = deploy_job[positions[-1]:activation_position]
if "--exclude='/public/build/'" in upload_block or "--exclude='/public/build'" in upload_block:
    raise SystemExit('production workflow must upload public/build')

activation_block = deploy_job[activation_position:]
deploy_command = 'bash /var/www/pkgenerus.my.id/releases/$RELEASE_NAME/scripts/vps/deploy-release.sh $RELEASE_NAME'
reload_command = 'sudo -n systemctl reload php8.2-fpm'
deploy_position = activation_block.find(deploy_command)
reload_position = activation_block.find(reload_command)
if deploy_position < 0 or reload_position < 0 or deploy_position >= reload_position:
    raise SystemExit('production workflow must reload PHP-FPM only after deploy-release succeeds')
if '&&' not in activation_block[deploy_position:reload_position]:
    raise SystemExit('PHP-FPM reload must be conditional on successful deploy-release')
if 'systemctl restart php8.2-fpm' in activation_block:
    raise SystemExit('production workflow must reload PHP-FPM, not restart it')

if 'node scripts/vps/verify-vite-manifest-assets.mjs public/build' not in deploy_script:
    raise SystemExit('release activation must verify manifest assets after its server build')
PY

echo "production deploy static checks passed"
