# FLEETCORE

## Documento maestro de descubrimiento, requisitos y especificaciones del producto
**Versión:** 1.0 — Documento de descubrimiento
**Fecha:** 21 de noviembre de 2026
**Estado:** Análisis funcional y definición de producto
**Sector:** Excavaciones, demoliciones, maquinaria y movimiento de tierras
**Alcance inicial:** Gestión digital de albaranes y preparación administrativa de trabajos.

---

# 1. RESUMEN EJECUTIVO

## 1.1. ¿Qué es FleetCore?
FleetCore es un SaaS (Software as a Service) destinado a digitalizar y optimizar la gestión operativa de empresas de excavaciones, demoliciones, movimiento de tierras y servicios de maquinaria.

El producto nace de una necesidad real detectada en el funcionamiento diario de una empresa del sector: sustituir los albaranes físicos por un sistema digital que permita preparar los trabajos desde oficina, registrar las jornadas desde dispositivos móviles y obtener documentos verificables mediante la revisión y firma de los responsables.

FleetCore no debe limitarse a reproducir el albarán de papel en una pantalla.

Su finalidad es mejorar todo el proceso que existe antes, durante y después de la generación del albarán.

Para conseguirlo, el sistema necesita:

1. Una base de datos centralizada con la información de clientes, obras, maquinaria, camiones y equipos.
2. Un entorno administrativo para que oficina prepare los trabajos.
3. Un entorno móvil para que los operarios registren sus jornadas.
4. Un sistema de revisión y firma para los encargados.
5. Un sistema de almacenamiento documental e historial.
6. Un mecanismo de validación, seguridad e integridad de datos.
7. Una arquitectura que permita ampliar el producto en el futuro.

## 1.2. Problema que resuelve
Actualmente, la empresa utiliza albaranes físicos que deben ser cumplimentados por los operarios y entregados posteriormente en oficina, normalmente con la firma del responsable.

Este procedimiento presenta varias limitaciones:

- Introducción manual de información.
- Repetición de datos en jornadas consecutivas.
- Posibilidad de errores al escribir nombres, matrículas y referencias.
- Dependencia del papel.
- Necesidad de transportar y almacenar documentos físicos.
- Dificultad para consultar rápidamente albaranes anteriores.
- Dificultad para conocer qué documentos están pendientes de firma.
- Dependencia de que los documentos lleguen físicamente a oficina.
- Falta de centralización de la información.
FleetCore pretende solucionar estos problemas mediante una aplicación web con usuarios, roles, formularios administrativos y generación automatizada de albaranes.

## 1.3. Principio fundamental del producto
La decisión funcional más importante tomada durante el descubrimiento es:

**La oficina prepara todo el trabajo y el operario solamente prepara los albaranes.**

Esto significa que la oficina es responsable de introducir previamente los datos administrativos y operativos necesarios.

El operario no debe tener que crear clientes, obras, máquinas ni completar información que ya debería estar registrada.

El sistema debe ofrecerle información previamente preparada para que pueda realizar su trabajo administrativo diario en el menor tiempo posible.

---

# 2. CONTEXTO OPERATIVO DE LA EMPRESA

## 2.1. Actividad empresarial
La empresa desarrolla actividades relacionadas con:

- Excavaciones.
- Demoliciones.
- Movimientos de tierras.
- Trabajos de maquinaria pesada.
- Servicios de camiones.
- Transporte y movimiento de materiales.
- Servicios de maquinaria con operario.
Una particularidad importante es que la empresa presta servicios a otras empresas.

Por ejemplo, un cliente puede contratar una excavadora con su operario para realizar determinados trabajos en una obra.

También puede contratar un camión con conductor o diferentes servicios de maquinaria.

Por tanto, FleetCore debe permitir documentar qué servicio se ha realizado, para qué cliente, en qué obra, con qué recursos y durante cuánto tiempo.

## 2.2. Cómo funciona actualmente el trabajo
El proceso operativo que queremos digitalizar es:

1. La empresa recibe un encargo.
2. Oficina conoce al cliente y la obra.
3. Oficina organiza los recursos necesarios.
4. Se determina qué maquinaria o camiones se utilizarán.
5. Los operarios realizan los trabajos.
6. Se cumplimentan los albaranes.
7. El encargado revisa y firma.
8. Los documentos quedan registrados para su posterior consulta y gestión.
El SaaS debe digitalizar este procedimiento sin trasladar responsabilidades administrativas innecesarias a los operarios.

## 2.3. Particularidades de la maquinaria
Se han identificado varias características del funcionamiento real:

- Los operarios pueden cambiar de máquina.
- Una máquina puede ser utilizada por distintos operarios.
- Una máquina puede cambiar de obra.
- Se alquilan máquinas a terceros.
- Las máquinas alquiladas pueden incorporarse a una obra temporalmente.
- Oficina debe conocer y registrar las máquinas que se van a utilizar.
- Los implementos pueden cambiar durante una jornada.
Por tanto, no debe existir una relación permanente entre operario, máquina y obra.

Las relaciones deben establecerse en función de cada trabajo y de cada jornada.

---

# 3. ALCANCE DEL PRODUCTO

## 3.1. Alcance de la primera versión
Se ha decidido centrar inicialmente el proyecto en los albaranes.

No vamos a intentar desarrollar desde el principio un ERP completo ni gestionar todas las áreas de la empresa.

La primera versión debe resolver correctamente:

- Gestión de clientes.
- Gestión de obras.
- Gestión de maquinaria.
- Gestión de camiones.
- Gestión de equipos de transporte.
- Preparación de trabajos desde oficina.
- Creación de albaranes por operarios.
- Registro de horas e implementos.
- Registro de viajes.
- Revisión y firma.
- Historial de documentos.

## 3.2. Funcionalidades futuras
Se han identificado posibles ampliaciones:

- Gestión de alquileres.
- Control avanzado de disponibilidad de maquinaria.
- Informes operativos.
- Estadísticas de utilización.
- Gestión de desplazamientos.
- Gestión de hoteles y comidas.
- Gestión documental ampliada.
- Control de costes.
- Gestión administrativa avanzada.
Estas funcionalidades no deben considerarse obligatorias para la primera versión.

---

