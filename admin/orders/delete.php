<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int) $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
$stmt->execute([$order_id]);

header("Location: index.php");
exit;
