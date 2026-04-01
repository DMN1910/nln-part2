<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
