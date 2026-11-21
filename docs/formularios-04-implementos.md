# FLEETCORE — Formularios definitivos: Implementos de maquinaria

**Fecha:** 21 de noviembre de 2026
**Fase del plan:** 6 (Implementos) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md`, `formularios-02-maquinaria-proveedores-archivos.md` (categorías de maquinaria)
**Estado:** Definitivo para la primera versión. El concepto de servicio queda en cuestión aparte (sección 7)

---

## 1. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Catálogo | Lista completa de implementos de obra pública, editable por oficina | Empresa |
| 2 | Tipo o unidad | El operario elige el **tipo**. Si la empresa tiene varias unidades del mismo tipo y quiere distinguirlas, oficina da de alta cada unidad con un identificador único | Empresa |
| 3 | Horas por implemento | Obligatorias al añadir un implemento, mayores que cero | Empresa |
| 4 | Concepto de servicio | Eliminado de la primera versión. Ver sección 7 | Empresa |
| 5 | Implementos por categoría de máquina | Sí, como filtro suave: habituales primero, el resto bajo "más implementos" | Empresa |

---

## 2. TIPO DE IMPLEMENTO (ImplementType)

Catálogo mantenido por oficina. Es lo que el operario anota hoy en papel: "martillo", "cazo".

### 2.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| nombre | Texto (máx. 80) | Sí | Único, sin distinguir mayúsculas |
| grupo | Enum | Sí | Ver 2.2. Solo para ordenar el selector |
| orden | Entero | No | |
| estado | Enum | Sí | `activo` / `inactivo` |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 2.2. Catálogo inicial precargado

| Grupo | Tipos de implemento |
|---|---|
| Cazos y cucharas | Cazo de excavación · Cazo de limpieza o taludes · Cazo trapezoidal de cunetas · Cazo de roca · Cazo de zanjeo estrecho · Cazo cribador · Cazo mezclador · Cazo inclinable · Cuchara bivalva · Cuchara de carga · Cuchara 4 en 1 |
| Demolición | Martillo hidráulico · Cizalla hidráulica · Pulverizador · Multiprocesador · Pinza de demolición y clasificación · Pulpo · Ripper |
| Compactación y perforación | Compactador vibratorio de brazo · Rodillo de zanja de brazo · Ahoyador · Perforadora de brazo |
| Corte, fresado y vegetación | Fresadora de brazo · Disco de corte · Desbrozadora de brazo · Cabezal triturador forestal |
| Manipulación y elevación | Pinza para tubos · Imán · Gancho de elevación · Horquillas · Plataforma o cesta · Plumín |
| Acoplamiento | Enganche rápido · Tiltrotator |
| Pala y minicargadora | Hoja empujadora · Barredora · Zanjadora de brazo |

Nota sobre la terminología recogida en el descubrimiento: "sisaya" corresponde a **cizalla hidráulica**.

### 2.3. Implementos habituales por categoría de máquina (ImplementTypeMachineCategory)
Relación N:M entre ImplementType y MachineCategory. Oficina la mantiene. Define qué implementos aparecen primero al seleccionar una máquina de esa categoría.

Precarga inicial propuesta:

| Categoría de máquina | Implementos habituales |
|---|---|
| Excavadora giratoria (cadenas y ruedas), Excavadora de brazo largo | Todos los cazos salvo cuchara de carga y 4 en 1 · Martillo · Cizalla · Pulverizador · Multiprocesador · Pinza · Pulpo · Ripper · Compactador de brazo · Rodillo de zanja · Ahoyador · Fresadora · Disco de corte · Desbrozadora · Cabezal forestal · Pinza para tubos · Imán · Gancho · Enganche rápido · Tiltrotator |
| Miniexcavadora | Cazo de excavación · Cazo de limpieza · Cazo trapezoidal · Cazo inclinable · Martillo · Ahoyador · Compactador de brazo · Enganche rápido |
| Retroexcavadora mixta | Cazo de excavación · Cazo de limpieza · Cazo trapezoidal · Martillo · Ahoyador · Cuchara de carga · Cuchara 4 en 1 · Horquillas |
| Pala cargadora de ruedas | Cuchara de carga · Cuchara 4 en 1 · Horquillas · Hoja empujadora |
| Minicargadora | Cuchara de carga · Cuchara 4 en 1 · Horquillas · Barredora · Zanjadora de brazo · Martillo · Ahoyador · Hoja empujadora |
| Manipulador telescópico | Horquillas · Cuchara de carga · Plataforma o cesta · Plumín · Gancho de elevación |
| Resto de categorías | Sin habituales. Se muestra directamente "más implementos" |

La relación es un **filtro suave**: el operario siempre puede abrir "más implementos" y elegir cualquiera activo. No hay bloqueo.

---

## 3. IMPLEMENTO (Implement)

Entrada seleccionable por el operario. Puede ser genérica (solo el tipo) o una unidad física identificada.

### 3.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| tipo | Relación ImplementType | Sí | Tipo `activo` al crear |
| identificador | Texto (máx. 30) | No | Mayúsculas. Si es `null`, es la entrada genérica del tipo. Único dentro del tipo si se informa |
| numero_serie | Texto (máx. 50) | No | Único si se informa |
| marca, modelo | Texto (máx. 80) | No | Útiles cuando la unidad está identificada |
| propiedad | Enum | No | `propio` / `alquilado`. Opcional porque la mayoría serán genéricos |
| proveedor | Relación Supplier | Condicional | Obligatorio si `alquilado` |
| estado | Enum | Sí | `activo` / `en_taller` / `baja` |
| observaciones | Texto (máx. 1000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

Archivos: foto principal y documentos opcionales, igual que en Máquina. Se añade `implement` al enum `entidad_tipo` de Attachment.

### 3.2. Entrada genérica automática
Al crear un tipo de implemento, el sistema crea automáticamente su **entrada genérica** (identificador `null`). Oficina no tiene que hacer nada más para que el operario pueda elegir "Martillo".

Cuando la empresa quiera distinguir unidades:
1. Oficina da de alta "Martillo · HM-01" y "Martillo · HM-02".
2. Opcionalmente desactiva la entrada genérica "Martillo" para obligar a elegir una unidad concreta. Si no la desactiva, conviven.

### 3.3. Etiqueta en el selector del operario
- Entrada genérica: **nombre del tipo**. Ejemplo: `Martillo hidráulico`.
- Unidad identificada: **nombre del tipo · identificador**. Ejemplo: `Martillo hidráulico · HM-02`.

### 3.4. Orden del selector
Al añadir un implemento a una línea de máquina:
1. Implementos activos cuyo tipo es habitual para la categoría de esa máquina, en el orden del catálogo.
2. Implementos que el operario usó con esa misma máquina en sus últimos 30 días, si no están ya arriba.
3. Botón "Más implementos", que despliega el resto de activos agrupados por grupo, con buscador.

### 3.5. Estados y reglas de borrado
Idénticos a Máquina: `activo` / `en_taller` / `baja`; no se elimina físicamente un implemento con uso en albaranes; la entrada genérica de un tipo no puede eliminarse mientras el tipo exista, solo desactivarse.

---

## 4. USO DE IMPLEMENTO EN EL ALBARÁN (ReportImplementUsage), adelanto

Se detalla en la fase 9. Lo que afecta a esta fase:

| Campo | Tipo | Obligatorio | Regla |
|---|---|---|---|
| linea_maquina | Relación ReportMachineLine | Sí | El implemento cuelga siempre de una máquina concreta del albarán |
| implemento | Relación Implement | Sí | `activo`, o con estado posterior si el albarán es anterior al cambio |
| horas | Decimal (4,2) | Sí | Mayor que 0, máximo 24 |
| orden | Entero | Automático | Orden en que el operario los añadió |

### 4.1. Reglas confirmadas en el maestro y en las decisiones
- Un albarán puede incluir varios implementos, cada uno con sus horas.
- Las horas de implementos **no tienen que coincidir** con las horas de la máquina. El sistema muestra la suma como información; no valida igualdad.
- Un mismo implemento no se repite dos veces en la misma línea de máquina. Si el operario lo usó en dos tramos, suma las horas.
- El mismo implemento sí puede aparecer en dos líneas de máquina distintas del mismo albarán (lo cambió de máquina), y en albaranes de otros operarios el mismo día. No se controla solapamiento de implementos: no compensa la fricción.

### 4.2. Interacción confirmada (sección 17 del maestro)
Selector de implemento → campo de horas → "Aceptar" → aparece como tarjeta bajo la máquina → botón "Añadir implemento" para repetir. Las tarjetas se pueden editar o quitar mientras el albarán está en borrador. Bajo la lista se muestra "Total implementos: N h" como dato informativo.

---

## 5. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| implement_type | `UNIQUE (lower(nombre))`; `CHECK (estado IN ('activo','inactivo'))` |
| implement_type_machine_category | `PK (implement_type_id, machine_category_id)`; FK a ambas |
| implement | `FK implement_type_id`; `FK supplier_id`; `UNIQUE (implement_type_id, identificador)` con `identificador` tratado como `''` cuando es `null` para que la genérica sea única por tipo; `UNIQUE (numero_serie) WHERE numero_serie IS NOT NULL`; `CHECK ((propiedad = 'alquilado') = (supplier_id IS NOT NULL) OR propiedad IS NULL)`; `CHECK (estado IN ('activo','en_taller','baja'))` |
| report_implement_usage (adelanto) | `FK report_machine_line_id`; `FK implement_id`; `CHECK (horas > 0 AND horas <= 24)`; `UNIQUE (report_machine_line_id, implement_id)` |

---

## 6. Qué ve cada rol

| Acción | Administrador | Oficina | Operario |
|---|---|---|---|
| Gestionar tipos y su relación con categorías de máquina | Sí | Sí | No |
| Dar de alta unidades identificadas | Sí | Sí | No |
| Cambiar estados | Sí | Sí | No |
| Ver implementos activos en el selector | Sí | Sí | Sí |
| Ver proveedor, serie y documentos | Sí | Sí | No |

---

## 7. Concepto de servicio: eliminado

Cerrado el 21/11/2026 al fijar el foco en albaranes. No forma parte de las líneas de recurso. Ver `decisiones-descubrimiento-02.md`.

---

## 8. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 8 Catálogo | Lista completa por grupos. "Sisaya" pasa a cizalla hidráulica |
| 8 Requisitos | Se añade la posibilidad de unidades identificadas sin obligar a ello |
| 15 Modelo | Implement se desdobla en ImplementType (catálogo) e Implement (entrada seleccionable). Se añade ImplementTypeMachineCategory. ImplementUsage pasa a ReportImplementUsage colgando de la línea de máquina |

## 9. Pendiente para fases siguientes
- Usuarios, empleados y matriz de permisos (fase 7).
- Albarán completo (fase 9), que consolida los adelantos de líneas de máquina, camión, viaje e implemento.
