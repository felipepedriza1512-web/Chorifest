<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
$rol = (int)($currentUser['rol'] ?? $currentUser['id_rol'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Empleado' ?> - Chorifest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Hyper-Premium Dark Mode Palette */
            --primary: #ea580c;       
            --primary-glow: rgba(234, 88, 12, 0.5);
            --primary-light: #ffedd5; 
            --dark: #000000;          
            --surface: #0a0a0c;       
            --surface-hover: #121214;
            --glass: rgba(15, 15, 18, 0.65);
            --border: rgba(255, 255, 255, 0.08);        
            --border-highlight: rgba(255, 255, 255, 0.15);
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
            --sidebar-width: 280px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--dark);
            background-image: 
                radial-gradient(circle at 15% 0%, rgba(234, 88, 12, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 85% 100%, rgba(234, 88, 12, 0.05) 0%, transparent 40%);
            background-attachment: fixed;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 35px 0 25px 0;
            z-index: 50;
        }

        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            text-decoration: none;
            padding: 0 35px;
            margin-bottom: 50px;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }
        
        .sidebar-brand::before {
            content: '';
            display: block;
            width: 14px;
            height: 14px;
            background: linear-gradient(135deg, var(--primary), #fb923c);
            border-radius: 4px;
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0 20px;
        }

        .nav-item {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 12px 18px;
            border-radius: 10px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid transparent;
        }

        .nav-item svg {
            transition: var(--transition);
            opacity: 0.7;
        }

        .nav-item:hover {
            color: var(--text-main);
            background: var(--surface-hover);
            border-color: var(--border);
            transform: translateX(4px);
        }
        
        .nav-item:hover svg {
            opacity: 1;
            stroke: var(--text-main);
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(234, 88, 12, 0.1) 0%, transparent 100%);
            color: var(--primary);
            border: 1px solid var(--border);
            border-left: 3px solid var(--primary);
        }
        
        .nav-item.active svg {
            opacity: 1;
            stroke: var(--primary);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 25px 35px 0 35px;
        }

        .btn-logout {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
        }
        .btn-logout:hover { 
            color: #ef4444; 
            transform: translateX(4px);
        }

        /* --- Main Content --- */
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: var(--glass);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid var(--border);
            padding: 18px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.4px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-main);
            padding: 6px 12px 6px 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 30px;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .user-profile:hover {
            border-color: var(--border-highlight);
            background: var(--surface-hover);
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #fb923c);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 800;
            font-size: 0.9rem;
            box-shadow: 0 0 10px var(--primary-glow);
        }

        .content-area {
            padding: 50px;
            flex: 1;
        }

        /* --- Common UI (Cards & Panels) --- */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .card:hover {
            border-color: var(--border-highlight);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.7);
        }

        /* Card Top Glow */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .badge-primary { background: rgba(255, 75, 43, 0.2); color: var(--primary); border: 1px solid var(--primary); }
        .badge-success { background: rgba(46, 213, 115, 0.2); color: #2ed573; border: 1px solid #2ed573; }
        .badge-warning { background: rgba(255, 165, 2, 0.2); color: #ffa502; border: 1px solid #ffa502; }
        
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            border: none;
            display: inline-block;
            text-decoration: none;
            transition: var(--transition);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), #fb923c);
            color: white;
            box-shadow: 0 0 10px var(--primary-glow);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px var(--primary-glow);
        }
        .btn:active { transform: scale(0.97); }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="<?= $rol === 3 ? '/controllers/cocinera/pedidos.php' : ($rol === 4 ? '/controllers/domiciliario/rutas.php' : '/controllers/admin/dashboard.php') ?>" class="sidebar-brand">STAFF.</a>
        
        <nav class="nav-links">
            <?php if ($rol === 3 || $rol === 2): ?>
            <a href="/controllers/cocinera/pedidos.php" class="nav-item <?= ($currentPage ?? '') === 'cocina' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20"></path><path d="M20 12v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-8"></path><path d="M4 6v6"></path><path d="M20 6v6"></path><path d="M8 2v4"></path><path d="M12 2v4"></path><path d="M16 2v4"></path></svg>
                Panel de Cocina
            </a>
            <?php endif; ?>
            <?php if ($rol === 4 || $rol === 2): ?>
            <a href="/controllers/domiciliario/rutas.php" class="nav-item <?= ($currentPage ?? '') === 'rutas' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
                Rutas de Entrega
            </a>
            <?php endif; ?>
            <?php if ($rol === 2): ?>
            <a href="/controllers/admin/dashboard.php" class="nav-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Panel Admin
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="/controllers/auth/logout.php" class="btn-logout">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Cerrar sesión
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-wrapper">
        <header class="topbar">
            <h2 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Panel') ?></h2>
            <div class="user-profile">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['name'] ?? 'E', 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($currentUser['name'] ?? 'Empleado') ?></span>
            </div>
        </header>

        <div class="content-area">
