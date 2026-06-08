#!/bin/bash
# Quick update after git push — run as site user on server
set -euo pipefail

SITE_DIR="${SITE_DIR:-$(pwd)}"
cd "$SITE_DIR"

echo "[*] Pulling latest code..."
git pull origin main

echo "[*] Composer..."
php /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction

echo "[*] Frontend build..."
npm run build

echo "[*] Migrations..."
php artisan migrate --force

echo "[*] Cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[OK] Update complete — $(date)"
