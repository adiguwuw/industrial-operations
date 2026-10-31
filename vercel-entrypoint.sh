#!/bin/sh
set -eu

storage_root="${LARAVEL_STORAGE_PATH:-/tmp/laravel-storage}"

mkdir -p \
    "$storage_root/app/private" \
    "$storage_root/app/public" \
    "$storage_root/framework/cache/data" \
    "$storage_root/framework/sessions" \
    "$storage_root/framework/views" \
    "$storage_root/logs" \
    /tmp/caddy

if [ -n "${MYSQL_CA_CERT_BASE64:-}" ]; then
    printf '%s' "$MYSQL_CA_CERT_BASE64" | base64 -d > /tmp/aiven-ca.pem
    chmod 0444 /tmp/aiven-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem
fi

exec "$@"
