# CHECKLIST DE REQUERIMIENTOS - SISTEMA INVENTARIO TESA

## Fase 1: Funcionalidades Completadas ✅

### I. SEGURIDAD Y AUTENTICACIÓN
- [x] Login de usuarios con sesiones seguras
- [x] Sistema de Roles: Administrador (1) y Lector (2)
- [x] Módulo de gestión de usuarios (CRUD completo)
- [x] Sistema de logs de acciones (admin/logs.php)
- [x] Control de permisos por rol

### II. GESTIÓN DE PERSONAS (EMPLEADOS)
- [x] CRUD completo de personas
- [x] Búsqueda avanzada de personas
- [x] Código QR único por persona
- [x] Historial de equipos por persona
- [x] Detalle de persona con equipos asignados

### III. GESTIÓN DE EQUIPOS
- [x] CRUD completo de equipos
- [x] Códigos únicos automáticos (PRO-XXXXXX)
- [x] Escáner QR/código de barras
<<<<<<< HEAD
- [x] Sistema de códigos QR por equipo
- [x] Subida de fotos de equipos
=======
- [x] Subida de fotos (Implementado en agregar/editar equipo y visualización en detalle)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9
- [x] Estados: Disponible, Asignado, En mantenimiento, Baja
- [x] Componentes asociados a equipos
- [x] Trazabilidad completa (movimientos)
- [x] Historial por equipo
- [x] Registro rápido de equipos
- [x] Equipos eliminados (papelera)
- [x] Restaurar equipos eliminados

### IV. ASIGNACIONES Y PRÉSTAMOS
- [x] Asignar equipo a persona (con acta)
- [x] Devolución de equipos (con acta)
<<<<<<< HEAD
- [x] Préstamos rápidos (sin acta formal)
- [x] Traspaso de custodio (básico)
- [x] Vista de préstamos vencidos
- [x] **Traspaso múltiple de dispositivos** (NUEVO)
=======
- [x] Préstamos rápidos (sin acta)
- [x] Alertas de préstamos vencidos (módulo modules/prestamos_rapidos/vencidos.php)
- [x] Traspaso de custodio (módulo modules/movimientos/traspaso.php)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9

### V. ACTAS Y DOCUMENTOS
- [x] Acta de entrega de inventario
- [x] Acta de devolución
- [x] Descargo de responsabilidad
- [x] Acta de baja (individual)
- [x] Acta de baja masiva
- [x] Acta de ingreso de inventario
- [x] Acta de traspaso (cambio de custodio)
- [x] Configuración de firmantes
- [x] Códigos QR para equipos
- [x] **Documentos adjuntos (PDF) por equipo** (NUEVO)

### VI. MANTENIMIENTOS
- [x] Módulo de mantenimientos
<<<<<<< HEAD
- [x] Registro de mantenimiento desde devoluciones
- [x] Estados de mantenimiento
- [x] Historial de mantenimiento por equipo
=======
- [x] Registro automático desde devoluciones (Implementado en devolucion.php)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9

### VII. UBICACIONES
- [x] CRUD de ubicaciones
- [x] Asignar equipos a ubicaciones
- [x] Listado de equipos por ubicación

### VIII. COMPONENTES
- [x] CRUD de componentes
- [x] Asignar componentes a equipos
- [x] Trazabilidad por componente
- [x] Historial de reemplazos
- [x] Estados de componentes

### IX. REPORTES BÁSICOS
- [x] Listados generales
- [x] Filtros por ubicación/estado
<<<<<<< HEAD
- [x] Reporte de inventario general (Excel)
- [x] Reporte de préstamos activos (Excel)
- [x] Reporte de personas y equipos (Excel)
- [x] Exportar a PDF
- [x] Reporte de actas generadas
- [x] Trazabilidad de equipo
- [x] Componentes en mal estado
- [x] Equipos por persona
- [x] **Reportes por rango de fechas** (NUEVO)

### X. REPORTES AVANZADOS (NUEVO)
- [x] Reportes por rango de fechas (fecha inicio - fecha fin)
- [x] Reporte de movimientos de equipos
- [x] Reporte de asignaciones realizadas
- [x] Reporte de mantenimientos realizados
- [x] Reporte de equipos dados de baja
- [x] Reporte de equipos sin asignar
- [x] Reporte de equipos en mantenimiento
- [x] Reporte de personas sin equipos

### XI. DASHBOARD CON GRÁFICOS (NUEVO)
- [x] Gráfico: Equipos por estado (doughnut)
- [x] Gráfico: Equipos por tipo (barras)
- [x] Gráfico: Equipos por ubicación (pie)
- [x] Gráfico: Movimientos por mes (línea)

