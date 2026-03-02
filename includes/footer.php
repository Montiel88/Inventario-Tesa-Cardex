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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    
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
</body>
</html>