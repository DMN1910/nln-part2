<?php
require_once "../../includes/admin_auth.php";
require_once "../../config/database.php";
require_once "../../models/Product.php";
require_once "../../models/ProductVariant.php";

header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['error' => 'Invalid ID']); exit; }

$product  = (new Product($pdo))->find($id);
$variants = (new ProductVariant($pdo))->byProduct($id);

echo json_encode(compact('product', 'variants'));