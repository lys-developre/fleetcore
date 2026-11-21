# FLEETCORE — Registro de decisiones de descubrimiento nº 1

**Fecha:** 21 de noviembre de 2026
**Complementa a:** `FLEETCORE-descubrimiento-v1.0.md` (secciones 6, 9, 10, 11, 12, 15 y 20)
**Estado:** Propuestas derivadas de las respuestas de la empresa. Pendientes de confirmación final.

Este documento recoge siete puntos abiertos, la respuesta dada por la empresa y la decisión que se propone a partir de ella. Cuando una propuesta contradice el documento maestro v1.0, se indica expresamente.

---

## 1. Obra, trabajo y encargados

### Respuesta de la empresa
En una misma obra puede haber varios encargados, una o varias máquinas y camiones, y un operario puede cambiar de máquina o camión una o varias veces durante la jornada. Además (punto 3), cualquier operario puede ir a cualquier obra abierta y eso se decide por logística del momento, de un día para otro.

### Decisión propuesta
**No crear la entidad Trabajo (WorkOrder) en la primera versión.** Esto corrige la propuesta de la sección 9 del documento maestro.

Motivo: la entidad Trabajo tenía sentido como paquete fijo "encargado + recursos + operarios asignados". La realidad descrita es que esas tres cosas varían a diario y dentro del mismo día. Un paquete fijo obligaría a oficina a rehacerlo constantemente, que es justo lo que queremos evitar.

En su lugar, la **Obra** pasa a ser la unidad central y se enriquece con tres listas que oficina mantiene:

| Lista | Contenido | Uso por el operario |
|---|---|---|
| Encargados de obra | Contactos firmantes autorizados en esa obra (uno o varios) | Elige a quién va dirigido el albarán |
| Recursos previstos | Máquinas, camiones y equipos que oficina espera en esa obra | Aparecen primero en el selector; puede elegir cualquier otro recurso activo si la logística cambió |
| Recorridos | Trayectos de camión definidos para esa obra (ver punto 6) | Elige recorrido e indica número de viajes |

La "preparación de trabajos" del documento maestro se convierte en **preparación de la obra**: oficina sigue preparando todo, pero prepara la obra, no un trabajo.

### Estructura del albarán resultante
Un albarán = **un operario + una obra + un encargado + una fecha**, con **N líneas de recurso**.

- Cada línea de recurso es una máquina, un camión o un equipo acoplable, con sus horas.
- Las líneas de máquina llevan sus implementos con horas propias.
- Las líneas de camión llevan sus viajes por recorrido y, si es cabeza tractora, el equipo acoplado.
- Debe haber al menos una línea de recurso. No se exige que haya máquina (resuelve el punto 7).

Si un operario trabaja por la mañana en Toledo y por la tarde en Jaén, genera dos albaranes, porque son dos obras y dos encargados distintos. Si cambia de máquina dentro de la misma obra, es un solo albarán con dos líneas.

### Reutilización diaria (sección 11 del maestro)
Se resuelve con la acción **"Repetir último albarán"**: el operario parte de su albarán anterior en esa obra, con todos los datos precargados, y solo cambia horas, implementos y viajes. No hace falta la entidad Trabajo para esto.

### Decisión cerrada (21/11/2026): concepto de servicio
**Eliminado de la primera versión.** Se propuso como ayuda a facturación, pero la empresa gestiona el cobro fuera de FleetCore y el albarán actual solo recoge máquina y horas. Con el foco en albaranes (ver `decisiones-descubrimiento-02.md`), un campo más que puede quedar mal relleno no aporta. Si entra una fase de costes, se añadirá a la línea de recurso como opcional.

### Cambios al modelo conceptual de la sección 15
Eliminar WorkOrder y WorkResource. Añadir WorkSiteForeman (encargados por obra), WorkSitePlannedResource (recursos previstos) y Route (recorridos).

---

## 2. Firma del encargado

### Respuesta de la empresa
La firma debe ser muy fácil. Muchos encargados no se llevan bien con la tecnología, tienen poco tiempo y se mueven entre obras, así que no siempre pueden firmar a diario. Se planteó la huella digital y también un enlace remoto. Se necesita obtener la firma de todos.

