<?php
require_once "../../config/database.php";

$id = $_GET['id'] ?? null;
if (!$id) die("Thiếu ID");

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php");
exit;
