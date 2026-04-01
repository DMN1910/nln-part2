<?php
require_once "../../includes/admin_auth.php";
require_once "../../config/database.php";

$id = $_GET['id'];

/* xóa ảnh vật lý */
$imgs = $pdo->prepare(
    "SELECT image_path FROM product_images WHERE product_id=?"
);
$imgs->execute([$id]);

foreach ($imgs as $img) {
    $file = "../../uploads/products/" . $img['image_path'];
    if (file_exists($file)) unlink($file);
}

/* xóa product (variant + image CASCADE) */
$stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
$stmt->execute([$id]);

header("Location: index.php");