# 4. ARQUITECTURA GENERAL DEL SAAS

## 4.1. Tipo de aplicación
**Decisión funcional confirmada:** FleetCore será una aplicación web con usuarios y diferentes permisos de acceso.

No será una página web estática ni un conjunto de formularios independientes.

Será una plataforma centralizada en la que cada usuario accederá mediante sus credenciales y dispondrá de las funcionalidades correspondientes a su rol.

La aplicación tendrá una interfaz adaptada a ordenadores, tabletas y teléfonos móviles.

## 4.2. Entornos de trabajo
Se han identificado tres entornos principales.

### A. Entorno de oficina
Es el panel administrativo.

Desde aquí se preparan los datos que posteriormente utilizarán los operarios.

Contendrá:

- Gestión de clientes.
- Gestión de obras.
- Gestión de maquinaria.
- Gestión de camiones.
- Gestión de equipos de transporte.
- Gestión de implementos.
- Gestión de usuarios.
- Preparación de trabajos.
- Consulta de albaranes.
- Gestión de información administrativa.

### B. Entorno de operarios
Es la interfaz móvil de uso diario.

El operario podrá:

- Iniciar sesión.
- Consultar sus trabajos preparados.
- Seleccionar una obra o trabajo.
- Seleccionar la máquina correspondiente.
- Seleccionar un camión cuando corresponda.
- Seleccionar un equipo de transporte cuando corresponda.
- Introducir horas.
- Añadir implementos.
- Introducir horas por implemento.
- Registrar viajes.
- Añadir observaciones.
- Enviar el albarán.
No tendrá que crear los registros administrativos.

### C. Entorno de encargados
Permitirá:

- Consultar albaranes recibidos.
- Revisar la información.
- Identificar al operario que lo generó.
- Consultar cliente y obra.
- Revisar máquinas y camiones.
- Comprobar horas.
- Revisar implementos.
- Firmar los documentos.
- Consultar los albaranes firmados.
Se ha confirmado que deben contemplarse tanto la firma diaria como la firma de varios albaranes acumulados.

---

# 5. ARQUITECTURA TECNOLÓGICA

## 5.1. Backend
**Decisión confirmada: Symfony.**

Se utilizará Symfony como framework principal del backend.

El backend será responsable de:

- Gestionar usuarios.
- Autenticar usuarios.
- Autorizar operaciones.
- Recibir información de los formularios.
- Validar los datos.
- Ejecutar las reglas de negocio.
- Gestionar relaciones entre entidades.
- Almacenar información.
- Generar y gestionar albaranes.
- Gestionar firmas.
- Mantener la trazabilidad.
- Proteger los documentos.
La lógica de negocio no debe depender del frontend.

Symfony será la fuente de verdad para las validaciones y autorizaciones.

## 5.2. Frontend
La arquitectura propuesta es Next.js.

El frontend tendrá dos responsabilidades principales:

1. Presentar los formularios y las interfaces.
2. Facilitar la interacción de los usuarios con el backend.
Se había planteado utilizar Zod para las validaciones del lado del cliente.

Las validaciones del frontend mejorarán la experiencia de usuario, pero nunca sustituirán las comprobaciones del backend.

**Estado:** Next.js forma parte de la arquitectura propuesta anteriormente; queda pendiente cerrar formalmente la versión y las decisiones técnicas definitivas.

## 5.3. Base de datos
La propuesta inicial es PostgreSQL.

Se utilizaría como almacenamiento centralizado de:

- Usuarios.
- Clientes.
- Obras.
- Maquinaria.
- Camiones.
- Equipos de transporte.
- Implementos.
- Trabajos.
- Albaranes.
- Firmas.
- Historial de acciones.
Se plantea utilizar Doctrine ORM para gestionar las entidades y sus relaciones.

**Estado:** PostgreSQL está propuesto como motor de base de datos, pero todavía no hemos cerrado el diseño físico ni todas las decisiones de infraestructura.

## 5.4. Arquitectura general
El esquema funcional sería:

Usuario → Next.js → API Symfony → Doctrine ORM → PostgreSQL.

Los documentos y fotografías se gestionarían mediante un sistema de almacenamiento de archivos protegido, cuya solución técnica queda pendiente.

No se ha decidido utilizar microservicios. Para la primera versión, una arquitectura modular dentro de una aplicación Symfony sería una alternativa razonable.

---

# 6. SISTEMA DE USUARIOS Y ROLES

## 6.1. Usuarios
Cada trabajador que utilice FleetCore tendrá una cuenta individual.

La identidad del usuario será importante para registrar quién ha realizado cada acción.

No se deben compartir cuentas entre operarios.

## 6.2. Roles identificados

### Administrador
Responsable de la configuración general.

Permisos previstos:

- Gestionar usuarios.
- Gestionar roles.
- Consultar información general.
- Gestionar configuración.
- Acceder a funcionalidades administrativas.

### Oficina
Responsable de preparar y mantener la información.

Permisos previstos:

- Crear clientes.
- Modificar clientes.
- Crear obras.
- Modificar obras.
- Registrar máquinas.
- Registrar camiones.
- Registrar equipos.
- Gestionar catálogos.
- Preparar trabajos.
- Consultar albaranes.

### Operario
Responsable de crear los albaranes.

Permisos previstos:

- Consultar trabajos autorizados.
- Consultar recursos disponibles para sus trabajos.
- Crear albaranes.
- Introducir horas.
- Añadir implementos.
- Registrar viajes.
- Añadir observaciones.
- Enviar albaranes.

### Encargado
Responsable de revisar y firmar.

Permisos previstos:

- Consultar albaranes asignados.
- Revisar datos.
- Firmar documentos.
- Consultar historial de firmas.
La matriz completa de permisos todavía está pendiente.

---

# 7. FORMULARIOS ADMINISTRATIVOS DE OFICINA
Este es uno de los bloques más importantes de FleetCore.

Hemos determinado que la oficina debe introducir previamente los datos necesarios para que el operario no tenga que reconstruir el trabajo cada día.

Los formularios administrativos generan los registros maestros que utilizará el resto del sistema.

## 7.1. FORMULARIO 1: CLIENTES
**Estado: estructura inicial aceptada.**

Cada cliente tendrá una ficha independiente.

