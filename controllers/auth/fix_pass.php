<?php
require_once __DIR__ . '/../../config/database.php';
$db = getDatabaseConnection();
$hash = '$2y$10$3z4fk./9inMoYpCb/Wd86ORVDfVoDU20o546F2341ebR/NmG6m.76';
$stmt = $db->prepare('UPDATE usuario SET password_hash = ? WHERE id_rol = 3');
$stmt->execute([$hash]);
echo "Updated " . $stmt->rowCount() . " rows.";
