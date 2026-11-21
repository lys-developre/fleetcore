# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es/1.1.0/). Las versiones siguen los hitos del plan de entregas.

## [Unreleased] — H0 Cimientos
### Added
- Monorepo con `apps/api` (Symfony 7.4, PHP 8.4) y `apps/web` (Next.js 16, TypeScript, Tailwind).
- Stack de desarrollo en Docker Compose: PostgreSQL 17, API, worker, Gotenberg, web y Caddy como único punto de entrada.
- Dockerfiles multi-stage con targets `dev` y `prod` para ambas aplicaciones.
- Endpoint `GET /api/health` con comprobación de base de datos y página de estado en el frontend.
- CI en GitHub Actions: validación de Composer, lint de contenedor, PHPStan, php-cs-fixer, PHPUnit, ESLint, tsc, Vitest, build de Next.js y construcción de imágenes de producción.
- `Makefile` y `dev.sh` con los comandos de desarrollo.
- Documentación de descubrimiento completa, modelo entidad-relación, arquitectura, plan por hitos, ADR 001 a 005 y glosario.
