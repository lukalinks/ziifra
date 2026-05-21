#!/usr/bin/env bash
# ZIIFRA — one-time VPS bootstrap (Ubuntu 22.04/24.04 on Hostinger or similar)
# Run as root: sudo bash deploy/setup-server.sh
set -euo pipefail

APP_DOMAIN="${APP_DOMAIN:-app.ziifra.com}"
APP_DIR="${APP_DIR:-/var/www/ziifra}"
DB_NAME="${DB_NAME:-ziifra}"
DB_USER="${DB_USER:-ziifra}"
DEPLOY_USER="${DEPLOY_USER:-deploy}"

echo "==> ZIIFRA server setup"
echo "    Domain:  ${APP_DOMAIN}"
echo "    App dir: ${APP_DIR}"

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq \
  nginx \
  postgresql postgresql-contrib \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-opcache \
  supervisor certbot python3-certbot-nginx \
  git unzip curl

# Composer
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Node.js 20 (build assets during deploy only)
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y -qq nodejs
fi

# App directory
mkdir -p "${APP_DIR}"
chown -R www-data:www-data "${APP_DIR}"

# Deploy user (SSH git pull)
if ! id "${DEPLOY_USER}" &>/dev/null; then
  useradd -m -s /bin/bash "${DEPLOY_USER}"
  usermod -aG www-data "${DEPLOY_USER}"
fi

# PostgreSQL
DB_PASS="${DB_PASS:-$(openssl rand -base64 24)}"
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1; then
  sudo -u postgres psql -c "CREATE USER ${DB_USER} WITH PASSWORD '${DB_PASS}';"
fi
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='${DB_NAME}'" | grep -q 1; then
  sudo -u postgres psql -c "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER};"
fi
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${DB_NAME} TO ${DB_USER};"

echo ""
echo "PostgreSQL credentials (save in .env):"
echo "  DB_DATABASE=${DB_NAME}"
echo "  DB_USERNAME=${DB_USER}"
echo "  DB_PASSWORD=${DB_PASS}"
echo "${DB_PASS}" > /tmp/ziifra-db-pass
sudo -u postgres psql -d "${DB_NAME}" -c "GRANT ALL ON SCHEMA public TO ${DB_USER};" 2>/dev/null || true
if [[ -f "${APP_DIR}/deploy/.env.production.example" && ! -f "${APP_DIR}/.env" ]]; then
  cp "${APP_DIR}/deploy/.env.production.example" "${APP_DIR}/.env"
fi
if [[ -f "${APP_DIR}/.env" ]]; then
  sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_NAME}|" "${APP_DIR}/.env"
  sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${DB_USER}|" "${APP_DIR}/.env"
  awk -v pass="$DB_PASS" 'BEGIN{q=sprintf("%c",39)} /^DB_PASSWORD=/{print "DB_PASSWORD=" q pass q; next} {print}' "${APP_DIR}/.env" > "${APP_DIR}/.env.tmp"
  mv "${APP_DIR}/.env.tmp" "${APP_DIR}/.env"
  sed -i "s|^APP_URL=.*|APP_URL=http://${APP_DOMAIN}|" "${APP_DIR}/.env"
fi
mkdir -p "${APP_DIR}/storage/logs" "${APP_DIR}/storage/framework/"{cache,sessions,views} "${APP_DIR}/bootstrap/cache"

# PHP-FPM tuning for Laravel
PHP_INI="/etc/php/8.3/fpm/php.ini"
sed -i 's/^;*upload_max_filesize.*/upload_max_filesize = 32M/' "${PHP_INI}" || true
sed -i 's/^;*post_max_size.*/post_max_size = 32M/' "${PHP_INI}" || true
sed -i 's/^;*memory_limit.*/memory_limit = 256M/' "${PHP_INI}" || true
systemctl restart php8.3-fpm

# Nginx site
NGINX_SITE="/etc/nginx/sites-available/ziifra"
sed -e "s|APP_DOMAIN|${APP_DOMAIN}|g" -e "s|APP_ROOT|${APP_DIR}|g" \
  "$(dirname "$0")/nginx/ziifra.conf" > "${NGINX_SITE}"
ln -sf "${NGINX_SITE}" /etc/nginx/sites-enabled/ziifra
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# Supervisor queue worker
SUP_CONF="/etc/supervisor/conf.d/ziifra-worker.conf"
sed -e "s|APP_ROOT|${APP_DIR}|g" "$(dirname "$0")/supervisor/ziifra-worker.conf" > "${SUP_CONF}"
supervisorctl reread 2>/dev/null || true
supervisorctl update 2>/dev/null || true

# Cron — Laravel scheduler
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
( crontab -u www-data -l 2>/dev/null | grep -v 'schedule:run' || true; echo "${CRON_LINE}" ) | crontab -u www-data -

# Permissions helper
cat > /usr/local/bin/ziifra-fix-perms <<'PERMS'
#!/usr/bin/env bash
APP_DIR="${1:-/var/www/ziifra}"
chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
chmod -R ug+rwx "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
PERMS
chmod +x /usr/local/bin/ziifra-fix-perms

echo ""
echo "==> Base stack ready."
echo "Next steps:"
echo "  1. Clone repo to ${APP_DIR} (as ${DEPLOY_USER})"
echo "  2. cp deploy/.env.production.example ${APP_DIR}/.env && edit secrets"
echo "  3. bash deploy/deploy.sh"
echo "  4. certbot --nginx -d ${APP_DOMAIN}"
