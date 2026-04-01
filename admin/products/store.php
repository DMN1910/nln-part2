<?php
require_once "../../includes/admin_auth.php";
require_once "../../controllers/ProductController.php";
require_once "../../config/database.php";

$controller = new ProductController($pdo);

/* 1. Lưu sản phẩm + biến thể */
$variants = [];
foreach ($_POST['condition'] as $i => $v) {
    $variants[] = [
        'condition'   => $_POST['condition'][$i],
        'cost_price'  => $_POST['cost_price'][$i],
        'sell_price'  => $_POST['sell_price'][$i],
        'stock'       => $_POST['stock'][$i],
    ];
}

$product_id = $controller->store($_POST, $variants);

/* 2. Upload ảnh vào uploads/products */
$uploadDir = __DIR__ . "/../../uploads/products/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {

    if ($tmp == '') continue;

    $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
    $fileName = uniqid("product_") . "." . $ext;

    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($tmp, $targetPath)) {

        $stmt = $pdo->prepare(
            "INSERT INTO product_images (product_id, image_path)
             VALUES (?, ?)"
        );

        // ✅ CHỈ LƯU TÊN FILE
        $stmt->execute([
            $product_id,
            $fileName
        ]);
    }
}


header("Location: index.php");
exit;
