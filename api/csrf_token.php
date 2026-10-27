<?php
require_once __DIR__ . '/../config/database.php';
startSecureSession();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['csrf_token' => getCsrfToken()]);
