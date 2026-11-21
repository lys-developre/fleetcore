# ADR 005 — Todo en Docker: desarrollo, CI y producción con las mismas imágenes

**Fecha:** 2026-11-21 · **Estado:** aceptada

## Contexto
Un desarrollador solo, en Windows, con PHP 8.2 local mientras el objetivo es PHP 8.4. El proyecto debe desplegarse en un servidor virtual pequeño y ser reproducible por cualquiera que clone el repositorio.

## Decisión
Los Dockerfiles de `apps/api` y `apps/web` tienen un target `dev` (código montado, dependencias instaladas dentro del contenedor) y un target `prod` (imagen inmutable). `compose.yaml` levanta PostgreSQL, API, worker, Gotenberg, web y Caddy. La CI construye los mismos targets `prod` que se despliegan. En la máquina del desarrollador solo hacen falta Docker y Git; Composer y pnpm se ejecutan dentro de los contenedores mediante `make` o `./dev.sh`.

## Consecuencias
- Sin diferencias entre "funciona en mi máquina" y el servidor.
- Montajes de archivos en Windows son más lentos; se mitiga con volúmenes con nombre para `vendor`, `node_modules` y `.next`.
- Caddy y el contenedor de PHP comparten la ruta `/app/public` para que `php_fastcgi` funcione sin configuración extra.
