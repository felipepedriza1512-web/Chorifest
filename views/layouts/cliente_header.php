<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;

$cartCount = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cartCount += $item['cantidad'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Cliente' ?> - Chorifest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ea580c;
            --primary-glow: rgba(234, 88, 12, 0.4);
            --secondary: #fb923c;
            --dark: #000000;
            --darker: #050505;
            --light: #f4f4f5;
            --glass-bg: rgba(15, 15, 18, 0.5);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-highlight: rgba(255, 255, 255, 0.15);
            --text-muted: #a1a1aa;
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--dark);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(234, 88, 12, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(251, 146, 60, 0.05) 0%, transparent 40%),
                url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--light);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(5, 5, 5, 0.92) 0%, rgba(10, 10, 15, 0.96) 100%);
            z-index: -1;
            backdrop-filter: blur(5px);
        }
        
        /* Premium Custom Scrollbar */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; border: 2px solid var(--dark); }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

        /* Navbar */
        .navbar {
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-brand {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            letter-spacing: -1px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .nav-item {
            color: #ccc;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-item:hover, .nav-item.active {
            color: var(--primary);
        }

        .cart-icon {
            position: relative;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-left: 30px;
            border-left: 1px solid var(--glass-border);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
        }

        .user-name {
            font-weight: 600;
            color: white;
        }

        .btn-logout {
            color: #ff8a8a;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .btn-logout:hover {
            color: var(--primary);
        }

        .main-content {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
            .nav-links { width: 100%; justify-content: space-between; overflow-x: auto; padding-bottom: 10px; }
            .user-menu { border-left: none; padding-left: 0; width: 100%; justify-content: space-between; }
            .main-content { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/controllers/cliente/catalogo.php" class="nav-brand">CHORIFEST.</a>
        
        <div class="nav-links">
            <a href="/controllers/cliente/catalogo.php" class="nav-item <?= ($currentPage ?? '') === 'catalogo' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Menú
            </a>
            <a href="/controllers/cliente/pedidos.php" class="nav-item <?= ($currentPage ?? '') === 'pedidos' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Mis Pedidos
            </a>
            <a href="/controllers/cliente/carrito.php" class="nav-item cart-icon <?= ($currentPage ?? '') === 'carrito' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                Carrito
                <?php if($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="user-menu">
            <?php if($currentUser): ?>
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['name'] ?? 'C', 0, 1)) ?>
                </div>
                <div>
                    <div class="user-name"><?= htmlspecialchars($currentUser['name'] ?? 'Usuario') ?></div>
                    <a href="/controllers/auth/logout.php" class="btn-logout">Cerrar sesión</a>
                </div>
            <?php else: ?>
                <a href="/controllers/auth/login.php" class="nav-item">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="main-content">
