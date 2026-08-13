#!/usr/bin/env bash
# Local HTTPS for *.lms.local dev domains.
#
# Run `composer run dev` (or `php artisan serve --port 80`) in one terminal,
# then this script in another to get HTTPS on port 443, forwarding to it.
# Certs are generated once via mkcert; see docs/local-https.md.

set -euo pipefail
cd "$(dirname "$0")/.."

CERT_DIR=".cert"
CERT_FILE="$CERT_DIR"/school.lms.local+5.pem
KEY_FILE="$CERT_DIR"/school.lms.local+5-key.pem

if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    echo "Certificate not found at $CERT_FILE — see docs/local-https.md to generate it." >&2
    exit 1
fi

npx --yes local-ssl-proxy --source 443 --target 80 --cert "$CERT_FILE" --key "$KEY_FILE"