Un cliente puede tener múltiples obras.

### Campos definidos

Campo | Tipo | Validación

ID | UUID o identificador interno | Generación automática

Razón social | Texto | Obligatorio

CIF/NIF | Texto | Formato y dígito de control

Teléfono | Texto | Formato razonable

Email | Texto | Formato de email

Dirección fiscal | Texto | Validación de longitud

Fecha de creación | Fecha/hora | Automática

Estado | Booleano o estado | Controlado

### Reglas funcionales

1. La razón social será obligatoria.
2. El CIF/NIF será opcional cuando corresponda.
3. Si existe identificación fiscal, se comprobará su formato.
4. Se evitarán duplicados de identificación fiscal.
5. Los emails se normalizarán.
6. Los teléfonos se almacenarán como texto, no como números matemáticos.
7. El cliente podrá tener múltiples obras.
8. No se eliminará físicamente un cliente que tenga documentos históricos relacionados.

### Relación de datos
Cliente 1 → N Obras.

### Cuestiones pendientes

- Si será obligatorio disponer de CIF/NIF.
- Si se almacenarán varios teléfonos.
- Si habrá varios contactos por cliente.
- Si habrá direcciones diferentes de facturación y contacto.
- Si será necesario registrar personas de contacto.

---

## 7.2. FORMULARIO 2: OBRAS
**Estado: estructura inicial aceptada provisionalmente.**

Cada obra estará asociada a un cliente existente.

La oficina será responsable de crearla.

El operario no podrá crear obras libremente.

### Campos definidos

Campo | Tipo | Validación

ID | UUID o identificador | Automático

Cliente | Selector de cliente | Obligatorio

Nombre de obra | Texto | Obligatorio

Código interno | Texto | Opcional

Dirección | Texto | Según definición final

Localidad | Texto | Pendiente

Provincia | Texto | Pendiente

Código postal | Texto | Pendiente

Encargado | Relación con usuario/contacto | Opcional

Fecha de inicio | Fecha | Opcional

Fecha de finalización | Fecha | Opcional

Estado | Estado controlado | Obligatorio

### Funcionamiento
La oficina entra en el formulario de obras.

Primero selecciona un cliente existente.

Después introduce los datos de la obra.

Por ejemplo:

Cliente: Red Eléctrica.

Obra: Numancia de la Vega.

Ubicación: dirección correspondiente.

Encargado: Pepe.

La información queda registrada y disponible para preparar los trabajos.

### Reglas de negocio

- No se puede crear una obra sin cliente.
- El cliente seleccionado debe existir.
- La obra debe tener identificación propia.
- Una obra puede tener muchos albaranes.
- Una obra puede utilizar diferentes máquinas.
- Una obra puede tener diferentes operarios.
- Una obra no debe tener máquinas asignadas permanentemente.

### Relación de datos
Cliente 1 → N Obras.

Obra 1 → N Trabajos.

Obra 1 → N Albaranes.

### Cuestiones pendientes

- Si una obra puede tener varios encargados.
- Si los encargados serán usuarios de FleetCore o contactos externos.
- Si una obra puede tener varias ubicaciones.
- Si se necesitan coordenadas GPS.
- Si se permitirá cambiar el cliente asociado a una obra después de crearla.
- Si se manejarán fechas reales y fechas previstas por separado.

---

## 7.3. FORMULARIO 3: MAQUINARIA
**Estado: estructura funcional aceptada.**

Cada máquina tendrá una ficha independiente.

La oficina registra la máquina una sola vez y posteriormente el operario la selecciona en los albaranes.

Se ha confirmado que la categoría debe formar parte del formulario.

No queremos registrar una máquina únicamente con su marca y modelo: debemos identificar qué clase de máquina es.

### Campos del formulario

Campo | Tipo | Estado

ID | UUID | Automático

Categoría | Selector | Obligatorio

Marca | Texto/selector | Según catálogo

Modelo | Texto | Obligatorio

Número de serie | Texto | Según disponibilidad

Matrícula | Texto | Cuando corresponda

Número interno de flota | Texto | Opcional

Propiedad | Propia/alquilada | Obligatorio

Proveedor | Relación con proveedor | Si es alquilada

Fotografía | Archivo | Opcional

Fotografía de placa | Archivo | Opcional

Documentación | Archivos | Opcional

Fecha de alta | Fecha/hora | Automática

Estado | Estado controlado | Obligatorio

Observaciones | Texto largo | Opcional

### Categorías de maquinaria
Catálogo inicial propuesto:

- Excavadora giratoria.
- Retroexcavadora.
- Pala cargadora.
- Dumper.
- Manipulador telescópico.
- Tractor.
- Cosechadora.
- Otras categorías utilizadas por la empresa.
La lista definitiva deberá adaptarse al parque real de maquinaria.

### Propiedad
Se contemplan dos posibilidades:

**Maquinaria propia**

Pertenece a la empresa.

**Maquinaria alquilada**

Procede de un proveedor externo.

Cuando sea alquilada, deberá poder relacionarse con el proveedor correspondiente.

### Identificación de máquinas
Se ha hablado expresamente de la importancia de la placa identificativa.

La fotografía de la placa permite identificar correctamente modelo y número de serie.

Sin embargo, se ha decidido que el proceso normal de alta debe realizarlo oficina.

El operario no debe convertirse en responsable de registrar las máquinas alquiladas.

La fotografía de la placa puede almacenarse como documentación de la ficha.

### Reglas de validación

- Categoría obligatoria.
- Marca y modelo correctamente identificados.
- Matrícula validada cuando corresponda.
- Número de serie validado.
- Control de duplicados.
- Propiedad obligatoria.
- Proveedor relacionado cuando se trate de maquinaria alquilada.
- Estado controlado.
- Archivos verificados.

### Consideración importante
No todas las máquinas tienen matrícula.

Por tanto, no se debe utilizar la matrícula como identificador universal.

La identificación interna y el número de serie deben tener un tratamiento independiente.

---

## 7.4. FORMULARIO 4: CAMIONES
**Estado: formulario identificado; estructura pendiente de cerrar.**

Inicialmente se planteó utilizar el mismo formulario de maquinaria y añadir camión como categoría.

Posteriormente detectamos que los camiones tienen particularidades que justifican un formulario específico.

