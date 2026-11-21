#!/usr/bin/env bash
# Same targets as the Makefile, for machines without make (Windows Git Bash).
# Usage: ./dev.sh up | down | logs | ps | sh-api | sh-web | composer <args> | console <args> | pnpm <args>
#        ./dev.sh migrate | fixtures | test | test-api | test-web | lint | fix | worker | reset
set -euo pipefail
cd "$(dirname "$0")"
if docker compose version >/dev/null 2>&1; then C="docker compose"; else C="docker-compose"; fi
cmd="${1:-help}"
shift || true
case "$cmd" in
  up)        $C up -d --build ;;
  down)      $C down ;;
  logs)      $C logs -f --tail=100 "$@" ;;
  ps)        $C ps ;;
  sh-api)    $C exec api bash ;;
  sh-web)    $C exec web sh ;;
  composer)  $C exec api composer "$@" ;;
  console)   $C exec api php bin/console "$@" ;;
  pnpm)      $C exec web pnpm "$@" ;;
  migrate)   $C exec api php bin/console doctrine:migrations:migrate --no-interaction ;;
  fixtures)
    $C exec api php bin/console doctrine:database:drop --force --if-exists
    $C exec api php bin/console doctrine:database:create
    $C exec api php bin/console doctrine:migrations:migrate --no-interaction
    $C exec api php bin/console doctrine:fixtures:load --no-interaction ;;
  test)      $C exec api php bin/phpunit; $C exec web pnpm test ;;
  test-api)  $C exec api php bin/phpunit "$@" ;;
  test-web)  $C exec web pnpm test "$@" ;;
  lint)
    $C exec api php bin/console cache:warmup --env=test
    $C exec api vendor/bin/phpstan analyse --memory-limit=512M
    $C exec api vendor/bin/php-cs-fixer fix --dry-run --diff
    $C exec web pnpm lint
    $C exec web pnpm typecheck ;;
  fix)       $C exec api vendor/bin/php-cs-fixer fix; $C exec web pnpm lint --fix ;;
  worker)    $C --profile worker up -d worker ;;
  reset)     $C down -v --remove-orphans ;;
  *)         sed -n '3,4p' "$0" ;;
esac
