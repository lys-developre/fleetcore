# FLEETCORE — Formularios definitivos: Camiones y Equipos acoplables

**Fecha:** 21 de noviembre de 2026
**Fases del plan:** 4 (Camiones) y 5 (Equipos acoplables) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md`, `formularios-02-maquinaria-proveedores-archivos.md` (Proveedor, Archivos, regla de estados)
**Estado:** Definitivo para la primera versión

---

## 1. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Tipos de camión | Catálogo editable por oficina con precarga: rígido basculante, cabeza tractora, camión grúa, cuba de agua, hormigonera, portacontenedores | Empresa |
| 2 | Ejes y capacidad | Opcionales. No afectan al albarán | Empresa |
| 3 | Identificación en el selector | **Matrícula, marca y modelo.** La matrícula es universal en camiones y los conductores la conocen | Empresa |
| 4 | Horas y viajes | Ambos opcionales en la línea de camión. Al menos uno obligatorio | Empresa |
| 5 | Categorías de equipos | Nombres oficiales. "Chata" y "carro" pasan a **góndola portamáquinas** | Empresa |
| 6 | Compatibilidad tractora-equipo | Sin matriz. Cualquier equipo con cualquier camión que admita acople | Empresa |
| 7 | Qué camiones admiten equipo | Marca `admite_equipo_acoplable` en el **tipo de camión**, no fijado a cabeza tractora | Empresa |

---

## 2. TIPO DE CAMIÓN (TruckType)

Catálogo mantenido por oficina.

### 2.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| nombre | Texto (máx. 80) | Sí | Único, sin distinguir mayúsculas |
| admite_equipo_acoplable | Booleano | Sí | Si `true`, el operario puede seleccionar un equipo acoplable al usar un camión de este tipo |
| orden | Entero | No | |
| estado | Enum | Sí | `activo` / `inactivo` |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 2.2. Catálogo inicial precargado

| Tipo | Admite equipo acoplable | Nota |
|---|---|---|
| Cabeza tractora | Sí | Siempre lleva semirremolque |
| Camión rígido basculante | No | Oficina puede activarlo si la empresa hace trenes de carretera con remolque |
| Camión grúa | No | |
| Cuba de agua | No | |
| Hormigonera | No | |
| Portacontenedores | No | |

### 2.3. Reglas
1. Cambiar `admite_equipo_acoplable` de `true` a `false` no se permite si existen albaranes con equipo acoplado para camiones de ese tipo. Alternativa: crear un tipo nuevo y reasignar los camiones.
2. No se elimina físicamente un tipo con camiones asociados.

---

## 3. CAMIÓN (Truck)

### 3.1. Campos

| Campo | Tipo | Obligatorio | Validación y normalización |
|---|---|---|---|
| id | UUID | Automático | |
| tipo | Relación TruckType | Sí | Tipo `activo` al crear |
| matricula | Texto (máx. 15) | **Sí** | Mayúsculas, sin espacios ni guiones. Única en todo el sistema (compartida con equipos acoplables, ver 6) |
| marca | Texto (máx. 80) | Sí | Autocompletado con marcas ya usadas |
| modelo | Texto (máx. 80) | Sí | |
| numero_bastidor | Texto (17) | No | VIN: 17 caracteres alfanuméricos sin I, O ni Q. Mayúsculas. Único si se informa |
| numero_flota | Texto (máx. 20) | No | Único si se informa |
| alias | Texto (máx. 40) | No | |
| numero_ejes | Entero | No | Entre 2 y 5 |
| capacidad_carga_kg | Entero | No | Entre 1.000 y 60.000 |
| propiedad | Enum | Sí | `propio` / `alquilado` |
| proveedor | Relación Supplier | Condicional | Obligatorio si `alquilado`; `null` si `propio` |
| alquiler_inicio, alquiler_fin | Fecha | No | Mismas reglas que en Máquina |
| estado | Enum | Sí | `activo` / `en_taller` / `baja` |
| observaciones | Texto largo (máx. 2000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

Archivos: foto principal, N documentos (ficha técnica, seguro, ITV, tarjeta de transporte, contrato de alquiler, otro). Se añade el tipo `tarjeta_transporte` al enum de Attachment.

### 3.2. Etiqueta en el selector del operario
**Matrícula · marca modelo**, con el alias delante si existe. Ejemplo: `1234 KLM · Volvo FH 500` o `El grande · 1234 KLM · Volvo FH 500`. El tipo de camión como texto secundario. No hace falta regla de desambiguación: la matrícula es única.

### 3.3. Orden del selector
Igual que en máquinas: previstos de la obra, usados por el operario en los últimos 30 días, resto de activos.

### 3.4. Estados, alquiler vencido, duplicados, reglas de borrado
Idénticos a Máquina (secciones 4.4 a 4.7 del documento de maquinaria), con `activo` / `en_taller` / `baja`.

---

## 4. CATEGORÍA DE EQUIPO ACOPLABLE (TransportEquipmentCategory)

### 4.1. Campos
Iguales a TruckType salvo que no existe `admite_equipo_acoplable`: nombre único, orden, estado.

### 4.2. Catálogo inicial precargado, nombres oficiales

| Categoría | Equivalencias coloquiales recogidas en el descubrimiento |
|---|---|
| Semirremolque bañera | bañera |
| Semirremolque góndola portamáquinas | chata, chata transportadora, carro transportador |
| Semirremolque plataforma | |
| Semirremolque cisterna | |
| Semirremolque caja | |
| Remolque bañera | |
| Remolque portamáquinas | |
| Remolque plataforma | |

Los "semirremolque" se acoplan a cabeza tractora. Los "remolque" se enganchan a un camión rígido. Esta distinción es **informativa**: por decisión 6 el sistema no la impone.

---

## 5. EQUIPO ACOPLABLE (TransportEquipment)

Elemento independiente con matrícula y serie propias. Nunca pertenece de forma fija a un camión.

### 5.1. Campos

| Campo | Tipo | Obligatorio | Validación y normalización |
|---|---|---|---|
| id | UUID | Automático | |
| categoria | Relación | Sí | Categoría `activa` al crear |
| matricula | Texto (máx. 15) | **Sí** | Mayúsculas, sin espacios ni guiones. Única (ver 6) |
| numero_serie | Texto (máx. 50) | No | Mayúsculas. Único si se informa |
| marca | Texto (máx. 80) | No | |
| modelo | Texto (máx. 80) | No | |
| numero_flota | Texto (máx. 20) | No | Único si se informa |
| alias | Texto (máx. 40) | No | |
| propiedad | Enum | Sí | `propio` / `alquilado` |
| proveedor | Relación Supplier | Condicional | Como en Camión |
| alquiler_inicio, alquiler_fin | Fecha | No | Como en Camión |
| estado | Enum | Sí | `activo` / `en_taller` / `baja` |
| observaciones | Texto largo (máx. 2000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

Archivos: foto principal, N documentos.

### 5.2. Etiqueta en el selector del operario
**Categoría corta · matrícula**, alias delante si existe. Ejemplo: `Bañera · R1234BBB`. Para la categoría corta se usa el nombre sin el prefijo "Semirremolque" o "Remolque".

---

## 6. Unicidad de matrícula entre camiones y equipos
Una matrícula no puede repetirse entre un camión y un equipo acoplable. Se garantiza con una tabla auxiliar `vehicle_plate (matricula PK, entidad_tipo, entidad_id)` que ambas fichas alimentan en la misma transacción, o con una restricción única en una vista materializada. La aplicación valida antes y la base de datos lo impone.

Las máquinas quedan fuera de esta unicidad cruzada porque su matrícula es opcional y de otro régimen.

---

## 7. Relación temporal camión y equipo acoplable

### 7.1. Decisión de modelado
La relación se registra **en la línea de camión del albarán**, no en una entidad de asignación independiente. Esto resuelve la cuestión abierta de la sección 7.5 del maestro.

Motivo: el albarán es el único momento en que la empresa sabe con certeza qué equipo llevaba qué camión. Una entidad de asignación aparte exigiría que oficina o el operario la mantuvieran al margen del albarán, que es trabajo duplicado. El historial "qué equipo llevó este camión y cuándo" se obtiene consultando las líneas de albarán.

Si en el futuro hace falta planificar acoples por adelantado, se añade la entidad de asignación sin tocar lo existente.

### 7.2. Línea de camión del albarán (ReportTruckLine), adelanto
Se detalla en la fase 9 (albarán). Lo que afecta a esta fase:

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| camion | Relación Truck | Sí | `activo` o con alquiler vencido |
| equipo_acoplable | Relación TransportEquipment | Condicional | Solo si el tipo del camión `admite_equipo_acoplable`. Si lo admite, es **opcional** (una tractora puede moverse sin semirremolque) |
| horas | Decimal (5,2) | No | Entre 0 y 24 |
| viajes | N líneas de viaje | No | Cada línea: recorrido + número entero de viajes |

**Validación:** `horas` informada o al menos una línea de viaje. No se admite una línea de camión vacía.

### 7.3. Cambio de equipo durante la jornada
Si una tractora lleva bañera por la mañana y góndola por la tarde, el operario añade **dos líneas de camión** para el mismo camión, cada una con su equipo y sus horas o viajes. Así se cumple "una tractora lleva un solo equipo a la vez" sin pedir horas de inicio y fin.

### 7.4. Restricciones de solapamiento

| Situación | Tratamiento |
|---|---|
| Dos equipos en la misma línea de camión | Imposible por diseño: la línea tiene un solo campo `equipo_acoplable` |
| Mismo camión y mismo equipo repetidos en dos líneas del mismo albarán | Bloqueo. Debe sumar las horas en una sola línea |
| Mismo equipo en dos camiones distintos el mismo día, en albaranes distintos | **Aviso, no bloqueo.** Puede ser legítimo (cambio de tractora a mediodía) o un error. El segundo operario ve "Este equipo figura hoy con el camión 5678 XYZ". Oficina ve un panel de posibles conflictos del día |
| Equipo con un camión cuyo tipo no admite acople | Bloqueo en backend. En frontend el campo no se muestra |

### 7.5. Consulta de historial
Oficina dispone, en la ficha del equipo, de la lista "con qué camión, qué día, qué operario, qué obra", derivada de las líneas de albarán. Y en la ficha del camión, la lista inversa.

---

## 8. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| truck_type | `UNIQUE (lower(nombre))`; `CHECK (estado IN ('activo','inactivo'))` |
| truck | `FK truck_type_id`; `FK supplier_id`; `UNIQUE (matricula)`; `UNIQUE (numero_bastidor) WHERE numero_bastidor IS NOT NULL`; `UNIQUE (numero_flota) WHERE numero_flota IS NOT NULL`; `CHECK (numero_ejes IS NULL OR numero_ejes BETWEEN 2 AND 5)`; `CHECK (capacidad_carga_kg IS NULL OR capacidad_carga_kg BETWEEN 1000 AND 60000)`; mismas restricciones de propiedad, proveedor, alquiler y estado que `machine` |
| transport_equipment_category | `UNIQUE (lower(nombre))`; `CHECK (estado IN ('activa','inactiva'))` |
| transport_equipment | `FK category_id`; `FK supplier_id`; `UNIQUE (matricula)`; `UNIQUE (numero_serie) WHERE numero_serie IS NOT NULL`; `UNIQUE (numero_flota) WHERE numero_flota IS NOT NULL`; mismas restricciones de propiedad, proveedor, alquiler y estado |
| vehicle_plate | `PK (matricula)`; `CHECK (entidad_tipo IN ('truck','transport_equipment'))`; `UNIQUE (entidad_tipo, entidad_id)` |
| report_truck_line (adelanto) | `FK truck_id`; `FK transport_equipment_id`; `CHECK (horas IS NULL OR horas BETWEEN 0 AND 24)`; `UNIQUE (report_id, truck_id, COALESCE(transport_equipment_id, uuid_nil()))`. La regla "equipo solo si el tipo lo admite" se valida en aplicación y con trigger |

---

## 9. Qué ve cada rol

| Acción | Administrador | Oficina | Operario |
|---|---|---|---|
| Gestionar tipos de camión y categorías de equipo | Sí | Sí | No |
| Crear y editar camiones y equipos | Sí | Sí | No |
| Cambiar estados | Sí | Sí | No |
| Ver camiones y equipos activos en el selector | Sí | Sí | Sí |
| Ver documentos, proveedor y alquiler | Sí | Sí | No |
| Ver historial de acoples | Sí | Sí | No |
| Ver panel de conflictos de equipo del día | Sí | Sí | No |

---

## 10. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 7.4 Tipos de vehículo | "Dumper" sale de camiones: ya es categoría de maquinaria (dumper articulado y rígido). Se añaden grúa, cuba, hormigonera y portacontenedores |
| 7.4 Cabeza tractora | La capacidad de llevar equipo pasa a ser una marca del tipo de camión, no una propiedad de "cabeza tractora" |
| 7.5 Categorías | Nombres oficiales. Chata y carro unificados en góndola portamáquinas. Se añaden plataforma, cisterna, caja y la familia "remolque" |
| 7.5 Implicación para base de datos | Resuelta: la relación camión-equipo vive en la línea de camión del albarán. No se crea TransportAssignment en la primera versión |
| 15 Modelo | Eliminar TransportAssignment. Añadir TruckType (sustituye a TruckCategory), TransportEquipmentCategory (sustituye a EquipmentCategory), VehiclePlate |

## 11. Pendiente para fases siguientes
- Implementos y conceptos de servicio (fase 6).
- Usuarios, empleados y matriz de permisos (fase 7).
- Albarán completo con las tres clases de línea de recurso (fase 9).
