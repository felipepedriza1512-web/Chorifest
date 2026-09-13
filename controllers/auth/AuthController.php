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

        if ($email === '') {
            return ['success' => false, 'message' => 'Ingresa tu correo electrónico.'];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return ['success' => true, 'message' => 'Si el correo existe, se enviará un código de recuperación.'];
        }

        $code = sprintf("%06d", mt_rand(1, 999999));
        
        if ($this->userModel->saveRecoveryCode($email, $code)) {
            $subject = "Código de Recuperación - Chorifest";
            $message = "Tu código de recuperación es: " . $code . "\nExpira en 15 minutos.";
            $headers = "From: noreply@chorifest.local\r\n";
            
            mail($email, $subject, $message, $headers);
        }

        return ['success' => true, 'message' => 'Si el correo existe, se enviará un código de recuperación.'];
    }

    public function handleResetPassword(array $input): array
    {
        $email = strtolower(trim($input['email'] ?? ''));
        $code = trim($input['code'] ?? '');
        $password = $input['password'] ?? '';
        $confirmPassword = $input['password_confirm'] ?? '';

        if ($email === '' || $code === '' || $password === '') {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if (!$this->userModel->verifyRecoveryCode($email, $code)) {
            return ['success' => false, 'message' => 'El código de recuperación es incorrecto o ha expirado.'];
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
