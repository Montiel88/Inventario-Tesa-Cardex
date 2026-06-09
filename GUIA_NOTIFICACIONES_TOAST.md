# 📬 Sistema de Notificaciones Toast - Guía de Uso

## ✨ Características

- **Notificaciones emergentes** temporales (5 segundos por defecto)
- **Clickeables** - Redirigen a páginas relevantes
- **4 tipos**: success (verde), error (rojo), warning (amarillo), info (azul)
- **Animaciones** suaves de entrada/salida
- **Auto-cerrables** o cierre manual
- **Responsive** - Funciona en móviles y desktop

---

## 🎯 Funciones Disponibles

### Función Genérica
```javascript
mostrarToast(tipo, titulo, mensaje, url, duracion);
```

**Parámetros:**
- `tipo`: 'success', 'error', 'warning', 'info'
- `titulo`: Título de la notificación
- `mensaje`: Mensaje detallado (puede incluir HTML)
- `url`: URL opcional para redirigir al hacer click
- `duracion`: Duración en ms (default: 5000)

**Ejemplo:**
```javascript
mostrarToast('success', '✅ Operación Exitosa', 'Los datos fueron guardados', '/modulos/listar.php', 4000);
```

---

## 🔔 Funciones Específicas

### 1. Asignaciones de Equipos

```javascript
// Éxito: Equipo asignado
notificarEquipoAsignado('Laptop HP ProBook', 'Juan Pérez', '/inventario_ti/modules/asignaciones/listar.php');

// Error: Falló asignación
notificarErrorAsignacion('El equipo ya está asignado');
```

### 2. Personas

```javascript
// Persona creada
notificarPersonaCreada('María González', '/inventario_ti/modules/personas/listar.php');

// Persona actualizada
notificarPersonaActualizada('Carlos Rodríguez');
```

### 3. Componentes

```javascript
// Componente asignado
notificarComponenteAsignado('Memoria RAM 8GB', 'Laptop Dell Inspiron');

// Error con componente
notificarErrorComponente('El componente no es compatible');
```

### 4. Correos

```javascript
// Correo enviado
notificarCorreoEnviado('usuario@email.com', 'Notificación de Préstamo Vencido');

// Error al enviar
notificarErrorCorreo('SMTP no configurado', 'usuario@email.com');
```

### 5. Equipos

```javascript
// Equipo registrado
notificarEquipoCreado('HP-2024-001');

// Stock bajo
notificarStockBajo('Mouse Logitech', 3);
```

### 6. Préstamos

```javascript
// Préstamo registrado
notificarPrestamoRegistrado('Proyector Epson', 'Ana López');

// Devolución registrada
notificarDevolucionRegistrada('Proyector Epson', 'Ana López');

// Préstamo vencido
notificarPrestamoVencido('Laptop HP', 'Pedro Sánchez', 15);
```

### 7. Mantenimientos

```javascript
// Mantenimiento programado
notificarMantenimientoProgramado('Servidor Dell', '2024-12-25');
```

### 8. Usuarios

```javascript
// Usuario creado
notificarUsuarioCreado('jmartin');
```

### 9. Genéricas

```javascript
// Éxito genérico
notificarExito('Configuración guardada correctamente', '/configuracion.php');

// Error genérico
notificarError('No se pudo guardar los datos', '/formulario.php');
```

---

## 🔧 Integración en Formularios AJAX

### Ejemplo: Formulario de Asignación

```javascript
$('#formAsignacion').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/inventario_ti/modules/asignaciones/guardar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar notificación de éxito
            notificarEquipoAsignado(
                data.equipo, 
                data.persona, 
                '/inventario_ti/modules/asignaciones/listar.php'
            );
            
            // Redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = '/inventario_ti/modules/asignaciones/listar.php';
            }, 2000);
        } else {
            // Mostrar notificación de error
            notificarErrorAsignacion(data.mensaje);
        }
    })
    .catch(error => {
        notificarError('Error de conexión: ' + error);
    });
});
```

### Ejemplo: Crear Persona

```javascript
$('#formPersona').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const nombreCompleto = $('#nombres').val() + ' ' + $('#apellido').val();
    
    fetch('/inventario_ti/modules/personas/guardar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notificarPersonaCreada(nombreCompleto, '/inventario_ti/modules/personas/listar.php');
            setTimeout(() => {
                window.location.href = '/inventario_ti/modules/personas/listar.php';
            }, 2000);
        } else {
            notificarError(data.mensaje);
        }
    });
});
```

### Ejemplo: Asignar Componente

```javascript
$('#formComponente').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const componente = $('#nombre_componente').val();
    const equipo = $('#equipo_select').find('option:selected').text();
    
    fetch('/inventario_ti/modules/componentes/asignar.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            notificarComponenteAsignado(componente, equipo);
            setTimeout(() => {
                window.location.href = '/inventario_ti/modules/componentes/listar.php';
            }, 2000);
        } else {
            notificarErrorComponente(data.mensaje);
        }
    });
});
```

---

## 🎨 Personalización de Estilos

Los toasts usan CSS customizado. Puedes modificar:

### Colores por Tipo
```css
/* En includes/footer.php */
.toast-notification.success { border-left-color: #10b981; }
.toast-notification.error { border-left-color: #ef4444; }
.toast-notification.warning { border-left-color: #f59e0b; }
.toast-notification.info { border-left-color: #3b82f6; }
```

### Duración
```javascript
// Cambiar duración por defecto (5000ms = 5 segundos)
mostrarToast('success', 'Título', 'Mensaje', null, 10000); // 10 segundos
```

### Posición
```css
/* En includes/footer.php */
.toast-container {
    top: 90px;      /* Distancia desde arriba */
    right: 20px;    /* Distancia desde derecha */
}
```

---

## 📱 Responsive

Los toasts se adaptan automáticamente a móviles:

```css
@media (max-width: 768px) {
    .toast-container {
        top: 70px;
        right: 10px;
        left: 10px;   /* Ocupa todo el ancho */
    }
}
```

---

## ✅ Mejores Prácticas

1. **Usar la función específica** para cada operación (ej: `notificarCorreoEnviado()` en vez de `mostrarToast()`)
2. **Incluir URL** siempre que sea posible para navegación rápida
3. **Mensajes cortos y claros** - Máximo 2-3 líneas
4. **Duración apropiada**:
   - Éxito: 4000-5000ms
   - Error: 6000-7000ms (para que el usuario lea el error)
   - Info: 5000ms
5. **No abusar** - Máximo 2-3 toasts simultáneos

---

## 🐛 Troubleshooting

### Los toasts no aparecen
- Verificar que `notificaciones-toast.js` esté cargado en footer.php
- Verificar que el contenedor se inicialice: `initToastContainer()`
- Revisar consola del navegador por errores

### Los toasts no se cierran
- Verificar que la función `cerrarToast()` esté definida
- Revisar que no haya conflictos con otras librerías

### Los toasts no son clickeables
- Asegurar que el parámetro `url` no sea null
- Verificar que la URL sea válida

---

## 📁 Archivos

| Archivo | Descripción |
|---------|-------------|
| `js/notificaciones-toast.js` | Lógica JavaScript |
| `includes/footer.php` | Estilos CSS y carga del script |
| `modules/correos/composer.php` | Ejemplo de integración |
| `modules/correos/enviar.php` | Backend que retorna JSON |

---

**¡Listo! Ahora tu sistema tiene notificaciones profesionales y dinámicas!** 🚀