La conclusión funcional es que conviene mantener una gestión específica para camiones, aunque compartan algunos datos generales con maquinaria.

### Campos identificados

Campo | Tipo

ID | Identificador interno

Matrícula | Texto

Marca | Texto

Modelo | Texto

Número de bastidor | Texto

Número interno de flota | Texto

Tipo de vehículo | Selector

Número de ejes | Número

Capacidad de carga | Número/unidad

Propiedad | Propio/alquilado

Proveedor | Relación

Documentación | Archivos

Fotografía | Archivo

Estado | Estado controlado

Observaciones | Texto

### Tipos de vehículos identificados

- Camión rígido.
- Cabeza tractora.
- Dumper.
- Otros tipos que utilice la empresa.

### Particularidad de las cabezas tractoras
Se ha identificado una situación real de la empresa:

Hay cabezas tractoras que pueden trabajar con distintos equipos acoplables.

Una cabeza tractora puede llevar una bañera y posteriormente utilizar un carro transportador.

Por eso no debemos almacenar el tipo de remolque como característica fija de la cabeza tractora.

El camión y el equipo acoplable son elementos independientes.

---

## 7.5. FORMULARIO 5: EQUIPOS ACOPLABLES DE TRANSPORTE
**Estado: necesidad confirmada; formulario identificado.**

Durante el análisis de camiones apareció una necesidad que no estaba contemplada inicialmente.

Existen equipos de transporte que no forman parte permanente de una cabeza tractora.

Ejemplos:

- Bañeras.
- Chatas transportadoras.
- Carros transportadores.
- Remolques.
- Otros equipos similares.
Se aclaró que estos elementos tienen:

- Matrícula propia.
- Número de serie.
- Identificación independiente.
Por tanto, son equipos independientes y no simples accesorios del camión.

### Campos propuestos

Campo | Tipo

ID | UUID

Categoría | Selector

Matrícula | Texto

Número de serie | Texto

Marca | Texto

Modelo | Texto

Número interno | Texto

Propiedad | Propio/alquilado

Proveedor | Relación

Documentación | Archivos

Fotografía | Archivo

Estado | Estado controlado

Observaciones | Texto

### Categorías iniciales

- Bañera.
- Chata transportadora.
- Carro transportador.
- Remolque.
- Otros.

### Decisión especialmente importante: intercambiabilidad
Se ha confirmado que estos equipos pueden cambiarse entre distintas cabezas tractoras.

Ejemplo real explicado:

La empresa dispone de dos cabezas tractoras que pueden utilizar cualquiera de los equipos compatibles disponibles.

Por tanto:

- Los equipos no pertenecen permanentemente a una cabeza tractora.
- Las cabezas tractoras no tienen un equipo fijo.
- El equipo utilizado debe registrarse según la jornada.
- FleetCore debe conservar qué equipo se utilizó con cada camión.

### Restricción confirmada
Una cabeza tractora solo puede llevar un equipo acoplado simultáneamente.

No se permitirá registrar dos equipos acoplados a la misma cabeza tractora en el mismo momento.

### Implicación para la base de datos
La relación entre camiones y equipos debe ser flexible y temporal.

No debe existir una columna fija del estilo `truck_id` dentro de la ficha permanente del equipo para representar una asignación exclusiva.

La asignación deberá registrarse en una entidad independiente o en el detalle correspondiente del albarán.

---

# 8. CATÁLOGO DE IMPLEMENTOS DE MAQUINARIA
**Estado: catálogo necesario y funcionamiento confirmado.**

Los implementos de maquinaria son distintos de los equipos acoplables de transporte.

Los implementos se utilizan con máquinas de excavación y maquinaria de obra.

Se han mencionado:

- Cazo.
- Martillo.
- Sisaya.
- Pinza.
- Otros implementos.

### Requisitos

1. Oficina mantiene el catálogo.
2. El operario selecciona los implementos.
3. No escribe sus nombres manualmente.
4. Un mismo albarán puede incluir varios implementos.
5. Cada implemento tiene sus propias horas.
6. Cada implemento queda asociado a la máquina que lo utilizó.

### Diferencia entre categorías
Es fundamental no mezclar estos conceptos:

Elemento | Función

Máquina | Equipo principal de trabajo

Implemento | Herramienta utilizada por una máquina

Camión | Vehículo de transporte

Equipo acoplable | Bañera, chata o carro conectado a una cabeza tractora
Aunque todos sean recursos de la empresa, tienen comportamientos funcionales diferentes.

---

# 9. PREPARACIÓN DE TRABAJOS DESDE OFICINA
**Decisión confirmada: oficina prepara todo el trabajo.**

Este punto es fundamental para comprender cómo se utilizará FleetCore.

La oficina no solamente registra clientes y máquinas.

También debe preparar los trabajos para que los operarios puedan seleccionarlos.

### Ejemplo
La empresa recibe un encargo para trabajar en una obra de Red Eléctrica en Madrid.

La oficina:

1. Selecciona el cliente.
2. Selecciona o crea la obra.
3. Registra la ubicación.
4. Selecciona el encargado.
5. Prepara la maquinaria que se utilizará.
6. Prepara los camiones necesarios.
7. Prepara los equipos de transporte cuando corresponda.
8. Define los recursos disponibles para ese trabajo.
9. Deja el trabajo disponible para los operarios correspondientes.

### Principio de funcionamiento
El operario no debe reconstruir la configuración del trabajo.

Debe encontrarla preparada.

### Entidad propuesta: Trabajo
Conviene diferenciar una obra de un trabajo.

Una obra representa el lugar o proyecto donde se presta el servicio.

Un trabajo representa la organización operativa de una actividad concreta dentro de esa obra.

Por ejemplo:

- Cliente: Red Eléctrica.
- Obra: Numancia de la Vega.
- Trabajo: Excavación y movimiento de tierras.
- Encargado: Pepe.
- Recursos habilitados: máquinas y camiones correspondientes.
Una obra puede tener varios trabajos.

Esta separación es una propuesta de modelado que todavía debemos terminar de validar.

---

# 10. FUNCIONAMIENTO DEL OPERARIO Y CREACIÓN DEL ALBARÁN
Este es el proceso central de FleetCore.

