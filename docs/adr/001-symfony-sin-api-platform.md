# ADR 001 — Symfony como backend, sin API Platform

**Fecha:** 2026-11-21 · **Estado:** aceptada

## Contexto
El documento maestro fijaba Symfony por empleabilidad en el mercado español. El desarrollador viene de Laravel. API Platform aceleraría los CRUD de datos maestros pero oculta buena parte del framework.

## Decisión
Symfony 7.4 LTS sobre PHP 8.4, organizado como monolito modular (ver `docs/arquitectura-01-tecnica-y-plan.md`). Controladores, DTO, validadores y voters escritos a mano. Sin API Platform ni bundles de administración.

## Consecuencias
- Más código por recurso que con API Platform, pero cada pieza es explícita y revisable.
- El proyecto enseña Security, Validator, Serializer, Messenger y Doctrine tal como los usa una empresa.
- Si en el futuro hiciera falta exponer una API pública con documentación rica, API Platform puede añadirse módulo a módulo.
