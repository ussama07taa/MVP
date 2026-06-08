#!/bin/bash
# =============================================================================
# Menuiserie ERP — CloudPanel Deployment Script
# Run this script as the SITE USER (not root) via SSH after creating the site.
#
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh
#
# Prerequisites:
#   - CloudPanel PHP site created (Laravel template or Generic + /public root)
#   - MySQL database created in CloudPanel
#   - .env file configured (copy from .env.production.example)
# =============================================================================

set -euo pipefail

# --- Configuration (edit these before running) ---
DOMAIN="${DOMAIN:-}"                          # e.g. erp.example.com
DB_NAME="${DB_NAME:-menuiserie_erp}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
GIT_REPO="${GIT_REPO:-https://github.com/ussama07taa/MVP.git}"
GIT_BRANCH="${GIT_BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"                     # e.g. php8.3 if multiple versions

# --- Colors ---
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log()  { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
err()  { echo -e "${RED}[✗]${NC} $1"; exit 1; }

# --- Detect site directory ---
if [ -z "$DOMAIN" ]; then
    # Try to auto-detect from htdocs
    if [ -d "$HOME/htdocs" ]; then
        DOMAIN=$(ls -1 "$HOME/htdocs" 2>/dev/null | head -1)
        warn "DOMAIN not set, auto-detected: $DOMAIN"
    else
        err "Set DOMAIN env var: DOMAIN=erp.example.com ./deploy.sh"
    fi
fi

SITE_DIR="$HOME/htdocs/$DOMAIN"
cd "$HOME/htdocs"

# --- Step 1: Clone or pull project ---
if [ -d "$SITE_DIR/.git" ]; then
    log "Pulling latest changes..."
    cd "$SITE_DIR"
    git pull origin "$GIT_BRANCH"
else
    log "Cloning project..."
    rm -rf "$SITE_DIR"
    git clone -b "$GIT_BRANCH" "$GIT_REPO" "$SITE_DIR"
    cd "$SITE_DIR"
fi

# --- Step 2: Install PHP dependencies ---
log "Installing Composer dependencies..."
$PHP_BIN /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction

# --- Step 3: Environment file ---
if [ ! -f .env ]; then
    if [ -f .env.production.example ]; then
        cp .env.production.example .env
        warn "Created .env from .env.production.example — EDIT IT before continuing!"
        warn "Set: APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
        err "Edit .env then re-run this script."
    else
        err ".env file missing. Create it from .env.production.example"
    fi
fi

# Generate key if empty
if ! grep -q "APP_KEY=base64:" .env; then
    log "Generating APP_KEY..."
    $PHP_BIN artisan key:generate --force
fi

# --- Step 4: Build frontend ---
if command -v npm &>/dev/null; then
    log "Building frontend assets..."
    npm ci --production=false
    npm run build
else
    warn "npm not found — make sure public/build/ exists (build locally if needed)"
fi

# --- Step 5: Laravel setup ---
log "Running migrations..."
$PHP_BIN artisan migrate --force

log "Seeding database (admin user)..."
$PHP_BIN artisan db:seed --force

log "Creating storage link..."
$PHP_BIN artisan storage:link --force 2>/dev/null || true

log "Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R "$(whoami):$(whoami)" storage bootstrap/cache 2>/dev/null || true

log "Optimizing for production..."
$PHP_BIN artisan optimize:clear
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

echo ""
log "Deployment complete!"
echo ""
echo "  URL:   https://$DOMAIN"
echo "  Admin: admin@taaouati.com / password"
echo ""
warn "Change the admin password after first login!"
warn "Ensure CloudPanel Root Directory is set to: $DOMAIN/public"
