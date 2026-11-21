# FLEETCORE — La firma: modos, pantallas del encargado, evidencia y verificación

**Fecha:** 21 de noviembre de 2026
**Fase del plan:** 10 (Firmas) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md` (puntos 2 y 5), `formularios-05-usuarios-permisos.md` (enlaces de firma), `formularios-06-albaran.md` (snapshot, hash, PDF)
**Estado:** Definitivo para la primera versión

---

## 1. Principio

El encargado firma con el **mínimo esfuerzo posible sin perder fiabilidad**. Nunca tiene cuenta ni contraseña. La fiabilidad no sale de lo que el encargado hace, sino de lo que el sistema registra alrededor de su gesto: qué documento exacto vio (hash), por qué canal se verificó su identidad, cuándo, desde dónde y con qué dispositivo.

---

## 2. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Firma presencial | Resumen más firma con el dedo. "Ver detalle" disponible, no obligatorio | Empresa |
| 2 | Texto del SMS | "[Empresa]: tienes N albaranes pendientes de firma. Fírmalos aquí: [enlace]" | Empresa |
| 3 | Recordatorios | A los 2 y 5 días, después semanal. Configurable por oficina. Nunca más de uno al día | Empresa |
| 4 | Entrega del PDF firmado | El encargado lo descarga desde su enlace. Sin envío automático por correo en la primera versión | Empresa |
| 5 | Firma en papel durante la transición | Tercer modo: oficina registra la firma adjuntando foto del papel firmado. Marcado como tal en el PDF y en la evidencia | Empresa |
| 6 | Cambiar de presencial a remoto | Permitido desde la lista del operario con "Enviar al encargado" mientras esté pendiente | Empresa |

---

## 3. Los tres modos de firma

| Modo | Código | Quién inicia | Dónde firma el encargado | Qué prueba la identidad |
|---|---|---|---|---|
| Presencial | `presencial` | Operario, al enviar | En el móvil del operario, en el momento | El operario autenticado estaba delante; GPS y hora; rúbrica manuscrita |
| Remota | `remota` | Operario al enviar, u oficina después | En su propio teléfono, cuando pueda | Código de un solo uso recibido en el móvil registrado del encargado; rúbrica manuscrita |
| Papel registrada | `papel` | Oficina | En papel, fuera del sistema | Foto del papel firmado subida por un usuario de oficina identificado |

Los tres producen la misma entidad **Firma** y dejan el albarán en `firmado`. El PDF indica el modo en el pie. La remota es la de mayor valor probatorio; la presencial es equivalente al papel actual más contexto; la de papel registrada es la más débil y existe solo para no bloquear la transición.

---

## 4. FIRMA (Signature)

Una firma es una **sesión**: un encargado, un momento, un modo, una evidencia. Cubre uno o varios albaranes.

### 4.1. Campos

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| id | UUID | Automático | |
| encargado | Relación ClientContact | Sí | Copia del nombre y teléfono en `snapshot_firmante` |
| modo | Enum | Sí | `presencial` / `remota` / `papel` |
| firmado_en | Fecha/hora | Sí | Hora del servidor, no del dispositivo |
| rubrica | Attachment | Condicional | Imagen PNG de la firma manuscrita. Obligatoria en `presencial` y `remota`. En `papel` es la foto del documento |
| dispositivo_operario | Relación User | Condicional | Solo `presencial`: operario cuya sesión se usó |
| registrado_por | Relación User | Condicional | Solo `papel`: usuario de oficina que lo registró |
| enlace_firma | Relación SigningLink | Condicional | Solo `remota` |
| telefono_verificado | Texto | Condicional | Solo `remota`: número al que se envió el código validado |
| ip, user_agent | Texto | Automático | Del dispositivo donde se firmó |
| gps_lat, gps_lng | Decimal | No | Si el dispositivo lo permitió |
| snapshot_firmante | JSON | Sí | Nombre, cargo, empresa y teléfono del encargado en ese momento |
| hash_sesion | Texto (64) | Sí | SHA-256 de la lista ordenada de (albarán id, hash de albarán) firmados, más `firmado_en` y `encargado`. Es el sello de la sesión |
| created_at | | Automático | |

### 4.2. Albaranes de la firma (SignedReport)

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| firma | Relación Signature | Sí | |
| albaran | Relación Report | Sí | Un albarán solo puede estar en una firma con decisión `conforme` |
| hash_albaran | Texto (64) | Sí | Copia del hash del albarán en el momento exacto de firmar. Debe coincidir con `report.hash` |
| decision | Enum | Sí | `conforme` / `no_conforme` |
| motivos_rechazo | Conjunto | Condicional | Si `no_conforme`: uno o más de `horas_incorrectas`, `recurso_incorrecto`, `viajes_incorrectos`, `no_es_mi_obra`, `otro` |
| texto_rechazo | Texto (máx. 500) | No | |

Cada albarán queda con su propia constancia aunque se firmara en lote: "Firmado por Moisés García el 28/11/2026 18:42, modo remoto, teléfono verificado +34 6••• ••• •16, junto con otros 4 albaranes". Un `no_conforme` lleva ese albarán a `rechazado` y crea su versión 2; el resto de la sesión se firma igual.

### 4.3. Qué ocurre al firmar (transacción única en backend)
1. Verificar que cada albarán sigue en `pendiente_firma` y que su `hash` actual coincide con el que el encargado vio al abrir la pantalla. Si oficina editó entre medias, se rechaza la firma de ese albarán y se le muestra de nuevo.
2. Crear Signature con la evidencia del modo.
3. Crear un SignedReport por albarán con su hash y decisión.
4. Pasar los `conforme` a `firmado`; los `no_conforme` a `rechazado` y crear sus versiones 2.
5. Calcular `hash_sesion`.
6. Regenerar el PDF definitivo de cada albarán firmado, con rúbrica, modo, fecha y código de verificación, y almacenarlo como Attachment inmutable.
7. Registrar en auditoría. Notificar al operario de cada albarán (firmado o rechazado).

---

## 5. MODO PRESENCIAL

### 5.1. Flujo
1. El operario, en el resumen, elige "Firmar aquí" y confirma el envío. El albarán pasa a `pendiente_firma` y se asigna número.
2. La pantalla gira a **modo encargado**: fondo distinto, sin menú, sin botón atrás del operario. Arriba: "Albarán 2026-000124 · para Moisés García".
3. **Resumen** con el mismo orden que el papel: fecha, obra, camiones con horas y viajes, máquinas con horas e implementos, observaciones, miniaturas de fotos. Enlace "Ver detalle" que despliega todo.
4. Debajo, el recuadro de firma a pantalla completa en horizontal si el móvil lo permite. Botón "Borrar" para repetir el trazo.
5. Dos botones: **"Firmar"** (grande, verde) y "No estoy conforme" (pequeño).
6. Al firmar: pantalla "Firmado. Gracias, Moisés" durante 3 segundos y vuelta al operario.
7. "No estoy conforme": lista de motivos, texto opcional, botón "Rechazar". Vuelta al operario con el albarán ya en versión 2 para corregir en el momento si el encargado sigue allí.

### 5.2. Garantías
- La rúbrica debe tener un trazo mínimo (más de 20 puntos y más de 100 píxeles de recorrido). Un punto o una raya no se acepta.
- El operario no puede modificar el albarán desde el modo encargado. Para corregir debe salir del modo (botón "Cancelar firma", que deja el albarán pendiente) y retirarlo.
- Se registra el operario autenticado, su sesión y GPS. Si el GPS está disponible y dista más de 2 km de las coordenadas de la obra, se marca `firma_lejos_de_obra` en auditoría. No bloquea.
- Si el encargado no estaba y el operario cancela, el albarán queda `pendiente_firma` y puede enviarse como remoto desde la lista (decisión 6).

---

## 6. MODO REMOTO

### 6.1. Envío
- Se dispara cuando el operario elige "Enviar al encargado", cuando oficina pulsa "Reenviar", o cuando un albarán presencial cancelado se pasa a remoto.
- FleetCore **agrupa**: si el encargado ya tiene un enlace vigente, no se envía otro SMS por cada albarán. Se envía un SMS por encargado como máximo cada 4 horas, con el total de pendientes. Oficina puede forzar un envío.
- El enlace abre la lista de todos sus pendientes, no solo el último.

### 6.2. Textos de SMS

| Momento | Texto |
|---|---|
| Primer envío | `Excavaciones Santa Bárbara: tienes 3 albaranes pendientes de firma. Fírmalos aquí: https://app.fleetcore.es/f/Ab3dE9` |
| Recordatorio | `Excavaciones Santa Bárbara: recuerda que tienes 3 albaranes pendientes de firma: https://app.fleetcore.es/f/Ab3dE9` |
| Código | `Tu código FleetCore: 482 913. Caduca en 10 minutos.` |
| Confirmación tras firmar | `Firmados 3 albaranes de Excavaciones Santa Bárbara. Puedes verlos en: https://app.fleetcore.es/f/Ab3dE9` |

