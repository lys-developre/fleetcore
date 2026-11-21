# FLEETCORE — Registro de decisiones de descubrimiento nº 2

**Fecha:** 21 de noviembre de 2026
**Complementa a:** `decisiones-descubrimiento-01.md` y sección 3 del documento maestro
**Estado:** Confirmado por la empresa

---

## 1. Foco del producto: albaranes

La empresa confirma lo que el documento maestro fijaba en la sección 3.1: **la primera versión de FleetCore es un sistema de albaranes**, no una gestión total de la empresa.

Razones expresadas por la empresa, desde su experiencia en obra:

- Quienes crean los albaranes (operarios) y quienes los firman (encargados) no tienen interés en complicaciones tecnológicas.
- Están ocupados, tienen poco tiempo, y su trabajo no exige saber de formularios ni de tecnología.
- Por tanto, el albarán tiene que ser **muy fácil de completar y muy fácil de firmar**, o no se usará.

Razones de producto:

- El albarán firmado es el documento que permite facturar y defender el trabajo. Resolverlo bien ya justifica el producto.
- La adopción por los operarios es el riesgo principal. Un producto pequeño que funcione el primer mes vale más que uno completo que se abandone.
- Facturación, contabilidad, nóminas y mantenimiento ya los cubren ERP y gestorías. El albarán de maquinaria con firma del encargado no está bien resuelto en el mercado.
- Todo lo definido hasta ahora (clientes, obras, máquinas, camiones, equipos, implementos, proveedores, archivos, auditoría) sirve igual para una futura gestión total.

---

## 2. Criterio de inclusión para la primera versión

Una funcionalidad entra en la primera versión **solo si responde sí a al menos una** de estas preguntas:

1. ¿Hace que el albarán se cree más rápido o con menos errores?
2. ¿Hace que el albarán se firme antes o sea más defendible ante dudas?

Si la respuesta es no a ambas, la funcionalidad pasa a la lista de futuro aunque sea útil.

### Aplicación del criterio a lo ya discutido

| Funcionalidad | ¿Entra? | Motivo |
|---|---|---|
| Recorridos con kilómetros y material | Sí | Sin ellos el albarán de camión no sirve para cobrar |
| Repetir último albarán | Sí | Reduce el tiempo de creación a cambiar horas |
| Firma presencial y remota con SMS | Sí | Es la firma en sí |
| Recordatorios de firma pendiente | Sí | Hace que se firme antes |
| Auditoría y hash del documento | Sí | Defendible ante dudas |
| Avisos de caducidad de ITV y seguro | Límite | No afecta al albarán, pero cuesta muy poco con la ficha de documentos. Se mantiene |
| Concepto de servicio | **No** | Campo adicional sin efecto en creación ni firma. Eliminado |
| Precios, tarifas, costes | No | Fuera |
| Gestión de contratos de alquiler | No | Solo proveedor y fechas en la ficha |
| Horómetro y mantenimiento | No | Fuera |
| Dietas, hoteles, desplazamientos | No | Fuera |
| Planificación de recursos por adelantado | No | Los recursos previstos de la obra son un atajo del selector, no planificación |

---

## 3. Principios de diseño para operario y encargado

Derivados del foco. Toda pantalla que vean operario o encargado debe cumplirlos.

1. **Cero datos maestros.** El operario nunca escribe nombres de clientes, obras, máquinas ni implementos. Solo elige y pone números. Las dos excepciones están acotadas: fijar coordenadas de obra si están vacías, y crear un recorrido provisional en texto libre.
2. **Un albarán en menos de dos minutos.** Caso típico: repetir el de ayer, cambiar horas, enviar. Tres toques más los números.
3. **Lo previsto primero.** Cada selector muestra arriba lo que oficina preparó para la obra y lo que el operario usó hace poco. Lo demás existe, pero detrás de "más".
4. **Sin cuenta para el encargado.** El encargado no tiene contraseña. Firma con el dedo en el móvil del operario, o abre un enlace, escribe el código que le llega por SMS y firma.
5. **Errores en lenguaje de obra.** "Falta la máquina" en lugar de "campo requerido". Nada de códigos ni términos técnicos.
6. **Sin tablas.** Implementos, viajes y recursos se muestran como tarjetas que se añaden una a una. Nunca una rejilla editable.
7. **Funciona sin cobertura.** El borrador se guarda en el móvil y se sincroniza cuando hay red. El operario no debe perder nada por estar en un tajo sin señal.
8. **Confirmación antes de enviar.** Una pantalla de resumen con lo esencial y un solo botón "Enviar". Después, no hay vuelta atrás salvo "retirar para corregir".

---

## 4. Lista de futuro

Lo que queda fuera de la primera versión, ordenado por probabilidad de ser lo siguiente:

1. Concepto de servicio en la línea de recurso.
2. Tarifas por cliente y valoración del albarán.
3. Planificación de recursos y operarios por día.
4. Gestión de alquileres con contratos y vencimientos.
5. Horómetro y mantenimiento preventivo.
6. Dietas, hoteles y desplazamientos.
7. Passkeys para encargados frecuentes.
8. WhatsApp como canal de firma.
9. Entrega automática del albarán firmado al cliente.
10. Informes y estadísticas de utilización.

---

## 5. Cambios aplicados a otros documentos por esta decisión

| Documento | Cambio |
|---|---|
| `decisiones-descubrimiento-01.md` | Punto 3 (concepto de servicio) revocado. ServiceConcept eliminado del modelo |
| `formularios-01-clientes-obras.md` | Lista d (conceptos de servicio habilitados) eliminada de la obra |
| `formularios-03-camiones-equipos.md` | Campo `concepto_servicio` eliminado de la línea de camión |
| `formularios-04-implementos.md` | Sección 7 cerrada como eliminado |