El albarán es el resultado documental de la actividad diaria.

Pero antes de llegar a él necesitamos que oficina haya preparado toda la información.

## 10.1. Acceso
El operario inicia sesión con su usuario.

FleetCore identifica automáticamente:

- Usuario.
- Identificador de operario.
- Rol.
- Permisos.
No se debe pedir al operario que escriba manualmente su nombre en cada albarán.

## 10.2. Selección del trabajo
El operario consulta los trabajos disponibles para él.

Selecciona el correspondiente a la jornada.

Por ejemplo:

Red Eléctrica — Numancia de la Vega.

Los datos generales se recuperan automáticamente.

## 10.3. Selección de máquina
El operario selecciona una máquina del catálogo habilitado para el trabajo.

El sistema recupera:

- Categoría.
- Marca.
- Modelo.
- Identificación.
- Número interno.

## 10.4. Registro de horas de máquina
El operario introduce las horas totales trabajadas durante la jornada.

Se ha confirmado que las horas se registran como horas totales, no necesariamente mediante contador inicial y final.

## 10.5. Registro de implementos
Se ha definido una interfaz sencilla.

El operario selecciona un implemento y escribe las horas utilizadas.

Después puede pulsar un botón para añadir otro implemento.

Ejemplo:

Implemento | Horas

Cazo | 4

Martillo | 4

Sisaya | 2

Total | 10
El sistema calcula automáticamente la suma.

**Regla confirmada:** las horas de los implementos no tienen que coincidir obligatoriamente con las horas totales de la máquina.

Son registros independientes.

## 10.6. Registro de camión
Cuando corresponda, el operario podrá seleccionar un camión.

Introducirá sus horas totales y los viajes realizados.

Si se trata de una cabeza tractora con equipo acoplable, se deberá registrar qué equipo lleva ese día.

## 10.7. Registro de equipo acoplable
El operario selecciona el equipo correspondiente de los habilitados por oficina.

Solo puede seleccionar uno para cada cabeza tractora en un mismo momento.

La relación queda registrada en el albarán.

## 10.8. Observaciones
El albarán podrá incorporar observaciones.

Su obligatoriedad, extensión máxima y tratamiento todavía deben definirse.

## 10.9. Generación
Una vez completados los datos, el sistema genera un albarán diario.

Cada jornada produce un documento independiente.

---

# 11. CONTINUIDAD DE TRABAJOS Y REUTILIZACIÓN DE INFORMACIÓN
**Necesidad confirmada.**

Esta funcionalidad es esencial para conseguir una buena adopción entre los operarios.

### Problema
Un operario puede trabajar durante cinco días en la misma obra, con la misma máquina y el mismo encargado.

No queremos que tenga que introducir todos los datos cinco veces.

### Solución
FleetCore conserva la configuración del trabajo.

El primer día se selecciona el trabajo preparado por oficina.

Los siguientes días se reutiliza esa configuración.

El operario modifica únicamente los datos variables de cada jornada.

### Ejemplo

Día | Acción del operario

Lunes | Selecciona trabajo y completa los datos

Martes | Recupera trabajo y actualiza horas

Miércoles | Recupera trabajo y modifica implementos si es necesario

Jueves | Recupera trabajo y actualiza horas

Viernes | Recupera trabajo y completa el último albarán
Cada día genera un documento nuevo.

No se debe sobrescribir el albarán anterior.

### Datos reutilizables

- Cliente.
- Obra.
- Ubicación.
- Encargado.
- Máquina.
- Camión.
- Equipos disponibles.
- Otros datos generales.

### Datos variables

- Fecha.
- Horas.
- Implementos.
- Horas por implemento.
- Viajes.
- Observaciones.
- Recursos efectivamente utilizados.

---

# 12. ALBARÁN: DOCUMENTO FINAL

## 12.1. Estructura funcional
El albarán digital debe conservar la información necesaria del documento físico actual.

Campos identificados:

Campo | Origen

Número de albarán | Generación automática

Fecha | Registro de jornada

Cliente | Datos precargados

Obra | Datos precargados

Ubicación | Datos de obra

Operario | Usuario autenticado

Identificador de operario | Usuario autenticado

Encargado | Trabajo preparado

Máquina | Catálogo

Horas de máquina | Operario

Implementos | Catálogo

Horas de implementos | Operario

Camión | Catálogo

Equipo acoplable | Catálogo

Horas de camión | Operario

Viajes | Operario

Observaciones | Operario

Firma | Encargado

## 12.2. Identidad del operario
Se ha aclarado expresamente que el operario no firma el albarán.

Sin embargo, debe quedar registrado quién lo generó.

Si un trabajador inicia sesión y crea un albarán, el sistema guarda automáticamente su identidad.

Se deben conservar:

- Nombre del usuario.
- Identificador interno.
- Número de operario, si existe.
- Fecha y hora de creación.
Estos datos no tienen por qué aparecer todos visualmente en el PDF, pero deben quedar registrados.

## 12.3. Firma del encargado
El encargado es quien firma.

Se contemplan dos modalidades:

- Firma diaria.
- Firma acumulada de varios albaranes al final de la semana.
La solución técnica de firma y su nivel de autenticación todavía están pendientes.

## 12.4. Estados documentales
Estados propuestos:

- Borrador.
- Enviado.
- Pendiente de firma.
- Firmado.
- Rechazado.
- Rectificado o anulado.
La máquina de estados definitiva debe diseñarse antes de desarrollar.

## 12.5. Integridad documental
Un documento firmado no debe poder modificarse silenciosamente.

Las correcciones deberán conservar:

- Documento original.
- Usuario que solicita la corrección.
- Motivo.
- Fecha.
- Cambios realizados.
- Nueva aprobación cuando corresponda.

## 12.6. Generación PDF
Se ha identificado la necesidad de poder obtener documentos PDF.

El diseño definitivo debe conservar la estructura del albarán físico y adaptarla al entorno digital.

---

# 13. VALIDACIÓN, SANITIZACIÓN Y SEGURIDAD
Este bloque se incorporó expresamente al descubrimiento técnico.

La finalidad es garantizar que los formularios de oficina y los albaranes no admitan datos incorrectos o maliciosos.

