# CHECKLIST DE REQUERIMIENTOS - SISTEMA INVENTARIO TESA

## Fase 1: Funcionalidades Completadas ✅

### I. SEGURIDAD Y AUTENTICACIÓN
- [x] Login de usuarios con sesiones seguras
- [x] Sistema de Roles: Administrador (1) y Lector (2)
- [x] Módulo de gestión de usuarios (CRUD completo)
- [x] Sistema de logs de acciones
- [x] Control de permisos por rol

### II. GESTIÓN DE PERSONAS (EMPLEADOS)
- [x] CRUD completo de personas
- [x] Búsqueda de personas
- [x] Código QR único por persona
- [x] Historial de equipos por persona
- [x] Detalle de persona con equipos asignados

### III. GESTIÓN DE EQUIPOS
- [x] CRUD completo de equipos
- [x] Códigos únicos automáticos (PRO-XXXXXX)
- [x] Escáner QR/código de barras
- [x] Subida de fotos (agregar/editar + visualización en detalle)
- [x] Estados: Disponible, Asignado, Prestado, En mantenimiento, Baja
- [x] Componentes asociados a equipos
- [x] Trazabilidad completa (movimientos)
- [x] Historial por equipo
- [x] Registro rápido de equipos
- [x] Equipos eliminados (papelera) y restauración

### IV. ASIGNACIONES Y PRÉSTAMOS
- [x] Asignar equipo a persona (con acta)
- [x] Devolución de equipos (con acta)
- [x] Préstamos rápidos
- [x] Alertas / vista de préstamos vencidos
- [x] Traspaso de custodio
- [x] Traspaso múltiple de dispositivos

### V. ACTAS Y DOCUMENTOS
- [x] Acta de entrega de inventario
- [x] Acta de devolución
- [x] Descargo de responsabilidad
- [x] Acta de baja (individual)
- [x] Acta de baja masiva
- [x] Acta de ingreso de inventario
- [x] Acta de traspaso (cambio de custodio)
- [x] Configuración de firmantes
- [x] Documentos firmados adjuntos (PDF) y visualización desde historial

### VI. MANTENIMIENTOS
- [x] Módulo de mantenimientos
- [x] Registro automático desde devoluciones
- [x] Historial de mantenimiento por equipo

### VII. UBICACIONES
- [x] CRUD de ubicaciones
- [x] Asignar equipos a ubicaciones
- [x] Listado de equipos por ubicación / sin ubicación

### VIII. COMPONENTES
- [x] CRUD de componentes
- [x] Asignar componentes a equipos
- [x] Trazabilidad por componente
- [x] Historial de reemplazos
- [x] Estados de componentes

### IX. REPORTES
- [x] Listados generales y filtros
- [x] Reportes por rango de fechas (movimientos/asignaciones/mantenimientos/bajas)
- [x] Exportación a Excel (CSV compatible) y PDF
- [x] Trazabilidad de equipo / equipos por persona / componentes en mal estado

### X. DASHBOARD
- [x] Dashboard con estadísticas y últimos movimientos
- [ ] Dashboard con gráficos

### XI. IMPORTACIÓN/EXPORTACIÓN
- [x] Importación masiva (CSV)
- [x] Plantillas CSV para importación

### XII. MULTIMEDIA
- [x] Foto principal por equipo
- [x] Galería de fotos por equipo

### XIII. NOTIFICACIONES
- [x] Configuración SMTP de email
- [x] Email de prueba

### XIV. INTERFAZ
- [x] Tema oscuro/claro
- [x] Selector de tema en tiempo real

### XV. MÓDULOS AUXILIARES
- [x] Búsqueda general
- [x] Escaneo/verificación
- [x] Incidencias de equipos
- [x] Backup de base de datos

---

## Archivos y rutas clave

### APIs
- `api/subir_acta_firmada.php`
- `api/generar_acta_entrega.php`
- `api/generar_acta_devolucion.php`
- `api/generar_acta_baja.php`
- `api/generar_acta_baja_masiva.php`
- `api/generar_acta_ingreso.php`
- `api/generar_acta_traspaso.php`

### Módulos
- `/modules/personas/`
- `/modules/equipos/`
- `/modules/asignaciones/cargar_equipos.php`
- `/modules/movimientos/traspaso.php`
- `/modules/movimientos/traspaso_multiple.php`
- `/modules/prestamos_rapidos/`
- `/modules/reportes/index.php`
- `/modules/admin/configuracion_email.php`
