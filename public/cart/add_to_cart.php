<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user']['id'])
) {
    die("Bạn cần đăng nhập để thêm vào giỏ hàng.");
}

if (
    !isset($_POST['variant_id'], $_POST['quantity']) ||
    !is_numeric($_POST['variant_id']) ||
    !is_numeric($_POST['quantity'])
) {
    die("Dữ liệu không hợp lệ.");
}

$user_id   = (int)$_SESSION['user']['id'];
$variant_id = (int)$_POST['variant_id'];
$quantity   = max(1, (int)$_POST['quantity']);

/* Kiểm tra tồn kho */
$stmt = $pdo->prepare(
    "SELECT stock FROM product_variants WHERE id = ?"
);
$stmt->execute([$variant_id]);
$variant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$variant) {
    die("Loại sản phẩm không tồn tại.");
}

if ($quantity > $variant['stock']) {
    die("Số lượng vượt quá tồn kho.");
}

/* Kiểm tra đã có trong giỏ chưa */
$check = $pdo->prepare(
    "SELECT id FROM cart
     WHERE user_id = ? AND product_variant_id = ?"
);
$check->execute([$user_id, $variant_id]);
$cartItem = $check->fetch(PDO::FETCH_ASSOC);

if ($cartItem) {
    $update = $pdo->prepare(
        "UPDATE cart
         SET quantity = quantity + ?
         WHERE id = ?"
    );
    $update->execute([$quantity, $cartItem['id']]);
} else {
    $insert = $pdo->prepare(
        "INSERT INTO cart (user_id, product_variant_id, quantity)
         VALUES (?, ?, ?)"
    );
    $insert->execute([$user_id, $variant_id, $quantity]);
}

header("Location: cart.php");
exit;
