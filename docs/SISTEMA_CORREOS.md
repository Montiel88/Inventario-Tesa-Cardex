# 📧 Sistema de Gestión de Correos - TESA

## Descripción

Sistema profesional de envío de correos electrónicos para notificaciones automáticas y manuales a usuarios del sistema de inventario TESA.

## ✨ Características Principales

### 🎯 Tipos de Notificaciones

1. **Préstamos Vencidos** (>30 días)
   - Detección automática de préstamos vencidos
   - Muestra días de retraso
   - Enlace directo para enviar correo

2. **Préstamos por Vencer** (≤5 días)
   - Recordatorios preventivos
   - Muestra días restantes
   - Permite enviar recordatorio amigable

3. **Componentes Dañados**
   - Notificación de componentes en mal estado
   - Incluye descripción del problema
   - Responsable del equipo asignado

4. **Correos Manuales**
   - Composición libre
   - Plantillas predefinidas
   - Búsqueda de destinatarios

### 🎨 Diseño Profesional

- **Interfaz moderna** con gradientes corporativos
- **Tarjetas de estadísticas** en tiempo real
- **Tablas interactivas** con hover effects
- **Responsive design** para móviles
- **Vista previa** en tiempo real del correo

### 📊 Funcionalidades

- ✅ **Detección automática** de casos que requieren notificación
- ✅ **Plantillas predefinidas** para cada tipo de notificación
- ✅ **Vista previa** antes de enviar
- ✅ **Histórico completo** de todos los correos enviados
- ✅ **Estadísticas** de envíos exitosos/fallidos
- ✅ **Filtros avanzados** en el historial
- ✅ **Placeholders** dinámicos ([NOMBRE], [EQUIPO], [CÓDIGO], etc.)
- ✅ **Email HTML** con diseño profesional
- ✅ **Integración con SMTP** (vía NotificadorEmail.php)

## 📁 Estructura de Archivos

```
inventario_ti/
├── modules/correos/
│   ├── listar.php          # Panel principal de gestión
│   ├── composer.php        # Formulario de composición
│   ├── enviar.php          # Procesa el envío
│   └── historial.php       # Historial de correos
├── database/
│   └── correos_table.sql   # Script de creación de tabla
└── config/
    └── NotificadorEmail.php # Clase de envío de emails
```

## 🗄️ Base de Datos

### Tabla: `correos_enviados`

```sql
CREATE TABLE correos_enviados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    asignacion_id INT DEFAULT NULL,
    componente_id INT DEFAULT NULL,
    tipo_motivo VARCHAR(50) NOT NULL,
    asunto VARCHAR(500) NOT NULL,
    mensaje TEXT NOT NULL,
    email_destino VARCHAR(255) NOT NULL,
    email_enviado TINYINT(1) DEFAULT 0,
    error_email TEXT DEFAULT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🚀 Cómo Usar

1. Acceder: Menú **Correos** → **Gestión de Correos**
2. Ver notificaciones pendientes en el panel principal
3. Click en **"Enviar"** para cada registro
4. Revisar/ajustar plantilla en el Composer
5. Enviar correo

## 📊 Estadísticas

El sistema muestra:
- **Total Correos**: Todos los correos registrados
- **Enviados Exitosamente**: Correos que llegaron al destinatario
- **Fallidos**: Correos que no se pudieron enviar

## 🔧 Configuración SMTP

Para enviar correos reales:
1. Ir a **Admin** → **Configuración de Email**
2. Configurar SMTP (host, puerto, usuario, password)
3. Guardar configuración

## 🎨 Diseño UI/UX

- Gradientes corporativos morados/dorados
- Sombras suaves y bordes redondeados
- Iconos FontAwesome
- Animaciones hover
- Badges de estado por tipo

## 🔐 Permisos

- **Solo Administradores** pueden acceder
- Rol **Lector** no ve la opción en el menú

---

**Creado**: 2025 | **Versión**: 1.0
