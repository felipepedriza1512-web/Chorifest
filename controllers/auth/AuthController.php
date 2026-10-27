<?php

class AuthController
{
    private UserModel $userModel;

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    public function handleRegister(array $input): array
    {
        $password = $input['password'] ?? '';
        $confirmPassword = $input['password_confirm'] ?? '';

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        return $this->userModel->register(
            $input['name'] ?? '',
            $input['telefono'] ?? '',
            $input['email'] ?? '',
            $password
        );
    }

    public function handleLogin(array $input): array
    {
        $result = $this->userModel->login(
            $input['email'] ?? '',
            $input['password'] ?? ''
        );

        if ($result['success'] ?? false) {
            // Regenerar ID de sesión para prevenir Session Fixation
            session_regenerate_id(true);

            // Limpieza total previa para evitar cualquier residuo de roles anteriores
            $_SESSION = [];
            $_SESSION['user'] = $result['user'];
            $_SESSION['CREATED'] = time();
            $_SESSION['LAST_ACTIVITY'] = time();
        }

        return $result;
    }

    public function logout(?int $role = null): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }

    public function handleForgotPassword(array $input): array
    {
        $email = strtolower(trim($input['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ingresa un correo electrónico válido.'];
        }

        $user = $this->userModel->findByEmail($email);
        
        // Respuesta siempre idéntica exista o no el correo (previene enumeración de usuarios)
        $genericSuccessResponse = [
            'success' => true, 
            'message' => 'Si el correo existe en el sistema, recibirás las instrucciones para restablecer tu contraseña.'
        ];

        if ($user) {
            // Token criptográficamente seguro de 32 caracteres hexadecimales
            $token = bin2hex(random_bytes(16));
            
            if ($this->userModel->saveRecoveryCode($email, $token)) {
                $subject = "Restablecer Contraseña - Chorifest";
                $message = "Has solicitado restablecer tu contraseña.\nToken: " . $token . "\nExpira en 15 minutos y es de un solo uso.";
                $headers = "From: noreply@chorifest.local\r\n";
                
                @mail($email, $subject, $message, $headers);
            }
        }

        return $genericSuccessResponse;
    }

    public function handleResetPassword(array $input): array
    {
        $email = strtolower(trim($input['email'] ?? ''));
        $token = trim($input['code'] ?? $input['token'] ?? '');
        $password = $input['password'] ?? '';
        $confirmPassword = $input['password_confirm'] ?? '';

        if ($email === '' || $token === '' || $password === '') {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if (!$this->userModel->verifyRecoveryCode($email, $token)) {
            return ['success' => false, 'message' => 'El código de recuperación es incorrecto, ya fue utilizado o ha expirado.'];
        }

        if ($this->userModel->updatePassword($email, $password)) {
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente.'];
        }

        return ['success' => false, 'message' => 'Hubo un error al actualizar la contraseña.'];
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}
