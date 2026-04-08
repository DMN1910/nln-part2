<?php
require_once "../../config/config.php";
require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: /camerashop/auth/login.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /camerashop/public/index.php");
    exit;
}

$userId    = (int)$_SESSION['user']['id'];
$productId = (int)($_POST['product_id'] ?? 0);
$rating    = (int)($_POST['rating']     ?? 0);
$comment   = trim($_POST['comment']     ?? '');

$redirectBack = "/camerashop/public/product.php?id={$productId}#reviews";

// ── 2. Validate input 
if ($productId <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = 'Dữ liệu không hợp lệ.';
    header("Location: $redirectBack");
    exit;
}

// ── 3. Sản phẩm tồn tại 
$chkProduct = $pdo->prepare("SELECT id, name FROM products WHERE id = ? LIMIT 1");
$chkProduct->execute([$productId]);
$product = $chkProduct->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    header("Location: /camerashop/public/index.php");
    exit;
}

// ── 4. Đã đánh giá rồi 
$chkDup = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
$chkDup->execute([$userId, $productId]);
if ($chkDup->fetch()) {
    $_SESSION['review_error'] = 'Bạn đã đánh giá sản phẩm này rồi.';
    header("Location: $redirectBack");
    exit;
}

// ── 5. Đã mua sản phẩm chưa
$chkBought = $pdo->prepare("
    SELECT oi.id
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.user_id = ?
      AND oi.product_name = ?
      AND o.status != 'Đã huỷ'
    LIMIT 1
");
$chkBought->execute([$userId, $product['name']]);
$bought = $chkBought->fetch();

if (!$bought) {
    $_SESSION['review_error'] = 'Bạn chưa mua sản phẩm này.';
    header("Location: $redirectBack");
    exit;
}

// ── 6. Lưu review + ảnh 
try {
    $pdo->beginTransaction();

    $insReview = $pdo->prepare("
        INSERT INTO reviews (user_id, product_id, rating, comment)
        VALUES (?, ?, ?, ?)
    ");
    $insReview->execute([$userId, $productId, $rating, $comment]);
    $reviewId = $pdo->lastInsertId();

    // Upload ảnh
    if (!empty($_FILES['images']['name'][0])) {
        $uploadDir    = __DIR__ . '/../../uploads/reviews/';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize      = 5 * 1024 * 1024; // 5 MB
        $maxImages    = 5;
        $count        = 0;

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $insImg = $pdo->prepare("INSERT INTO review_images (review_id, image_path) VALUES (?, ?)");

        foreach ($_FILES['images']['tmp_name'] as $idx => $tmp) {
            if ($count >= $maxImages) break;
            if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
            if ($_FILES['images']['size'][$idx]  >  $maxSize)       continue;
            if (!in_array(mime_content_type($tmp), $allowedMimes))  continue;

            $ext      = strtolower(pathinfo($_FILES['images']['name'][$idx], PATHINFO_EXTENSION));
            $filename = 'rv_' . $reviewId . '_' . uniqid() . '.' . $ext;

            if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                $insImg->execute([$reviewId, $filename]);
                $count++;
            }
        }
    }

    $pdo->commit();
    $_SESSION['review_success'] = 'Cảm ơn bạn đã đánh giá sản phẩm!';

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['review_error'] = 'Có lỗi xảy ra, vui lòng thử lại.';
}

header("Location: $redirectBack");
exit;