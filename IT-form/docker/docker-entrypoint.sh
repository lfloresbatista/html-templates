#!/bin/bash
set -euo pipefail
cd /var/www/html

# Permisos runtime
mkdir -p storage/db storage/logs uploads
chown -R www-data:www-data storage uploads 2>/dev/null || true
chmod 750 storage storage/db storage/logs uploads 2>/dev/null || true

# Si no hay .env, copiar example (solo primera vez)
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

exec "$@"
