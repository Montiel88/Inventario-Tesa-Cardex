# 📋 Sistema de Logs del Sistema TESA

## Descripción General

Se ha implementado un sistema completo de registro de logs (bitácora) para el sistema de inventario TESA. Este sistema permite auditar y rastrear todas las acciones importantes realizadas por los usuarios.

## 🎯 Objetivos

- **Auditoría**: Registrar quién hizo qué y cuándo
- **Seguridad**: Detectar actividades sospechosas
- **Troubleshooting**: Diagnosticar problemas
- **Cumplimiento**: Mantener registro de operaciones

## 📁 Archivos Creados

### 1. Base de Datos
- **`database/logs_table.sql`** - Estructura de la tabla `logs`

### 2. Funciones Core
- **`includes/logs_functions.php`** - Función `registrarLog()` (ya existía)

### 3. Interfaz de Administración
- **`admin/logs.php`** - Página principal de visualización de logs
- **`admin/logs_exportar.php`** - Exportación a CSV

## 🗄️ Estructura de la Tabla

```sql
CREATE TABLE `logs` (
  `id` int(11) AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `detalle` text DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `fecha` (`fecha`),
  KEY `accion` (`accion`)
)
```

### Campos

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | ID único del log |
| `usuario_id` | int | ID del usuario que realizó la acción |
| `accion` | varchar | Tipo de acción (Login, Crear, Editar, Eliminar, etc.) |
| `detalle` | text | Descripción detallada de la acción |
| `ip` | varchar | Dirección IP desde donde se realizó |
| `fecha` | datetime | Fecha y hora exacta del evento |

## 🔧 Función `registrarLog()`

**Ubicación**: `includes/logs_functions.php`

```php
registrarLog($conn, $accion, $detalle, $usuario_id)
```

### Parámetros

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `$conn` | mysqli | Conexión a la base de datos |
| `$accion` | string | Nombre de la acción (ej: "Crear persona") |
| `$detalle` | string | Detalles adicionales (ej: "Cédula: 1234567890") |
| `$usuario_id` | int|null | ID del usuario (null para acciones del sistema) |

### Ejemplo de Uso

```php
require_once 'includes/logs_functions.php';

// Registrar una acción
registrarLog($conn, 'Crear equipo', "Código: EQ-001, Tipo: Laptop", $_SESSION['user_id']);
```

## 📊 Página de Administración (`admin/logs.php`)

### Características

✅ **Filtros Avanzados**
- Por usuario (nombre o ID)
- Por tipo de acción
- Por rango de fechas

✅ **Estadísticas en Tiempo Real**
- Total de logs
- Usuarios activos
- Primer y último registro

✅ **Paginación**
- 50 registros por página
- Navegación intuitiva

✅ **Código de Colores**
- 🟢 Verde: Creación/Agregado
- 🔵 Azul: Actualización/Edición
- 🔴 Rojo: Eliminación/Borrado
- 🟣 Violeta: Login/Sesión
- 🟡 Amarillo: Exportar/Imprimir
- ⚠️ Amarillo resaltado: Acciones críticas

✅ **Exportación**
- Download a CSV
- Imprimir reporte

✅ **Panel Lateral**
- Top 10 acciones más comunes
- Información del sistema

### Captura de Pantalla

La página incluye:
- Header con gradiente profesional
- 4 tarjetas de estadísticas
- Formulario de filtros
- Tabla responsive con paginación
- Panel lateral con métricas

## 📤 Exportación (`admin/logs_exportar.php`)

### Funcionalidad

- Exporta logs filtrados por fecha
- Formato CSV compatible con Excel
- Incluye BOM UTF-8 para caracteres especiales
- Nombre automático: `logs_YYYY-MM-DD_al_YYYY-MM-DD.csv`

### Columnas Exportadas

1. ID
2. Fecha
3. Hora
4. Usuario
5. Email
6. Acción
7. Detalle
8. IP

## 🔐 Seguridad

### Permisos

- **Solo administradores** (rol_id = 1) pueden ver logs
- Intento de acceso no autorizado redirige al dashboard con error

### Protección de Datos

- Los logs se guardan indefinidamente
- No se pueden eliminar desde la interfaz (solo por DBA)
- IP se registra automáticamente

## 📍 Puntos de Integración

### Operaciones que Registran Logs

