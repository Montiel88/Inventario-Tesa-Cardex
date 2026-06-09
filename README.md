# 🎓 SISTEMA DE GESTIÓN DE INVENTARIO - TESA

**Instituto Tecnológico San Antonio**  
**Versión:** 1.1  
**Última actualización:** Marzo 2026

---

## 📖 DESCRIPCIÓN

Sistema profesional de gestión de inventario y control de préstamos de equipos tecnológicos para el Instituto Tecnológico San Antonio (TESA).

Permite administrar:
- 📱 Equipos tecnológicos (laptops, proyectores, tablets, etc.)
- 🔌 Componentes y accesorios
- 👨‍🎓 Personas (docentes y estudiantes)
- 📋 Préstamos y devoluciones
- 📄 Actas oficiales en PDF
- 📊 Reportes y estadísticas

---

## 🚀 INSTALACIÓN

### Requisitos Previos

- **XAMPP** con PHP 8.0+ y MySQL
- **Composer** (para dependencias)
- Navegador web moderno

### Pasos de Instalación

1. **Clonar/Copiar el proyecto**
   ```
   C:\xampp\htdocs\inventario_ti\
   ```

2. **Instalar dependencias**
   ```bash
   cd C:\xampp\htdocs\inventario_ti
   composer install
   ```

3. **Configurar base de datos**
   - Abrir phpMyAdmin: http://localhost/phpmyadmin
   - Crear base de datos: `inventario_ti`
   - Importar migraciones desde `db/migrations/`

4. **Configurar conexión**
   - Editar `config/database.php` si es necesario
   - Por defecto: root / sin contraseña

5. **Crear carpetas de uploads**
   ```bash
   mkdir uploads\equipos
   mkdir uploads\documentos
   mkdir uploads\actas
   mkdir logs
   ```

6. **Acceder al sistema**
   ```
   http://localhost/inventario_ti/
   ```

---

## 👤 USUARIOS POR DEFECTO

### Administrador
- **Email:** admin@tesa.edu.ec
- **Rol:** Administrador (acceso completo)

### Usuario Invitado
- **Email:** invitado@tesa.edu.ec
- **Rol:** Usuario (solo lectura)

---

## 📁 ESTRUCTURA DEL PROYECTO

```
inventario_ti/
├── 📂 api/                    # Endpoints API
├── 📂 assets/                 # Recursos estáticos
│   ├── css/                  # Estilos
│   ├── js/                   # JavaScript
│   └── img/                  # Imágenes
├── 📂 config/                 # Configuración
├── 📂 db/migrations/          # Migraciones BD
├── 📂 includes/               # Componentes compartidos
├── 📂 modules/                # Módulos del sistema
│   ├── admin/               # Administración
│   ├── asignaciones/        # Asignaciones
│   ├── componentes/         # Componentes
│   ├── equipos/             # Equipos
│   ├── movimientos/         # Movimientos
│   ├── personas/            # Personas
│   ├── reportes/            # Reportes
│   └── ...
├── 📂 templates/              # Plantillas CSV
├── 📂 uploads/                # Archivos subidos
├── 📂 vendor/                 # Dependencias
├── index.php                 # Punto de entrada
├── login.php                 # Login
└── ESTADO_SISTEMA.md         # Estado actual
```

---

## 🎯 MÓDULOS PRINCIPALES

### 1. Dashboard
- Estadísticas generales
- Accesos rápidos
- Últimos movimientos
- Gráficos resumen

### 2. Personas
- Listado completo
- Agregar/Editar/Eliminar
- Códigos QR individuales
- Historial de equipos asignados
- Búsqueda avanzada

### 3. Equipos
- Inventario completo
- Códigos únicos (PRO-XXXXXX)
- Escáner QR/Barras
- Fotos y galería
- Estados y ubicaciones
- Componentes asociados

### 4. Componentes
- CRUD de componentes
- Asignación a equipos
- Trazabilidad individual
- Historial de reemplazos