### Sobre la huella digital
No es viable como mecanismo principal por tres razones:

1. Un navegador no puede leer una huella como dato de identidad. Lo que existe (WebAuthn / passkeys) usa la huella o la cara del teléfono solo para desbloquear una clave guardada en ese dispositivo. Prueba que "quien desbloqueó este teléfono aprobó esto", no "esta huella pertenece a Pepe".
2. Exige que cada encargado registre previamente su propio teléfono. Es un paso de configuración que precisamente los encargados menos tecnológicos no van a completar.
3. No sirve si el encargado firma en el teléfono del operario, que es el caso presencial más habitual.

Puede ofrecerse más adelante como opción cómoda para los encargados que firman mucho (ver nivel C), pero no como base.

### Decisión propuesta: el encargado es un contacto firmante, no un usuario con contraseña
Se crea la entidad **Contacto firmante** (Signer) con nombre, empresa cliente, teléfono móvil y correo opcional. No tiene contraseña ni inicia sesión. Esto elimina el rol "Encargado" como usuario de FleetCore de la sección 6.2 del maestro.

Toda firma, se haga como se haga, produce el mismo registro de **Firma** con la misma evidencia. Se ofrecen dos modos desde el inicio y uno opcional después:

**Nivel A — Firma presencial en el dispositivo del operario**
- El operario termina el albarán y pulsa "Firmar aquí".
- La pantalla muestra el resumen del albarán y un recuadro donde el encargado firma con el dedo.
- El sistema registra: imagen de la firma, encargado elegido, operario con sesión iniciada, fecha y hora, posición GPS si está disponible, dispositivo.
- Es el equivalente digital exacto del papel actual. Para quien no quiere tecnología, es firmar con el dedo en lugar de con bolígrafo.

**Nivel B — Firma remota por enlace con código**
- FleetCore envía al encargado un mensaje (SMS o WhatsApp, con correo como alternativa) con un enlace.
- Al abrirlo, se le pide un código de un solo uso que recibe en el mismo teléfono. No hay cuenta ni contraseña que recordar.
- Ve la lista de albaranes pendientes, revisa cada uno y firma.
- El sistema registra: número de teléfono verificado, código entregado y validado, fecha y hora, dirección IP, dispositivo, y opcionalmente la firma manuscrita.
- Resuelve el caso del encargado que no está en la obra o que firma la semana completa el viernes.

**Nivel C — Passkey (opcional, fase posterior)**
- Para encargados frecuentes que lo pidan: registran su teléfono una vez y firman con huella o cara sin código.

### Evidencia que debe conservar cada firma
Independientemente del nivel:

- Hash del contenido del albarán en el momento de la firma. Si alguien altera el albarán después, el hash no coincide.
- Identidad del firmante (contacto) y mecanismo usado (presencial, enlace con código, passkey).
- Fecha y hora del servidor.
- Datos de contexto: IP, dispositivo, GPS si lo hay, teléfono verificado si aplica.
- Imagen de firma manuscrita si se dibujó.
- Copia PDF generada en ese momento y almacenada de forma inmutable.

Esto es lo que en la normativa europea (eIDAS) se llama firma electrónica simple o avanzada según el nivel. Es admisible como prueba y es exactamente lo que ofrecen los servicios comerciales de firma. Si en el futuro hace falta reforzarlo, se puede añadir sellado de tiempo cualificado sin cambiar el modelo.

### Recordatorios
Para "obtener firmas de todos": FleetCore envía un recordatorio automático al encargado con albaranes pendientes (frecuencia configurable, por ejemplo cada 2 días y resumen semanal), y oficina ve un panel de pendientes por encargado y antigüedad.

### Decisiones cerradas (21/11/2026)
- **Canal de envío:** SMS como canal principal, correo como copia cuando el contacto tenga dirección. WhatsApp queda para una fase posterior. Cada envío registra el canal utilizado.
- **Firma remota:** código de un solo uso **más firma manuscrita dibujada en cada sesión**. Se prioriza la adopción inicial: el cliente ve una rúbrica en cada albarán como en papel. Más adelante puede ofrecerse reutilizar la rúbrica guardada.
- **Cambio de teléfono del encargado:** oficina actualiza el contacto; el cambio queda en auditoría y las firmas anteriores conservan el número con el que se verificaron.

