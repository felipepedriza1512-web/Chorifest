<?php
require_once __DIR__ . '/../../config/database.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualización de Contraseña - Chorifest</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF4B2B;
            --secondary: #FF416C;
            --dark: #0f0c29;
            --darker: #000000;
            --light: #f8f9fa;
            --glass-bg: rgba(20, 20, 25, 0.75);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background: var(--dark);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(255, 75, 43, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(255, 65, 108, 0.15), transparent 25%),
                url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            padding: 20px;
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 12, 41, 0.92) 0%, rgba(48, 43, 99, 0.85) 50%, rgba(36, 36, 62, 0.92) 100%);
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 950px;
            display: flex;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            animation: floatUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        @keyframes floatUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-section {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, rgba(255,75,43,0.12) 0%, rgba(255,65,108,0.12) 100%);
            border-right: 1px solid var(--glass-border);
        }

        .brand-logo {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
            letter-spacing: -1px;
        }

        .brand-tagline {
            font-size: 1.15rem;
            line-height: 1.6;
            color: #b0b0b0;
            font-weight: 300;
        }

        .security-badge {
            margin-top: 30px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            background: rgba(255, 75, 43, 0.1);
            border: 1px solid rgba(255, 75, 43, 0.25);
            border-radius: 12px;
            color: #ffaa99;
            font-size: 0.9rem;
        }

        .form-section {
            flex: 1.2;
            padding: 50px;
            background: rgba(10, 10, 15, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 { font-size: 1.8rem; margin-bottom: 8px; font-weight: 700; }
        .subtitle { color: #999; margin-bottom: 30px; font-size: 0.95rem; line-height: 1.5; }

        .input-group { position: relative; margin-bottom: 20px; }
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.88rem;
            color: #bbb;
            font-weight: 500;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper input {
            width: 100%;
            padding: 14px 20px 14px 46px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: white;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.3s ease;
        }
        .input-wrapper input:focus {
            background: rgba(255, 255, 255, 0.07);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 75, 43, 0.15);
        }
        .input-wrapper svg {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #777;
            transition: color 0.3s ease;
            pointer-events: none;
        }
        .input-wrapper input:focus + svg {
            color: var(--primary);
        }

        .policy-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 0.85rem;
            color: #aaa;
            line-height: 1.4;
        }
        .policy-box ul {
            margin-top: 6px;
            padding-left: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px -10px rgba(255, 75, 43, 0.5);
            text-align: center;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -10px rgba(255, 75, 43, 0.7);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert.error {
            background: rgba(255, 75, 43, 0.12);
            color: #ff8a8a;
            border: 1px solid rgba(255, 75, 43, 0.25);
        }

        .logout-link {
            margin-top: 24px;
            text-align: center;
            font-size: 0.88rem;
        }
        .logout-link a {
            color: #888;
            text-decoration: none;
            transition: color 0.2s;
        }
        .logout-link a:hover {
            color: #ffaa99;
        }

        @media (max-width: 850px) {
            .container { flex-direction: column; }
            .brand-section { padding: 35px; border-right: none; border-bottom: 1px solid var(--glass-border); }
            .form-section { padding: 35px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand-section">
            <h1 class="brand-logo">Chorifest</h1>
            <p class="brand-tagline">Seguridad y control en cada experiencia culinaria.</p>
            <div class="security-badge">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Acceso inicial seguro</span>
            </div>
        </div>

        <div class="form-section">
            <h2>Actualizar Contraseña</h2>
            <p class="subtitle">Tu cuenta requiere que configures una nueva contraseña antes de comenzar a operar en la plataforma.</p>

            <?php if (!empty($error)): ?>
                <div class="alert error">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"></circle>
                        <line x1="12" y1="8" x2="12" y2="12" stroke-width="2"></line>
                        <circle cx="12" cy="16" r="1" fill="currentColor"></circle>
                    </svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= baseUrl('/controllers/auth/cambiar_password_obligatorio.php') ?>" method="POST" id="formCambiarPassword">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">

                <div class="input-group">
                    <label for="password">Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required minlength="8" placeholder="••••••••••••" autofocus>
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke-width="2"></rect>
                            <path d="M7 11V7a5 5 0 0110 0v4" stroke-width="2"></path>
                        </svg>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password_confirm">Confirmar Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8" placeholder="••••••••••••">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>

                <div class="policy-box">
                    <strong>Requisitos de seguridad:</strong>
                    <ul>
                        <li>Al menos 8 caracteres de longitud.</li>
                        <li>No usar contraseñas comunes ni predecibles (123456, admin123, etc.).</li>
                    </ul>
                </div>

                <button type="submit" class="btn-submit" id="btnGuardar">Guardar Contraseña y Continuar</button>

                <div class="logout-link">
                    <a href="<?= baseUrl('/controllers/auth/logout.php') ?>">Cerrar sesión e ingresar más tarde</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