| Módulo | Acción | Tipo de Log |
|--------|--------|-------------|
| `login.php` | Inicio sesión exitoso | "Inicio de sesión" |
| `login.php` | Intento fallido | "Login fallido" |
| `logout.php` | Cierre de sesión | "Cierre de sesión" |
| `personas/agregar.php` | Crear persona | "Crear persona" |
| `equipos/agregar.php` | Crear equipo | "Crear equipo" |
| `movimientos/devolucion.php` | Devolución equipo | "Devolución equipo" |
| `componentes/asignar.php` | Asignar componente | "Asignar componente" |
| `correos/enviar.php` | Enviar correo | "Enviar correo" |
| `api/registrar_movimiento.php` | Préstamo producto | "SALIDA producto" / "ENTRADA producto" |

### Cómo Agregar Más Logs

```php
// 1. Incluir la función
require_once 'includes/logs_functions.php';

// 2. Llamar después de la operación exitosa
registrarLog($conn, 'Tu Acción', "Detalle: información relevante", $_SESSION['user_id']);
```

## 🎨 Diseño UI/UX

### Paleta de Colores

```css
--primary: #6366f1 (Indigo)
--success: #10b981 (Emerald)
--warning: #f59e0b (Amber)
--danger: #ef4444 (Red)
--info: #3b82f6 (Blue)
```

### Componentes

- **Cards**: Sombras suaves, bordes redondeados
- **Badges**: Colores semánticos por tipo de acción
- **Tablas**: Hover effect, responsive
- **Paginación**: Bootstrap-style
- **Gráficos**: Iconos FontAwesome

## 📈 Métricas y Estadísticas

### Dashboard de Logs

La página principal muestra:

1. **Total de Logs**: Cantidad de registros en el período
2. **Usuarios Activos**: Usuarios únicos con actividad
3. **Primer Registro**: Fecha del log más antiguo
4. **Último Registro**: Fecha del log más reciente

### Top Acciones

Lista las 10 acciones más frecuentes en el período seleccionado.

## 🔍 Consultas de Ejemplo

### Logs de un Usuario Específico

```sql
SELECT * FROM logs WHERE usuario_id = 1 ORDER BY fecha DESC LIMIT 50;
```

### Intentos Fallidos de Login

```sql
SELECT * FROM logs 
WHERE accion = 'Login fallido' 
ORDER BY fecha DESC;
```

### Actividad por Día

```sql
SELECT DATE(fecha) as dia, COUNT(*) as cantidad 
FROM logs 
GROUP BY DATE(fecha) 
ORDER BY dia DESC;
```

### Acciones Críticas

```sql
SELECT * FROM logs 
WHERE accion LIKE '%Eliminar%' OR accion LIKE '%Borrar%' 
ORDER BY fecha DESC;
```

## 🚀 Próximas Mejoras

- [ ] Logs de modificaciones (editar/actualizar)
- [ ] Logs de eliminaciones
- [ ] Gráficos de actividad (Chart.js)
- [ ] Alertas de actividades sospechosas
- [ ] Rotación automática de logs antiguos
- [ ] Búsqueda full-text en detalles
- [ ] Filtros por IP
- [ ] Exportación a PDF

## 📝 Notas Importantes

1. **Rendimiento**: Los índices en `usuario_id`, `fecha` y `accion` optimizan las búsquedas
2. **Espacio**: La tabla puede crecer rápidamente. Considerar archivado periódico
3. **Privacidad**: Los logs pueden contener información sensible. Acceso restringido
4. **Backup**: Incluir tabla `logs` en backups regulares

## 🎯 Mejores Prácticas

### Qué Registrar

✅ Autenticación (login/logout)
✅ Creación de registros
✅ Modificaciones importantes
✅ Eliminaciones
✅ Acciones administrativas
✅ Errores críticos
✅ Exportaciones de datos

### Qué NO Registrar

❌ Contraseñas
❌ Tokens de sesión
❌ Datos financieros sensibles
❌ Información médica privada

## 📖 Referencias

- [Función registrarLog()](../includes/logs_functions.php)
- [Tabla logs](../database/logs_table.sql)
- [Página de logs](../admin/logs.php)
- [Exportar logs](../admin/logs_exportar.php)

---

**Fecha de Implementación**: 2025
**Versión**: 1.0
**Estado**: ✅ Implementado y en Producción
