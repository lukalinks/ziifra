#!/usr/bin/env bash
# Only fix .env database lines — run: sudo bash deploy/write-env-db.sh
set -euo pipefail
cd "${APP_DIR:-/var/www/ziifra}"
DB_PASS="$(tr -d '\n' < /tmp/ziifra-db-pass 2>/dev/null || openssl rand -base64 24)"
grep -q '^DB_PASSWORD=' .env && awk -v p="$DB_PASS" 'BEGIN{q=sprintf("%c",39)} /^DB_PASSWORD=/{print "DB_PASSWORD=" q p q; next} {print}' .env > .env.tmp && mv .env.tmp .env || echo "DB_PASSWORD='${DB_PASS}'" >> .env
for kv in "DB_CONNECTION=pgsql" "DB_HOST=127.0.0.1" "DB_PORT=5432" "DB_DATABASE=ziifra" "DB_USERNAME=ziifra"; do
  k="${kv%%=*}"; v="${kv#*=}"
  grep -q "^${k}=" .env && sed -i "s|^${k}=.*|${k}=${v}|" .env || echo "${k}=${v}" >> .env
done
php artisan config:clear
echo "DB fixed. Password in /tmp/ziifra-db-pass"
