<?php
$file = 'c:\laragon\www\Chorifest\sql\bd. chorifest.sql';
$sql = file_get_contents($file);

// Replace modern collations with highly compatible ones
$sql = str_replace('utf8mb4_0900_ai_ci', 'utf8_general_ci', $sql);
$sql = str_replace('utf8mb4_unicode_ci', 'utf8_general_ci', $sql);
$sql = str_replace('utf8mb4', 'utf8', $sql);
$sql = str_replace('utf8_unicode_ci', 'utf8_general_ci', $sql);

file_put_contents($file, $sql);
echo "SQL Sanitizado para Tonohost";
