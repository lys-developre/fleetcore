# FLEETCORE — Formularios definitivos: Maquinaria, Categorías, Proveedores y Archivos

**Fecha:** 21 de noviembre de 2026
**Fase del plan:** 3 (Maquinaria) de la sección 22 del documento maestro. Incluye Proveedores y Archivos porque los comparten las fases 4 y 5
**Depende de:** `decisiones-descubrimiento-01.md`, `formularios-01-clientes-obras.md`
**Estado:** Definitivo para la primera versión

---

## 1. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Catálogo de categorías | Maquinaria vial y de obra pública. Sin agrícola. Catálogo editable por oficina con precarga inicial | Empresa |
| 2 | Campos por categoría | Un único formulario para todas. Matrícula opcional | Empresa |
| 3 | Estado de la máquina | `activa` / `en_taller` / `baja`. Sin control de disponibilidad por obra | Empresa |
| 4 | Alquiler | Ficha de Proveedor mínima. En la máquina alquilada: proveedor obligatorio y fechas de alquiler opcionales. Sin gestión de contratos | Empresa |
| 5 | Fotos y documentos | Foto principal, foto de placa y lista de documentos tipificados con caducidad opcional y aviso a oficina | Empresa |
| 6 | Identificación ante el operario | Los operarios identifican por **marca y modelo**, no por número de flota. Número de flota opcional. Alias opcional. Desambiguación automática solo cuando hay duplicados | Empresa |
| 7 | Horómetro | Fuera de la primera versión | Empresa |

---

## 2. CATEGORÍA DE MAQUINARIA (MachineCategory)

Catálogo mantenido por oficina. Define qué clase de máquina es, independientemente de marca y modelo.

### 2.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| nombre | Texto (máx. 80) | Sí | Único, sin distinguir mayúsculas |
| grupo | Enum | Sí | Ver grupos abajo. Solo para ordenar y agrupar el selector |
| orden | Entero | No | Orden de aparición dentro del grupo |
| estado | Enum | Sí | `activa` / `inactiva`. Una categoría inactiva no se ofrece para máquinas nuevas pero sigue en las existentes |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 2.2. Catálogo inicial precargado

| Grupo | Categorías |
|---|---|
| Excavación | Excavadora giratoria de cadenas · Excavadora giratoria de ruedas · Miniexcavadora · Retroexcavadora mixta · Excavadora de brazo largo / demolición en altura · Zanjadora |
| Carga | Pala cargadora de ruedas · Minicargadora · Manipulador telescópico |
| Movimiento de tierras | Bulldozer · Motoniveladora · Mototraílla · Dumper articulado · Dumper rígido de obra · Minidumper |
| Compactación | Rodillo vibratorio liso · Rodillo pata de cabra · Rodillo de neumáticos · Rodillo tándem de asfalto · Bandeja vibrante · Pisón |
| Pavimentación | Extendedora de asfalto · Fresadora de asfalto · Extendedora de hormigón · Barredora |
| Demolición y machaqueo | Robot de demolición · Machacadora móvil · Cribadora móvil |
| Perforación y cimentación | Carro perforador · Pilotadora |
| Elevación y auxiliares | Grúa autopropulsada · Plataforma elevadora · Grupo electrógeno · Compresor |

Nota: bandeja vibrante y pisón son equipos ligeros. Se incluyen como categorías para no bloquear a nadie, pero oficina puede desactivarlas si prefiere no registrarlos.

### 2.3. Reglas
1. Oficina y Administración crean, renombran y desactivan categorías.
2. No se elimina físicamente una categoría con máquinas asociadas.
3. Renombrar una categoría no afecta a los albaranes firmados: el PDF guardado es la copia válida.

---

## 3. PROVEEDOR (Supplier)

Empresa externa que alquila máquinas, camiones o equipos acoplables. Ficha mínima, compartida por las tres fichas de recursos.

