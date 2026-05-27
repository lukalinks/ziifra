#!/usr/bin/env bash
# ZIIFRA — pull latest code from GitHub and deploy (run on VPS)
# Usage:
#   bash deploy/pull-deploy.sh
#   RUN_MIGRATIONS=0 bash deploy/pull-deploy.sh   # skip migrations
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "${APP_DIR}"
REPO_URL="${REPO_URL:-https://github.com/lukalinks/ziifra.git}"
BRANCH="${BRANCH:-main}"

echo "==> ZIIFRA git deploy (${APP_DIR})"

if [[ ! -d .git ]]; then
  echo "ERROR: ${APP_DIR} is not a git repository."
  echo "       Clone first: git clone ${REPO_URL} ${APP_DIR}"
  exit 1
fi

if ! git remote get-url origin >/dev/null 2>&1; then
  git remote add origin "${REPO_URL}"
fi

echo "==> Fetching ${BRANCH} from origin"
git fetch origin "${BRANCH}"
git checkout "${BRANCH}" 2>/dev/null || git checkout -b "${BRANCH}" "origin/${BRANCH}"
git reset --hard "origin/${BRANCH}"

if [[ "${RUN_MIGRATIONS:-1}" == "1" ]]; then
  export RUN_MIGRATIONS=1
  bash deploy/release.sh
else
  export RUN_MIGRATIONS=0
  bash deploy/release.sh
fi

echo "==> Git deploy finished"
