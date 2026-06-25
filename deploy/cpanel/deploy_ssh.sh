#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${APP_ROOT:-$HOME/pembinaan-karakter-generus}"
PUBLIC_ROOT="${PUBLIC_ROOT:-$HOME/public_html}"

cd "$APP_ROOT"

php_cmd=""
for candidate in \
  "/opt/alt/php82/usr/bin/php" \
  "/opt/cpanel/ea-php82/root/usr/bin/php" \
  "php82" \
  "php"
do
  if [ -x "$candidate" ]; then
    candidate_path="$candidate"
  elif command -v "$candidate" >/dev/null 2>&1; then
    candidate_path="$(command -v "$candidate")"
  else
    continue
  fi

  php_version_id="$("$candidate_path" -r 'echo PHP_VERSION_ID;' 2>/dev/null || echo 0)"
  if [ "$php_version_id" -ge 80200 ]; then
    php_cmd="$candidate_path"
    break
  fi
done

if [ -z "$php_cmd" ]; then
  echo "PHP CLI 8.2 tidak ditemukan."
  echo "Coba cek path PHP 8.2 di cPanel, misalnya /opt/alt/php82/usr/bin/php atau /opt/cpanel/ea-php82/root/usr/bin/php."
  exit 1
fi

echo "Memakai PHP CLI: $php_cmd ($("$php_cmd" -r 'echo PHP_VERSION;'))"

composer_cmd=""
if command -v composer >/dev/null 2>&1; then
  composer_cmd="composer"
elif command -v composer2 >/dev/null 2>&1; then
  composer_cmd="composer2"
elif [ -x "/opt/cpanel/composer/bin/composer" ]; then
  composer_cmd="/opt/cpanel/composer/bin/composer"
elif [ -x "$HOME/bin/composer" ]; then
  composer_cmd="$HOME/bin/composer"
fi

echo "Pull source terbaru..."
git pull --ff-only origin main

if [ -n "$composer_cmd" ]; then
  echo "Install dependency PHP production..."
  "$composer_cmd" install --no-dev --optimize-autoloader
elif [ -f "$APP_ROOT/vendor/autoload.php" ]; then
  echo "Composer tidak tersedia; memakai vendor yang sudah ada."
else
  echo "Composer tidak tersedia dan vendor/autoload.php belum ada."
  echo "Upload vendor.zip dari lokal lalu extract ke $APP_ROOT/vendor, atau install Composer lokal."
  exit 1
fi

echo "Rapikan permission vendor dan cache Laravel..."
if [ -d "$APP_ROOT/vendor" ]; then
  find "$APP_ROOT/vendor" -type d -exec chmod 755 {} +
  find "$APP_ROOT/vendor" -type f -exec chmod 644 {} +
fi

mkdir -p \
  "$APP_ROOT/storage/logs" \
  "$APP_ROOT/storage/framework/cache/data" \
  "$APP_ROOT/storage/framework/sessions" \
  "$APP_ROOT/storage/framework/views" \
  "$APP_ROOT/bootstrap/cache"

chmod -R u+rwX,go+rwX "$APP_ROOT/storage" "$APP_ROOT/bootstrap/cache"

echo "Salin asset public ke public_html..."
mkdir -p "$PUBLIC_ROOT"

htaccess_backup=""
if [ -f "$PUBLIC_ROOT/.htaccess" ]; then
  htaccess_backup="$(mktemp)"
  cp "$PUBLIC_ROOT/.htaccess" "$htaccess_backup"
fi

if command -v rsync >/dev/null 2>&1; then
  rsync -a \
    --exclude='/.htaccess' \
    --exclude='/storage' \
    --exclude='/hot' \
    --exclude='/.well-known' \
    --exclude='/cgi-bin' \
    "$APP_ROOT/public/" "$PUBLIC_ROOT/"
else
  cp -a "$APP_ROOT/public/." "$PUBLIC_ROOT/"
  rm -f "$PUBLIC_ROOT/hot"
fi

cat > "$PUBLIC_ROOT/.htaccess" <<'HTACCESS'
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
HTACCESS

handler_written=0
if [ -n "$htaccess_backup" ]; then
  cpanel_handler_block="$(awk '
    /# php -- BEGIN cPanel-generated handler/ { in_block=1 }
    in_block { print }
    /# php -- END cPanel-generated handler/ { in_block=0 }
  ' "$htaccess_backup")"

  if [ -n "$cpanel_handler_block" ]; then
    {
      echo
      printf '%s\n' "$cpanel_handler_block"
    } >> "$PUBLIC_ROOT/.htaccess"
    handler_written=1
  elif grep -Eq 'x-httpd-(ea|alt)-php|ea-php[0-9]+|alt-php[0-9]+' "$htaccess_backup"; then
    {
      echo
      echo '<IfModule mime_module>'
      grep -E 'AddHandler .*x-httpd-(ea|alt)-php|SetHandler .*php' "$htaccess_backup" || true
      echo '</IfModule>'
    } >> "$PUBLIC_ROOT/.htaccess"
    handler_written=1
  fi

  rm -f "$htaccess_backup"
fi

if [ "$handler_written" -eq 0 ] && "$php_cmd" -r 'exit(PHP_VERSION_ID >= 80200 && PHP_VERSION_ID < 80300 ? 0 : 1);'; then
  cat >> "$PUBLIC_ROOT/.htaccess" <<'HTACCESS'

# php -- BEGIN cPanel-generated handler, do not edit
# Set the alt-php82 package as the default PHP programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-alt-php82 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
HTACCESS
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
"$php_cmd" artisan optimize:clear
"$php_cmd" artisan config:cache
"$php_cmd" artisan route:cache
"$php_cmd" artisan view:cache

echo "Deploy selesai."
