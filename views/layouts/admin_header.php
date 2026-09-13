<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUser = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Panel Admin' ?> - Chorifest</title>
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
            min-width: 0;
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
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/controllers/admin/dashboard.php" class="sidebar-brand">ADMIN.</a>
        
        <nav class="nav-links">
            <a href="/controllers/admin/dashboard.php" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </a>
            <a href="/controllers/admin/pedidos.php" class="nav-item <?= ($currentPage ?? '') === 'pedidos' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Gestión Pedidos
            </a>
            <a href="/controllers/admin/productos.php" class="nav-item <?= ($currentPage ?? '') === 'productos' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                Productos
            </a>
            <a href="/controllers/admin/ingredientes.php" class="nav-item <?= ($currentPage ?? '') === 'ingredientes' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                Ingredientes
            </a>
            <a href="/controllers/admin/zonas.php" class="nav-item <?= ($currentPage ?? '') === 'zonas' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                Zonas de Envío
            </a>
            <a href="/controllers/admin/empleados.php" class="nav-item <?= ($currentPage ?? '') === 'empleados' ? 'active' : '' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Gestión Personal
            </a>
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
                    <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                </div>
                <span><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
            </div>
        </header>

        <div class="content-area">
