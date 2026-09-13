<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/ProductoModel.php';
require_once __DIR__ . '/controllers/auth/AuthController.php';
require_once __DIR__ . '/controllers/auth/AuthMiddleware.php';

enforceSecureSession();

$db = getDatabaseConnection();
$userModel = new UserModel($db);
$productoModel = new ProductoModel($db);
$authController = new AuthController($userModel);

$productos = $productoModel->getProductos();
$categorias = $productoModel->getCategorias();

$message = null;
$isSuccess = true;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'register') {
    $result = $authController->handleRegister($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $result = $authController->handleLogin($_POST);
    $message = $result['message'];
    $isSuccess = $result['success'];

    if ($result['success']) {
        $rol = (int)($result['user']['rol'] ?? 1);
        header('Location: ' . getRoleRedirectUrl($rol));
        exit;
    }
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $message = "Sesión cerrada de forma segura.";
    $isSuccess = true;
}

$currentUser = $_SESSION['user'] ?? null;
$currentRole = isset($currentUser['rol']) ? (int)$currentUser['rol'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chorifest - Gourmet Grill System</title>
    <meta name="description" content="Chorifest - Premium Gourmet Grill System. Authentic flavors.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Fonts: Lora for elegant headings, Inter for UI text, Montserrat for bold modern titles -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Lora:wght@500;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="public/css/styles.css?v=<?= time() ?>">
</head>
<body>

    <!-- ===== NAVBAR ===== -->
    <header class="navbar">
        <a href="index.php" class="brand">
            <div class="brand-logo-container">
                <div class="brand-logo-circle">
                    <span class="brand-logo-text">CHORIFEST</span>
                </div>
            </div>
            <div class="brand-text">
                <span class="brand-name">CHORIFEST</span>
                <span class="brand-sub">GOURMET GRILL SYSTEM</span>
            </div>
        </a>
        <nav class="nav-links">
            <a href="#menu">Menú</a>
            <a href="#combos">Combos</a>
            <?php if ($currentUser): ?>
                <div class="user-chip">
                    <span class="user-avatar"><?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?></span>
                    <span class="nav-user"><?= htmlspecialchars($currentUser['name'] ?? 'Usuario') ?></span>
                </div>
                <?php if ($currentRole === 2): ?>
                    <a href="/controllers/admin/dashboard.php" class="btn btn-primary btn-sm">Ir al Panel Admin</a>
                <?php elseif ($currentRole === 3): ?>
                    <a href="/controllers/cocinera/pedidos.php" class="btn btn-primary btn-sm">Ir al Panel Cocina</a>
                <?php elseif ($currentRole === 4): ?>
                    <a href="/controllers/domiciliario/rutas.php" class="btn btn-primary btn-sm">Ir al Panel Rutas</a>
                <?php else: ?>
                    <a href="/controllers/cliente/catalogo.php" class="btn btn-primary btn-sm">Ir a Tienda</a>
                <?php endif; ?>
                <a href="/controllers/auth/logout.php" class="btn btn-outline btn-sm">Cerrar sesión</a>
            <?php else: ?>
                <a href="/controllers/auth/login.php" class="btn btn-primary btn-sm">Iniciar sesión</a>
                <a href="/controllers/auth/register.php" class="btn btn-primary btn-sm">Registrarse</a>
            <?php endif; ?>
        </nav>
        <button class="hamburger" id="hamburger" aria-label="Menú">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
        </button>
    </header>

    <!-- ===== ALERT ===== -->
    <?php if (isset($message) && $message !== null): ?>
        <div class="alert-container">
            <div class="alert <?= $isSuccess ? 'alert-success' : 'alert-error' ?>">
                <span class="alert-icon"><?= $isSuccess ? '✓' : '⚠' ?></span>
                <div class="alert-content">
                    <span class="alert-msg"><?= htmlspecialchars($message) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="hero-bg">
            <video autoplay loop muted playsinline class="hero-video" poster="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80">
                <source src="https://cdn.pixabay.com/video/2023/10/22/185960-876722238_large.mp4" type="video/mp4">
            </video>
            <div class="hero-overlay"></div>
        </div>
        <div class="hero-content">
            <h1>Tradición a la brasa</h1>
            <p class="hero-desc">Descubre el auténtico sabor de nuestros chorizos artesanales y cortes premium. Una experiencia gourmet inigualable en cada bocado.</p>
            
            <div class="hero-actions">
                <a href="#menu" class="btn btn-primary btn-lg">Ver Menú</a>
            </div>
        </div>
    </section>

    <!-- ===== CATEGORÍAS ===== -->
    <section class="menu-section" id="menu">
        <div class="category-tabs">
            <button class="cat-tab active" data-cat="all">Todos</button>
            <?php foreach ($categorias as $cat): ?>
                <button class="cat-tab" data-cat="cat-<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- ===== GRID DE PRODUCTOS ===== -->
        <div class="products-grid">
            <?php foreach ($productos as $prod): ?>
                <div class="product-card-wrapper" data-cat="cat-<?= $prod['id_categoria'] ?>">
                    <article class="product-card <?= !$prod['disponible'] ? 'unavailable' : '' ?>" data-cat="cat-<?= $prod['id_categoria'] ?>">
                        <div class="card-reflection"></div>
                        <?php 
                            $imgUrl = $productoModel->getImagenUrl($prod['nombre_producto'], $prod['id_producto'], 600);
                        ?>
                        <div class="product-img <?= !$prod['disponible'] ? 'grayscale' : '' ?>">
                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($prod['nombre_producto']) ?>">
                            <?php if (!$prod['disponible']): ?>
                                <div class="overlay-badge" style="position: absolute; top: 15px; right: 15px; background: rgba(239, 68, 68, 0.8); color: white; padding: 6px 14px; border-radius: 12px; font-weight: 800; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 1); backdrop-filter: blur(5px); z-index: 10;">NO DISPONIBLE</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3><?= htmlspecialchars($prod['nombre_producto']) ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; margin-bottom: 15px; line-height: 1.4; flex: 1;"><?= htmlspecialchars($prod['descripcion'] ?? 'Delicioso producto a la parrilla preparado al instante.') ?></p>
                            <div class="product-footer">
                                <span class="product-price">$<?= number_format($prod['precio'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>



    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="brand-logo-container mini">
                    <div class="brand-logo-circle">
                        <span class="brand-logo-text">CF</span>
                    </div>
                </div>
                <div>
                    <span class="brand-name">CHORIFEST</span>
                    <p>Gourmet Grill System</p>
                </div>
            </div>
            <div class="footer-links">
                <a href="#">Términos y Condiciones</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Contacto</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Chorifest. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
        // Smooth Category Filtering
        const tabs = document.querySelectorAll('.cat-tab');
        const cards = document.querySelectorAll('.product-card-wrapper');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const cat = tab.dataset.cat;
                
                cards.forEach(card => {
                    if (cat === 'all' || card.dataset.cat === cat) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'scale(1)';
                        }, 10);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        });

        // Add button interaction
        document.querySelectorAll('.btn-add-circle:not(.disabled)').forEach(btn => {
            btn.addEventListener('click', function () {
                const originalContent = this.innerHTML;
                this.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                this.classList.add('success');
                
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.classList.remove('success');
                }, 1500);
            });
        });

        // Hamburger Menu
        document.getElementById('hamburger').addEventListener('click', () => {
            document.querySelector('.nav-links').classList.toggle('open');
        });

        // 3D Hover Effect for Cards
        document.querySelectorAll('.product-card-wrapper').forEach(wrapper => {
            const card = wrapper.querySelector('.product-card');
            const reflection = wrapper.querySelector('.card-reflection');
            
            wrapper.addEventListener('mousemove', (e) => {
                const rect = wrapper.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                
                const rotateX = ((y - centerY) / centerY) * -15; 
                const rotateY = ((x - centerX) / centerX) * 15;
                
                card.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateZ(20px)`;
                
                if(reflection) {
                    const bgX = (x / rect.width) * 100;
                    const bgY = (y / rect.height) * 100;
                    reflection.style.background = `radial-gradient(circle at ${bgX}% ${bgY}%, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 60%)`;
                    reflection.style.opacity = '1';
                }
            });
            
            wrapper.addEventListener('mouseleave', () => {
                card.style.transition = 'transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                card.style.transform = `rotateX(0deg) rotateY(0deg) translateZ(0)`;
                if(reflection) {
                    reflection.style.opacity = '0';
                    reflection.style.background = 'none';
                }
                
                setTimeout(() => {
                    card.style.transition = 'transform 0.2s, box-shadow 0.2s'; 
                }, 500);
            });
            
            wrapper.addEventListener('mouseenter', () => {
                card.style.transition = 'transform 0.1s ease-out'; 
            });
        });
    </script>
</body>
</html>
