#!/usr/bin/env bash
# Nightly backup: PostgreSQL dump + uploads volume, encrypted, 30-day retention.
# Schedule with cron on the server:  15 3 * * * /opt/fleetcore/infra/scripts/backup.sh >> /var/log/fleetcore-backup.log 2>&1
# Requires BACKUP_DIR and BACKUP_PASSPHRASE in .env.prod. Ship $BACKUP_DIR to external storage (rclone, restic) separately.
set -euo pipefail
cd "$(dirname "$0")/../.."
set -a; source .env.prod; set +a

COMPOSE="docker compose -f compose.prod.yaml --env-file .env.prod"
STAMP="$(date +%Y%m%d-%H%M%S)"
DEST="${BACKUP_DIR:-/var/backups/fleetcore}"
mkdir -p "$DEST"

echo "==> Database dump"
$COMPOSE exec -T db pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom \
  | gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase "$BACKUP_PASSPHRASE" \
  > "$DEST/db-$STAMP.dump.gpg"

echo "==> Uploads"
docker run --rm -v fleetcore_uploads:/data:ro alpine tar -C /data -czf - . \
  | gpg --batch --yes --symmetric --cipher-algo AES256 --passphrase "$BACKUP_PASSPHRASE" \
  > "$DEST/uploads-$STAMP.tar.gz.gpg"

echo "==> Retention (30 days)"
find "$DEST" -type f -name '*.gpg' -mtime +30 -delete

echo "==> Done: $(ls -1 "$DEST" | wc -l) files in $DEST"
