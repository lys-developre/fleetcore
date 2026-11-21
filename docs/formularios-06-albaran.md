# FLEETCORE — El albarán: documento, pantallas del operario y PDF

**Fecha:** 21 de noviembre de 2026
**Fase del plan:** 9 (Albarán) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md` (estados, firma, edición por oficina), `decisiones-descubrimiento-02.md` (criterio y principios de diseño), formularios 01 a 05
**Referencia:** `referencia/albaran-papel-actual.jpeg`, albarán físico nº 9932 del 27/10/2026
**Estado:** Definitivo para la primera versión

---

## 1. Lo que dice el albarán de papel

El albarán actual de Excavaciones Santa Bárbara tiene exactamente esto:

| Zona | Contenido |
|---|---|
| Margen | Nombre de la empresa, web y teléfono móvil |
| Cabecera | CLIENTE · OBRA · Nº correlativo |
| Bloque camión | Dos filas: CAMIÓN · HORAS · VIAJES |
| Bloque máquina | Dos filas: MÁQUINA · HORAS · MARTILLO (horas de martillo) |
| Pie | FECHA · RESPONSABLE · FIRMA |

Lo que **no** tiene: nombre del operario, dirección de la obra, datos fiscales del cliente, observaciones, número de serie ni matrícula. El operario escribe marca y modelo ("Takeuchi 290") y las horas en texto ("10 h de máquina").

El albarán digital conserva esta estructura y este nivel de simplicidad. Todo lo que se añade por debajo (auditoría, hash, identidad del operario) existe para hacerlo defendible, no para que nadie lo rellene.

---

## 2. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Fecha de jornada | Hoy por defecto. Hasta 7 días atrás sin aviso. Más atrás se permite, pero oficina recibe aviso | Empresa |
| 2 | Precisión de horas | Horas enteras por defecto. Se permite media hora como opción secundaria | Empresa |
| 3 | Observaciones | Siempre opcionales. Máximo 1000 caracteres | Empresa |
| 4 | Fotos | Opcionales. Máximo 3 por albarán | Empresa |
| 5 | Numeración | Correlativa anual asignada al enviar. Formato fijo `2026-000123`, empezando en 1 cada año. Versiones por rechazo con sufijo `v2`. Sin continuidad con la serie del papel: FleetCore es una serie nueva | Empresa |
| 6 | Motivos de rechazo | Lista corta más texto libre opcional | Empresa |
| 7 | Dos albaranes mismo operario, obra y día | Aviso con opción de abrir el existente. Sin bloqueo | Empresa |
| 8 | Qué ve el encargado al firmar | Fecha, obra, operario, recursos con horas, implementos con horas, viajes por recorrido, observaciones, fotos | Empresa |
| 9 | Logo y datos de empresa en el PDF | Sí. Ficha de configuración de empresa gestionada por el administrador | Empresa |

---

## 3. CONFIGURACIÓN DE EMPRESA (CompanySettings)

Ficha única por instalación. La gestiona el Administrador. Aparece en la cabecera del PDF y en los SMS.

| Campo | Tipo | Obligatorio | Uso |
|---|---|---|---|
| nombre_comercial | Texto (máx. 150) | Sí | Cabecera del PDF y remitente de los SMS ("tu acceso a FleetCore de Excavaciones Santa Bárbara") |
| razon_social | Texto (máx. 200) | Sí | Pie del PDF |
| nif | Texto (máx. 20) | Sí | Pie del PDF |
| direccion | Texto (máx. 250) | No | Pie del PDF |
| telefono | Texto (máx. 20) | Sí | Cabecera del PDF, como en el margen del papel |
| email | Texto (máx. 254) | No | Pie del PDF |
| web | Texto (máx. 100) | No | Cabecera del PDF, como en el papel |
| logo | Archivo | No | PNG, JPEG o SVG, máximo 2 MB. Se muestra arriba a la izquierda del PDF. Sin logo, se muestra el nombre comercial en grande |
| mostrar_operario_en_pdf | Booleano | Sí | Por defecto `true`. Controla la línea "Generado por…" del pie |
| dias_gracia_obra_cerrada | Entero | Sí | Por defecto 7 |
| dias_atras_sin_aviso | Entero | Sí | Por defecto 7 |
| texto_pie_pdf | Texto (máx. 500) | No | Texto legal o aviso que la empresa quiera al pie |

El logo se almacena como Attachment con `entidad_tipo = company`. Cualquier cambio queda en auditoría. Los PDF ya generados no cambian: cada uno lleva incrustado el logo y los datos vigentes en el momento de la firma.

---

## 4. ALBARÁN (Report)

### 4.1. Cabecera

| Campo | Tipo | Obligatorio | Origen y regla |
|---|---|---|---|
| id | UUID | Automático | |
| numero | Entero | Al enviar | Correlativo anual. `null` en borrador |
| anio | Entero | Al enviar | Año de la fecha de jornada |
| version | Entero | Automático | 1 por defecto. Se incrementa en la versión creada tras un rechazo |
| albaran_origen | Relación Report | Condicional | Versión anterior rechazada, o albarán anulado al que rectifica |
| tipo_relacion | Enum | Condicional | `version_tras_rechazo` / `rectificativo` |
| fecha_jornada | Fecha | Sí | Por defecto hoy. No futura |
| obra | Relación WorkSite | Sí | `abierta`, o `cerrada` dentro del plazo de gracia |
| encargado | Relación ClientContact | Sí | Firmante activo de esa obra. Si la obra tiene uno solo, se preselecciona |
| operario | Relación User | Automático | Usuario autenticado. Nunca editable |
| estado | Enum | Sí | `borrador` / `pendiente_firma` / `firmado` / `rechazado` / `anulado` |
| observaciones | Texto (máx. 1000) | No | |
| tardio | Booleano | Automático | `true` si se envió con la obra cerrada en gracia, o con fecha de jornada anterior al plazo sin aviso |
| enviado_en | Fecha/hora | Automático | Momento del paso a `pendiente_firma` |
| gps_envio_lat, gps_envio_lng | Decimal | Automático | Posición del móvil al enviar, si el operario lo permite. Evidencia, no visible en el PDF |
| snapshot | JSON | Al enviar | Copia literal de todos los textos que aparecen en el PDF (nombres de cliente, obra, encargado, operario, recursos, implementos, recorridos, datos de empresa). Ver 4.6 |
| hash | Texto (64) | Al enviar | SHA-256 del snapshot canónico. Se recalcula si oficina edita en `pendiente_firma`. Queda congelado al firmar |
| created_at, updated_at | | Automático | |

### 4.2. Línea de máquina (ReportMachineLine)

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| id | UUID | Automático | |
| albaran | Relación | Sí | |
| maquina | Relación Machine | Sí | `activa`, o cualquier estado si el albarán es anterior al cambio |
| horas | Decimal (4,1) | Sí | Mayor que 0, máximo 24, múltiplo de 0,5 |
| orden | Entero | Automático | |

Implementos: N líneas ReportImplementUsage (implemento, horas > 0, múltiplo de 0,5). Definidas en `formularios-04-implementos.md`. La columna "MARTILLO" del papel se generaliza a "implementos con horas".

Una misma máquina no se repite en dos líneas del mismo albarán. Si el operario la usó en dos tramos, suma.

### 4.3. Línea de camión (ReportTruckLine)

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| id | UUID | Automático | |
| albaran | Relación | Sí | |
| camion | Relación Truck | Sí | |
| equipo_acoplable | Relación TransportEquipment | No | Solo si el tipo de camión lo admite |
| horas | Decimal (4,1) | No | Mayor que 0, máximo 24, múltiplo de 0,5 |
| orden | Entero | Automático | |

Viajes: N líneas ReportTripLine (recorrido de la obra, viajes entero > 0). Un recorrido no se repite dentro de la misma línea de camión.

Validación: horas informadas o al menos una línea de viaje. Mismo camión con mismo equipo no se repite en dos líneas; mismo camión con equipos distintos sí (cambio a mediodía). Definido en `formularios-03-camiones-equipos.md`.

### 4.4. Línea de equipo acoplable suelto
No existe. Un equipo acoplable siempre va dentro de una línea de camión. Un remolque no trabaja solo.

### 4.5. Fotos (Attachment con `entidad_tipo = report`)
Hasta 3. Tipo `foto_albaran`. Solo se pueden añadir en `borrador`. Oficina puede eliminarlas en `pendiente_firma` con auditoría. Tras la firma, inmutables. Aparecen en el PDF como miniaturas en una segunda página; la primera página conserva el formato del papel.

### 4.6. Snapshot: por qué el albarán copia los nombres
El albarán referencia por UUID a cliente, obra, máquina, etc. Pero si oficina renombra una máquina el año que viene, el albarán firmado de hoy no puede cambiar. Por eso al enviar se guarda un `snapshot` con los textos literales. El PDF se genera siempre desde el snapshot, nunca desde las entidades vivas. El hash se calcula sobre el snapshot. Es lo que hace al documento defendible.

### 4.7. Reglas de validación en el envío (backend, Symfony)
1. Operario autenticado con `ROLE_OPERARIO` y estado `activo`.
2. Obra `abierta`, o `cerrada` con `fecha_cierre` dentro del plazo de gracia y `fecha_jornada` no posterior a `fecha_cierre`.
3. Encargado es contacto firmante activo de esa obra.
4. Al menos una línea de máquina o de camión.
5. Cada línea de máquina con horas válidas. Cada línea de camión con horas o viajes.
6. Cada implemento con horas válidas y sin repetirse en la misma línea.
7. Equipo acoplable solo en camiones cuyo tipo lo admite.
8. Recorridos pertenecen a la obra del albarán.
9. `fecha_jornada` no futura. Si es anterior a hoy menos `dias_atras_sin_aviso`, se marca `tardio` y se avisa a oficina.
10. Si existe otro albarán del mismo operario, obra y fecha en cualquier estado salvo `rechazado` o `anulado`, el frontend ya avisó; el backend lo permite.
11. Se asigna número, se construye snapshot, se calcula hash, se genera PDF provisional (sin firma) y se notifica al encargado según el modo de firma elegido.

Todo esto se repite en Zod en el frontend salvo lo que requiere base de datos, para que el operario vea el error antes de enviar.

---

## 5. PANTALLAS DEL OPERARIO

Mobile-first. Cada pantalla tiene un título, lo imprescindible y un botón principal grande. Tiempo objetivo para el caso típico: menos de dos minutos.

### 5.1. Inicio
- Botón grande **"Nuevo albarán"**.
- Botón **"Repetir el de ayer"** si existe un albarán propio en los últimos 7 días. Muestra obra y recursos de ese albarán para que sepa qué va a repetir.
- Lista "Mis albaranes" con los últimos 30 días: fecha, obra, estado con color (gris borrador, naranja pendiente, verde firmado, rojo rechazado).
- Un borrador sin terminar aparece arriba con "Continuar".

### 5.2. Paso 1: Obra
- Lista "Mis obras recientes" arriba, resto de obras abiertas debajo, buscador.
- Al elegir, se muestran nombre de obra, cliente y las observaciones de obra si las hay (acceso, horarios).
- Si la obra tiene un solo encargado, se preselecciona y se muestra su nombre. Si tiene varios, aparece el paso 1b "¿Para quién es el albarán?" con los nombres.
- Fecha: hoy, con posibilidad de cambiarla tocando. Si elige más de 7 días atrás, aviso suave "Oficina recibirá un aviso de albarán atrasado".
- Si ya tiene un albarán hoy en esa obra: "Ya tienes un albarán hoy en esta obra. ¿Abrirlo o crear otro?".

### 5.3. Paso 2: Recursos
- Pantalla con dos botones: **"Añadir máquina"** y **"Añadir camión"**. Si la obra tiene recursos previstos, se muestran como tarjetas para añadir con un toque.
- **Añadir máquina:** selector (previstos, recientes, resto con buscador) → horas con teclado numérico grande, botones "−1" y "+1", y un enlace pequeño "media hora" para sumar 0,5 → "Aceptar".
- La máquina aparece como tarjeta con botón "Añadir implemento" → selector (habituales de la categoría, recientes, "más implementos") → horas → "Aceptar". Las tarjetas de implemento cuelgan de la máquina. Bajo ellas, "Total implementos: N h" informativo.
- **Añadir camión:** selector → si el tipo admite equipo, selector de equipo opcional con "Sin equipo" como primera opción → horas opcionales → "Añadir viajes": selector de recorrido de la obra, número de viajes con "−1" y "+1" → "Aceptar". Botón "Otro recorrido" para añadir una segunda línea de viajes. Enlace "Nuevo recorrido" que abre origen y destino en texto libre y lo crea como provisional.
- Cada tarjeta se puede editar o quitar tocando.
- Botón principal **"Siguiente"**, activo cuando hay al menos un recurso válido.

### 5.4. Paso 3: Observaciones y fotos
- Campo de texto opcional con el teclado del móvil.
- Botón "Añadir foto" (cámara o galería), máximo 3, con miniaturas.
- Botón **"Revisar"**.

### 5.5. Paso 4: Resumen y envío
- Tarjeta con el mismo orden que el papel: cliente, obra, fecha, encargado, camiones con horas y viajes, máquinas con horas e implementos, observaciones.
- Selector de **modo de firma**: "Firmar aquí" (presencial) o "Enviar al encargado" (remota por SMS). Se recuerda la última elección por obra.
- Botón **"Enviar"**. Confirmación: "Se enviará el albarán nº 2026-000124 a Moisés. ¿Confirmas?".
- Si eligió "Firmar aquí": tras confirmar, pasa a la pantalla de firma presencial (fase 10).

### 5.6. Repetir el de ayer
Crea un borrador nuevo copiando obra, encargado, recursos, equipos e implementos del albarán elegido, con **horas y viajes vacíos**. El operario rellena los números y va directo al resumen. Fecha hoy.

### 5.7. Sin cobertura
El borrador se guarda localmente a cada cambio. Si no hay red al enviar, queda "Pendiente de enviar" con icono de nube tachada y se envía solo al recuperar conexión. El número de albarán se asigna en servidor cuando llega.

### 5.8. Después de enviar
- El albarán pasa a "Pendiente de firma" en su lista.
- Si el encargado rechaza, recibe aviso en la app y SMS: "Moisés ha rechazado tu albarán del 27/10 en Azañuelo: horas incorrectas. Toca para corregir". Al abrirlo encuentra la versión 2 precargada.
- Si se firma, el estado cambia a verde y puede descargar el PDF.
- "Retirar para corregir" disponible mientras esté pendiente.

---

## 6. LO QUE VE EL ENCARGADO

Detallado en la fase 10. Lo que fija esta fase es el **contenido**: fecha, obra, operario, cada camión con horas, equipo y viajes por recorrido, cada máquina con horas e implementos con horas, observaciones, fotos. **No** ve datos fiscales ni de contacto del cliente, ni datos de otros encargados.

Motivos de rechazo: "Horas incorrectas" · "Máquina o camión incorrecto" · "Viajes incorrectos" · "No es mi obra" · "Otro", más un campo de texto opcional. Al menos un motivo.

---

## 7. PDF

### 7.1. Página 1: calcada al papel

```
┌──────────────────────────────────────────────────────────────┐
│ [LOGO]   Excavaciones Santa Bárbara                           │
│          excavacionessantabarbara.com · 661 77 67 16          │
│                                                               │
│ CLIENTE   Painhas                          Nº 2026-000124     │
│ OBRA      Azañuelo Morata                  FECHA 27/10/2026   │
│                                                               │
│ CAMIÓN                          HORAS   VIAJES                │
│ 1234 KLM · Volvo FH 500           8     6 × Tajo → Vertedero  │
│   Bañera R1234BBB                       2 × Tajo → Planta     │
│                                                               │
│ MÁQUINA                         HORAS   IMPLEMENTOS           │
│ Takeuchi 290                     10     Martillo 4 h          │
│                                         Cazo 6 h              │
│                                                               │
│ OBSERVACIONES                                                 │
│ (texto, si lo hay)                                            │
│                                                               │
│ RESPONSABLE                     FIRMA                         │
│ Moisés García                   [imagen de firma]             │
│                                 Firmado el 27/10/2026 18:42   │
│                                                               │
│ Excavaciones Santa Bárbara S.L. · B12345678 · dirección       │
│ Documento 2026-000124 · Verificación: a3f9…c2 · fleetcore/v/… │
└──────────────────────────────────────────────────────────────┘
```

- Los bloques de camión y máquina crecen con las líneas; el papel tenía dos filas fijas, el digital tiene las que haga falta.
- El operario **no aparece** en el cuerpo, igual que en el papel. Aparece en el pie en letra pequeña: "Generado por [nombre] el [fecha hora]". Oficina puede desactivarlo en configuración si el cliente no debe verlo.
- El pie lleva los primeros y últimos caracteres del hash y un enlace de verificación. Quien tenga el PDF puede comprobar en FleetCore que no ha sido alterado. Esto pasa el criterio "defendible".
- Cuando el albarán está `pendiente_firma`, el PDF provisional lleva la marca "PENDIENTE DE FIRMA" en diagonal. Cuando está `anulado`, "ANULADO" y referencia al rectificativo. Cuando es versión 2, el pie indica "Sustituye a la versión 1, rechazada el … por …".

### 7.2. Página 2: solo si hay fotos
Miniaturas de las fotos con fecha y hora de captura.

### 7.3. Generación y almacenamiento
- Se genera en servidor desde el snapshot, nunca desde las entidades vivas.
- Se genera al enviar (provisional) y se regenera al firmar (definitivo). Oficina puede regenerar el provisional tras editar.
- El PDF firmado se almacena como Attachment inmutable. Cualquier descarga posterior entrega ese archivo, no uno regenerado.

---

## 8. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| company_settings | Una sola fila: `CHECK (id = 1)` |
| report | `FK work_site_id`; `FK client_contact_id`; `FK user_id`; `FK albaran_origen_id`; `UNIQUE (anio, numero, version) WHERE numero IS NOT NULL`; `CHECK (estado IN (...))`; `CHECK (estado = 'borrador' OR (numero IS NOT NULL AND snapshot IS NOT NULL AND hash IS NOT NULL))`; `CHECK (fecha_jornada <= CURRENT_DATE)`; `CHECK ((albaran_origen_id IS NULL) = (tipo_relacion IS NULL))`; índice en `(user_id, fecha_jornada)`, `(work_site_id, fecha_jornada)`, `(client_contact_id, estado)` |
| report_sequence | `(anio PK, ultimo_numero)`. Asignación con `SELECT … FOR UPDATE` dentro de la transacción de envío para garantizar correlatividad sin huecos |
| report_machine_line | `FK report_id`; `FK machine_id`; `UNIQUE (report_id, machine_id)`; `CHECK (horas > 0 AND horas <= 24 AND horas * 2 = floor(horas * 2))` |
| report_truck_line | Definida en formularios-03; se añade `CHECK (horas IS NULL OR horas * 2 = floor(horas * 2))` |
| report_trip_line | `FK report_truck_line_id`; `FK route_id`; `UNIQUE (report_truck_line_id, route_id)`; `CHECK (viajes > 0)` |
| report_implement_usage | Definida en formularios-04 |

Inmutabilidad tras firma: trigger `BEFORE UPDATE` en `report` y en todas sus tablas de líneas que rechaza cualquier cambio si el albarán está en `firmado`, salvo la transición a `anulado` con sus campos de motivo. La aplicación lo valida antes; la base de datos lo garantiza.

---

## 9. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 10.3 y 16.2 | La máquina deja de ser obligatoria. Se añade la elección del encargado como paso |
| 10.4 | Horas enteras por defecto, media hora como opción. Múltiplo de 0,5 |
| 10.6 | Horas de camión opcionales si hay viajes |
| 12.1 | Se añaden fotos, modo de firma, snapshot y hash. Se quita "ubicación" del cuerpo del PDF porque el papel no la lleva |
| 12.2 | El operario no aparece en el cuerpo del PDF, solo en el pie y desactivable |
| 12.6 | Diseño del PDF definido, calcado al papel, con logo y datos de empresa |
| 15 Modelo | DailyReport pasa a Report con líneas tipadas. Se añaden CompanySettings y ReportSequence |

## 10. Pendiente para fases siguientes
- Fase 10, firma: pantallas del encargado, contenido de los SMS, evidencia y entidad Signature.
- Fase 11, base de datos: consolidar todos los adelantos en un modelo entidad-relación único.
- Fase 13, API: contratos de endpoints.
