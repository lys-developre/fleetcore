# FLEETCORE — Registro de decisiones de descubrimiento nº 3: decisiones técnicas

**Fecha:** 21 de noviembre de 2026
**Complementa a:** sección 5 y 19 del documento maestro, `decisiones-descubrimiento-02.md`
**Estado:** Confirmado

---

## 1. Contexto que condiciona las decisiones técnicas

FleetCore lo desarrolla **una sola persona**, Larry Yoffre, desarrollador full stack junior con experiencia en Laravel, Next.js, TypeScript y PostgreSQL, sin proyectos previos en Symfony. El proyecto tiene dos objetivos a la vez:

1. Ser un producto real para una empresa de excavaciones.
2. Servir como demostración profesional ante recruiters, en un mercado donde las ofertas piden Symfony.

Las decisiones técnicas se toman para que **las dos cosas ocurran**: el producto debe terminarse y el código debe enseñar Symfony bien hecho. Cuando esos objetivos chocan, gana terminar: un proyecto a medias no demuestra nada.

---

## 2. Decisiones

| Nº | Decisión | Estado anterior en el maestro | Motivo |
|---|---|---|---|
| 1 | **Symfony 7.4 LTS, PHP 8.4** como backend API | Confirmado | Empleabilidad en el mercado español. Versión LTS para que el proyecto no envejezca durante el desarrollo |
| 2 | **Next.js 16 con App Router, TypeScript, Tailwind** como frontend, empaquetado como aplicación web progresiva instalable | Propuesto | Stack que el desarrollador domina. Una PWA cubre móvil sin tiendas de aplicaciones |
| 3 | **PostgreSQL 17** | Propuesto | Sin alternativa seria. Ya definido el modelo |
| 4 | **Doctrine ORM** con migraciones | Propuesto | Estándar en Symfony. El patrón data-mapper es distinto de Eloquent y es justo lo que un recruiter Symfony espera ver bien usado |
| 5 | **Monolito modular** en una sola aplicación Symfony. Sin microservicios, sin API Platform | Propuesto | API Platform acelera CRUD pero oculta el framework; para aprender y demostrar Symfony conviene escribir controladores, DTO, validadores y voters a mano |
| 6 | **Tokens opacos por dispositivo** almacenados con hash en base de datos, en lugar de JWT con renovación | Nuevo | Simplifica el documento de usuarios (sección 7): un solo token revocable por dispositivo con caducidad de 8 h o 30 días según interfaz. Patrón equivalente a Laravel Sanctum, que el desarrollador ya conoce |
| 7 | **Symfony Messenger con transporte Doctrine** para trabajos en segundo plano (SMS, PDF, recordatorios), y **Symfony Scheduler** para tareas periódicas | Nuevo | Sin Redis ni colas externas. Una tabla en PostgreSQL basta para este volumen |
| 8 | **Gotenberg** en contenedor para generar PDF desde HTML | Pendiente en maestro | Fidelidad total de diseño con HTML y CSS, sin pelear con librerías PHP de PDF |
| 9 | **Flysystem** con disco local en la primera versión, preparado para almacenamiento S3 compatible | Pendiente en maestro | Cambiar de disco a S3 es una línea de configuración |
| 10 | **Twilio** para SMS detrás de una interfaz propia `SmsSenderInterface`, con un **modo sandbox** que guarda los mensajes en base de datos y los muestra en un panel | Nuevo | La demo no gasta dinero y el recruiter ve el flujo completo en pantalla. Cambiar de proveedor no toca el dominio |
| 11 | **Hetzner** servidor virtual en la Unión Europea, Docker Compose, Caddy como proxy con certificados automáticos | Pendiente en maestro | Coste de unos 5 euros al mes. Protección de datos europea |
| 12 | **Dominio de demo** `fleetcore.larryyoffre.com` | Nuevo | Enlaza portfolio y producto sin coste. Dominio propio cuando haya empresa real |
| 13 | **Una empresa por instalación** | Nuevo | Ver `modelo-datos-01`, sección 7 |
| 14 | **Repositorio público desde el primer día**, monorepo | Nuevo | El historial de commits y los documentos de descubrimiento cuentan la historia del proyecto |
| 15 | **Idiomas**: código, commits, tablas e identificadores en inglés; documentación funcional en español; README bilingüe | Nuevo | Lo que esperan los recruiters en España |
| 16 | **Pruebas**: PHPUnit en backend, Vitest en frontend, Playwright para recorridos completos | Pendiente en maestro | PHPUnit es el estándar Symfony; Pest es del ecosistema Laravel |
| 17 | **Integración continua** con GitHub Actions: análisis estático, pruebas, construcción de imágenes | Nuevo | Visible en el repositorio público |

---

## 3. Correspondencia Laravel → Symfony

Para quien viene de Laravel, estos son los equivalentes que se usarán. Sirve como mapa de aprendizaje.

| Laravel | Symfony en FleetCore |
|---|---|
| Eloquent (active record) | Doctrine ORM (data mapper): entidades sin lógica de persistencia, repositorios separados |
| Migraciones con `php artisan make:migration` | Doctrine Migrations generadas desde el diff de entidades |
| Form Requests | DTO con atributos de Symfony Validator, hidratados con el Serializer y `#[MapRequestPayload]` |
| Policies y Gates | Voters |
| Sanctum | Autenticador `AccessTokenHandler` propio con tabla de tokens |
| Middleware | Event subscribers sobre `kernel.request` y `kernel.response`, o atributos en rutas |
| Jobs y colas | Messenger con mensajes y manejadores |
| Scheduler (`schedule:run`) | Symfony Scheduler |
| Service container con auto-inyección | Lo mismo, con autowiring y autoconfiguración |
| Seeders y factories | Fixtures con DoctrineFixturesBundle y Foundry para factories |
| `php artisan` | `bin/console` |
| `.env` | `.env` y `.env.local`, misma idea, con secretos en `secrets:` para producción |
| Pest | PHPUnit con `WebTestCase` y `KernelTestCase` |
| Blade | No se usa: la API devuelve JSON y Next.js renderiza. Twig solo para la plantilla HTML del PDF |

---

## 4. Lo que queda cerrado del documento maestro

| Sección del maestro | Antes | Ahora |
|---|---|---|
| 5.2 Frontend | Next.js propuesto, versión pendiente | Next.js 16, confirmado |
| 5.3 Base de datos | PostgreSQL propuesto | PostgreSQL 17, confirmado |
| 5.4 Almacenamiento de archivos | Pendiente | Flysystem local, S3 compatible después |
| 19 Pest/PHPUnit | Pendiente | PHPUnit |
| 19 Vitest | Pendiente | Vitest, más Playwright |
| 20.9 Infraestructura | Pendiente | Hetzner, Docker Compose, Caddy, copias diarias |