El nombre de la empresa sale de CompanySettings. Los enlaces son cortos y únicos por encargado, no por albarán.

### 6.3. Pantallas del encargado
1. **Apertura del enlace**: "Hola Moisés. Para ver tus albaranes de Excavaciones Santa Bárbara, te enviamos un código al móvil terminado en 16". Botón "Enviar código". Campo de 6 dígitos con teclado numérico. Enlace "No me llega el código" que permite reenviar pasados 60 segundos, máximo 3 veces.
2. **Lista de pendientes**: una tarjeta por albarán con fecha, obra, operario, resumen de recursos en una línea ("Takeuchi 290 · 10 h · Martillo 4 h") y una casilla "Conforme" desmarcada. Al tocar la tarjeta se abre el detalle completo. Enlace "No estoy conforme" en cada tarjeta.
3. Botón fijo abajo: **"Firmar los N seleccionados"**. Activo cuando hay al menos uno marcado. Un enlace "Marcar todos" existe pero no es el comportamiento por defecto.
4. **Firma**: recuadro de rúbrica, botón "Firmar". Un solo trazo para toda la sesión.
5. **Confirmación**: "Firmados N albaranes. Rechazado 1." Lista con enlaces "Descargar PDF" por albarán. Pestaña "Firmados anteriormente" con los últimos 90 días.
6. La sesión dura 30 minutos de inactividad. Al volver, pide código de nuevo.

