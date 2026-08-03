// Funcionalidad de Tema Oscuro/Claro
(function() {
    const THEME_KEY = 'tesa_theme';
    const themes = ['light', 'dark'];
    
    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'dark';
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
        
        const btn = document.getElementById('themeToggle');
        if (btn) {
            btn.addEventListener('click', toggleTheme);
        }
    });
    
    // Exponer función global
    window.toggleTheme = toggleTheme;
    window.setTheme = setTheme;
})();
