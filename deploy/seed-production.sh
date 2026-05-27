#!/usr/bin/env bash
# Seed demo workspace + platform admin on production (one-time / safe to re-run)
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "${APP_DIR}"

ADMIN_PASSWORD="${ADMIN_PASSWORD:-ZiifraAdmin2026@Live}"

php artisan config:clear
php artisan ziifra:grant-super-admin --create
php artisan db:seed --class=Database\\Seeders\\DemoDataSeeder --force

php artisan tinker --execute="
\$hash = Illuminate\Support\Facades\Hash::make('${ADMIN_PASSWORD}');
App\Models\User::query()->where('email', 'admin@ziifra.com')->update(['password' => \$hash]);
echo 'admin@ziifra.com password updated'.PHP_EOL;
"

php artisan config:cache
php artisan route:cache

echo ""
echo "Platform admin: admin@ziifra.com / ${ADMIN_PASSWORD}  -> /admin"
echo "Demo owner:     owner@demo.test / password            -> workspace"
echo "Demo HR:        hr@demo.test / password"
echo "Demo employee:  employee@demo.test / password"
