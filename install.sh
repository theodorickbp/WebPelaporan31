#!/usr/bin/env bash
# Memasang SI-PASAR di atas Laravel baru.
# Pemakaian:  ./install.sh [nama-folder]      (default: sipasar)
# Opsional:   ADMIN_EMAIL=toolman@sekolah.sch.id ADMIN_PASSWORD='rahasia-kuat' ./install.sh
set -euo pipefail

APP_DIR="${1:-sipasar}"
SRC="$(cd "$(dirname "$0")" && pwd)"

command -v php >/dev/null || { echo "PHP belum terpasang (butuh 8.2+)."; exit 1; }
command -v composer >/dev/null || { echo "Composer belum terpasang."; exit 1; }

if [ -e "$APP_DIR" ]; then
  echo "Folder '$APP_DIR' sudah ada. Pilih nama lain atau hapus dulu."; exit 1
fi

echo ">> Membuat proyek Laravel di $APP_DIR ..."
composer create-project laravel/laravel "$APP_DIR" --prefer-dist

echo ">> Menyalin kode SI-PASAR ..."
for d in app config database lang public resources routes tests; do
  cp -R "$SRC/$d" "$APP_DIR/"
done

cd "$APP_DIR"

set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" .env; then
    sed -i.bak "s|^${key}=.*|${key}=${val}|" .env
  else
    printf '%s=%s\n' "$key" "$val" >> .env
  fi
}

ADMIN_NAME="${ADMIN_NAME:-Toolman}"
ADMIN_EMAIL="${ADMIN_EMAIL:-toolman@sipasar.test}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-$(php -r 'echo bin2hex(random_bytes(6));')}"

set_env APP_NAME '"SI-PASAR"'
set_env APP_LOCALE id
set_env APP_TIMEZONE Asia/Jakarta
set_env SESSION_DRIVER database
set_env ADMIN_NAME "\"$ADMIN_NAME\""
set_env ADMIN_EMAIL "$ADMIN_EMAIL"
set_env ADMIN_PASSWORD "\"$ADMIN_PASSWORD\""
set_env SIPASAR_OVERDUE_HOURS 48
rm -f .env.bak

if command -v npm >/dev/null; then
  echo ">> Node ditemukan, membangun ulang CSS/JS terminifikasi ..."
  npm install --no-audit --no-fund >/dev/null 2>&1 && npm run build || echo "   (build dilewati, file .min.* bawaan tetap dipakai)"
else
  echo ">> Node tidak ditemukan - memakai file .min.* yang sudah tersedia (tidak wajib Node di server produksi)."
fi

echo ">> Migrasi database dan membuat akun Toolman ..."
php artisan migrate --force --seed

cat <<MSG

Selesai.

  Jalankan   : php artisan serve --host=0.0.0.0
  Toolman    : $ADMIN_EMAIL
  Kata sandi : $ADMIN_PASSWORD   (segera ganti lewat menu Profil)

  Tes        : php artisan test
  Data demo  : php artisan db:seed --class=DemoSeeder
MSG
