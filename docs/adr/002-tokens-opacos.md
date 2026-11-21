# ADR 002 — Tokens opacos por dispositivo en lugar de JWT

**Fecha:** 2026-11-21 · **Estado:** aceptada

## Contexto
Hay dos perfiles de sesión: oficina en escritorio (8 h) y operario en móvil (30 días), con cierre remoto desde oficina. El primer diseño usaba JWT de acceso corto más token de renovación.

## Decisión
Un único token aleatorio por dispositivo, guardado con hash en la tabla `access_token` con perfil, caducidad y marca de revocado. Cada petición autenticada extiende la caducidad. Revocar es poner una marca.

## Consecuencias
- Una consulta a base de datos por petición. Irrelevante para el volumen previsto.
- Cierre remoto inmediato, sin ventana de validez residual.
- Es el patrón de Laravel Sanctum, que el desarrollador conoce; reduce riesgo.
- Si algún día hay varios servicios que deban validar sin consultar la base de datos, se introduce JWT entonces.
