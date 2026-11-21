<p align="center"><strong>FleetCore</strong><br/>Albaranes digitales para empresas de excavación, demolición y maquinaria<br/><em>Digital delivery notes for excavation, demolition and heavy-machinery companies</em></p>

<p align="center">
  <a href="https://github.com/lys-developre/fleetcore/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/lys-developre/fleetcore/actions/workflows/ci.yml/badge.svg"></a>
  <img alt="Symfony 7.4" src="https://img.shields.io/badge/Symfony-7.4-black">
  <img alt="Next.js 16" src="https://img.shields.io/badge/Next.js-16-black">
  <img alt="PostgreSQL 17" src="https://img.shields.io/badge/PostgreSQL-17-336791">
  <img alt="License MIT" src="https://img.shields.io/badge/license-MIT-green">
</p>

---

## Español

### El problema
Las empresas de maquinaria documentan cada jornada con un **albarán de papel**: cliente, obra, máquina, horas, viajes y la firma del encargado. El papel viaja en la cabina, se escribe a mano, se pierde, llega tarde a oficina y es difícil saber qué está pendiente de firmar. Con ese albarán se factura y se defiende el trabajo hecho.

### La solución
FleetCore digitaliza la cadena completa sin trasladar trabajo administrativo a quien está en el tajo:

1. **Oficina prepara los datos** una sola vez: clientes, obras, máquinas, camiones, equipos acoplables, implementos y recorridos.
2. **El operario crea el albarán desde el móvil** en menos de dos minutos: elige entre lo preparado, pone horas y viajes, envía. Al día siguiente repite el de ayer y cambia los números. Funciona sin cobertura.
3. **El encargado firma** sin cuenta ni contraseña: con el dedo en el móvil del operario, o desde su teléfono con un enlace y un código por SMS, uno o varios albaranes de golpe.
4. Cada albarán firmado es un **PDF inmutable y verificable**, con el diseño del albarán de papel, un hash del contenido y una página pública de verificación.

El alcance de la primera versión es deliberadamente **solo albaranes**. Una funcionalidad entra únicamente si hace que el albarán se cree más rápido o se firme antes y de forma más defendible.

### Estado del proyecto
En desarrollo. Hito actual: **H0 Cimientos**. El plan completo por hitos está en [`docs/arquitectura-01-tecnica-y-plan.md`](docs/arquitectura-01-tecnica-y-plan.md).

### Documentación
El producto nace de un descubrimiento funcional completo con una empresa real del sector, documentado antes de escribir código:

| Documento | Contenido |
|---|---|
| [Documento maestro v1.0](docs/FLEETCORE-descubrimiento-v1.0.md) | Descubrimiento, requisitos y especificaciones de producto |
| [Decisiones 01](docs/decisiones-descubrimiento-01.md) · [02](docs/decisiones-descubrimiento-02.md) · [03](docs/decisiones-descubrimiento-03-tecnicas.md) | Decisiones funcionales, foco del producto, decisiones técnicas |
| [Formularios 01 a 07](docs/) | Clientes y obras · Maquinaria · Camiones y equipos · Implementos · Usuarios y permisos · Albarán · Firma |
| [Modelo entidad-relación](docs/modelo-datos-01-entidad-relacion.md) | 35 tablas, restricciones, índices |
| [Arquitectura y plan](docs/arquitectura-01-tecnica-y-plan.md) | Monolito modular Symfony, PWA Next.js, Docker, hitos |
| [ADR](docs/adr/) | Decisiones de arquitectura razonadas |
| [Glosario](docs/glosario.md) | Español del negocio ↔ inglés del código |

### Stack
**Backend:** Symfony 7.4 LTS · PHP 8.4 · Doctrine ORM · Symfony Security con voters · Messenger y Scheduler · Gotenberg para PDF · Flysystem · PHPUnit · PHPStan nivel 8.
**Frontend:** Next.js 16 (App Router) · TypeScript · Tailwind CSS · Zod · TanStack Query · PWA con soporte offline · Vitest · Playwright.
**Infraestructura:** PostgreSQL 17 · Docker Compose · Caddy · GitHub Actions.

### Arrancar en local
Solo necesitas Docker y Git.

```bash
git clone https://github.com/lys-developre/fleetcore.git
cd fleetcore
./dev.sh up          # o: make up
```

Abre <http://localhost:8080>. La API responde en <http://localhost:8080/api/health>.

Comandos habituales: `./dev.sh test`, `./dev.sh lint`, `./dev.sh composer require vendor/paquete`, `./dev.sh console cache:clear`, `./dev.sh pnpm add zod`, `./dev.sh logs api`. Lista completa en el [`Makefile`](Makefile).

### Estructura
```
apps/api      Symfony, organizado por módulos de dominio (Identity, Client, WorkSite, Fleet, Report, Signing…)
apps/web      Next.js: oficina (escritorio), operario (móvil), firma del encargado, verificación pública
infra/        Caddy y scripts de despliegue
docs/         Descubrimiento, decisiones, formularios, modelo de datos, ADR
compose.yaml  Stack de desarrollo completo
```

---

## English

**FleetCore** replaces the paper delivery note ("albarán") used daily by excavation and heavy-machinery companies in Spain. The office prepares master data once; operators fill a daily report from their phone in under two minutes, offline if needed; the client's foreman signs with a finger or through an SMS link with a one-time code, one report or a whole week at once. Every signed report becomes an immutable, publicly verifiable PDF that mirrors the paper layout and carries a content hash.

Scope of v1 is deliberately **reports only**. A feature is included only if it makes the report faster to create or faster and more defensible to sign.

**Stack:** Symfony 7.4 LTS · PHP 8.4 · Doctrine · PostgreSQL 17 · Next.js 16 · TypeScript · Tailwind · Docker Compose · Caddy · GitHub Actions.

**Run locally** with Docker and Git only: `./dev.sh up`, then open <http://localhost:8080>.

The full functional discovery (in Spanish) lives in [`docs/`](docs/), with architecture decision records in [`docs/adr/`](docs/adr/) and a Spanish ↔ English domain glossary in [`docs/glosario.md`](docs/glosario.md).

---

## Autor · Author
**Larry Yoffre** — Full Stack Developer · [larryyoffre.com](https://www.larryyoffre.com) · [GitHub](https://github.com/lys-developre) · [LinkedIn](https://www.linkedin.com/in/larry-yoffre-9b45102bb/)

Licencia MIT. Ver [`LICENSE`](LICENSE).
