#!/usr/bin/env bash
set -euo pipefail
cd /app
# Install vendors on first start (volume may be empty) and keep them in sync with the lock file.
if [ ! -f vendor/autoload_runtime.php ] || [ composer.lock -nt vendor/autoload_runtime.php ]; then
  composer install --no-interaction --no-progress --prefer-dist
fi
mkdir -p var/cache var/log
chmod -R a+rwX var || true
exec "$@"
