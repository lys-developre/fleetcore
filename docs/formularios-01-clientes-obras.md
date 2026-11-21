# FLEETCORE — Formularios definitivos: Clientes y Obras

**Fecha:** 21 de noviembre de 2026
**Fases del plan:** 1 (Clientes) y 2 (Obras) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md`
**Estado:** Definitivo para la primera versión. Las decisiones marcadas "por defecto" se tomaron sin respuesta expresa de la empresa y pueden revisarse sin coste hasta que empiece el desarrollo.

---

## 1. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | CIF/NIF | Obligatorio al dar de alta el cliente en oficina. El operario nunca interviene | Empresa |
| 2 | Contactos | Lista de personas de contacto por cliente. Los encargados firmantes salen de esa lista | Empresa |
| 3 | Direcciones | Solo dirección fiscal en el cliente. La dirección operativa vive en la obra | Por defecto |
| 4 | Localidad, provincia, CP | Texto opcional, para buscar y agrupar | Empresa |
| 5 | Coordenadas GPS de obra | Opcionales. Las puede cargar oficina o el operario desde el móvil | Empresa |
| 6 | Cambiar cliente de una obra | Permitido solo mientras la obra no tenga albaranes | Por defecto |
| 7 | Fechas de obra | Sin fechas de planificación. El sistema solo registra automáticamente la fecha de cierre, que necesita la regla de 7 días de gracia | Empresa |
| 8 | Clientes por obra | Uno solo. Una obra factura siempre a una única empresa | Empresa |

---

## 2. CLIENTE (Client)

Ficha de la empresa a la que se factura. Oficina la crea una sola vez.

### 2.1. Campos

| Campo | Tipo | Obligatorio | Validación y normalización |
|---|---|---|---|
| id | UUID | Automático | Generado por el sistema |
| razon_social | Texto (máx. 200) | Sí | Espacios sobrantes eliminados. Única entre clientes activos, sin distinguir mayúsculas |
| nif | Texto (máx. 9) | Sí | Mayúsculas, sin espacios ni guiones. Formato NIF, NIE o CIF español con dígito de control. Único en todo el sistema |
| nombre_comercial | Texto (máx. 200) | No | Lo que ve el operario en el selector si existe; si no, la razón social |
| direccion_fiscal_linea | Texto (máx. 250) | No | Calle, número, piso |
| direccion_fiscal_cp | Texto (máx. 10) | No | Si es España, 5 dígitos |
| direccion_fiscal_localidad | Texto (máx. 100) | No | |
| direccion_fiscal_provincia | Texto (máx. 100) | No | |
| direccion_fiscal_pais | Texto (máx. 2) | No | Código ISO, por defecto `ES` |
| telefono | Texto (máx. 20) | No | Teléfono general de la empresa. Solo dígitos, `+`, espacios. Se guarda como texto |
| email | Texto (máx. 254) | No | Correo general. Minúsculas, formato válido |
| estado | Enum | Sí | `activo` / `inactivo`. Por defecto `activo` |
| observaciones | Texto largo (máx. 2000) | No | |
| created_at, updated_at | Fecha/hora | Automático | |
| created_by, updated_by | Usuario | Automático | |

### 2.2. Validación del NIF
Se comprueba en backend (Symfony Validator, restricción propia) y en frontend (Zod) con la misma regla:

- **NIF de persona física:** 8 dígitos + letra de control calculada con módulo 23.
- **NIE:** X, Y o Z + 7 dígitos + letra de control (se sustituye la inicial por 0, 1, 2 para el cálculo).
- **CIF de entidad:** letra inicial válida + 7 dígitos + dígito o letra de control según el tipo de entidad.

Si un cliente extranjero no tiene identificación española, se admite un identificador fiscal libre marcando `pais` distinto de `ES`; en ese caso solo se valida longitud y unicidad.

### 2.3. Reglas de negocio
1. Oficina y Administración pueden crear y modificar clientes. Operario solo lee lo que necesita para el albarán.
2. No se puede crear una obra para un cliente `inactivo`.
3. Un cliente con obras o albaranes no se elimina físicamente; se pasa a `inactivo`.
4. Un cliente sin ninguna obra puede eliminarse físicamente (limpieza de errores de alta). Queda en auditoría.
5. Cambiar `razon_social` o `nif` no altera los albaranes firmados: el PDF guardado es la copia válida.

### 2.4. Relaciones
- Cliente 1 → N Contactos de cliente.
- Cliente 1 → N Obras.

---

## 3. CONTACTO DE CLIENTE (ClientContact)

Persona física que trabaja para el cliente. Los encargados que firman albaranes son contactos con la marca `es_firmante`. Esta entidad sustituye al rol "Encargado" del documento maestro y a la entidad "Contacto firmante" mencionada en el registro de decisiones.

### 3.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| cliente | Relación | Sí | Debe existir y estar activo al crear |
| nombre | Texto (máx. 150) | Sí | |
| cargo | Texto (máx. 100) | No | Encargado, jefe de obra, administración… |
| telefono_movil | Texto (máx. 20) | Condicional | Obligatorio si `es_firmante`. Normalizado a formato internacional (`+34…`) para el envío de SMS |
| email | Texto (máx. 254) | No | Minúsculas, formato válido. Se usa como copia en la firma remota |
| es_firmante | Booleano | Sí | Por defecto `false` |
| estado | Enum | Sí | `activo` / `inactivo` |
| notas | Texto (máx. 1000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 3.2. Reglas de negocio
1. Un contacto necesita al menos teléfono o correo.
2. Para ser firmante necesita teléfono móvil, porque el código de un solo uso se envía por SMS.
3. Un contacto `inactivo` no aparece en el selector de encargados del operario, pero sus firmas anteriores se conservan.
4. Cambiar el teléfono de un firmante queda en auditoría. Las firmas anteriores conservan el número con el que se verificaron.
5. Un mismo contacto puede ser encargado en varias obras del mismo cliente.
6. No se elimina físicamente un contacto que haya firmado algún albarán.

---

## 4. OBRA (WorkSite)

Lugar o proyecto donde se presta el servicio a un cliente. Es la unidad central de autorización y preparación según el registro de decisiones.

### 4.1. Campos de la ficha

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| cliente | Relación | Sí | Debe existir y estar `activo`. Solo modificable mientras la obra no tenga albaranes |
| nombre | Texto (máx. 200) | Sí | Único dentro del mismo cliente, sin distinguir mayúsculas |
| codigo_interno | Texto (máx. 30) | No | Si se informa, único en todo el sistema. Mayúsculas |
| direccion | Texto (máx. 250) | No | Texto libre: calle, paraje, kilómetro de carretera… |
| localidad | Texto (máx. 100) | No | |
| provincia | Texto (máx. 100) | No | |
| codigo_postal | Texto (máx. 10) | No | |
| latitud, longitud | Decimal (9,6) | No | Rango válido. Los dos o ninguno |
| coordenadas_origen | Enum | Condicional | `oficina` / `operario`. Obligatorio si hay coordenadas |
| coordenadas_fijadas_por, coordenadas_fijadas_en | Usuario, fecha/hora | Automático | |
| estado | Enum | Sí | `en_preparacion` / `abierta` / `cerrada` |
| fecha_cierre | Fecha/hora | Automático | Se rellena al pasar a `cerrada`; se vacía al reabrir |
| observaciones | Texto largo (máx. 2000) | No | Visibles para el operario al seleccionar la obra (acceso, horarios, avisos) |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 4.2. Listas que oficina prepara en la obra

Estas tres listas son la "preparación de la obra" que sustituye a la preparación de trabajos del documento maestro.

**a) Encargados firmantes (WorkSiteForeman)**
- Relación Obra N:M ContactoCliente, restringida a contactos del mismo cliente con `es_firmante = true` y `activo`.
- Mínimo uno para que la obra pueda pasar a `abierta`.
- El operario elige uno de esta lista al crear el albarán. Si solo hay uno, se selecciona solo.

**b) Recursos previstos (WorkSitePlannedResource)**
- Relación Obra N:M con Máquina, Camión o Equipo acoplable.
- Puramente informativa: ordena el selector del operario poniendo primero lo previsto. No restringe.
- Se define en detalle en las fases 3 a 5 (maquinaria, camiones, equipos).

**c) Recorridos (Route)**
- Relación Obra 1:N Recorrido. Ver registro de decisiones, punto 6.
- Oficina puede "copiar recorridos de otra obra": crea copias nuevas en esta obra.

### 4.3. Estados y transiciones

| Estado | Significado |
|---|---|
| `en_preparacion` | Oficina la está configurando. No visible para operarios |
| `abierta` | Visible para todos los operarios. Admite albaranes |
| `cerrada` | No visible en el selector normal. Admite albaranes tardíos durante 7 días |

| De | A | Quién | Condición |
|---|---|---|---|
| `en_preparacion` | `abierta` | Oficina | Al menos un encargado firmante activo |
| `abierta` | `cerrada` | Oficina | Se registra `fecha_cierre`. Los albaranes en borrador de esa obra siguen editables y enviables dentro del plazo de gracia |
| `cerrada` | `abierta` | Oficina | Motivo obligatorio. Se vacía `fecha_cierre`. Queda en auditoría |
| `abierta` | `en_preparacion` | Oficina | Solo si no tiene albaranes |

### 4.4. Reglas de negocio
1. Oficina y Administración crean y modifican obras. El operario solo lee, con la excepción de las coordenadas (regla 5).
2. El selector del operario muestra primero "Mis obras recientes" (obras de sus últimos 30 días de albaranes) y después el resto de obras `abiertas`, con buscador por nombre, cliente y localidad.
3. Un albarán tardío es el que se envía con la obra `cerrada`, dentro de los 7 días posteriores a `fecha_cierre` y con fecha de jornada no posterior a `fecha_cierre`. Queda marcado como tardío en auditoría. Fuera de ese plazo, oficina debe reabrir.
4. Cambiar el cliente de una obra solo es posible sin albaranes. Con albaranes, el camino es cerrar la obra y crear otra.
5. **Coordenadas por el operario:** si la obra no tiene coordenadas, el operario ve el botón "Fijar ubicación" al seleccionar la obra, que toma la posición del móvil. Si ya tiene coordenadas, solo oficina puede cambiarlas. Es la única escritura del operario sobre datos maestros, junto con los recorridos provisionales.
6. No se elimina físicamente una obra con albaranes. Sin albaranes puede eliminarse, con auditoría.
7. Cerrar una obra no cierra nada en el cliente. Un cliente puede tener obras cerradas y abiertas a la vez.

### 4.5. Relaciones
- Cliente 1 → N Obras.
- Obra N → M Contactos firmantes (del mismo cliente).
- Obra N → M Recursos previstos.
- Obra 1 → N Recorridos.
- Obra 1 → N Albaranes.

---

## 5. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| client | `UNIQUE (nif)`; `UNIQUE (lower(razon_social)) WHERE estado = 'activo'`; `CHECK (estado IN ('activo','inactivo'))` |
| client_contact | `FK client_id`; `CHECK (telefono_movil IS NOT NULL OR email IS NOT NULL)`; `CHECK (NOT es_firmante OR telefono_movil IS NOT NULL)` |
| work_site | `FK client_id`; `UNIQUE (client_id, lower(nombre))`; `UNIQUE (codigo_interno) WHERE codigo_interno IS NOT NULL`; `CHECK ((latitud IS NULL) = (longitud IS NULL))`; `CHECK (estado IN ('en_preparacion','abierta','cerrada'))`; `CHECK (estado <> 'cerrada' OR fecha_cierre IS NOT NULL)` |
| work_site_foreman | `PK (work_site_id, client_contact_id)`; FK a ambas. La coincidencia de cliente entre obra y contacto se valida en aplicación y con un trigger o restricción diferida |
| route | `FK work_site_id`; `CHECK (estado IN ('validado','provisional','inactivo'))` |

---

## 6. Qué ve cada rol

| Acción | Administrador | Oficina | Operario |
|---|---|---|---|
| Crear y editar clientes | Sí | Sí | No |
| Crear y editar contactos | Sí | Sí | No |
| Crear y editar obras | Sí | Sí | No |
| Abrir, cerrar y reabrir obras | Sí | Sí | No |
| Ver lista de obras abiertas | Sí | Sí | Sí |
| Ver obras en preparación o cerradas | Sí | Sí | Solo cerradas en plazo de gracia donde tenga borradores |
| Fijar coordenadas de obra | Sí | Sí | Solo si están vacías |
| Ver datos fiscales del cliente | Sí | Sí | No. Solo nombre comercial o razón social |

---

## 7. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 7.1 Clientes | CIF/NIF pasa de opcional a obligatorio. Se añade nombre comercial y dirección fiscal estructurada. Teléfono y correo generales se mantienen; las personas van a Contactos |
| 7.2 Obras | Desaparecen fecha de inicio y fin. El campo "Encargado" pasa a ser una lista de contactos firmantes. Se añaden coordenadas, estado `en_preparacion` y las tres listas de preparación |
| 15 Modelo | Nueva entidad ClientContact. WorkSite incorpora WorkSiteForeman, WorkSitePlannedResource y Route |

## 8. Pendiente para fases siguientes
- Campos de Máquina, Camión y Equipo acoplable que alimentan los recursos previstos (fases 3, 4 y 5).
- Si el operario necesita ver el teléfono del encargado desde el albarán para llamarle. Recomendación: sí, solo el nombre y el móvil.