## 13.1. Validación en frontend
Se ha planteado utilizar Zod en Next.js.

Responsabilidades:

- Comprobar campos obligatorios.
- Validar formatos.
- Comprobar longitudes.
- Validar números.
- Validar fechas.
- Mostrar errores comprensibles.
- Evitar envíos innecesarios con datos incompletos.

## 13.2. Validación en backend
**Symfony será la fuente de verdad.**

Se utilizará Symfony Validator y las reglas correspondientes en cada entidad o DTO.

La validación del frontend no será suficiente.

Un usuario podría enviar una petición directamente a la API sin utilizar la interfaz de Next.js.

Por tanto, el backend deberá volver a validar todos los datos.

## 13.3. Validaciones específicas

### Clientes

- Razón social obligatoria.
- CIF/NIF con formato válido.
- Comprobación del dígito de control cuando corresponda.
- Email válido.
- Teléfono controlado.
- Longitudes máximas.
- Identificación fiscal duplicada controlada.

### Obras

- Cliente existente.
- Nombre obligatorio.
- Código interno controlado.
- Fechas coherentes.
- Relaciones válidas.
- Longitudes máximas.

### Maquinaria

- Categoría válida.
- Marca y modelo.
- Matrícula validada cuando corresponda.
- Número de serie.
- Control de duplicados.
- Propiedad válida.
- Proveedor existente cuando corresponda.

### Camiones

- Matrícula válida.
- Número de bastidor.
- Tipo de vehículo.
- Datos de propiedad.
- Proveedor.
- Características numéricas dentro de límites razonables.

### Equipos de transporte

- Categoría válida.
- Matrícula.
- Número de serie.
- Identificación.
- Propiedad.
- Control de duplicados.

### Albaranes

- Usuario autenticado.
- Trabajo autorizado.
- Recursos existentes.
- Horas numéricas y no negativas.
- Implementos válidos.
- Equipo acoplable compatible.
- Un único equipo acoplado por cabeza tractora.
- Fecha válida.
- Estado documental permitido.

## 13.4. Sanitización
Se ha aclarado que sanitizar no significa eliminar indiscriminadamente comillas, símbolos o caracteres especiales.

Las operaciones adecuadas incluyen:

- Eliminar espacios sobrantes.
- Normalizar matrículas.
- Normalizar emails.
- Controlar caracteres de control.
- Establecer longitudes máximas.
- Validar tipos.
- Aplicar codificación segura en la salida.
Los datos legítimos deben conservarse correctamente.

## 13.5. Protección SQL Injection
Se utilizará Doctrine ORM y consultas parametrizadas.

No se concatenarán datos introducidos por usuarios directamente en sentencias SQL.

También se revisarán las consultas DQL y SQL nativo que se incorporen al proyecto.

## 13.6. Protección XSS
Se aprovechará el escape automático de React.

Se evitará introducir contenido HTML no confiable mediante mecanismos que permitan ejecutar código.

Los datos almacenados no se considerarán seguros simplemente por haber pasado una validación.

## 13.7. Integridad en PostgreSQL
Además de Symfony, la base de datos debe aplicar restricciones.

Ejemplos:

- Claves primarias.
- Claves foráneas.
- Restricciones UNIQUE.
- NOT NULL.
- CHECK.
- Transacciones.
La validación en aplicación y la integridad en base de datos son capas complementarias.

## 13.8. Archivos
Para fotografías y documentación:

- Validación MIME.
- Control de extensiones.
- Tamaño máximo.
- Nombres aleatorios.
- Almacenamiento protegido.
- Control de permisos.
- Prohibición de ejecución de archivos subidos.

## 13.9. Autorización
La autorización real estará en Symfony.

Se plantea utilizar Symfony Security y Voters para comprobar los permisos sobre recursos concretos.

No bastará con ocultar botones en Next.js.

Un operario no debe poder consultar o modificar trabajos ajenos manipulando una petición.

---

# 14. AUDITORÍA Y TRAZABILIDAD
Se ha identificado como necesidad importante poder saber quién realizó cada operación.

Proponemos un registro de auditoría que permita almacenar:

- Usuario.
- Acción.
- Entidad afectada.
- Identificador del registro.
- Fecha y hora.
- Datos anteriores.
- Datos nuevos.
- Resultado de la operación.
Ejemplos:

- Oficina creó un cliente.
- Oficina modificó una obra.
- Oficina registró una máquina.
- Un operario creó un albarán.
- Un encargado firmó un documento.
La auditoría es especialmente importante cuando los documentos se utilizan como justificantes de trabajos realizados.

### Propuesta técnica
En Symfony se podría implementar mediante servicios de auditoría y eventos de Doctrine, complementados con registros explícitos para operaciones de negocio.

No se ha aprobado todavía un bundle específico.

---

# 15. MODELO DE DATOS CONCEPTUAL
A partir de las necesidades identificadas, estas son las entidades principales que deberá contemplar la base de datos.

Entidad | Finalidad

User | Usuarios del sistema

Role | Roles y permisos

Employee | Datos del trabajador

Client | Clientes

WorkSite | Obras

WorkOrder | Trabajos preparados

Machine | Maquinaria

MachineCategory | Categorías de maquinaria

Truck | Camiones

TruckCategory | Categorías de camiones

TransportEquipment | Bañeras, chatas y carros

EquipmentCategory | Categorías de equipos

Implement | Implementos de maquinaria

Supplier | Proveedores

WorkResource | Recursos habilitados para trabajos

DailyReport | Albaranes diarios

ReportResource | Recursos incluidos en albaranes

ImplementUsage | Horas por implemento

TransportAssignment | Relaciones temporales entre tractora y equipo

ReportSignature | Firmas

Attachment | Archivos

AuditLog | Registro de acciones
Este modelo es conceptual. Los nombres definitivos de las entidades y las relaciones todavía deben definirse.

## 15.1. Relaciones principales
Cliente 1:N Obra.

Obra 1:N Trabajo.

Trabajo 1:N Albarán.

Trabajo N:M Recursos habilitados.

Usuario 1:N Albaranes creados.

Encargado 1:N Firmas.

Albarán 1:N Recursos.

Recurso de máquina 1:N Usos de implementos.

Albarán 1:N Líneas de implementos.

