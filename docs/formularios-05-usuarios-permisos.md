# FLEETCORE — Usuarios, roles y matriz de permisos

**Fecha:** 21 de noviembre de 2026
**Fase del plan:** 7 (Usuarios y roles) de la sección 22 del documento maestro
**Depende de:** `decisiones-descubrimiento-01.md`, `decisiones-descubrimiento-02.md`, formularios 01 a 04
**Estado:** Definitivo para la primera versión

---

## 1. Principio que gobierna los permisos

La cadena de FleetCore es:

**Oficina prepara los datos → el operario crea el albarán → el encargado firma.**

Cada eslabón escribe solo en su tramo:

| Quién | Qué escribe | Qué no escribe nunca |
|---|---|---|
| Oficina | Clientes, contactos, obras, máquinas, camiones, equipos, implementos, proveedores, recorridos, usuarios operarios | Albaranes nuevos (sí puede editar pendientes de firma, con auditoría) |
| Operario | Sus albaranes en borrador. Excepciones acotadas: coordenadas de obra vacías y recorridos provisionales | Ningún otro dato maestro |
| Encargado | Su firma o su rechazo con motivo | Nada más. No tiene cuenta |

El albarán es el final de la cadena. Si falta algo en oficina, el operario no puede inventarlo; debe pedirlo.

---

## 2. Decisiones de esta fase

| Nº | Cuestión | Decisión | Origen |
|---|---|---|---|
| 1 | Datos del operario | Nombre completo, teléfono móvil, número de operario opcional. Sin DNI ni datos laborales | Empresa |
| 2 | Varios roles por usuario | Permitido | Empresa |
| 3 | Rol de solo consulta | Se crea desde el inicio | Empresa |
| 4 | Acceso del operario | Oficina lo da de alta y le envía por SMS un enlace para fijar contraseña. Sesión móvil de 30 días. Cierre remoto desde oficina | Empresa |
| 5 | Contraseñas | Mínimo 10 caracteres, bloqueo temporal tras 5 fallos, recuperación por SMS. Doble factor opcional para administradores | Empresa |

---

## 3. USUARIO (User)

Una sola entidad para todas las personas con acceso. No se separa en User y Employee como proponía el maestro: con los datos mínimos aprobados no hay nada que justifique dos tablas.

### 3.1. Campos

| Campo | Tipo | Obligatorio | Validación y normalización |
|---|---|---|---|
| id | UUID | Automático | |
| nombre_completo | Texto (máx. 150) | Sí | Espacios sobrantes eliminados. Es lo que aparece en el albarán como operario |
| telefono_movil | Texto (máx. 20) | Sí | Formato internacional (`+34…`). Único. Es el identificador de acceso y el canal de recuperación |
| email | Texto (máx. 254) | No | Minúsculas. Único si se informa. Canal alternativo de recuperación y avisos |
| numero_operario | Texto (máx. 20) | No | Único si se informa. Se guarda en el albarán según sección 12.2 del maestro |
| roles | Conjunto | Sí | Al menos uno. Ver sección 4 |
| password_hash | Texto | Condicional | Vacío hasta que el usuario fija su contraseña con el enlace |
| estado | Enum | Sí | `pendiente_activacion` / `activo` / `bloqueado` / `baja` |
| doble_factor | Booleano | Sí | Por defecto `false`. Solo configurable si tiene rol Administrador |
| ultimo_acceso | Fecha/hora | Automático | |
| intentos_fallidos | Entero | Automático | Se reinicia al acceder correctamente |
| bloqueado_hasta | Fecha/hora | Automático | Bloqueo temporal por intentos fallidos |
| created_at, updated_at, created_by, updated_by | | Automático | |

### 3.2. Estados y transiciones

| Estado | Significado |
|---|---|
| `pendiente_activacion` | Dado de alta por oficina; no ha fijado contraseña |
| `activo` | Puede acceder |
| `bloqueado` | Bloqueado manualmente por oficina o administración (no el bloqueo temporal por intentos) |
| `baja` | Ya no trabaja con la empresa. Conserva historial |

| De | A | Quién | Condición |
|---|---|---|---|
| `pendiente_activacion` | `activo` | El propio usuario | Al fijar contraseña con el enlace. El enlace caduca a las 72 h; oficina puede reenviarlo |
| `activo` | `bloqueado` | Oficina o Administrador | Motivo opcional. Cierra todas las sesiones |
| `bloqueado` | `activo` | Oficina o Administrador | |
| `activo` o `bloqueado` | `baja` | Administrador | Motivo obligatorio. Cierra todas las sesiones. Los albaranes creados se conservan con su nombre |
| `baja` | `activo` | Administrador | Readmisión. Queda en auditoría |