### 3.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| nombre | Texto (máx. 200) | Sí | Único, sin distinguir mayúsculas |
| nif | Texto (máx. 20) | No | Si se informa: misma validación que en Cliente para España; único |
| telefono | Texto (máx. 20) | No | Como en Cliente |
| email | Texto (máx. 254) | No | Como en Cliente |
| direccion | Texto (máx. 250) | No | Texto libre |
| estado | Enum | Sí | `activo` / `inactivo` |
| observaciones | Texto largo (máx. 2000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 3.2. Reglas
1. Un proveedor `inactivo` no se ofrece para nuevos recursos alquilados, pero los recursos existentes lo conservan.
2. No se elimina físicamente un proveedor con recursos asociados.
3. Oficina ve, en la ficha del proveedor, la lista de recursos alquilados a él y cuáles tienen alquiler vencido.

---

## 4. MÁQUINA (Machine)

### 4.1. Campos

| Campo | Tipo | Obligatorio | Validación y normalización |
|---|---|---|---|
| id | UUID | Automático | |
| categoria | Relación | Sí | Categoría `activa` al crear |
| marca | Texto (máx. 80) | Sí | Espacios sobrantes eliminados. Autocompletado con marcas ya usadas; no es catálogo cerrado |
| modelo | Texto (máx. 80) | Sí | Espacios sobrantes eliminados |
| numero_serie | Texto (máx. 50) | No | Mayúsculas, sin espacios. Único si se informa |
| matricula | Texto (máx. 15) | No | Mayúsculas, sin espacios ni guiones. Único si se informa |
| numero_flota | Texto (máx. 20) | No | Mayúsculas. Único si se informa |
| alias | Texto (máx. 40) | No | Nombre coloquial que ve el operario si existe |
| propiedad | Enum | Sí | `propia` / `alquilada` |
| proveedor | Relación | Condicional | Obligatorio si `alquilada`; debe ser `null` si `propia` |
| alquiler_inicio | Fecha | No | Solo si `alquilada` |
| alquiler_fin | Fecha | No | Solo si `alquilada`. Si existe, no anterior a `alquiler_inicio` |
| estado | Enum | Sí | `activa` / `en_taller` / `baja`. Por defecto `activa` |
| observaciones | Texto largo (máx. 2000) | No | |
| created_at, updated_at, created_by, updated_by | | Automático | |

Archivos asociados (ver sección 5): una foto principal, una foto de placa, N documentos.

### 4.2. Cómo ve el operario la máquina
Regla de composición de la etiqueta del selector, en este orden:

1. Si tiene `alias`: **alias** seguido de marca y modelo entre paréntesis. Ejemplo: `La amarilla (CAT 320)`.
2. Si no tiene alias: **marca modelo**. Ejemplo: `CAT 320`.
3. Si entre las máquinas visibles hay **más de una con la misma marca y modelo**, se añade un distintivo: matrícula si la tiene, si no los últimos cuatro caracteres del número de serie, si no el número de flota. Ejemplo: `CAT 320 · 4F2A`.

La categoría se muestra como texto secundario bajo la etiqueta. El operario nunca ve el UUID.

### 4.3. Orden del selector del operario
1. Máquinas en los **recursos previstos** de la obra seleccionada.
2. Máquinas que el operario usó en sus últimos 30 días de albaranes.
3. Resto de máquinas `activas`, agrupadas por grupo de categoría.

Las máquinas `en_taller` y `baja` no aparecen. Hay un buscador por marca, modelo, alias y matrícula.

### 4.4. Control de duplicados
- **Bloqueo:** número de serie, matrícula o número de flota repetidos.
- **Aviso, no bloqueo:** al crear una máquina con misma categoría, marca y modelo que otra existente **sin número de serie**, oficina ve "Ya existe una CAT 320 sin número de serie. ¿Es la misma?". Puede continuar.

### 4.5. Estados y transiciones

| Estado | Significado | Visible al operario |
|---|---|---|
| `activa` | Disponible para trabajar | Sí |
| `en_taller` | Averiada o en mantenimiento. Temporal | No |
| `baja` | Ya no está en la empresa (vendida, devuelta al proveedor, siniestro) | No |

| De | A | Quién | Condición |
|---|---|---|---|
| `activa` | `en_taller` | Oficina | Motivo opcional |
| `en_taller` | `activa` | Oficina | |
| `activa` o `en_taller` | `baja` | Oficina | Motivo obligatorio. Fecha de baja automática |
| `baja` | `activa` | Oficina | Motivo obligatorio. Queda en auditoría |

### 4.6. Alquiler vencido
Si `alquilada` y `alquiler_fin` es anterior a hoy, la máquina **sigue seleccionable** (la realidad va por delante del papel), pero:
- El operario ve un aviso discreto "alquiler vencido" al elegirla.
- Oficina la ve en un panel de alquileres vencidos para dar de baja o prorrogar.

### 4.7. Reglas de negocio
1. Oficina y Administración crean y modifican máquinas. El operario solo lee.
2. No se elimina físicamente una máquina con albaranes. Sin albaranes puede eliminarse, con auditoría.
3. Cambiar marca, modelo o categoría no altera albaranes firmados.
4. Una máquina `alquilada` que pasa a `propia` (compra) pierde el proveedor y las fechas de alquiler; el cambio queda en auditoría.

---

## 5. ARCHIVOS (Attachment)

Entidad genérica para fotos y documentos de máquinas, camiones y equipos acoplables. Aplica la sección 13.8 del documento maestro.

### 5.1. Campos

| Campo | Tipo | Obligatorio | Validación |
|---|---|---|---|
| id | UUID | Automático | |
| entidad_tipo | Enum | Sí | `machine` / `truck` / `transport_equipment` (ampliable) |
| entidad_id | UUID | Sí | |
| tipo | Enum | Sí | `foto_principal` / `foto_placa` / `ficha_tecnica` / `seguro` / `itv` / `contrato_alquiler` / `otro` |
| titulo | Texto (máx. 150) | No | Descripción libre, útil en `otro` |
| nombre_original | Texto (máx. 255) | Sí | Solo informativo. Nunca se usa como ruta |
| nombre_almacenado | Texto | Sí | UUID más extensión controlada. Generado por el sistema |
| mime | Texto | Sí | Detectado por contenido, no por extensión declarada |
| tamano_bytes | Entero | Sí | |
| fecha_caducidad | Fecha | No | Solo tiene sentido en seguro, ITV, contrato de alquiler |
| subido_por, subido_en | | Automático | |

### 5.2. Reglas técnicas
- **Formatos admitidos:** imágenes `image/jpeg`, `image/png`, `image/webp`; documentos `application/pdf`. Nada más en la primera versión.
- **Tamaño máximo:** 10 MB por archivo. Las imágenes se recomprimen en servidor a un máximo de 2000 píxeles de lado y se genera miniatura.
- **Almacenamiento:** fuera del directorio público del servidor web. Nunca se sirve un archivo por ruta directa; siempre a través de un endpoint que comprueba permisos y entrega con `Content-Disposition` y tipo correcto.
- **Nombres:** el nombre en disco es aleatorio. El nombre original solo se muestra.
- **Ejecución:** el directorio de almacenamiento no permite ejecución. Se verifica la cabecera real del archivo (magic bytes) antes de aceptarlo.
- **Un solo archivo** de tipo `foto_principal` y uno de `foto_placa` por entidad; subir otro sustituye al anterior (el anterior queda en auditoría y se elimina físicamente tras 30 días).
- **Borrado:** los documentos se pueden eliminar desde oficina; la eliminación queda en auditoría.

### 5.3. Avisos de caducidad
- Panel en oficina: documentos que caducan en los próximos 30 días y documentos caducados, agrupados por recurso.
- Correo diario resumen a los usuarios de oficina si hay novedades (nuevos caducados o nuevos en ventana de 30 días). Sin correo si no hay cambios.
- La caducidad **no bloquea** el uso del recurso en albaranes.

---

## 6. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| machine_category | `UNIQUE (lower(nombre))`; `CHECK (estado IN ('activa','inactiva'))` |
| supplier | `UNIQUE (lower(nombre))`; `UNIQUE (nif) WHERE nif IS NOT NULL`; `CHECK (estado IN ('activo','inactivo'))` |
| machine | `FK category_id`; `FK supplier_id`; `UNIQUE (numero_serie) WHERE numero_serie IS NOT NULL`; `UNIQUE (matricula) WHERE matricula IS NOT NULL`; `UNIQUE (numero_flota) WHERE numero_flota IS NOT NULL`; `CHECK (propiedad IN ('propia','alquilada'))`; `CHECK ((propiedad = 'alquilada') = (supplier_id IS NOT NULL))`; `CHECK (propiedad = 'alquilada' OR (alquiler_inicio IS NULL AND alquiler_fin IS NULL))`; `CHECK (alquiler_fin IS NULL OR alquiler_inicio IS NULL OR alquiler_fin >= alquiler_inicio)`; `CHECK (estado IN ('activa','en_taller','baja'))` |
| attachment | `CHECK (entidad_tipo IN (...))`; `CHECK (tipo IN (...))`; `CHECK (tamano_bytes > 0 AND tamano_bytes <= 10485760)`; `UNIQUE (entidad_tipo, entidad_id, tipo) WHERE tipo IN ('foto_principal','foto_placa')`; índice en `(entidad_tipo, entidad_id)` y en `fecha_caducidad` |

---

## 7. Qué ve cada rol

| Acción | Administrador | Oficina | Operario |
|---|---|---|---|
| Gestionar categorías | Sí | Sí | No |
| Gestionar proveedores | Sí | Sí | No |
| Crear y editar máquinas | Sí | Sí | No |
| Cambiar estado de máquina | Sí | Sí | No |
| Subir y eliminar archivos | Sí | Sí | No |
| Ver máquinas activas en el selector | Sí | Sí | Sí |
| Ver foto principal de la máquina | Sí | Sí | Sí, en el selector como ayuda visual |
| Ver documentos, proveedor y fechas de alquiler | Sí | Sí | No |
| Ver panel de caducidades y alquileres vencidos | Sí | Sí | No |

---

## 8. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 7.3 Categorías | Catálogo sustituido por el de maquinaria vial de la sección 2.2. Tractor y cosechadora fuera. Se añade el concepto de grupo |
| 7.3 Marca | Texto libre con autocompletado, no catálogo cerrado |
| 7.3 Identificación | Se añade `alias`. Se define la regla de etiqueta del selector basada en marca y modelo |
| 7.3 Estado | Concretado en `activa` / `en_taller` / `baja` |
| 7.3 Alquiler | Se añaden `alquiler_inicio` y `alquiler_fin` |
| 13.8 Archivos | Concretado en la entidad Attachment con tipos, límites y avisos de caducidad |
| 15 Modelo | Supplier y Attachment definidos. MachineCategory incorpora grupo |

## 9. Pendiente para fases siguientes
- Camiones (fase 4) y Equipos acoplables (fase 5) reutilizan Proveedor, Archivos y la misma regla de estados. Falta definir sus categorías, su etiqueta en el selector y la relación temporal tractora-equipo.
- Implementos (fase 6): catálogo propio, sin ficha individual por unidad física salvo que la empresa lo pida.