Cabeza tractora 1:N Asignaciones temporales de equipos, sin simultaneidad de equipos incompatibles.

Equipo de transporte 1:N Asignaciones temporales, sin solapamientos incompatibles.

## 15.2. Identificadores
Se propone utilizar UUID para identificadores internos.

La matrícula y el número de serie no deben sustituir al identificador interno.

Esto permitirá que los registros mantengan su identidad aunque cambien algunos datos administrativos.

## 15.3. Eliminación de datos
Se recomienda evitar el borrado físico de registros que tengan relaciones históricas.

Por ejemplo, una máquina que deja de trabajar con la empresa debería marcarse como inactiva, no desaparecer de los albaranes anteriores.

---

# 16. FLUJOS PRINCIPALES DEL SISTEMA

## 16.1. Flujo administrativo

1. Oficina inicia sesión.
2. Registra cliente.
3. Registra obra.
4. Registra maquinaria.
5. Registra camiones.
6. Registra equipos de transporte.
7. Mantiene los catálogos.
8. Prepara un trabajo.
9. Habilita los recursos.
10. El trabajo queda disponible para el operario.

## 16.2. Flujo del operario

1. Inicia sesión.
2. Consulta trabajos disponibles.
3. Selecciona el trabajo.
4. Selecciona máquina.
5. Introduce horas.
6. Añade implementos y horas.
7. Selecciona camión cuando corresponda.
8. Selecciona equipo de transporte cuando corresponda.
9. Registra viajes.
10. Añade observaciones.
11. Revisa los datos.
12. Envía el albarán.

## 16.3. Flujo del encargado

1. Inicia sesión.
2. Consulta albaranes pendientes.
3. Selecciona uno o varios documentos.
4. Comprueba los datos.
5. Firma.
6. FleetCore registra la firma.
7. Los documentos pasan al historial.

---

# 17. REQUISITOS DE EXPERIENCIA DE USUARIO
La facilidad de uso no es un elemento secundario.

Es una condición necesaria para que los operarios adopten el sistema.

### Requisitos identificados

- Diseño mobile-first.
- Pocos pasos.
- Campos precargados.
- Selectores en lugar de escritura manual.
- Reutilización de trabajos.
- Botones claros.
- Formularios breves.
- Errores comprensibles.
- Guardado seguro.
- Confirmación antes de enviar.
- Consulta de albaranes anteriores.

### Diseño de implementos
Se ha definido una interacción especialmente concreta:

El operario selecciona un implemento, introduce las horas y acepta.

Después puede pulsar «Añadir implemento» para repetir la operación.

No queremos una tabla compleja que obligue al operario a comprender estructuras administrativas.

---

# 18. DECISIONES DE PRODUCTO CONSOLIDADAS
Esta tabla resume las decisiones más importantes tomadas durante el descubrimiento.

N.º | Decisión | Estado

1 | FleetCore será un SaaS web | Confirmada

2 | Tendrá usuarios y permisos | Confirmada

3 | El alcance inicial serán los albaranes | Confirmada

4 | Oficina prepara todo el trabajo | Confirmada

5 | El operario solo prepara albaranes | Confirmada

6 | Cliente tiene formulario propio | Aceptada

7 | Obra tiene formulario propio | Aceptada provisionalmente

8 | Maquinaria tiene formulario propio | Aceptada

9 | Maquinaria incluye categoría | Confirmada

10 | Maquinaria propia y alquilada | Confirmada

11 | Oficina registra las máquinas | Confirmada

12 | Los operarios pueden cambiar de máquina | Confirmada

13 | Camiones tienen formulario específico | Acordada funcionalmente

14 | Cabezas tractoras y equipos son independientes | Confirmada

15 | Bañeras y carros tienen matrícula y serie | Confirmada

16 | Equipos intercambiables entre cabezas tractoras | Confirmada

17 | Una tractora lleva un solo equipo a la vez | Confirmada

18 | Implementos tienen catálogo propio | Confirmada

19 | Un albarán admite varios implementos | Confirmada

20 | Cada implemento tiene horas propias | Confirmada

21 | Las horas de implementos se suman automáticamente | Confirmada

22 | No tienen que coincidir obligatoriamente con horas de máquina | Confirmada

23 | Las horas principales son horas totales | Confirmada

24 | Los trabajos pueden reutilizarse durante varios días | Confirmada

25 | Cada jornada genera un albarán independiente | Confirmada

26 | El operario no firma | Confirmada

27 | El encargado firma | Confirmada

28 | Firma diaria y semanal | Confirmada

29 | Symfony será el backend | Confirmada

30 | Validación también en backend | Confirmada

31 | SQL Injection debe prevenirse mediante consultas seguras | Confirmada como requisito

32 | Los documentos firmados deben conservar integridad | Requisito identificado

---

# 19. DECISIONES TÉCNICAS PROPUESTAS

Tecnología o mecanismo | Utilidad | Estado

Symfony | Backend | Confirmado

Next.js | Frontend | Propuesto

PostgreSQL | Base de datos | Propuesto

Doctrine ORM | Persistencia | Propuesto

Symfony Validator | Validación backend | Propuesto

Symfony Security | Autenticación y autorización | Propuesto

Symfony Voters | Permisos sobre recursos | Propuesto

Zod | Validación frontend | Propuesto

Pest/PHPUnit | Pruebas backend | Pendiente

Vitest | Pruebas frontend | Pendiente

UUID | Identificadores internos | Propuesto

Sistema de auditoría | Trazabilidad | Requisito identificado

---

# 20. CUESTIONES PENDIENTES DE DESCUBRIMIENTO
No debemos considerar terminado el análisis funcional.

Estas son las principales áreas que necesitan más conversación.

## 20.1. Clientes

- ¿Puede haber varios contactos?
- ¿Necesitamos CIF obligatorio?
- ¿Se gestionan varias direcciones?
- ¿Se necesita un contacto administrativo diferente del encargado?

## 20.2. Obras

- ¿Una obra puede tener varios encargados?
- ¿Puede tener varias ubicaciones?
- ¿Qué datos necesita obligatoriamente?
- ¿Puede haber varias empresas trabajando en la misma obra?

## 20.3. Maquinaria

