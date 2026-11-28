# FLEETCORE — Estado del trabajo y punto de reanudación

**Última actualización:** 28 de noviembre de 2026
**Rama activa:** `h1/identity` (subida a origin, árbol limpio, CI en verde)
**Hito en curso:** H1 Identidad (plan completo en `arquitectura-01-tecnica-y-plan.md`, sección 8)

Este documento se actualiza al pausar el trabajo para poder retomarlo exactamente donde quedó. Regla del plan: nunca más de un hito abierto.

---

## 1. Dónde estamos

- **H0 Cimientos: cerrado** y fusionado en `main` el 24 de noviembre de 2026. Monorepo, Docker, CI, suites de pruebas, smoke de Playwright contra imágenes de producción, despliegue documentado.
- **H1 Identidad: backend terminado** en el commit `ab35dd9d` (`feat(identity): users, roles, device tokens, SMS activation and recovery, lockout, UserVoter`). CI de la rama en verde (verificado el 28-11-2026). 199 tests, PHPStan nivel 8 y php-cs-fixer limpios.
- **Frontend de H1: sin empezar.** `apps/web` sigue siendo la página de estado de H0.

## 2. Qué hay en el backend de H1

Implementa `formularios-05-usuarios-permisos.md`. Módulos `Identity`, `Notification` (SMS) y base de `Shared` (auditoría, excepciones, PhoneNumber).

**Endpoints:**

- `POST /api/auth/login` · `POST /api/auth/logout` — el body del login lleva `profile` (`desktop`|`mobile`, lo decide el frontend); la respuesta trae `token`, `expiresAt` y `user`.
- `GET|POST /api/auth/activation/{token}` — activación (enlace SMS, caduca a las 72 h).
- `POST /api/auth/password-reset` y `GET|POST /api/auth/password-reset/{token}` — recuperación (30 min); misma respuesta exista o no el teléfono.
- `GET|PATCH /api/me` · `GET /api/me/sessions` · `DELETE /api/me/sessions/{id}`.
- `/api/users`: list, create, show, PATCH, resend-activation, block, unblock, unlock, deactivate, readmit, DELETE sessions, DELETE.
- `GET /api/sms-sandbox` — bandeja de SMS en sandbox.

**Reglas clave:** tokens opacos por dispositivo (ADR 002: 8 h escritorio / 30 d móvil con caducidad deslizante, 3/2 dispositivos, cierre remoto); bloqueo progresivo (5 fallos, 15 min doblando hasta 4 h); contraseñas 10+ caracteres no filtradas (HIBP) sin nombre ni teléfono; `UserVoter` aplica la matriz 5.4 al pie de la letra y toda denegación se audita; `audit_log` de solo inserción y triggers que garantizan al menos un administrador activo; rate limiters en login, recuperación y enlaces.

**Formato de error único:** `{error: {code, violations: [{field, code}]}}` con códigos en snake_case que el frontend traduce al español.

**Cuentas de desarrollo (fixtures):** `+34600000001` admin, `…02` oficina, `…03` operario, `…04` consulta, `…05` pendiente de activar; contraseña `cazo-martillo-2026`. Para producción/CI existe `app:user:create-admin <name> <phone> [--password]`.

## 3. Pendiente de H1, en este orden

1. **Frontend (`apps/web`):**
   - BFF en route handlers `/bff/[...path]`: guarda el token en cookie HttpOnly (`SameSite=Lax` + comprobación de `Origin`), nunca lo expone a JS; elige `profile` por user-agent (`userAgent()` de `next/server`); reenvía `X-Forwarded-For` hacia `API_INTERNAL_URL`.
   - `src/proxy.ts` (Next 16; antes "middleware") redirige a `/login` si no hay cookie.
   - Páginas: `/login`, `/forgot-password`, `/activate/[token]`, `/reset-password/[token]`; `/office` (lista y alta de usuarios, acciones de estado, bandeja de SMS sandbox) y `/operator` (placeholder). Formularios con Zod.
2. **ADR 006:** cookie HttpOnly vía BFF para ambos perfiles. Se desvía de la sección 4.3 de la arquitectura (que preveía localStorage cifrado en la PWA); revisar la decisión en H8 (offline).
3. **Playwright e2e:** admin entra → crea operario → lee el SMS en el sandbox → activa la cuenta en móvil. En CI hay que crear el admin con `app:user:create-admin` antes del smoke (las imágenes de prod no llevan fixtures); credenciales por `E2E_ADMIN_PHONE` y `E2E_ADMIN_PASSWORD`.
4. **Docs de cierre de hito:** completar la entrada H1 del `CHANGELOG.md` con el frontend, añadir los estados de usuario al `glosario.md`, actualizar el README si cambia algo visible.
5. **Cierre:** CI en verde y **preguntar antes de fusionar en `main`** (el merge publica imágenes en GHCR y despliega).

**Aplazado conscientemente (documentarlo al cerrar H1):** doble factor para administradores, cambio de teléfono con confirmación por SMS, recuperación por correo.

## 4. Decisiones tomadas durante H1 (no están en ADR)

- Las acciones de estado de usuario van agrupadas en un solo caso de uso `ChangeUserStatus`, no una clase por acción.
- La matriz 5.4 se aplica literalmente: el rol Consulta no puede cerrar sus propias sesiones a distancia.
- El rate limiter de Symfony usa tiempo real (no el reloj simulado) y en los tests HTTP se reinicia entre peticiones: por eso el throttling se prueba en tests de integración, no funcionales.

## 5. Cómo retomar

```bash
git checkout h1/identity
./dev.sh up            # stack completo; app en http://localhost:8080
./dev.sh test          # suites de la API
./dev.sh e2e           # Playwright contra el stack de desarrollo
```

Flujo de trabajo: commits directos a la rama y push a origin (sin pull requests); la CI corre en cada push de cualquier rama.
