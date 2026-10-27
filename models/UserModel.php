<?php

class UserModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function register(string $name, string $telefono, string $email, string $password): array
    {
        $name = trim($name);
        $telefono = trim($telefono);
        $email = strtolower(trim($email));

        if ($name === '' || $telefono === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Todos los campos son obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ingresa un correo válido.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        if ($this->findByEmail($email) !== null) {
            return ['success' => false, 'message' => 'Este correo ya está registrado.'];
        }

        try {
            $this->db->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            // Asumimos id_rol = 1 para cliente, estado = 'ACTIVO'
            $stmt = $this->db->prepare('INSERT INTO usuario (nombre, correo, password_hash, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hashedPassword, $telefono, 1, 'ACTIVO']);
            
            $userId = $this->db->lastInsertId();
            
            // Register in cliente table
            $stmtCliente = $this->db->prepare('INSERT INTO cliente (id_usuario) VALUES (?)');
            $stmtCliente->execute([$userId]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Usuario registrado correctamente.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Ocurrió un error al registrar el usuario.'];
        }
    }

    public function isLoginBlocked(string $email, string $ip): bool
    {
        $windowMinutes = 15;
        $maxAttempts = 5;

        // Verificar intentos por correo
        $stmtCorreo = $this->db->prepare('SELECT COUNT(*) FROM intentos_login WHERE correo = ? AND fecha >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmtCorreo->execute([$email, $windowMinutes]);
        $intentosCorreo = (int)$stmtCorreo->fetchColumn();

        // Verificar intentos por IP
        $stmtIp = $this->db->prepare('SELECT COUNT(*) FROM intentos_login WHERE ip = ? AND fecha >= DATE_SUB(NOW(), INTERVAL ? MINUTE)');
        $stmtIp->execute([$ip, $windowMinutes]);
        $intentosIp = (int)$stmtIp->fetchColumn();

        return ($intentosCorreo >= $maxAttempts || $intentosIp >= $maxAttempts);
    }

    public function recordFailedLogin(string $email, string $ip): void
    {
        $stmt = $this->db->prepare('INSERT INTO intentos_login (ip, correo, fecha) VALUES (?, ?, NOW())');
        $stmt->execute([$ip, $email]);
    }

    public function clearFailedLogins(string $email, string $ip): void
    {
        $stmt = $this->db->prepare('DELETE FROM intentos_login WHERE correo = ? OR ip = ?');
        $stmt->execute([$email, $ip]);
    }

    public function login(string $email, string $password, ?string $ip = null): array
    {
        $email = strtolower(trim($email));
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if ($this->isLoginBlocked($email, $ip)) {
            return [
                'success' => false,
                'message' => 'Demasiados intentos fallidos. Tu cuenta o dirección IP han sido bloqueadas temporalmente. Por favor intenta más tarde.'
            ];
        }

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Correo y contraseña son obligatorios.'];
        }

        $user = $this->findByEmail($email);

        // Comparación segura resistente a ataques de temporización (timing attack)
        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
        $hashToVerify = ($user !== null && !empty($user['password_hash'])) ? $user['password_hash'] : $dummyHash;
        $passwordMatches = password_verify($password, $hashToVerify);

        if ($user === null || !$passwordMatches) {
            $this->recordFailedLogin($email, $ip);
            return ['success' => false, 'message' => 'Correo o contraseña incorrectos.'];
        }

        // Verificar si el usuario está activo
        if (isset($user['estado']) && strtoupper($user['estado']) !== 'ACTIVO') {
            return ['success' => false, 'message' => 'Tu usuario se encuentra inactivo. Contacta a un administrador.'];
        }

        // Limpiar intentos fallidos al iniciar sesión con éxito
        $this->clearFailedLogins($email, $ip);

        return [
            'success' => true,
            'user' => [
                'id' => (int) $user['id_usuario'],
                'name' => $user['nombre'],
                'email' => $user['correo'],
                'rol' => (int) ($user['id_rol'] ?? 1),
                'estado' => $user['estado'] ?? 'ACTIVO',
                'debe_cambiar_password' => (int) ($user['debe_cambiar_password'] ?? 0),
            ],
        ];
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM usuario WHERE correo = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function saveRecoveryCode(string $email, string $token): bool
    {
        // First delete any existing codes for this email
        $stmt = $this->db->prepare('DELETE FROM recuperacion_password WHERE correo = ?');
        $stmt->execute([$email]);

        // Expiration in 15 minutes and store secure SHA-256 hash
        $tokenHash = hash('sha256', $token);
        $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $this->db->prepare('INSERT INTO recuperacion_password (correo, codigo, fecha_expiracion) VALUES (?, ?, ?)');
        return $stmt->execute([$email, $tokenHash, $expiration]);
    }

    public function verifyRecoveryCode(string $email, string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare('SELECT id FROM recuperacion_password WHERE correo = ? AND codigo = ? AND fecha_expiracion > NOW() LIMIT 1');
        $stmt->execute([$email, $tokenHash]);
        $row = $stmt->fetch();

        if ($row) {
            // Eliminar de inmediato para garantizar uso único (single-use token)
            $delStmt = $this->db->prepare('DELETE FROM recuperacion_password WHERE id = ?');
            $delStmt->execute([$row['id']]);
            return true;
        }

        return false;
    }

    public function updatePassword(string $email, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE usuario SET password_hash = ? WHERE correo = ?');
        return $stmt->execute([$hashedPassword, $email]);
    }
}