### 6.4. Recordatorios
- Día 2 y día 5 tras el primer pendiente sin firmar. Después, uno a la semana el mismo día de la semana del primer envío.
- Nunca más de un SMS al día por encargado, sumando envíos y recordatorios.
- Oficina puede cambiar la pauta (días) y silenciar recordatorios por encargado si lo pide.
- Oficina ve un panel: encargado, pendientes, antigüedad del más viejo, último SMS, último acceso al enlace. Puede llamar por teléfono desde ahí.

---

## 7. MODO PAPEL REGISTRADA

Para el encargado que durante la transición sigue firmando en papel, o para casos sin cobertura ni teléfono.

### 7.1. Flujo
1. El operario envía el albarán como remoto o presencial-cancelado; queda `pendiente_firma`.
2. Imprime o muestra el PDF provisional, o rellena su papel de siempre. El encargado firma en papel.
3. El papel llega a oficina. Oficina abre el albarán, pulsa "Registrar firma en papel", sube la **foto del papel firmado** (obligatoria), indica la fecha de la firma en papel y confirma.
4. El albarán pasa a `firmado` con `modo = papel`.

### 7.2. Garantías y límites
- La foto es obligatoria y queda como rúbrica de la firma.
- El PDF definitivo lleva la leyenda "Firma en papel registrada por oficina el [fecha] por [usuario]. Original en archivo físico".
- El panel de oficina muestra el porcentaje de firmas en papel por encargado para medir la transición.
- Un albarán `firmado` por este modo es igual de inmutable que los demás.

---

## 8. VERIFICACIÓN PÚBLICA

Cualquier persona con un PDF puede comprobar que no ha sido alterado.

