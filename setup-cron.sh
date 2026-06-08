#!/bin/bash
# =============================================================================
# Install Laravel scheduler cron (backups + recurring expenses)
# Run as the SITE USER (menuiserie), not root.
#
# Usage:
#   chmod +x setup-cron.sh
#   ./setup-cron.sh
#   # or: SITE_DIR=/home/menuiserie/htdocs/domain ./setup-cron.sh
# =============================================================================

set -euo pipefail

SITE_DIR="${SITE_DIR:-}"

if [ -z "$SITE_DIR" ]; then
    if [ -d "$HOME/htdocs" ]; then
        DOMAIN=$(ls -1 "$HOME/htdocs" 2>/dev/null | head -1)
        SITE_DIR="$HOME/htdocs/$DOMAIN"
    else
        echo "Set SITE_DIR=/path/to/project"
        exit 1
    fi
fi

if [ ! -f "$SITE_DIR/artisan" ]; then
    echo "artisan not found in $SITE_DIR"
    exit 1
fi

CRON_LINE="* * * * * cd $SITE_DIR && php artisan schedule:run >> /dev/null 2>&1"

# Remove old schedule:run lines, add fresh one
( crontab -l 2>/dev/null | grep -v "artisan schedule:run" || true
  echo "$CRON_LINE"
) | crontab -

echo "[OK] Cron installed for: $SITE_DIR"
echo ""
crontab -l | grep "schedule:run"
echo ""
echo "Scheduled tasks:"
echo "  - backup:clean  → daily 01:00"
echo "  - backup:run    → daily 01:30"
echo "  - expenses:process-recurring → 1st of month"
