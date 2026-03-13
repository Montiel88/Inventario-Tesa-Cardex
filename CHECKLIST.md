# CHECKLIST DE REQUERIMIENTOS - SISTEMA INVENTARIO TESA

## ✅ COMPLETADO (YA FUNCIONA)

### I. SEGURIDAD Y USUARIOS
- [x] Login de usuarios
- [x] Roles: Admin (1) y Lector (2)
- [x] Módulo de usuarios (admin/usuarios.php)
- [x] Logs del sistema (admin/logs.php)

### II. GESTIÓN DE PERSONAS
- [x] CRUD completo de personas
- [x] Búsqueda de personas
- [x] QR para personas

### III. GESTIÓN DE EQUIPOS
- [x] CRUD completo de equipos
- [x] Códigos únicos (PRO-XXXXXX)
- [x] Escáner QR/código de barras
- [x] Subida de fotos (Implementado en agregar/editar equipo y visualización en detalle)
- [x] Estados: Disponible, Asignado, En mantenimiento, Baja
- [x] Componentes asociados a equipos
- [x] Trazabilidad completa (movimientos)
- [x] Historial por equipo

### IV. ASIGNACIONES Y PRÉSTAMOS
- [x] Asignar equipo a persona (con acta)
- [x] Devolución de equipos (con acta)
- [x] Préstamos rápidos (sin acta)
- [x] Alertas de préstamos vencidos (módulo modules/prestamos_rapidos/vencidos.php)
- [x] Traspaso de custodio (módulo modules/movimientos/traspaso.php)

### V. ACTAS Y DOCUMENTOS
- [x] Acta de entrega (generar_acta_entrega.php)
- [x] Acta de devolución (generar_acta_devolucion.php)
- [x] Descargo de responsabilidad (generar_descargo.php)
- [x] Acta de baja (generar_acta_baja.php)
- [x] Acta de baja masiva (generar_acta_baja_masiva.php)
- [x] Configuración de firmantes (admin/configuracion.php)
- [x] Códigos QR para equipos

### VI. MANTENIMIENTO
- [x] Módulo de mantenimientos
- [x] Registro automático desde devoluciones (Implementado en devolucion.php)

### VII. UBICACIONES
- [x] CRUD de ubicaciones
- [x] Asignar equipos a ubicaciones

### VIII. REPORTES BÁSICOS
- [x] Listados generales
- [x] Filtros por ubicación/estado
- [x] Exportar a Excel y PDF (módulo reportes/index.php)

---

## 🚧 PENDIENTE (POR IMPLEMENTAR)

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

### V. NOTIFICACIONES
- [ ] Alertas por email
- [ ] Recordatorios de devolución

### VI. MEJORAS EN SUBARTÍCULOS
- [x] Trazabilidad independiente por componente (Implementado en componentes/listar.php)
- [x] Historial de reemplazos (Implementado en componentes/trazabilidad.php)