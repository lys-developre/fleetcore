# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/). Las versiones siguen los hitos del plan de entregas.

## [Unreleased] — H1 Identidad
### Added
- Módulo `Identity`: usuarios con roles (tabla `app_user_role`), transiciones de estado y bloqueo progresivo tras intentos fallidos (5 fallos, 15 minutos doblando hasta 4 horas).
- Tokens opacos por dispositivo (ADR 002): caducidad deslizante de 8 horas en escritorio y 30 días en móvil, límite de 3/2 dispositivos y cierre remoto de sesiones.
- Activación de cuenta (72 h) y recuperación de contraseña (30 min) con enlaces por SMS; respuesta idéntica exista o no el teléfono.
- Política de contraseñas: 10+ caracteres, no filtradas (Have I Been Pwned), sin el nombre ni el teléfono del usuario.
- `UserVoter` con la matriz de permisos 5.4; toda denegación queda auditada. `audit_log` de solo inserción y triggers que garantizan al menos un administrador activo.
- Módulo `Notification`: envío de SMS por el worker de Messenger con remitente sandbox o Twilio, y bandeja de sandbox en `GET /api/sms-sandbox`.
- Rate limiters en login, recuperación y enlaces; formato único de error JSON para toda la API.
- Comando `app:user:create-admin` para la primera cuenta y fixtures de desarrollo con una cuenta por rol.
- 199 pruebas nuevas: unitarias (matriz del voter celda a celda), de integración (triggers, throttling, SMS) y funcionales (HTTP).

### Pendiente del hito
- Pantallas de acceso en el frontend con BFF y cookie HttpOnly, ADR 006, recorrido Playwright de activación. Detalle en [`docs/estado.md`](docs/estado.md).

## [H0] Cimientos — 2026-11-24
### Added
- Monorepo con `apps/api` (Symfony 7.4, PHP 8.4) y `apps/web` (Next.js 16, TypeScript, Tailwind).
- Stack de desarrollo en Docker Compose: PostgreSQL 17, API, worker, Gotenberg, web y Caddy como único punto de entrada.
- Dockerfiles multi-stage con targets `dev` y `prod` para ambas aplicaciones.
- Endpoint `GET /api/health` con comprobación de base de datos y página de estado en el frontend.
- CI en GitHub Actions: validación de Composer, lint de contenedor, PHPStan, php-cs-fixer, PHPUnit, ESLint, tsc, Vitest, build de Next.js y construcción de imágenes de producción.
- Pruebas de la API en tres suites (unit, integration con PostgreSQL real, functional por HTTP) y comprobación de que el esquema migrado coincide con los mapeos de Doctrine.
- Smoke de Playwright en escritorio y móvil: contra el stack de desarrollo (`./dev.sh e2e`) y, en CI, contra las imágenes de producción antes de publicarlas.
- `Makefile` y `dev.sh` con los comandos de desarrollo.

- Documentación de descubrimiento completa, modelo entidad-relación, arquitectura, plan por hitos, ADR 001 a 005 y glosario.

### Fixed
- En producción, la página de estado mostraba la API como «Sin respuesta»: la llamada interna de Next.js a `http://caddy/api` recibía una redirección a HTTPS. Caddy expone ahora la API en un puerto interno (`:8081`) que no se publica fuera de la red de Compose.
