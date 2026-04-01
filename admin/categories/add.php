<?php
require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);

        header("Location: index.php");
        exit;
    }
}
?>

<h2>➕ Thêm loại sản phẩm</h2>

<form method="POST">
    <label>Tên loại:</label><br>
    <input type="text" name="name" required>
    <br><br>
    <button type="submit">Lưu</button>
    <a href="index.php">⬅️ Quay lại</a>
</form>
