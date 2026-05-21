#!/usr/bin/env bash
# ZIIFRA — full remote install (run on VPS as root after uploading project to /var/www/ziifra)
# Usage: cd /var/www/ziifra && sudo bash deploy/install-remote.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ziifra}"
APP_DOMAIN="${APP_DOMAIN:-srv1682923.hstgr.cloud}"
cd "${APP_DIR}"

echo "==> ZIIFRA remote install (${APP_DIR})"

if [[ ! -f deploy/setup-server.sh ]]; then
  echo "Project files missing. Upload the app to ${APP_DIR} first."
  exit 1
fi

export APP_DOMAIN APP_DIR
bash deploy/setup-server.sh
bash deploy/fix-production.sh
