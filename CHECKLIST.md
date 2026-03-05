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
- [x] Subida de fotos
- [x] Estados: Disponible, Asignado, En mantenimiento, Baja
- [x] Componentes asociados a equipos
- [x] Trazabilidad completa (movimientos)
- [x] Historial por equipo

### IV. ASIGNACIONES Y PRÉSTAMOS
- [x] Asignar equipo a persona (con acta)
- [x] Devolución de equipos (con acta)
- [x] Préstamos rápidos (sin acta)
- [x] Alertas de préstamos vencidos
- [x] Traspaso de custodio (básico)

### V. ACTAS Y DOCUMENTOS
- [x] Acta de entrega (generar_acta_entrega.php)
- [x] Acta de devolución (generar_acta_devolucion.php)
- [x] Descargo de responsabilidad (generar_descargo.php)
- [x] Configuración de firmantes (admin/configuracion.php)
- [x] Códigos QR para equipos

### VI. MANTENIMIENTO
- [x] Módulo de mantenimientos
- [x] Registro automático desde devoluciones

### VII. UBICACIONES
- [x] CRUD de ubicaciones
- [x] Asignar equipos a ubicaciones

### VIII. REPORTES BÁSICOS
- [x] Listados generales
- [x] Filtros por ubicación/estado

---

## 🚧 PENDIENTE (POR IMPLEMENTAR)

### I. ACTAS FALTANTES
- [ ] Acta de ingreso de inventario (cuando se crea equipo)
- [ ] Acta de traspaso (cambio de custodio)
- [ ] Acta de baja (cuando se da de baja)

### II. MEJORAS EN PRÉSTAMOS
- [ ] Traspaso múltiple de dispositivos
- [ ] Vista de equipos por persona

### III. DOCUMENTOS ADJUNTOS
- [ ] Subir documentos firmados (PDF)
- [ ] Ver documentos en historial

### IV. REPORTES AVANZADOS
- [ ] Exportar a Excel
- [ ] Reportes por rango de fechas
- [ ] Dashboard con gráficos

### V. NOTIFICACIONES
- [ ] Alertas por email (opcional)
- [ ] Recordatorios de devolución

### VI. MEJORAS EN SUBARTÍCULOS
- [ ] Trazabilidad independiente por componente
- [ ] Historial de reemplazos