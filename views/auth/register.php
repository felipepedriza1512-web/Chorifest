<?php
// Ensure this view is accessed through the controller, not directly.
if (!isset($isSuccess)) {
    $isSuccess = true;
    $message = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Premium - Chorifest</title>
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

        /* Overlay to make background darker */
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
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .subtitle {
            color: #888;
            margin-bottom: 40px;
            font-weight: 300;
        }

        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .input-group input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .input-group input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 75, 43, 0.1);
        }

        .input-group svg {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            color: #666;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .input-group input:focus + svg,
        .input-group input:not(:placeholder-shown) + svg {
            color: var(--primary);
        }

        .input-row {
            display: flex;
            gap: 20px;
        }

        .input-row .input-group {
            flex: 1;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px -10px rgba(255, 75, 43, 0.5);
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -10px rgba(255, 75, 43, 0.6);
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .auth-links {
            margin-top: 30px;
            text-align: center;
        }

        .auth-links a {
            color: #a0a0a0;
            text-decoration: none;
            font-weight: 400;
            transition: color 0.3s ease;
            font-size: 0.95rem;
        }

        .auth-links a:hover {
            color: white;
        }

        .auth-links a span {
            color: var(--primary);
            font-weight: 600;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert.error { 
            background: rgba(255, 75, 43, 0.1); 
            color: #ff8a8a; 
            border: 1px solid rgba(255, 75, 43, 0.2); 
        }
        
        .alert.success { 
            background: rgba(46, 204, 113, 0.1); 
            color: #82f4b1; 
            border: 1px solid rgba(46, 204, 113, 0.2); 
        }

        @media (max-width: 900px) {
            .container { flex-direction: column; margin: 20px; }
            .brand-section { padding: 40px 20px; border-right: none; border-bottom: 1px solid var(--glass-border); text-align: center; }
            .form-section { padding: 40px 20px; }
            .input-row { flex-direction: column; gap: 0; }
        }

        @media (max-width: 480px) {
            body { align-items: flex-start; padding: 10px 0; }
            .container { margin: 10px auto; border-radius: 20px; width: calc(100% - 30px); }
            .brand-section { padding: 35px 25px 25px; }
            .brand-logo { font-size: 2.2rem; margin-bottom: 10px; }
            .brand-tagline { font-size: 1rem; }
            .form-section { padding: 30px 25px 40px; }
            h2 { font-size: 1.7rem; }
            .subtitle { margin-bottom: 25px; font-size: 0.9rem; }
            .input-group input { padding: 15px 15px 15px 45px; font-size: 0.95rem; }
            .btn-submit { padding: 15px; font-size: 1rem; margin-top: 5px; }
            .auth-links { margin-top: 25px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="brand-section">
            <div class="brand-logo">CHORIFEST.</div>
            <p class="brand-tagline">Eleva tu experiencia gastronómica. El sistema definitivo para amantes de la parrilla y el buen sabor.</p>
        </div>

        <div class="form-section">
            <h2>Únete ahora</h2>
            <p class="subtitle">Crea tu cuenta y comienza a disfrutar</p>

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

            <form method="POST" action="register.php">
                <input type="hidden" name="action" value="register">
                
                <div class="input-group">
                    <input type="text" name="name" placeholder="Nombre Completo" required>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </div>

                <div class="input-group">
                    <input type="tel" name="telefono" placeholder="Teléfono" required>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                </div>

                <div class="input-group">
                    <input type="email" name="email" placeholder="Correo Electrónico" required>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Contraseña" required>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
                    <div class="input-group">
                        <input type="password" name="password_confirm" placeholder="Confirmar" required>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Registrarme Ahora</button>
            </form>

            <div class="auth-links">
                <a href="login.php">¿Ya tienes una cuenta? <span>Inicia Sesión</span></a>
            </div>
        </div>
    </div>

</body>
</html>
