#!/usr/bin/env bash
# Deploy or update FleetCore on the server. Run from the repository checkout on the server.
# Requires: .env.prod next to compose.prod.yaml with POSTGRES_*, APP_SECRET, DOMAIN, ACME_EMAIL, IMAGE_TAG.
set -euo pipefail
cd "$(dirname "$0")/../.."

COMPOSE="docker compose -f compose.prod.yaml --env-file .env.prod"

echo "==> Pulling images"
$COMPOSE pull

echo "==> Starting database"
$COMPOSE up -d db
$COMPOSE exec -T db sh -c 'until pg_isready -U "$POSTGRES_USER" -d "$POSTGRES_DB"; do sleep 1; done'

echo "==> Running migrations"
$COMPOSE run --rm --no-deps api php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Refreshing public assets shared by api and caddy"
# A named volume is only seeded from the image when empty; recreate it so a new image's public/ is used
$COMPOSE rm -sf api caddy
docker volume rm -f fleetcore_api_public >/dev/null

echo "==> Starting services"
$COMPOSE up -d --remove-orphans

echo "==> Pruning old images"
docker image prune -f >/dev/null

echo "==> Health"
sleep 3
curl -fsS "https://${DOMAIN:-$(grep -E '^DOMAIN=' .env.prod | cut -d= -f2)}/api/health" && echo