### 3.3. Reglas
1. Un usuario no se elimina físicamente nunca si ha creado albaranes o ha realizado acciones auditadas. Sin actividad, Administrador puede eliminarlo.
2. Cambiar `nombre_completo` no altera albaranes firmados: el PDF guardado es la copia válida. El albarán conserva además una copia del nombre en el momento de creación.
3. Cambiar `telefono_movil` cierra todas las sesiones y exige confirmación por SMS al número nuevo.
4. Un usuario no puede quitarse a sí mismo el rol Administrador ni darse de baja. Debe existir siempre al menos un Administrador activo.

---

## 4. ROLES

Cuatro roles fijos. No hay roles personalizables en la primera versión: cuesta en interfaz y en pruebas y no responde al criterio de inclusión.

| Rol | Código Symfony | Para quién | Resumen |
|---|---|---|---|
| Administrador | `ROLE_ADMIN` | Dueño o responsable de la empresa | Todo lo de Oficina, más usuarios, configuración y anulaciones |
| Oficina | `ROLE_OFICINA` | Personal administrativo | Datos maestros, preparación de obras, edición de albaranes pendientes, panel de firmas |
| Operario | `ROLE_OPERARIO` | Maquinistas y conductores | Sus albaranes |
| Consulta | `ROLE_CONSULTA` | Dirección, gestoría, asesor externo | Solo lectura de albaranes y datos maestros |

Un usuario puede tener varios roles. Los permisos se suman. Ejemplo: alguien de oficina que a veces maneja máquina tiene `ROLE_OFICINA` y `ROLE_OPERARIO` y ve ambas interfaces.

Jerarquía en Symfony: `ROLE_ADMIN` hereda `ROLE_OFICINA`; `ROLE_OFICINA` hereda `ROLE_CONSULTA`. `ROLE_OPERARIO` es independiente.

El **encargado no es un rol**. Es un ContactoCliente con `es_firmante` que recibe enlaces firmados. Ver `formularios-01-clientes-obras.md`.

---

## 5. MATRIZ DE PERMISOS

Leyenda: **S** sí · **–** no · **P** propio (solo sobre lo suyo) · **A** con auditoría reforzada (motivo obligatorio)

### 5.1. Datos maestros

| Acción | Admin | Oficina | Operario | Consulta |
|---|---|---|---|---|
| Ver clientes (datos básicos) | S | S | S, solo nombre en selectores | S |
| Ver datos fiscales de clientes | S | S | – | S |
| Crear y editar clientes y contactos | S | S | – | – |
| Eliminar cliente sin obras | S | S | – | – |
| Ver obras abiertas | S | S | S | S |
| Ver obras en preparación y cerradas | S | S | Solo cerradas en gracia con borradores propios | S |
| Crear y editar obras | S | S | – | – |
| Abrir, cerrar y reabrir obras | S | S | – | – |
| Fijar coordenadas de obra | S | S | Solo si vacías | – |
| Gestionar encargados, recursos previstos y recorridos de la obra | S | S | – | – |
| Crear recorrido provisional | S | S | S | – |
| Validar o fusionar recorridos provisionales | S | S | – | – |
| Ver máquinas, camiones, equipos, implementos activos | S | S | S | S |
| Ver documentos, proveedor y alquiler de recursos | S | S | – | S |
| Crear, editar y cambiar estado de recursos | S | S | – | – |
| Gestionar catálogos (categorías, tipos, implementos) | S | S | – | – |
| Gestionar proveedores | S | S | – | – |
| Subir y eliminar archivos | S | S | – | – |
| Ver paneles de caducidades, alquileres vencidos y conflictos | S | S | – | S |

### 5.2. Albaranes

| Acción | Admin | Oficina | Operario | Consulta |
|---|---|---|---|---|
| Crear borrador | – | – | S | – |
| Ver borradores | S, contenido | S, contenido | P | – |
| Editar borrador | – | – | P | – |
| Eliminar borrador | – | – | P | – |
| Enviar a firma | – | – | P | – |
| Ver pendientes de firma y firmados | S | S | P | S |
| Editar pendiente de firma | S, A | S, A | – | – |
| Retirar pendiente a borrador | S | S | P | – |
| Reenviar enlace de firma al encargado | S | S | P | – |
| Ver historial de ediciones y auditoría del albarán | S | S | P, solo lectura | S |
| Anular firmado | S, A | – | – | – |
| Crear rectificativo de un anulado | S | S | – | – |
| Descargar PDF | S | S | P | S |
| Ver panel de firmas pendientes por encargado | S | S | – | S |

