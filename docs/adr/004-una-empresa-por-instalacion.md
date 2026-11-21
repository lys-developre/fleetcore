# ADR 004 — Una empresa por instalación

**Fecha:** 2026-11-21 · **Estado:** aceptada

## Contexto
FleetCore se describe como SaaS, pero la primera versión sirve a una empresa. Añadir `company_id` a todas las tablas encarece cada consulta, índice único y voter, y no responde al criterio de inclusión (crear o firmar albaranes mejor).

## Decisión
El modelo no tiene columna de empresa. `company_settings` es una fila única. Para servir a varias empresas se despliega una base de datos por empresa.

## Consecuencias
- Aislamiento de datos total entre empresas sin esfuerzo de código.
- Operar muchas instalaciones exige automatizar despliegues; aceptable hasta decenas de clientes.
- Si hiciera falta multiempresa en una sola base, se añade `company_id` con una migración: todos los identificadores son UUID y la única secuencia (`report_sequence`) pasaría a `(company_id, anio)`.
