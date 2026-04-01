<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";

/* ── Kiểm tra đăng nhập ── */
if (!isset($_SESSION['user']['id'])) {
    header("Location: " . BASE_URL . "/pages/auth/login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/pages/cart/checkout.php");
    exit;
}

$user_id    = (int)$_SESSION['user']['id'];
$addr_id    = (int)($_POST['selected_address_id'] ?? 0);

/* ── Kiểm tra địa chỉ hợp lệ và thuộc về user ── */
$addrStmt = $pdo->prepare("
    SELECT * FROM shipping_addresses WHERE id = ? AND user_id = ?
");
$addrStmt->execute([$addr_id, $user_id]);
$address = $addrStmt->fetch(PDO::FETCH_ASSOC);

if (!$address) {
    header("Location: " . BASE_URL . "/pages/cart/checkout.php?error=no_address");
    exit;
}

$pdo->beginTransaction();

try {
    /* ── Lấy giỏ hàng (lock để tránh race condition) ── */
    $stmt = $pdo->prepare("
        SELECT
            c.quantity,
            pv.id AS variant_id,
            pv.condition,
            pv.sell_price,
            pv.stock,
            p.name,
            (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS image
        FROM cart c
        JOIN product_variants pv ON c.product_variant_id = pv.id
        JOIN products p ON pv.product_id = p.id
        WHERE c.user_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        throw new Exception("Giỏ hàng trống.");
    }

    /* ── Kiểm tra tồn kho ── */
    $total = 0;
    foreach ($items as $item) {
        if ($item['quantity'] > $item['stock']) {
            throw new Exception("Sản phẩm \"{$item['name']}\" không đủ hàng (còn {$item['stock']}).");
        }
        $total += $item['sell_price'] * $item['quantity'];
    }

    /* ── Tạo đơn hàng kèm thông tin giao hàng ── */
    $pdo->prepare("
        INSERT INTO orders
            (user_id, recipient_name, recipient_phone, address_line,
             ward, district, province, delivery_notes, total_price, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ xác nhận')
    ")->execute([
        $user_id,
        $address['recipient_name'],
        $address['recipient_phone'],
        $address['address_line'],
        $address['ward'],
        $address['district'],
        $address['province'],
        $address['delivery_notes'],
        $total,
    ]);

    $order_id = $pdo->lastInsertId();

    /* ── Thêm order_items + trừ kho ── */
    foreach ($items as $item) {
        $pdo->prepare("
            INSERT INTO order_items
                (order_id, product_name, variant_condition, product_image, price, quantity)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([
            $order_id,
            $item['name'],
            $item['condition'],
            $item['image'],
            $item['sell_price'],
            $item['quantity'],
        ]);

        $pdo->prepare("
            UPDATE product_variants SET stock = stock - ? WHERE id = ?
        ")->execute([$item['quantity'], $item['variant_id']]);
    }

    /* ── Xóa giỏ hàng ── */
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

    $pdo->commit();

    /* ── Chuyển đến trang xác nhận ── */
    header("Location: order_success.php?id=$order_id");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $msg = urlencode($e->getMessage());
    header("Location: " . BASE_URL . "/pages/cart/checkout.php?error=" . $msg);
    exit;
}