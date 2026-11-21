# Glosario español ↔ inglés

El dominio se habla en español. El código se escribe en inglés. Esta tabla fija la traducción para que no haya dos nombres para lo mismo.

| Español (negocio) | Inglés (código) | Nota |
|---|---|---|
| Albarán | Report | `DailyReport` se descartó por largo; en contexto siempre es el albarán diario |
| Obra | WorkSite | |
| Cliente | Client | La empresa a la que se factura |
| Contacto de cliente | ClientContact | Personas del cliente |
| Encargado (firmante) | Foreman / signer | `work_site_foreman` para la relación; "signer" en el contexto de firma |
| Oficina | Office | Rol `ROLE_OFICINA` se mantiene en español en el código de rol por legibilidad en la matriz |
| Operario | Operator | Rol `ROLE_OPERARIO` |
| Consulta | Viewer | Rol `ROLE_CONSULTA` |
| Máquina | Machine | |
| Categoría de máquina | MachineCategory | |
| Implemento | Implement | Herramienta de la máquina (cazo, martillo) |
| Tipo de implemento | ImplementType | |
| Camión | Truck | |
| Tipo de camión | TruckType | |
| Cabeza tractora | Tractor unit | Es un tipo de camión, no una entidad |
| Equipo acoplable | TransportEquipment | Bañera, góndola, plataforma |
| Bañera | Tipper semi-trailer | Categoría |
| Góndola portamáquinas | Lowboy trailer | Categoría. Coloquial: chata, carro |
| Proveedor | Supplier | De alquiler |
| Recorrido | Route | Trayecto de camión definido por obra |
| Viaje | Trip | Número entero de viajes por recorrido |
| Recursos previstos | Planned resources | Solo ordenan el selector |
| Horas | Hours | Decimal con paso 0,5 |
| Firma | Signature | La sesión de firma |
| Albarán firmado (en una sesión) | SignedReport | Con hash y decisión |
| Enlace de firma | SigningLink | |
| Rúbrica | Handwritten signature image | Imagen PNG |
| Borrador / Pendiente de firma / Firmado / Rechazado / Anulado | draft / pending_signature / signed / rejected / cancelled | Enum `ReportStatus` |
| Rectificativo | Corrective report | Nuevo albarán que referencia a uno anulado |
| Tardío | Late | Marca de auditoría |
| Archivo adjunto | Attachment | Fotos, documentos, PDF, rúbricas, logo |
| Registro de auditoría | AuditLog | |
| Configuración de empresa | CompanySettings | Fila única |
