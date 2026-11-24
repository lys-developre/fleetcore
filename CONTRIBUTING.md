# Contribuir · Contributing

FleetCore lo desarrolla una persona, pero el repositorio sigue las convenciones de un equipo para que cualquiera pueda leerlo, ejecutarlo y revisarlo.

## Idiomas
- **Código, commits, nombres de tablas, rutas y mensajes de error de la API:** inglés.
- **Documentación funcional (`docs/`) y textos de interfaz:** español. Los textos que ven operarios y encargados se escriben en lenguaje de obra, no técnico.
- Traducciones fijas de los términos del dominio en [`docs/glosario.md`](docs/glosario.md).

## Commits
[Conventional Commits](https://www.conventionalcommits.org/): `feat(report): assign sequential number on send`, `fix(web): keep draft when network drops`, `docs(adr): add ADR 006`, `chore(ci): cache composer`. Ámbitos habituales: `api`, `web`, `infra`, `ci`, `docs`, y los módulos `identity`, `client`, `worksite`, `fleet`, `implement`, `report`, `signing`, `notification`.

Commits pequeños. Un commit, un cambio explicable en una línea.

## Ramas
`main` siempre desplegable. Trabajo en ramas `h<N>/<tema>` por hito (`h1/access-tokens`), que se suben directamente y se fusionan en `main` cuando la CI de la rama está en verde. Sin pull requests: el proyecto no tiene más revisores, y la CI se ejecuta en cada push a cualquier rama.

## Antes de subir una rama
```bash
./dev.sh lint   # PHPStan nivel 8, php-cs-fixer, ESLint, tsc
./dev.sh test   # PHPUnit y Vitest
./dev.sh e2e    # Playwright contra el stack de desarrollo
```
La CI ejecuta lo mismo y además construye las imágenes de producción, las levanta con `compose.prod.yaml` y pasa Playwright contra ellas. Desde `main` solo se publican si ese smoke pasa.

## Backend (apps/api)
- Un módulo por contexto de dominio en `src/<Módulo>/{Domain,Application,Infrastructure,Presentation}`.
- Entidades sin lógica de aplicación; transiciones de estado como métodos que lanzan excepciones de dominio.
- Un caso de uso por acción en `Application/`, con DTO de entrada y salida propios. Nunca se serializa una entidad.
- Toda ruta protegida pasa por un voter.
- Toda regla de negocio escrita en `docs/formularios-*.md` tiene al menos una prueba.

## Frontend (apps/web)
- Un directorio por módulo en `src/features/<módulo>/{api,schemas,components,hooks}`.
- Esquemas Zod espejo de los DTO del backend.
- Tres superficies con diseño propio: oficina (escritorio), operario (móvil), encargado (móvil, mínimo).
- Sin tablas editables en las pantallas de operario y encargado: tarjetas que se añaden una a una.

## Decisiones de arquitectura
Cualquier decisión técnica con alternativas razonables se documenta como ADR en `docs/adr/` con el formato de los existentes: contexto, decisión, consecuencias.
