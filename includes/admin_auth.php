<?php
require_once __DIR__ . '/../config/config.php';

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
