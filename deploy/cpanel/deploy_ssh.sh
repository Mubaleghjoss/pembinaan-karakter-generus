#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${APP_ROOT:-$HOME/pembinaan-karakter-generus}"
PUBLIC_ROOT="${PUBLIC_ROOT:-$HOME/public_html}"

cd "$APP_ROOT"

echo "Pull source terbaru..."
git pull --ff-only origin main

echo "Install dependency PHP production..."
composer install --no-dev --optimize-autoloader

echo "Salin asset public ke public_html..."
mkdir -p "$PUBLIC_ROOT"

if command -v rsync >/dev/null 2>&1; then
  rsync -a \
    --exclude='/storage' \
    --exclude='/hot' \
    --exclude='/.well-known' \
    --exclude='/cgi-bin' \
    "$APP_ROOT/public/" "$PUBLIC_ROOT/"
else
  cp -a "$APP_ROOT/public/." "$PUBLIC_ROOT/"
  rm -f "$PUBLIC_ROOT/hot"
fi

cp "$APP_ROOT/deploy/cpanel/public_html_index.pembinaan-karakter-generus.php.example" "$PUBLIC_ROOT/index.php"

echo "Pastikan folder upload publik tersedia..."
mkdir -p \
  "$PUBLIC_ROOT/storage/logos" \
  "$PUBLIC_ROOT/storage/avatars" \
  "$PUBLIC_ROOT/storage/berita" \
  "$PUBLIC_ROOT/storage/materi" \
  "$PUBLIC_ROOT/storage/photos" \
  "$PUBLIC_ROOT/storage/qrcodes" \
  "$PUBLIC_ROOT/storage/reward-templates" \
  "$PUBLIC_ROOT/storage/chat" \
  "$PUBLIC_ROOT/storage/logs" \
  "$PUBLIC_ROOT/storage/certificates" \
  "$PUBLIC_ROOT/storage/pr-submissions" \
  "$PUBLIC_ROOT/storage/siswa" \
  "$PUBLIC_ROOT/storage/tugas-bukti" \
  "$PUBLIC_ROOT/storage/tugas-bukti-audio"

rm -f "$PUBLIC_ROOT/hot"

echo "Refresh cache Laravel..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Deploy selesai."