### XII. IMPORTACIÓN/EXPORTACIÓN (NUEVO)
- [x] Importación masiva de equipos (CSV/Excel)
- [x] Importación masiva de personas (CSV/Excel)
- [x] Plantillas CSV para importación
- [x] Exportar equipo individual a PDF

### XIII. MULTIMEDIA (NUEVO)
- [x] Galería de fotos múltiples por equipo
- [x] Foto principal por equipo
- [x] Subir/eliminar fotos

### XIV. NOTIFICACIONES (NUEVO)
- [x] Configuración SMTP de email
- [x] Notificar nuevas asignaciones
- [x] Notificar devoluciones
- [x] Notificar préstamos próximos a vencer
- [x] Email de prueba

### XV. INTERFAZ DE USUARIO (NUEVO)
- [x] Tema oscuro/claro
- [x] Selector de tema en tiempo real

### XVI. MÓDULOS AUXILIARES
- [x] Dashboard con estadísticas
- [x] Módulo de búsqueda general
- [x] Módulo de escaneo
- [x] Incidencias de equipos
- [x] Backup de base de datos
- [x] Chat con IA (Ollama)
=======
- [x] Exportar a Excel y PDF (módulo reportes/index.php)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9

---

## Fase 2: Archivos Nuevos Creados

<<<<<<< HEAD
### APIs
- `api/documentos_adjuntos.php` - Gestión de documentos
- `api/equipos_fotos.php` - Gestión de fotos
- `api/generar_pdf_equipo.php` - Exportar equipo a PDF

### Módulos
- `modules/movimientos/traspaso_multiple.php` - Traspaso múltiple
- `modules/documentos/listar.php` - Documentos adjuntos
- `modules/equipos/galeria.php` - Galería de fotos
- `modules/admin/importar.php` - Importación masiva
- `modules/admin/configuracion_email.php` - Config SMTP

### Configuración
- `config/NotificadorEmail.php` - Clase de notificaciones
- `assets/js/theme.js` - Tema oscuro

### Base de Datos
- `db/migrations/20260313120000_nuevas_funcionalidades.php` - Nuevas tablas
- `templates/plantilla_equipos.csv` - Plantilla equipos
- `templates/plantilla_personas.csv` - Plantilla personas
=======
### I. ACTAS FALTANTES
- [x] Acta de ingreso de inventario (api/generar_acta_ingreso.php)
- [x] Acta de traspaso (api/generar_acta_traspaso.php)

### II. MEJORAS EN PRÉSTAMOS
- [ ] Traspaso múltiple de dispositivos
- [x] Vista de equipos por persona (Implementado en detalle de persona)

### III. DOCUMENTOS ADJUNTOS
- [x] Subir documentos firmados (PDF) (Implementado en actas generadas e historial de movimientos)
- [x] Ver documentos en historial (Botón "Ver Firmado" en tablas de actas y movimientos)

### IV. REPORTES AVANZADOS
- [x] Exportar a Excel (Funciona en reportes/generar.php)
- [ ] Reportes por rango de fechas
- [ ] Dashboard con gráficos (Actualmente solo tiene tarjetas de resumen)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9

---

<<<<<<< HEAD
## Notas de Implementación

### Para activar las nuevas funcionalidades:

1. **Ejecutar migración de base de datos:**
   ```bash
   php vendor/bin/phinx migrate
   ```

2. **Crear directorio de uploads:**
   ```bash
   mkdir -p uploads/documentos uploads/equipos
   ```

3. **Configurar SMTP:**
   - Ir a: `modules/admin/configuracion_email.php`
   - Ingresar datos del servidor SMTP
   - Probar configuración

4. **Para tema oscuro:**
   - El botón aparece automáticamente en el header
   - Click para cambiar entre tema claro/oscuro

### Rutas de las nuevas funcionalidades:
- Traspaso múltiple: `/modules/movimientos/traspaso_multiple.php`
- Documentos: `/modules/documentos/listar.php?id=X&tipo=equipo`
- Galería: `/modules/equipos/galeria.php?id=X`
- Importar: `/modules/admin/importar.php`
- Config email: `/modules/admin/configuracion_email.php`
- Reportes: `/modules/reportes/index.php`

---

*Documento actualizado: 2026-03-13*
*Versión del sistema: 1.1*
*Desarrollador: Equipo TESA*
=======
### VI. MEJORAS EN SUBARTÍCULOS
- [x] Trazabilidad independiente por componente (Implementado en componentes/listar.php)
- [x] Historial de reemplazos (Implementado en componentes/trazabilidad.php)
>>>>>>> 8c8c97232c6b6e81af466a35250fcaf3d10f88c9