---

## 3. Autorización del operario

### Respuesta de la empresa
Cualquier operario puede ir a cualquier obra abierta. Se decide por logística diaria.

### Decisión propuesta
La autorización se basa en el **estado de la obra**, no en asignaciones:

- Un operario puede crear albaranes en cualquier obra con estado "Abierta".
- Un operario solo puede ver y editar sus propios albaranes.
- Un operario no puede modificar un albarán después de enviarlo (salvo retirarlo, ver punto 4).
- Oficina y Administración ven todo.

Para que el selector no sea una lista enorme, el operario ve primero "Mis obras recientes" (las de sus últimos albaranes) y después el resto de obras abiertas con buscador.

Opcional sin coste de modelo: oficina puede marcar "obras previstas para mañana" por operario como ayuda visual, sin que restrinja nada.

### Decisión cerrada (21/11/2026): albaranes en obras cerradas
Se aplican **ambos mecanismos**:
- **Periodo de gracia de 7 días** tras el cierre, durante el cual se admiten albaranes con fecha de jornada anterior o igual a la fecha de cierre. El albarán queda marcado como "tardío" en auditoría.
- **Reapertura manual** por oficina para casos fuera del plazo, con motivo registrado.

---

## 4. Estados del albarán

### Respuesta de la empresa
Borrador probablemente en servidor. Enviado no significa firmado. Si se rechaza debería volver como borrador. Se pide evaluar la mejor forma.

### Decisión propuesta

**Borrador en servidor.** Motivos: el operario puede empezar en el tajo y terminar en casa, no se pierde si cambia de teléfono, y oficina puede ver cuántos albaranes están a medias. La aplicación móvil guarda copia local para funcionar sin cobertura y sincroniza al recuperar red.

**Cinco estados**, fusionando "Enviado" y "Pendiente de firma" del maestro en uno solo porque significan lo mismo:

| Estado | Significado | Quién puede modificarlo |
|---|---|---|
| Borrador | El operario lo está rellenando | El operario propietario |
| Pendiente de firma | Enviado por el operario, esperando al encargado | Nadie edita el contenido |
| Firmado | El encargado firmó. Contenido inmutable | Nadie |
| Rechazado | El encargado lo rechazó con motivo. Inmutable | Nadie |
| Anulado | Oficina lo anuló después de firmado, con motivo. Inmutable | Nadie |

**Transiciones:**

| De | A | Quién | Condición |
|---|---|---|---|
| Borrador | Pendiente de firma | Operario | Al menos una línea de recurso, horas válidas, encargado elegido. Se asigna número de albarán en este momento |
| Borrador | (eliminado) | Operario | Solo los borradores pueden eliminarse |
| Pendiente de firma | Firmado | Encargado | Vía nivel A, B o C |
| Pendiente de firma | Rechazado | Encargado | Motivo obligatorio. Se crea automáticamente un nuevo Borrador versión 2 precargado con los datos, para el mismo operario |
| Pendiente de firma | Borrador | Operario | "Retirar para corregir". Permitido porque nadie ha certificado nada todavía. Queda en auditoría |
| Pendiente de firma | Pendiente de firma | Oficina | **Edición directa** de cualquier campo, con auditoría de usuario, fecha, campo, valor anterior y valor nuevo. Ver nota más abajo |
| Firmado | Anulado | Oficina | Motivo obligatorio. Opcionalmente se crea un albarán rectificativo nuevo que referencia al anulado |

**Por qué el rechazo no "devuelve" el mismo albarán a borrador sino que crea una versión 2:** el encargado dijo que no a un contenido concreto. Ese contenido y ese motivo deben conservarse tal cual para poder explicar después qué pasó. La versión 2 lleva el mismo número de albarán con sufijo de versión porque la versión 1 nunca tuvo efecto. El operario no nota la diferencia: ve su albarán "devuelto" con el motivo y lo corrige.

