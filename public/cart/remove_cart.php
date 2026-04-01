<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

/* Check đăng nhập */
if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user']['id'])
) {
    die("Bạn cần đăng nhập.");
}

$id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user']['id'];

if ($id <= 0) {
    header("Location: cart.php");
    exit;
}

/* Chỉ xóa cart của đúng user */
$pdo->prepare(
    "DELETE FROM cart WHERE id = ? AND user_id = ?"
)->execute([$id, $user_id]);

header("Location: cart.php");
exit;