Nota: Admin y Oficina no crean albaranes. Si un administrador también trabaja en máquina, se le añade `ROLE_OPERARIO`.

### 5.3. Firma (sin rol, por enlace)

| Acción | Encargado por enlace con código | Encargado en móvil del operario |
|---|---|---|
| Ver albaranes pendientes dirigidos a él | S | Solo el albarán que se le muestra |
| Ver detalle completo del albarán | S | S |
| Firmar uno o varios | S | Solo el mostrado |
| Rechazar con motivo | S | S |
| Ver albaranes ya firmados por él | S, últimos 90 días | – |
| Cualquier otra acción | – | – |

### 5.4. Usuarios y sistema

| Acción | Admin | Oficina | Operario | Consulta |
|---|---|---|---|---|
| Crear usuarios Operario y enviar enlace de activación | S | S | – | – |
| Crear usuarios Oficina, Consulta y Administrador | S | – | – | – |
| Editar datos de usuarios | S | Solo Operarios | P, solo teléfono y correo | – |
| Bloquear y desbloquear | S | Solo Operarios | – | – |
| Dar de baja | S | – | – | – |
| Cerrar sesiones remotas de un usuario | S | Solo Operarios | P | – |
| Activar doble factor | S, propio | – | – | – |
| Ver registro de auditoría completo | S | S, sin acciones sobre usuarios | – | S |
| Configuración general (numeración, textos de SMS, plazos) | S | – | – | – |

---

## 6. AUTENTICACIÓN

### 6.1. Identificador de acceso
El **teléfono móvil** es el identificador. Los operarios no recuerdan correos de empresa pero sí su número. El correo es opcional y alternativo.

### 6.2. Alta y activación
1. Oficina crea el usuario con nombre, teléfono y roles.
2. FleetCore envía un SMS: "Hola Juan, tu acceso a FleetCore de [empresa]: [enlace]". El enlace lleva un token de un solo uso que caduca a las 72 h.
3. El usuario abre el enlace, ve su nombre, fija una contraseña y entra directamente.
4. Si el enlace caduca, oficina pulsa "Reenviar".

### 6.3. Política de contraseñas
- Mínimo 10 caracteres. Sin exigir mayúsculas ni símbolos: la longitud protege más y se recuerda mejor.
- Se rechazan contraseñas presentes en listas de contraseñas filtradas conocidas.
- Se rechaza la contraseña que contenga el nombre o el teléfono del usuario.
- Sin caducidad periódica.
- Hash con Argon2id (opción por defecto de Symfony).

### 6.4. Bloqueo por intentos
- 5 intentos fallidos seguidos: bloqueo de 15 minutos.
- Cada bloqueo adicional en 24 h duplica el tiempo, hasta 4 horas.
- Oficina y Administración ven el estado y pueden desbloquear.
- El contador se reinicia al acceder correctamente o al cambiar la contraseña.

### 6.5. Recuperación
- "He olvidado mi contraseña" → introduce teléfono → SMS con enlace de un solo uso, caducidad 30 minutos.
- La respuesta en pantalla es la misma exista o no el teléfono, para no revelar usuarios.
- Máximo 3 solicitudes por hora por teléfono.
- Si el usuario tiene correo, puede elegir recibirlo ahí.

### 6.6. Doble factor
- Solo disponible para `ROLE_ADMIN`, opcional, activado por el propio usuario.
- Código por SMS o aplicación de códigos temporales (TOTP). Se recomienda TOTP.
- Si lo activa, se le muestran 8 códigos de recuperación para guardar.

---

## 7. SESIONES

Dos perfiles de sesión según la interfaz. Se implementan con **un token opaco por dispositivo**, aleatorio, almacenado con hash en la tabla `access_token` con usuario, dispositivo, caducidad y marca de revocado. Es el patrón de Laravel Sanctum. Decisión en `decisiones-descubrimiento-03-tecnicas.md`, punto 6; sustituye al esquema de token de acceso más token de renovación.

| | Oficina y Consulta (navegador de escritorio) | Operario (móvil) |
|---|---|---|
| Duración sin actividad | 8 horas | 30 días |
| Renovación | La caducidad se extiende en cada petición autenticada | Igual |
| Cierre al cerrar el navegador | Sí | No |
| Sesiones simultáneas | Hasta 3 dispositivos | Hasta 2 dispositivos |
| Cierre remoto | Por Admin | Por Oficina o Admin, y por el propio operario desde otro dispositivo |

Cierre remoto: marca el token como revocado. El dispositivo pierde acceso en la siguiente petición. Los borradores guardados en ese móvil ya sincronizados no se pierden; los no sincronizados sí, y la pantalla lo advierte al volver a entrar.