- El pie del PDF lleva el código de verificación (primeros 8 y últimos 4 caracteres del hash) y un enlace `https://app.fleetcore.es/v/[id corto]`.
- La página de verificación **no pide acceso** y muestra solo: número de albarán, fecha, obra, cliente, estado, fecha y modo de firma, y "El código coincide" o "El código no coincide". No muestra horas, recursos ni nombres de personas.
- Si el PDF se ha modificado, el hash del contenido no coincidirá y la página lo dirá.
- Si el albarán está anulado, la página lo indica y enlaza al rectificativo.

Esto pasa el criterio "defendible ante dudas" sin que operario ni encargado hagan nada.

---

## 9. Estados del albarán y firma: cuadro completo

| Situación | Estado albarán | Entidad creada |
|---|---|---|
| Operario envía con "Firmar aquí" y el encargado firma | `firmado` | Signature `presencial` + SignedReport `conforme` |
| Operario envía con "Firmar aquí" y el encargado no está conforme | `rechazado`, nace v2 en `borrador` | Signature `presencial` + SignedReport `no_conforme` |
| Operario envía con "Firmar aquí" y cancela | `pendiente_firma` | Nada. Puede pasar a remoto |
| Operario envía con "Enviar al encargado" | `pendiente_firma` | SigningLink enviado o reutilizado |
| Encargado firma por enlace varios albaranes | `firmado` cada conforme | Una Signature `remota`, N SignedReport |
| Encargado rechaza uno en el lote | `rechazado` ese, nace v2 | SignedReport `no_conforme` en la misma Signature |
| Oficina registra firma en papel | `firmado` | Signature `papel` + SignedReport `conforme` |
| Oficina edita un pendiente | `pendiente_firma`, hash recalculado | Si el encargado tenía la pantalla abierta, al firmar se le pide revisar de nuevo ese albarán |
| Administrador anula un firmado | `anulado` | La Signature se conserva. SignedReport queda con `decision = conforme` y el albarán indica anulación posterior |

---

## 10. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| signature | `FK client_contact_id`; `CHECK (modo IN ('presencial','remota','papel'))`; `CHECK (modo <> 'presencial' OR dispositivo_operario_id IS NOT NULL)`; `CHECK (modo <> 'remota' OR (enlace_firma_id IS NOT NULL AND telefono_verificado IS NOT NULL))`; `CHECK (modo <> 'papel' OR registrado_por_id IS NOT NULL)`; `CHECK (rubrica_attachment_id IS NOT NULL)` |
| signed_report | `PK (signature_id, report_id)`; `FK` a ambas; `CHECK (decision IN ('conforme','no_conforme'))`; `CHECK (decision <> 'no_conforme' OR array_length(motivos_rechazo,1) >= 1)`; `UNIQUE (report_id) WHERE decision = 'conforme'` |
| signing_link | Definida en formularios-05 |
| report | Se añade `FK signature_id` nullable, rellenada al firmar, y `CHECK (estado <> 'firmado' OR signature_id IS NOT NULL)` |

Inmutabilidad: trigger `BEFORE UPDATE OR DELETE` en `signature` y `signed_report` que rechaza cualquier cambio. Son tablas de solo inserción.

---

## 11. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 12.3 | Tres modos definidos. La "firma acumulada" es el modo remoto con varios albaranes en una sesión |
| 12.5 | Integridad resuelta con snapshot, hash por albarán, hash de sesión, PDF inmutable y verificación pública |
| 15 Modelo | ReportSignature pasa a Signature 1:N SignedReport. Se añade SigningLink (ya en formularios-05) |
| 20.7 Firma y entrega al cliente | Firma resuelta. Entrega automática al cliente queda en la lista de futuro |

## 12. Pendiente para fases siguientes
- Fase 11: modelo entidad-relación consolidado de todas las entidades definidas en los documentos 01 a 07.
- Fase 12: especificación de validaciones y seguridad consolidada (ya repartida en los documentos; falta el documento único).
- Fase 13: contratos de la API.
- Fase 14: arquitectura técnica y cierre de Next.js y PostgreSQL como decisiones firmes.
- Fase 15: prototipos de pantallas.
