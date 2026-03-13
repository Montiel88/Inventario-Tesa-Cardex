// Funcionalidad de Tema Oscuro/Claro
(function() {
    const THEME_KEY = 'tesa_theme';
    const themes = ['light', 'dark'];
    
    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'light';
    }
    
    function setTheme(theme) {
        localStorage.setItem(THEME_KEY, theme);
        applyTheme(theme);
        updateToggleButton(theme);
    }
    
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        if (theme === 'dark') {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    }
    
    function updateToggleButton(theme) {
        const btn = document.getElementById('themeToggle');
        if (btn) {
            if (theme === 'dark') {
                btn.innerHTML = '<i class="fas fa-sun"></i>';
                btn.title = 'Cambiar a modo claro';
            } else {
                btn.innerHTML = '<i class="fas fa-moon"></i>';
                btn.title = 'Cambiar a modo oscuro';
            }
        }
    }
    
    function toggleTheme() {
        const current = getTheme();
        const next = current === 'light' ? 'dark' : 'light';
        setTheme(next);
    }
    
    // Inicializar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        const theme = getTheme();
        applyTheme(theme);
        updateToggleButton(theme);
        
        // Agregar botón al header si no existe
        const navRight = document.querySelector('.navbar-nav.ms-auto, .d-flex.align-items-center');
        if (navRight && !document.getElementById('themeToggle')) {
            const btn = document.createElement('button');
            btn.id = 'themeToggle';
            btn.className = 'btn btn-sm btn-outline-secondary ms-2';
            btn.onclick = toggleTheme;
            btn.style.cssText = 'border-radius: 50%; width: 36px; height: 36px; padding: 0;';
            navRight.appendChild(btn);
        }
    });
    
    // Exponer función global
    window.toggleTheme = toggleTheme;
    window.setTheme = setTheme;
})();
