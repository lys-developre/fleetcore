# ADR 003 — Snapshot y hash por albarán, PDF inmutable

**Fecha:** 2026-11-21 · **Estado:** aceptada

## Contexto
El albarán firmado se usa para facturar y defender el trabajo ante dudas. Referencia por UUID a entidades que oficina puede renombrar después. Un documento firmado no puede cambiar.

## Decisión
Al enviar, el albarán guarda un `snapshot` JSON con todos los textos que aparecen en el PDF y un `hash` SHA-256 del snapshot canónico. El PDF se genera siempre desde el snapshot. Al firmar, la firma guarda el hash de cada albarán cubierto y el PDF definitivo se almacena como archivo inmutable. Triggers en PostgreSQL impiden modificar albaranes firmados y hacen las tablas de firma de solo inserción. Una página pública permite verificar un PDF por su código.

## Consecuencias
- Duplicación controlada de datos: los nombres viven en las entidades y en el snapshot. Es intencionado.
- Cualquier edición de oficina en estado pendiente recalcula el hash; si el encargado tenía la pantalla abierta, se le pide revisar de nuevo.
- La verificación pública no expone horas ni nombres de personas, solo coincidencia del código.
