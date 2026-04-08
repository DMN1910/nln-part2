<?php
session_start();
require_once "../../includes/admin_auth.php";
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header("Location: index.php");
    exit;
}

$id          = (int)$_POST['id'];
$name        = trim($_POST['name'] ?? '');
$brand_id    = (int)($_POST['brand_id'] ?? 0);
$category_id = (int)($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');

// Validation
if ($name === '' || $brand_id === 0 || $category_id === 0) {
    $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin sản phẩm.";
    header("Location: index.php");
    exit;
}

// Cập nhật thông tin sản phẩm chính
$pdo->prepare("UPDATE products SET name=?, brand_id=?, category_id=?, description=? WHERE id=?")
    ->execute([$name, $brand_id, $category_id, $description, $id]);

//  XỬ LÝ BIẾN THỂ 

// Xóa những biến thể được đánh dấu xóa (nếu có)
if (!empty($_POST['delete_variant'])) {
    foreach ($_POST['delete_variant'] as $vid) {
        $pdo->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?")
            ->execute([(int)$vid, $id]);
    }
}

// Cập nhật biến thể hiện có
if (!empty($_POST['variant_id'])) {
    foreach ($_POST['variant_id'] as $i => $vid) {
        $vid = (int)$vid;
        if ($vid <= 0) continue;

        $pdo->prepare("UPDATE product_variants SET `condition`=?, cost_price=?, sell_price=?, stock=? WHERE id=?")
            ->execute([
                $_POST['condition'][$i] ?? '',
                $_POST['cost_price'][$i] ?? 0,
                $_POST['sell_price'][$i] ?? 0,
                $_POST['stock'][$i] ?? 0,
                $vid
            ]);
    }
}

// Thêm biến thể mới
if (!empty($_POST['new_condition'])) {
    foreach ($_POST['new_condition'] as $i => $cond) {
        if (empty($cond)) continue;

        $pdo->prepare("INSERT INTO product_variants (product_id, `condition`, cost_price, sell_price, stock) 
                       VALUES (?, ?, ?, ?, ?)")
            ->execute([
                $id,
                $cond,
                $_POST['new_cost_price'][$i] ?? 0,
                $_POST['new_sell_price'][$i] ?? 0,
                $_POST['new_stock'][$i] ?? 0
            ]);
    }
}

//  XỬ LÝ HÌNH ẢNH 

// Xóa hình ảnh được chọn
if (!empty($_POST['delete_image'])) {
    foreach ($_POST['delete_image'] as $img_id) {
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ? AND product_id = ?");
        $stmt->execute([(int)$img_id, $id]);
        $img = $stmt->fetch();

        if ($img) {
            $file_path = "../../uploads/products/" . $img['image_path'];
            if (file_exists($file_path)) unlink($file_path);

            $pdo->prepare("DELETE FROM product_images WHERE id = ?")->execute([(int)$img_id]);
        }
    }
}

// Upload hình ảnh mới
if (!empty($_FILES['images']['name'][0])) {
    $upload_dir = "../../uploads/products/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
        $new_name = time() . '_' . uniqid() . '.' . $ext;
        $target = $upload_dir . $new_name;

        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && move_uploaded_file($tmp_name, $target)) {
            $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")
                ->execute([$id, $new_name]);
        }
    }
}

$_SESSION['success'] = "Cập nhật sản phẩm thành công!";
header("Location: index.php");
exit;