#!/bin/sh
# Menyiapkan database lalu menyalakan server. Dipanggil sebagai ENTRYPOINT.
set -e
cd /var/www/html

# Kunci aplikasi: sebaiknya diisi lewat env (php artisan key:generate --show).
# Kalau kosong dibuat sementara, tapi login web akan terputus tiap restart.
if [ -z "$APP_KEY" ]; then
  export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
  echo "[brule] APP_KEY belum diisi, memakai kunci sementara."
fi
# Render menyediakan alamat publik layanan ini.
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

DB_FILE="${DB_DATABASE:-database/database.sqlite}"
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  mkdir -p "$(dirname "$DB_FILE")"
  [ -f "$DB_FILE" ] || touch "$DB_FILE"
fi

php artisan migrate --force

# Isi data contoh hanya kalau database masih kosong (SEED_DEMO=1).
if [ "${SEED_DEMO:-0}" = "1" ]; then
  if php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(App\Models\User::query()->exists() ? 0 : 1);'; then
    echo "[brule] data sudah ada, seed dilewati."
  else
    echo "[brule] mengisi data contoh..."
    php artisan db:seed --force
  fi
fi

php artisan storage:link >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}" --no-reload