- Catálogo definitivo de categorías.
- Campos obligatorios por categoría.
- Control de disponibilidad.
- Gestión de alquileres.
- Fotografías y documentación.
- Identificación interna.

## 20.4. Camiones

- Tipos definitivos.
- Datos específicos según vehículo.
- Campos de viajes.
- Control de disponibilidad.
- Características técnicas necesarias.

## 20.5. Equipos de transporte

- Catálogo definitivo.
- Compatibilidad entre tractores y equipos.
- Gestión de disponibilidad.
- Asignación temporal.
- Control de solapamientos.
- Tratamiento de equipos alquilados.

## 20.6. Trabajos

- Cómo se crean.
- Qué recursos se habilitan.
- Cómo se asignan operarios.
- Cómo se cambian los recursos.
- Cómo se cierran los trabajos.
- Cómo se gestionan varios trabajos simultáneos.

## 20.7. Albaranes

- Formato definitivo.
- Campos obligatorios.
- Numeración.
- Correcciones.
- Anulaciones.
- Estados.
- Firma.
- PDF.
- Entrega al cliente.

## 20.8. Seguridad

- Sistema de autenticación.
- Política de contraseñas.
- Recuperación de cuentas.
- Sesiones.
- Autorización por obra.
- Auditoría.
- Conservación documental.
- Protección de archivos.

## 20.9. Infraestructura

- Alojamiento.
- Copias de seguridad.
- Almacenamiento de archivos.
- Entornos de desarrollo y producción.
- Monitorización.
- Recuperación ante fallos.
- Disponibilidad de la aplicación.

---

# 21. RIESGOS Y ERRORES DE DISEÑO QUE DEBEMOS EVITAR
Durante el descubrimiento han aparecido varias situaciones que condicionan la arquitectura.

### 21.1. Asignaciones permanentes incorrectas
No debemos asociar permanentemente:

- Un operario a una máquina.
- Una máquina a una obra.
- Una bañera a una cabeza tractora.
Estas relaciones pueden cambiar.

### 21.2. Duplicar información
No debemos pedir al operario que vuelva a introducir datos que oficina ya ha registrado.

### 21.3. Mezclar conceptos
No debemos confundir:

- Maquinaria.
- Implementos.
- Camiones.
- Equipos acoplables.
Cada uno tiene características y comportamientos diferentes.

### 21.4. Sobrescribir albaranes
Cada jornada debe generar un documento independiente.

### 21.5. Modificar documentos firmados
No debemos permitir modificaciones invisibles de documentos aprobados.

### 21.6. Confiar solamente en el frontend
Toda validación crítica y autorización debe ejecutarse también en Symfony.

### 21.7. Trasladar tareas administrativas al operario
La oficina debe asumir la preparación y mantenimiento de la información.

---

# 22. PLAN DE TRABAJO PARA COMPLETAR EL DESCUBRIMIENTO
Propongo continuar en el siguiente orden.

Fase | Trabajo | Resultado

1 | Completar clientes | Formulario definitivo

2 | Completar obras | Formulario definitivo

3 | Completar maquinaria | Formulario definitivo

4 | Completar camiones | Formulario definitivo

5 | Completar equipos de transporte | Formulario definitivo

6 | Completar implementos | Catálogo definitivo

7 | Definir usuarios y roles | Matriz de permisos

8 | Definir preparación de trabajos | Formulario de trabajo

9 | Definir albarán | Formulario completo

10 | Definir firmas | Flujo documental

11 | Diseñar base de datos | Modelo entidad-relación

12 | Definir validaciones | Especificación de seguridad

13 | Definir API | Contratos de endpoints

14 | Definir arquitectura | Documento técnico

15 | Diseñar interfaces | Prototipos funcionales

---

# 23. ESTADO ACTUAL DEL PROYECTO
A fecha de este documento, FleetCore se encuentra en una fase de descubrimiento y definición de producto.

Ya tenemos una visión bastante clara de:

- El problema que queremos resolver.
- El usuario principal de cada entorno.
- La responsabilidad de oficina.
- La responsabilidad del operario.
- Los formularios administrativos iniciales.
- La diferencia entre maquinaria, camiones e implementos.
- La independencia de las cabezas tractoras y sus equipos acoplables.
- La lógica general de creación de albaranes.
- La continuidad de trabajos.
- La necesidad de firmas.
- La necesidad de validación y seguridad.
- La elección de Symfony para el backend.
Todavía no tenemos cerrado:

- El modelo de datos definitivo.
- Todos los campos de cada formulario.
- Las relaciones completas.
- El formulario definitivo de preparación de trabajos.
- El flujo definitivo de firma.
- Los estados y transiciones.
- Los contratos de la API.
- La infraestructura.
- El diseño final de las interfaces.

---

# 24. CONCLUSIÓN Y VISIÓN DEL PRODUCTO
FleetCore debe convertirse en una herramienta que centralice la información operativa de una empresa de maquinaria y facilite la generación de documentación fiable.

La solución debe partir de la realidad del trabajo diario.

La oficina conoce los clientes, las obras, las máquinas, los camiones y los equipos. Por eso debe preparar previamente esa información.

El operario conoce el trabajo realizado durante la jornada. Por eso debe limitarse a registrar los datos variables.

El encargado es quien revisa y valida el documento mediante su firma.

La plataforma debe conectar estas tres responsabilidades sin duplicar información ni generar tareas innecesarias.

La principal ventaja de FleetCore no será simplemente eliminar los albaranes de papel.

Será disponer de una estructura de datos centralizada que permita registrar cada trabajo una sola vez, reutilizar la información cuando corresponda, conservar el historial y obtener documentos digitales trazables.

**La prioridad del proyecto debe ser construir correctamente el modelo de negocio y los datos antes de empezar a desarrollar las pantallas.**

Un formulario bien diseñado depende de tener claro qué información necesita, quién la introduce, de dónde procede, dónde se almacena, cómo se valida y qué otras entidades dependen de ella.

Ese será el criterio que utilizaremos para continuar definiendo FleetCore.

---

**FIN DEL DOCUMENTO MAESTRO — VERSIÓN 1.0**

*Documento de trabajo. Las propuestas y cuestiones pendientes no deben interpretarse como funcionalidades aprobadas para producción.*