Un usuario con rol Oficina y Operario que entra desde el móvil tiene sesión de 30 días; desde escritorio, de 8 horas. El perfil lo decide la interfaz, no el rol.

---

## 8. ENLACES DE FIRMA PARA ENCARGADOS

No son sesiones de usuario, pero se gestionan en el mismo módulo de seguridad.

| Elemento | Regla |
|---|---|
| Token de enlace | Aleatorio, de un solo uso para iniciar; caduca a los 7 días. Oficina u operario pueden reenviar |
| Código SMS | 6 dígitos, caduca a los 10 minutos, máximo 5 intentos |
| Sesión de firma | Tras validar el código, 30 minutos de actividad para revisar y firmar. Después hay que pedir otro código |
| Reenvíos | Máximo 3 enlaces por encargado y día |
| Ámbito | Solo albaranes `pendiente_firma` dirigidos a ese contacto, más sus firmados de los últimos 90 días |
| Registro | Cada envío, apertura, código validado y firma quedan en auditoría con IP y dispositivo |

---

## 9. IMPLEMENTACIÓN EN SYMFONY

- **Symfony Security** con un proveedor de usuarios sobre la entidad User, identificador `telefono_movil`.
- **Jerarquía de roles** en `security.yaml` como se indica en la sección 4.
- **Voters**, uno por entidad protegida, con los atributos de acción de la matriz: `ReportVoter` (`VIEW`, `EDIT_DRAFT`, `SEND`, `EDIT_PENDING`, `WITHDRAW`, `CANCEL`, `RECTIFY`, `DOWNLOAD_PDF`), `WorkSiteVoter` (`VIEW`, `EDIT`, `OPEN`, `CLOSE`, `SET_COORDINATES`), `UserVoter` (`CREATE_WITH_ROLES`, `EDIT`, `BLOCK`, `DEACTIVATE`, `REVOKE_SESSIONS`), y voters genéricos para recursos maestros.
- **Autenticación de API** con tokens opacos por dispositivo (tabla `access_token`: usuario, hash del token, dispositivo, caducidad, revocado) mediante un `AccessTokenHandler` propio. Un autenticador distinto para los **enlaces de firma**, que produce un "usuario" efímero de tipo firmante limitado a `ReportSignVoter`.
- **Limitación de peticiones** (rate limiter de Symfony) en acceso, recuperación, envío de SMS y validación de códigos.
- **Auditoría**: cada decisión denegada por un Voter en una petición autenticada se registra, para detectar intentos de acceder a albaranes ajenos.

---

## 10. Integridad en base de datos (PostgreSQL)

| Tabla | Restricción |
|---|---|
| app_user | `UNIQUE (telefono_movil)`; `UNIQUE (email) WHERE email IS NOT NULL`; `UNIQUE (numero_operario) WHERE numero_operario IS NOT NULL`; `CHECK (estado IN ('pendiente_activacion','activo','bloqueado','baja'))`; `CHECK (array_length(roles,1) >= 1)` o tabla `app_user_role (user_id, role) PK` |
| access_token | `FK user_id`; `UNIQUE (token_hash)`; `CHECK (perfil IN ('escritorio','movil'))`; índice en `(user_id, revocado)` |
| activation_token | `FK user_id`; `UNIQUE (token_hash)`; `CHECK (tipo IN ('activacion','recuperacion'))` |
| signing_link | `FK client_contact_id`; `UNIQUE (token_hash)`; `CHECK (estado IN ('enviado','abierto','validado','caducado','agotado'))` |

Garantía "al menos un Administrador activo": se valida en aplicación dentro de la transacción y con un trigger de base de datos que impide dejar la tabla sin ninguno.

---

## 11. Cambios respecto al documento maestro v1.0

| Sección | Cambio |
|---|---|
| 6.2 Roles | Desaparece Encargado como rol. Aparece Consulta. Se permite más de un rol por usuario |
| 6.2 Permisos | Matriz completa definida (secciones 5.1 a 5.4) |
| 15 Modelo | User y Employee se fusionan en User. Role deja de ser entidad: son constantes. Se añaden RefreshToken, ActivationToken y SigningLink |
| 20.8 Seguridad | Resueltos autenticación, política de contraseñas, recuperación, sesiones y autorización por obra (por estado de obra, no por asignación) |

## 12. Pendiente para fases siguientes
- Albarán completo (fase 9): campos, líneas, numeración, PDF, pantallas del operario.
- Flujo de firma detallado (fase 10): pantallas del encargado, contenido de los SMS, evidencia almacenada.