**Por qué la anulación tras firma crea un número nuevo:** el albarán firmado sí tuvo efecto (pudo usarse para facturar). El rectificativo es un documento nuevo que referencia al anulado, igual que una factura rectificativa. Ambos se conservan.

**Numeración:** correlativa anual, asignada al enviar (no al crear el borrador) para que no queden huecos por borradores eliminados. Formato propuesto: `2026-000123`. Las versiones por rechazo se muestran como `2026-000123 v2`.

### Decisión cerrada (21/11/2026): oficina puede editar albaranes pendientes de firma
Oficina **puede editar directamente** un albarán en estado Pendiente de firma, con trazabilidad completa en el registro de auditoría (quién, cuándo, qué campo, valor anterior, valor nuevo).

Justificación dada por la empresa: el albarán es el documento con el que se cobra el trabajo realizado, no un documento que identifique al operario. Por tanto la autoría del contenido no es el valor a proteger antes de la firma. Lo que sí es inmutable es el albarán **una vez firmado**: a partir de ahí solo cabe anular y rectificar (ver transiciones).

Consecuencias:
- Se mantiene el registro interno de quién creó el albarán (sección 12.2 del maestro) a efectos de auditoría, aunque no sea relevante en el PDF.
- El albarán muestra en su historial las ediciones de oficina, visibles para oficina y administración.
- El operario recibe un aviso informativo cuando oficina modifica uno de sus albaranes, para que no lo repita por error.

### Pendiente
- Plazo de gracia para que un borrador no quede abandonado indefinidamente (aviso al operario a los N días).

---

## 5. Firma de varios albaranes

### Respuesta de la empresa
Se quiere permitir firmar varios albaranes de una semana por comodidad del encargado, pero sin comprometer nunca la credibilidad de cada albarán individual, porque con ellos se factura y se defienden dudas.

### Decisión propuesta
Se separa la **sesión de firma** del **albarán firmado**:

- Una **Firma** (sesión) es el acto: quién, cuándo, cómo, con qué evidencia (punto 2).
- Una Firma cubre **uno o varios albaranes**, pero registra para cada uno su hash individual en el momento de firmar.
- Cada albarán queda con su propia constancia: "Firmado por Pepe García el 28/11/2026 a las 18:42 mediante enlace con código verificado en el teléfono +34 6xx, junto con otros 4 albaranes".

Para que la firma múltiple no sea una firma a ciegas, la interfaz del encargado:

1. Muestra cada albarán como tarjeta con fecha, operario, recursos, horas y viajes.
2. Cada tarjeta tiene su casilla "Conforme", desmarcada por defecto.
3. Puede abrir cualquier tarjeta para ver el detalle completo.
4. Puede marcar una tarjeta como "No conforme" con motivo: ese albarán pasa a Rechazado y el resto se firma igualmente.
5. El botón "Firmar los N seleccionados" solo se activa si hay al menos uno marcado.

Credibilidad por albarán: cada uno tiene su hash, su constancia individual y la prueba de que el encargado lo tuvo delante y lo marcó. La comodidad está en que la verificación de identidad (código, firma manuscrita) se hace una vez por sesión.

### Cambio al modelo conceptual
ReportSignature de la sección 15 pasa de relación 1:1 con el albarán a: **Signature 1:N SignedReport**, donde SignedReport guarda albarán, hash y decisión (conforme / no conforme con motivo).

---

## 6. Viajes de camión

### Respuesta de la empresa
Oficina detalla el recorrido (origen, destino, kilómetros o lo necesario para cobrar). El operario solo indica un número entero de viajes. Si el recorrido cambia durante el día, debe ser fácil cambiarlo o crear uno nuevo.

### Decisión propuesta
Nueva entidad **Recorrido** (Route), asociada a una obra:

| Campo | Tipo | Notas |
|---|---|---|
| ID | UUID | |
| Obra | Relación | Obligatorio |
| Nombre corto | Texto | Lo que ve el operario, p. ej. "Tajo → Vertedero Alcalá" |
| Origen | Texto | |
| Destino | Texto | |
| Kilómetros | Decimal | Opcional, para cobro |
| Material | Texto o selector | Opcional (tierra, escombro, zahorra) |
| Estado | Validado / Provisional / Inactivo | Ver abajo |
| Creado por | Usuario | Para distinguir los creados por operario |

