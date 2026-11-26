<?php
session_start();

$userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
$isLoggedIn = (is_int($userId) || (is_string($userId) && ctype_digit($userId)))
    && (int) $userId > 0;

if (!$isLoggedIn) {
    $currentDirectory = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/View/catalogo.php')), '/');
    header('Location: ' . $currentDirectory . '/login.php?redirect=catalogo.php', true, 302);
    exit;
}
