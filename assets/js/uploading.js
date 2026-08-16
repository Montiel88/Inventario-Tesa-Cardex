/* ============================================================
   UX LOADING GLOBAL — Sistema Inventario TESA
   Auto-aplica feedback visual en TODOS los formularios y botones
   del sistema. No requiere tocar cada página individualmente.
   Incluir: <script src="/inventario_ti/assets/js/ux-loading.js"></script>
   ============================================================ */
(function () {
    'use strict';

    const APP_BASE = window.__APP_BASE__ || '/inventario_ti';

    /* ---------------- Utilidades ---------------- */
    function $(sel, ctx) { return (ctx || document).querySelectorAll(sel); }

    function btnEstaDeshabilitado(btn) {
        return !!btn.disabled || btn.classList.contains('disabled') || btn.getAttribute('aria-disabled') === 'true';
    }

    function deshabilitarFormulario(form, deshabilitar) {
        if (!form) return;
        const elements = form.querySelectorAll('input, select, textarea, button, fieldset');
        elements.forEach(function (el) {
            if (deshabilitar) {
                if (!el.__ux_orig_disabled) {
                    el.__ux_orig_disabled = !!el.disabled;
                }
                if (el.tagName !== 'BUTTON' || el.type !== 'submit') {
                    el.setAttribute('disabled', 'disabled');
                }
            } else {
                if (!el.__ux_orig_disabled) {
                    el.removeAttribute('disabled');
                } else {
                    if (!el.__ux_orig_disabled) el.removeAttribute('disabled');
                    else el.setAttribute('disabled', 'disabled');
                }
            }
        });
    }

    function extraerHtmlIcono(btn) {
        const icono = btn.querySelector('i.fas, i.fa-solid, i.fa, svg');
        return icono ? icono.outerHTML : '';
    }

    function btnGuardarEstadoLoading(btn, textoOpcional) {
        if (!btn || btn.__ux_loading_original !== undefined) return;
        btn.__ux_loading_original = btn.innerHTML;
        btn.__ux_loading_original_text = btn.textContent || '';
        btn.__ux_loading_original_title = btn.getAttribute('title') || '';
        btn.__ux_loading_original_icono = extraerHtmlIcono(btn);
        btn.__ux_loading_orig_disabled = btn.disabled;
        const txt = (textoOpcional && textoOpcional.trim()) ||
                    (btn.getAttribute('data-loading-text')) ||
                    (btn.__ux_loading_original_text.trim() + '...');
        btn.__ux_loading_next_text = txt;
    }

    function btnAplicarLoading(btn, textoOpcional) {
        if (!btn) return;
        btnGuardarEstadoLoading(btn, textoOpcional);
        btn.disabled = true;
        btn.classList.add('disabled');
        btn.setAttribute('aria-disabled', 'true');
        if (btn.style) {
            btn.style.setProperty('pointer-events', 'none', 'important');
            btn.style.setProperty('opacity', '0.8');
        }
        const iconSpinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display:inline-block;width:.9rem;height:.9rem;border-width:2px;vertical-align:-2px;"></span>';
        const textoFinal = btn.__ux_loading_next_text || 'Procesando...';
        // Evitar duplicar spinner si tiene ícono original
        btn.innerHTML = iconSpinner + (textoFinal || 'Procesando...');
    }

    function btnRestaurar(btn) {
        if (!btn) return;
        if (btn.__ux_loading_original !== undefined) {
            btn.innerHTML = btn.__ux_loading_original;
            delete btn.__ux_loading_original;
            delete btn.__ux_loading_next_text;
            delete btn.__ux_loading_original_title;
            delete btn.__ux_loading_original_icono;
        }
        btn.disabled = !!btn.__ux_loading_orig_disabled;
        delete btn.__ux_loading_orig_disabled;
        btn.classList.remove('disabled');
        btn.removeAttribute('aria-disabled');
        if (btn.style) {
            btn.style.removeProperty('pointer-events');
            btn.style.removeProperty('opacity');
        }
    }

    /* ---------------- Carga global de SweetAlert2 (lazy) ---------------- */
    function ensureSwal() {
        return new Promise(function (resolve, reject) {
            if (window.Swal) return resolve(window.Swal);
            const existente = document.querySelector('script[data-swal-global]');
            if (existente) {
                existente.addEventListener('load', function () { resolve(window.Swal); });
                existente.addEventListener('error', reject);
                return;
            }
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            s.setAttribute('data-swal-global', '1');
            s.async = true;
            s.onload = function () { resolve(window.Swal); };
            s.onerror = reject;
            document.head.appendChild(s);
        });
    }

    function mostrarToastSwal(titulo, icono, tiempo, texto) {
        return ensureSwal().then(function (Swal) {
            const opts = {
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: tiempo || 2200,
                timerProgressBar: true,
                icon: icono || 'info',
                title: titulo || 'Procesando...',
                didOpen: function (toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            };
            if (texto) opts.text = texto;
            return Swal.fire(opts);
        }).catch(function () { /* Swal no disponible, ignorar */ });
    }

    function mostrarLoadingGlobal(titulo, texto) {
        return ensureSwal().then(function (Swal) {
            Swal.fire({
                title: titulo || 'Procesando solicitud',
                html: texto || 'Por favor espera mientras se completa la operación... <div class="spinner-border mt-3 text-primary" role="status"></div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                showConfirmButton: false,
                didOpen: function () {
                    Swal.showLoading();
                }
            });
            return Swal;
        }).catch(function () { return null; });
    }

    function cerrarLoadingGlobal() {
        if (window.Swal) {
            try { window.Swal.close(); } catch (e) {}
            try { window.Swal.isLoading() && window.Swal.close(); } catch (e) {}
        }
    }

    /* ---------------- Auto-handle forms (submit natural POST/GET) ---------------- */
    function inicializarFormularios(root) {
        const forms = (root || document).querySelectorAll('form:not([data-ux-no-auto])');
        forms.forEach(function (form) {
            if (form.__ux_auto_form_loaded) return;
            form.__ux_auto_form_loaded = true;

            const buscaBotonSubmit = function () {
                return form.querySelector('button[type="submit"], input[type="submit"]');
            };

            form.addEventListener('submit', function (ev) {
                const metodo = (form.getAttribute('method') || 'GET').toUpperCase();
                const accion = form.getAttribute('action') || '';
                const esAjax = form.classList.contains('needs-ajax-upload') || form.hasAttribute('data-ajax');
                if (esAjax) return; // Lo maneja el handler específico del módulo

                // Ignorar forms sin method="post" que solo hagan filtros/gets de listado
                const soloFiltro = (metodo === 'GET') &&
                    /\?tipo=|\?buscar=|\?estado=|\?persona_id=|filter|buscar|filtro/i.test(accion || window.location.search);
                if (soloFiltro) return;

                // Buscar botón submit
                const btn = ev.submitter || buscaBotonSubmit();
                const loadingText = btn ? (btn.getAttribute('data-loading-text') || '') : '';
                if (btn) {
                    btnAplicarLoading(btn, loadingText || 'Guardando...');
                }
                deshabilitarFormulario(form, true);

                // Para forms que navegan a otra página no restauramos nada
                // (el browser carga la nueva pestaña). Pero para GETs sin navegación
                // o si cancela el submit luego, restauramos.
                const restaurar = function () {
                    deshabilitarFormulario(form, false);
                    if (btn) btnRestaurar(btn);
                };
                // Después de 15s por si acaso se queda pegado (timeout de seguridad)
                setTimeout(restaurar, 20000);
            });
        });
    }

    /* ---------------- Auto-handle botones con data-loading / clase loading-btn ---------------- */
    function inicializarBotones(root) {
        const botones = (root || document).querySelectorAll('button[data-loading], a[data-loading], .needs-loading-click');
        botones.forEach(function (btn) {
            if (btn.__ux_auto_btn_loaded) return;
            btn.__ux_auto_btn_loaded = true;

            btn.addEventListener('click', function (ev) {
                if (btnEstaDeshabilitado(btn)) return;
                const texto = btn.getAttribute('data-loading') || btn.getAttribute('data-loading-text') || '';
                btnAplicarLoading(btn, texto);
                // Por defecto restauramos a los 20s a menos que la página lo haga antes
                setTimeout(function () { btnRestaurar(btn); }, 30000);
            });
        });
    }

    /* ---------------- Exponer API global ---------------- */
    window.UXLoading = {
        btnAplicar: btnAplicarLoading,
        btnRestaurar: btnRestaurar,
        mostrarGlobal: mostrarLoadingGlobal,
        cerrarGlobal: cerrarLoadingGlobal,
        toast: mostrarToastSwal,
        ensureSwal: ensureSwal,
        inicializar: function (root) {
            inicializarFormularios(root);
            inicializarBotones(root);
        }
    };

    /* ---------------- Ejecución inicial + cada vez que cambia el DOM ---------------- */
    function domCargado() {
        try { inicializarFormularios(document); } catch (e) {}
        try { inicializarBotones(document); } catch (e) {}
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', domCargado);
    } else {
        domCargado();
    }

    // Re-inicializar cuando el DOM sufra cambios (e.g. fetchs que añaden formularios)
    if (window.MutationObserver) {
        const obs = new MutationObserver(function (mutations) {
            let addedNodes = false;
            mutations.forEach(function (m) { addedNodes = addedNodes || (m.addedNodes && m.addedNodes.length > 0); });
            if (addedNodes) {
                try { inicializarFormularios(document); } catch (e) {}
                try { inicializarBotones(document); } catch (e) {}
            }
        });
        try { obs.observe(document.body, { childList: true, subtree: true }); } catch (e) {}
    }
})();
