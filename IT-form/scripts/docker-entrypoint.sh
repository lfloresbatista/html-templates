#!/bin/bash
set -euo pipefail
cd /var/www/html

mkdir -p storage/db storage/logs uploads

# .htaccess de volúmenes vacíos
if [ ! -f storage/.htaccess ]; then
  printf 'Require all denied\n' > storage/.htaccess 2>/dev/null || true
fi
if [ ! -f uploads/.htaccess ]; then
  cat > uploads/.htaccess <<'EOF' 2>/dev/null || true
Options -Indexes
<FilesMatch "\.(?i:png|jpe?g|gif|webp)$">
    Require all granted
</FilesMatch>
<FilesMatch "(?i)\.(php|phtml|phar|cgi|pl|py|sh)$">
    Require all denied
</FilesMatch>
EOF
fi

# Migración automática de tablas (idempotente)
AUTO="${ITFORM_AUTO_MIGRATE:-1}"
if [ "$AUTO" = "1" ] || [ "$AUTO" = "true" ]; then
  echo "[itform] running database migrate..."
  # Reintentos si MySQL externo aún no acepta conexiones
  tries=0
  max_tries="${ITFORM_MIGRATE_RETRIES:-30}"
  until php database/migrate.php; do
    tries=$((tries + 1))
    if [ "$tries" -ge "$max_tries" ]; then
      echo "[itform] migrate failed after ${max_tries} attempts" >&2
      exit 1
    fi
    echo "[itform] DB not ready, retry ${tries}/${max_tries}..."
    sleep 2
  done
fi

exec "$@"
