<?php
require_once __DIR__ . '/../../config/database.php';
startSecureSession();
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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700;9..144,800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <style>
        :root {
            /* Chorifest Brand Aesthetics */
            --bg: #0c0505;
            --surface: #150909;
            --surface-card: #1f1011;
            --surface-hover: #2a1618;
            --border: rgba(191, 165, 147, 0.12);
            --border-highlight: rgba(168, 57, 31, 0.4);
            
            --accent: #A8391F;
            --accent-hover: #C2452A;
            --ember: #ff8a2a;
            
            --text-main: #F3ECDA;
            --text-muted: #bfa593;
            --text-dark: #0c0505;
            
            --font-title: 'Fraunces', Georgia, serif;
            --font-body: 'Inter', -apple-system, sans-serif;
            
            --sidebar-width: 270px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-body);
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: rgba(191, 165, 147, 0.2); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--surface);
            border-right: 1px solid var(--border);
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 24px 0;
            z-index: 50;
        }

        .sidebar-brand {
            padding: 0 24px;
            margin-bottom: 36px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-logo-emblem {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            font-family: var(--font-title);
            font-weight: 800;
            font-size: 1.4rem;
            box-shadow: 0 4px 15px rgba(168, 57, 31, 0.4);
            border: 1px solid rgba(255, 138, 42, 0.3);
        }

        .brand-text-container {
            display: flex;
            flex-direction: column;
        }

        .brand-name {
            font-family: var(--font-title);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.5px;
            line-height: 1.1;
        }

        .brand-sub {
            font-size: 0.65rem;
            color: var(--text-muted);
            letter-spacing: 1px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 16px;
        }

        .nav-item {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 12px 16px;
            border-radius: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .nav-item svg {
            transition: var(--transition);
            opacity: 0.75;
            stroke-width: 2;
        }

        .nav-item:hover {
            color: var(--text-main);
            background: var(--surface-card);
            border-color: var(--border);
        }
        
        .nav-item:hover svg {
            opacity: 1;
            stroke: var(--ember);
        }

        .nav-item.active {
            background: var(--surface-card);
            color: var(--text-main);
            border-color: var(--border-highlight);
            font-weight: 600;
        }
        
        .nav-item.active svg {
            opacity: 1;
            stroke: var(--accent);
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px 24px 0 24px;
            border-top: 1px solid var(--border);
        }

        .btn-logout {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }
        .btn-logout:hover { 
            color: #ef4444; 
        }

        /* --- Main Wrapper --- */
        .main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0;
        }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 36px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .page-title {
            font-family: var(--font-title);
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.3px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-bell {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--surface-card);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .notification-bell:hover {
            color: var(--text-main);
            border-color: var(--border-highlight);
            background: var(--surface-hover);
        }

        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--accent);
            color: var(--text-main);
            font-size: 0.7rem;
            font-weight: 800;
            height: 18px;
            min-width: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--surface);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
            padding: 6px 14px 6px 8px;
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            font-family: var(--font-title);
            font-weight: 800;
            font-size: 0.85rem;
        }

        .content-area {
            padding: 32px 36px;
            flex: 1;
        }

        /* --- Global Component Classes (Cards, Grids, Buttons) --- */
        .card {
            background: var(--surface-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            transition: var(--transition);
        }
        
        .card:hover {
            border-color: rgba(191, 165, 147, 0.25);
        }

        .btn {
            padding: 9px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.88rem;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-primary {
            background: var(--accent);
            color: var(--text-main);
        }
        .btn-primary:hover {
            background: var(--accent-hover);
        }

        .btn-secondary {
            background: rgba(191, 165, 147, 0.1);
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: rgba(191, 165, 147, 0.2);
        }

        .btn-danger {
            background: rgba(220, 38, 38, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(220, 38, 38, 0.3);
        }
        .btn-danger:hover {
            background: rgba(220, 38, 38, 0.3);
        }

        @media (max-width: 900px) {
            .sidebar { width: 80px; padding: 20px 0; }
            .brand-text-container, .nav-item span, .btn-logout span { display: none; }
            .sidebar-brand { padding: 0 18px; justify-content: center; }
            .nav-item { justify-content: center; padding: 12px; }
            .main-wrapper { margin-left: 80px; }
            .content-area { padding: 20px; }
            .topbar { padding: 14px 20px; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="<?= baseUrl('/controllers/admin/dashboard.php') ?>" class="sidebar-brand">
            <div class="brand-logo-emblem">C</div>
            <div class="brand-text-container">
                <span class="brand-name">CHORIFEST</span>
                <span class="brand-sub">Gourmet Grill</span>
            </div>
        </a>
        
        <nav class="nav-links">
            <a href="<?= baseUrl('/controllers/admin/dashboard.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <!-- Icono Dashboard / Layout -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/pedidos.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'pedidos' ? 'active' : '' ?>">
                <!-- Icono Pedidos / Lista -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                <span>Gestión Pedidos</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/productos.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'productos' ? 'active' : '' ?>">
                <!-- Icono Productos / Etiqueta -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><circle cx="7" cy="7" r="1.5"></circle></svg>
                <span>Productos</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/ingredientes.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'ingredientes' ? 'active' : '' ?>">
                <!-- Icono Ingredientes / Cocina -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                <span>Ingredientes</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/zonas.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'zonas' ? 'active' : '' ?>">
                <!-- Icono Zonas / Mapa -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                <span>Zonas de Envío</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/empleados.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'empleados' ? 'active' : '' ?>">
                <!-- Icono Personal / Usuarios -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <span>Gestión Personal</span>
            </a>
            <a href="<?= baseUrl('/controllers/admin/reportes.php') ?>" class="nav-item <?= ($currentPage ?? '') === 'reportes' ? 'active' : '' ?>">
                <!-- Icono Reportes / Métricas -->
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                <span>Reportes y Métricas</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= baseUrl('/controllers/auth/logout.php') ?>" class="btn-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-wrapper">
        <header class="topbar">
            <h2 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Panel Admin') ?></h2>
            
            <div class="topbar-actions">
                <a href="#alertasSection" class="notification-bell" title="Alertas activas">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span class="notification-badge" id="topNotificationCount"><?= (int)($stats['por_atender'] ?? 0) ?></span>
                </a>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($currentUser['name'] ?? 'Administrador') ?></span>
                </div>
            </div>
        </header>

        <div class="content-area">

