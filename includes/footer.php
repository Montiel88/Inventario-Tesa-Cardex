    </main> <!-- Cierra el main del header -->
    
    <footer class="main-footer">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0"><i class="fas fa-copyright me-2"></i><?php echo date('Y'); ?> Tecnológico San Antonio TESA</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-code me-2"></i>Sistema de Inventario v3.0
                        
                        <!-- Mostrar rol del usuario si existe -->
                        <?php if (isset($_SESSION['user_rol'])): ?>
                            <span class="ms-3 badge bg-<?php echo $_SESSION['user_rol'] == 1 ? 'warning text-dark' : 'success'; ?>">
                                <i class="fas <?php echo $_SESSION['user_rol'] == 1 ? 'fa-crown' : 'fa-eye'; ?> me-1"></i>
                                <?php echo $_SESSION['user_rol'] == 1 ? 'Admin' : 'Lector'; ?>
                            </span>
                        <?php endif; ?>
                        
                        <span class="ms-3"><i class="fas fa-heart" style="color: <?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 1) ? '#f3b229' : '#28a745'; ?>;"></i></span>
                    </p>
                </div>
            </div>
            
            <!-- Versión responsive para móviles -->
            <?php if (isset($_SESSION['user_name'])): ?>
            <div class="row d-md-none mt-2 text-center">
                <div class="col-12">
                    <small class="text-white-50">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
    </footer>
    
    <!-- Scripts -->
   <!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
    console.log('Bootstrap version:', typeof bootstrap !== 'undefined' ? 'cargado' : 'NO CARGADO');
 
</script>

    
    <script>
        // Inicializar AOS cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    once: true
                });
            }
            
            // Animación de contadores
            const counters = document.querySelectorAll('.count-up');
            if (counters.length > 0) {
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-target')) || 0;
                    const speed = 200;
                    let current = 0;
                    
                    const updateCount = () => {
                        const increment = Math.ceil(target / speed);
                        
                        if (current < target) {
                            current = Math.min(current + increment, target);
                            counter.innerText = current;
                            setTimeout(updateCount, 10);
                        } else {
                            counter.innerText = target;
                        }
                    };
                    
                    updateCount();
                });
            }
            
            console.log('🚀 Sistema TESA v3.0 cargado correctamente');
        });
    </script>
    
    <!-- JS personalizado -->
    <script src="/inventario_ti/assets/js/funciones.js"></script>
    <?php ob_end_flush(); ?>
    <?php if (isset($id, $total_componentes_disponibles, $componentes_disponibles)): ?>
    <!-- Modal Asignar Componente -->
    <div class="modal fade" id="assignComponentModal" tabindex="-1" aria-labelledby="assignComponentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="asignar_componente.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignComponentModalLabel">Asignar componente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="persona_id" value="<?php echo (int)$id; ?>">
                        <?php if ($total_componentes_disponibles > 0): ?>
                            <div class="mb-3">
                                <label for="componente_id" class="form-label">Componente disponible</label>
                                <select class="form-select" id="componente_id" name="componente_id" required>
                                    <option value="">-- Seleccione --</option>
                                    <?php foreach ($componentes_disponibles as $c): ?>
                                        <option value="<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['tipo'] . ' - ' . $c['nombre_componente'] . ' (' . $c['marca'] . ' ' . $c['modelo'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                        <?php else: ?>
                            <p class="text-danger">No hay componentes disponibles para asignar.</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <?php if ($total_componentes_disponibles > 0): ?>
                            <button type="submit" class="btn btn-primary">Asignar</button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('show.bs.modal', function (event) {
        if (!event.target.classList.contains('modal')) return;

        event.target.style.zIndex = '2000001';
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.style.zIndex = '2000000';
        });
    });

    document.addEventListener('hidden.bs.modal', function () {
        if (document.querySelector('.modal.show')) return;

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
        document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    });
    </script>

 <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchContainer = document.getElementById('globalSearchContainer');
        const searchIcon = document.getElementById('globalSearchIcon');
        const searchInput = document.querySelector('.search-global-input');

        if (searchContainer && searchIcon && searchInput) {
            searchIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                searchContainer.classList.add('active');
                searchInput.focus();
            });

            searchInput.addEventListener('blur', function() {
                if (window.innerWidth > 768 && searchInput.value === '') {
                    searchContainer.classList.remove('active');
                }
            });

            searchContainer.addEventListener('mouseenter', function() {
                if (window.innerWidth > 768) {
                    searchContainer.classList.add('active');
                }
            });

            searchContainer.addEventListener('mouseleave', function() {
                if (window.innerWidth > 768 && searchInput.value === '') {
                    searchContainer.classList.remove('active');
                }
            });
        }
    });
    </script>
</body>
</html>
