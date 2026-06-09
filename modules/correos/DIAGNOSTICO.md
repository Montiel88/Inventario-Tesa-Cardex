# 🔍 Diagnóstico - Sistema de Correos

## Problema Reportado
- Botones de "Gestión de Correos" y "Componer Correo" no hacen nada
- Icono de notificaciones desapareció

## Archivos de Test Creados

### 1. Test Completo de Enlaces
**URL:** `http://localhost/inventario_ti/modules/correos/debug.php`

Este archivo muestra:
- ✅ Si los archivos PHP existen
- ✅ Enlaces directos para probar
- ✅ Test de JavaScript
- ✅ Información de Bootstrap/jQuery

### 2. Test de Diagnóstico Bootstrap
**URL:** `http://localhost/inventario_ti/modules/correos/diagnostico.html`

Este archivo prueba:
- Si Bootstrap está cargado
- Si los dropdowns funcionan
- Enlaces directos a cada página

### 3. Test de Sesión
**URL:** `http://localhost/inventario_ti/modules/correos/test.php`

Verifica:
- Si hay sesión iniciada
- Si el usuario es admin
- Si la conexión a BD funciona

## 🔧 Pasos para Diagnosticar

### Paso 1: Abrir Debug
1. Abre: `http://localhost/inventario_ti/modules/correos/debug.php`
2. Verifica que todos los archivos aparezcan como ✅
3. Haz click en los botones de colores
4. **Dime:** ¿Abren las páginas o dan error?

### Paso 2: Probar Dropdown
1. Abre: `http://localhost/inventario_ti/modules/correos/diagnostico.html`
2. Haz click en el botón azul "📧 Correos"
3. **Dime:** ¿Se abre el dropdown?

### Paso 3: Probar Enlaces Directos
Desde el debug.php, haz click en:
- 📋 **Listar** → ¿Abre la página de gestión?
- ✍️ **Composer** → ¿Abre el formulario?
- 📜 **Historial** → ¿Abre el historial?

### Paso 4: Verificar Sesión
1. Abre: `http://localhost/inventario_ti/modules/correos/test.php`
2. **Dime:** ¿Qué mensaje aparece?

## 🐛 Posibles Problemas y Soluciones

### Problema 1: Dropdown no abre
**Causa:** Bootstrap JS no cargó o hay conflicto

**Solución:**
```html
<!-- Verificar que esto esté en footer.php -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
```

### Problema 2: Enlaces dan error 404
**Causa:** Ruta incorrecta o archivos no existen

**Solución:**
Verificar que los archivos estén en:
```
C:\xampp\htdocs\inventario_ti\modules\correos\
├── listar.php
├── composer.php
├── historial.php
└── enviar.php
```

### Problema 3: Error de sesión
**Causa:** No has iniciado sesión o no eres admin

**Solución:**
1. Cierra sesión
2. Inicia con usuario ADMIN
3. Intenta de nuevo

### Problema 4: Icono de notificaciones no aparece
**Causa:** Falta la función `toggleNotificationPanel()` en el JavaScript

**Solución:** ✅ YA ARREGLADO en footer.php

## 📋 Qué Decirme

Después de hacer las pruebas, dime:

1. **Debug.php:**
   - ¿Todos los archivos aparecen como ✅?
   - ¿Los botones de colores abren páginas?
   - ¿Qué error sale (si hay)?

2. **Diagnostico.html:**
   - ¿El dropdown "Correos" se abre al hacer click?

3. **Test.php:**
   - ¿Qué mensaje muestra?

4. **En el sistema principal:**
   - ¿El menú "Correos" aparece en el header?
   - ¿Al hacer click en "Correos" se abre el dropdown?
   - ¿Al hacer click en las opciones del dropdown pasa algo?

## 🎯 Solución Rápida

Si los enlaces directos funcionan pero el dropdown NO:

**Opción A:** Usa los enlaces directos:
- `http://localhost/inventario_ti/modules/correos/listar.php`
- `http://localhost/inventario_ti/modules/correos/composer.php`
- `http://localhost/inventario_ti/modules/correos/historial.php`

**Opción B:** Agrega esto al header después del script del search:

```html
<script>
// Forzar inicialización de dropdowns
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'))
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl)
    })
});
</script>
```

## ✅ Archivos Arreglados

1. **footer.php** - Agregadas funciones:
   - `toggleNotificationPanel()`
   - `actualizarBadge()`
   - `cargarNotificaciones()`

2. **header.php** - Menú de Correos agregado correctamente

---

**Prueba los archivos de test y dime qué pasa!**
