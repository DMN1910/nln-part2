<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user']['id'])
) {
    die("Bạn cần đăng nhập.");
}

$user_id = (int)$_SESSION['user']['id'];

/* Check dữ liệu */
if (!isset($_POST['qty']) || !is_array($_POST['qty'])) {
    header("Location: cart.php");
    exit;
}

foreach ($_POST['qty'] as $cart_id => $qty) {
    $cart_id = (int)$cart_id;
    $qty = max(1, (int)$qty);

    $stmt = $pdo->prepare(
        "UPDATE cart
         SET quantity = ?
         WHERE id = ? AND user_id = ?"
    );
    $stmt->execute([$qty, $cart_id, $user_id]);
}

header("Location: cart.php");
exit;
