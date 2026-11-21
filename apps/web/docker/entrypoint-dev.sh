#!/bin/sh
set -e
cd /app
if [ ! -d node_modules/next ] || [ pnpm-lock.yaml -nt node_modules/.modules.yaml ]; then
  pnpm install --frozen-lockfile
fi
exec "$@"
