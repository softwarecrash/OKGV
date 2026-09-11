#!/usr/bin/env sh
set -eu

PROJECT_ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$PROJECT_ROOT"

if [ -n "${OKGV_PHP_BINARY:-}" ]; then
    PHP_BIN="$OKGV_PHP_BINARY"
elif command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
elif [ -x /opt/plesk/php/8.3/bin/php ]; then
    PHP_BIN="/opt/plesk/php/8.3/bin/php"
elif [ -x /opt/plesk/php/8.4/bin/php ]; then
    PHP_BIN="/opt/plesk/php/8.4/bin/php"
else
    echo "No PHP binary found. Set OKGV_PHP_BINARY to the Plesk PHP path." >&2
    exit 1
fi

restore_application() {
    "$PHP_BIN" artisan up || true
}

"$PHP_BIN" artisan down --retry=60
trap restore_application EXIT INT TERM
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan okgv:deploy --skip-clear
"$PHP_BIN" artisan up
trap - EXIT INT TERM

echo "OKGV production deployment completed."
