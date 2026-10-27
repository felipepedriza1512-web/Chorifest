<?php
require_once __DIR__ . '/../../config/database.php';
startSecureSession();
if (!isset($authController)) {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/UserModel.php';
    require_once __DIR__ . '/../../controllers/auth/AuthController.php';
    require_once __DIR__ . '/../../controllers/auth/AuthMiddleware.php';
    enforceSecureSession();
    $db = getDatabaseConnection();
    $userModel = new UserModel($db);
    $authController = new AuthController($userModel);

    $email = $_GET['email'] ?? '';
    $message = null;
    $isSuccess = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
        $result = $authController->handleResetPassword($_POST);
        $message = $result['message'];
        $isSuccess = $result['success'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Chorifest</title>
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
            --glass-bg: rgba(20, 20, 25, 0.7);
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
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(15, 12, 41, 0.9) 0%, rgba(48, 43, 99, 0.8) 50%, rgba(36, 36, 62, 0.9) 100%);
            z-index: 0;
        }

        .container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1100px;
            display: flex;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            margin: 40px 20px;
            animation: floatUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            opacity: 0;
            transform: translateY(40px);
        }

        @keyframes floatUp {
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-section {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, rgba(255,75,43,0.1) 0%, rgba(255,65,108,0.1) 100%);
            border-right: 1px solid var(--glass-border);
        }

        .brand-logo {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            letter-spacing: -1px;
        }

        .brand-tagline {
            font-size: 1.2rem;
            line-height: 1.6;
            color: #a0a0a0;
            font-weight: 300;
        }

        .form-section {
            flex: 1;
            padding: 60px;
            background: rgba(10, 10, 15, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        h2 { font-size: 2rem; margin-bottom: 8px; font-weight: 600; }
        .subtitle { color: #888; margin-bottom: 40px; font-weight: 300; }

        .input-group { position: relative; margin-bottom: 24px; }
        .input-group input {
            width: 100%; padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px; color: white; font-size: 1rem;
            outline: none; transition: all 0.3s ease;
        }
        .input-group input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 75, 43, 0.1);
        }
        .input-group svg {
            position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
            width: 20px; height: 20px; color: #666; transition: color 0.3s ease; pointer-events: none;
        }
        .input-group input:focus + svg, .input-group input:not(:placeholder-shown) + svg {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; font-size: 1.1rem; font-weight: 600; cursor: pointer;
            transition: all 0.3s ease; margin-top: 10px; position: relative; overflow: hidden;
            box-shadow: 0 10px 20px -10px rgba(255, 75, 43, 0.5);
            text-align: center; display: inline-block;
        }
        .btn-submit::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 15px 25px -10px rgba(255, 75, 43, 0.6); }
        .btn-submit:hover::before { left: 100%; }

        .auth-links { margin-top: 30px; text-align: center; }
        .auth-links a {
            color: #a0a0a0; text-decoration: none; font-weight: 400;
            transition: color 0.3s ease; font-size: 0.95rem;
        }
        .auth-links a:hover { color: white; }
        .auth-links a span { color: var(--primary); font-weight: 600; }

        .alert {
            padding: 16px 20px; border-radius: 12px; margin-bottom: 24px;
            font-size: 0.95rem; display: flex; align-items: center; gap: 12px;
            animation: slideDown 0.4s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert.error { background: rgba(255, 75, 43, 0.1); color: #ff8a8a; border: 1px solid rgba(255, 75, 43, 0.2); }
        .alert.success { background: rgba(46, 204, 113, 0.1); color: #82f4b1; border: 1px solid rgba(46, 204, 113, 0.2); }

        @media (max-width: 900px) {
            .container { flex-direction: column; }
            .brand-section { padding: 40px; border-right: none; border-bottom: 1px solid var(--glass-border); }
            .form-section { padding: 40px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="brand-section">
            <div class="brand-logo">CHORIFEST.</div>
            <p class="brand-tagline">Ingresa el código que te hemos enviado y crea tu nueva contraseña.</p>
        </div>

        <div class="form-section">
            <h2>Cambiar Contraseña</h2>
            <p class="subtitle">Te hemos enviado un código a <strong><?= htmlspecialchars($email) ?></strong>.</p>

            <?php if (isset($message) && $message !== null): ?>
                <div class="alert <?= $isSuccess ? 'success' : 'error' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?= $isSuccess 
                            ? '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>' 
                            : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>' 
                        ?>
                    </svg>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($isSuccess && isset($message)): ?>
                <div class="auth-links" style="margin-top: 10px;">
                    <a href="login.php" class="btn-submit" style="text-decoration: none; color: white;">Ir a Iniciar Sesión</a>
                </div>
            <?php else: ?>
                <form method="POST" action="reset_password.php">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    
                    <div class="input-group">
                        <input type="text" name="code" placeholder="Token de recuperación" required value="<?= htmlspecialchars($_GET['token'] ?? ($_POST['code'] ?? '')) ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" placeholder="Nueva Contraseña" required>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password_confirm" placeholder="Confirmar Nueva Contraseña" required>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>

                    <button type="submit" class="btn-submit">Guardar y Continuar</button>
                </form>

                <div class="auth-links">
                    <a href="forgot_password.php">¿No recibiste el código? <span>Volver a enviar</span></a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= function_exists('baseUrl') ? baseUrl('/chorifest-fondo.js') : '../../chorifest-fondo.js' ?>"></script>
</body>
</html>