En el albarán, cada línea de camión tiene **N líneas de viaje**: recorrido + número de viajes (entero). Un camión puede hacer 6 viajes del recorrido A y 2 del recorrido B el mismo día.

**Cuando el recorrido cambia a mitad de jornada y no existe:** el operario pulsa "Nuevo recorrido", escribe origen y destino en texto libre y sigue trabajando. Ese recorrido nace con estado **Provisional** y aparece en un panel de oficina para completarlo (kilómetros, material) y validarlo, o fusionarlo con uno ya existente si era el mismo con otro nombre.

Esta es una excepción controlada al principio "el operario no crea datos maestros". Se admite porque la alternativa es que el operario no pueda terminar el albarán y vuelva al papel. Oficina mantiene el control porque nada provisional pasa a facturación sin validar.

### Decisiones cerradas (21/11/2026)
- **Recorridos siempre por obra.** No se comparten entre obras ni se ligan al cliente. Como comodidad de interfaz, oficina podrá "copiar recorridos de otra obra"; esto no cambia el modelo, solo crea registros nuevos en la obra destino.
- **Sin precio por viaje en la primera versión.** El cobro lo gestiona oficina fuera de FleetCore. El recorrido conserva kilómetros y material como base para una fase futura de costes.

---

## 7. Albarán sin máquina

### Respuesta de la empresa
De acuerdo con la propuesta.

### Decisión confirmada
El albarán tiene líneas de recurso de cualquier tipo (máquina, camión, equipo acoplable) y la única exigencia es que haya al menos una. El flujo de la sección 16.2 del maestro se reescribe como: seleccionar obra → seleccionar encargado → añadir recursos (los que sean) → horas, implementos y viajes según el tipo → observaciones → enviar.

---

## Resumen de cambios respecto al documento maestro v1.0

| Sección del maestro | Cambio |
|---|---|
| 6.2 Roles | Desaparece el rol Encargado como usuario. Aparece la entidad Contacto firmante |
| 9 Preparación de trabajos | Se sustituye por preparación de obra: encargados, recursos previstos, recorridos |
| 11 Continuidad | Se implementa con "Repetir último albarán", no con entidad Trabajo |
| 12.3 Firma | Dos niveles desde el inicio (presencial y enlace con código), passkey opcional después |
| 12.4 Estados | Cinco estados con transiciones definidas; versiones por rechazo; rectificativo por anulación |
| 15 Modelo conceptual | Eliminar WorkOrder, WorkResource. Añadir Signer, WorkSiteForeman, WorkSitePlannedResource, Route, TripLine, SignedReport |
| 16.2 Flujo operario | La máquina deja de ser obligatoria; se añade la elección del encargado |

## Cuestiones cerradas el 21/11/2026

| Nº | Cuestión | Decisión |
|---|---|---|
| 1 | Canal firma remota | SMS principal, correo como copia. WhatsApp más adelante |
| 2 | Firma remota | Código de un solo uso más firma manuscrita en cada sesión |
| 3 | Concepto de servicio | Aprobado como opcional y **revocado el mismo día** tras fijar el foco en albaranes. Fuera de la primera versión |
| 4 | Obras cerradas | Gracia de 7 días más reapertura manual con motivo |
| 5 | Edición por oficina | Permitida en Pendiente de firma con auditoría completa. Inmutable tras firma |
| 6 | Recorridos | Siempre por obra. Copia entre obras como comodidad de interfaz |
| 7 | Precio por viaje | Fuera de la primera versión. Lo gestiona oficina |

## Cuestiones que siguen abiertas

1. Plazo de aviso para borradores abandonados.
2. Todas las de la sección 20 del documento maestro que no se tratan aquí (campos definitivos de cada formulario, catálogos, infraestructura).

## Siguiente paso
Fase 1 y 2 del plan de la sección 22 del maestro: cerrar los campos definitivos de **Clientes** y **Obras**, incorporando a Obras las nuevas listas de encargados firmantes, recursos previstos, recorridos y conceptos de servicio.
