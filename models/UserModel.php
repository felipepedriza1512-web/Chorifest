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

    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Correo y contraseña son obligatorios.'];
        }

        $user = $this->findByEmail($email);

        if ($user === null) {
            return ['success' => false, 'message' => 'Correo o contraseña incorrectos.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Correo o contraseña incorrectos.'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => (int) $user['id_usuario'],
                'name' => $user['nombre'],
                'email' => $user['correo'],
                'rol' => (int) ($user['id_rol'] ?? 1),
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

    public function saveRecoveryCode(string $email, string $code): bool
    {
        // First delete any existing codes for this email
        $stmt = $this->db->prepare('DELETE FROM recuperacion_password WHERE correo = ?');
        $stmt->execute([$email]);

        // Expiration in 15 minutes
        $expiration = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $this->db->prepare('INSERT INTO recuperacion_password (correo, codigo, fecha_expiracion) VALUES (?, ?, ?)');
        return $stmt->execute([$email, $code, $expiration]);
    }

    public function verifyRecoveryCode(string $email, string $code): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM recuperacion_password WHERE correo = ? AND codigo = ? AND fecha_expiracion > NOW() LIMIT 1');
        $stmt->execute([$email, $code]);
        $row = $stmt->fetch();

        if ($row) {
            // Delete code after successful verification
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