### 5. Movimientos
- Préstamos de equipos
- Devoluciones
- Traspasos
- Historial completo
- Actas automáticas

### 6. Asignaciones
- Asignar equipos a personas
- Generar actas de entrega
- Control de custodios

### 7. Reportes
- Inventario general
- Préstamos activos
- Equipos por persona
- Exportar Excel/PDF
- Filtros personalizados

### 8. Administración
- Gestión de usuarios
- Logs del sistema
- Configuración
- Backup de BD

---

## 📄 GENERACIÓN DE ACTAS

El sistema genera automáticamente:

- ✅ Acta de Entrega de Inventario
- ✅ Acta de Devolución
- ✅ Acta de Baja (individual/masiva)
- ✅ Acta de Ingreso
- ✅ Acta de Traspaso
- ✅ Descargo de Responsabilidad

Todas las actas incluyen:
- Códigos QR
- Firmas digitales
- Fecha y hora
- Detalles completos

---

## 🔧 CARACTERÍSTICAS TÉCNICAS

### Backend
- **PHP 8.2** con MySQLi
- **Composer** para dependencias
- **TCPDF** para generación de PDFs
- Sesiones seguras
- Control de roles y permisos

### Frontend
- **Bootstrap 5** para diseño responsive
- **FontAwesome** para íconos
- **Google Fonts** (Poppins, Montserrat)
- **Animate.css** para animaciones
- Diseño moderno y profesional

### Base de Datos
- **17 tablas** relacionadas
- Migraciones versionadas
- Índices optimizados
- Logs de auditoría

---

## 🔐 SEGURIDAD

- Login con hash de contraseñas (password_hash)
- Sesiones PHP seguras
- Control de roles (Admin/Lector)
- Logs de todas las acciones
- Validación de datos
- Protección contra SQL Injection
- Escape de datos en frontend

---

## 📊 ESTADÍSTICAS ACTUALES

| Concepto | Cantidad |
|----------|----------|
| Personas | 38 |
| Equipos | 7 |
| Componentes | 1 |
| Asignaciones | 1 |
| Movimientos | 3 |
| Actas generadas | 71 |
| Ubicaciones | 8 |
| Usuarios | 2 |

---

## 🛠️ MANTENIMIENTO

### Limpieza de Logs
```sql
DELETE FROM logs WHERE fecha < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Backup de Base de Datos
```bash
mysqldump -u root inventario_ti > backup_$(date +%Y%m%d).sql
```

### Actualizar Dependencias
```bash
composer update
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### El sistema no carga
1. Verificar que XAMPP esté ejecutándose
2. Confirmar que Apache y MySQL estén activos
3. Revisar `config/database.php`

### Error de conexión a BD
1. Verificar credenciales en `config/database.php`
2. Confirmar que la BD `inventario_ti` exista
3. Revisar logs de error de MySQL

### Las actas PDF no se generan
1. Verificar que TCPDF esté instalado: `vendor/tecnickcom/tcpdf/`
2. Confirmar permisos de escritura en `uploads/actas/`
3. Revisar memoria PHP: `memory_limit`

### Las fotos no se suben
1. Verificar permisos de `uploads/equipos/`
2. Revisar `upload_max_filesize` en php.ini
3. Confirmar extensión GD habilitada

---

## 📞 SOPORTE

Para asistencia técnica o reportar errores:

- **Email:** soporte@tesa.edu.ec
- **Ubicación:** Departamento de Sistemas, TESA

---

## 📝 LICENCIA

Sistema desarrollado exclusivamente para el **Instituto Tecnológico San Antonio**.

Todos los derechos reservados © 2026 TESA

---

## 🎓 CRÉDITOS

**Desarrollado por:** Equipo de Sistemas TESA  
**Fecha de creación:** 2026  
**Versión actual:** 1.1

---

*Documento generado: Marzo 2026*
