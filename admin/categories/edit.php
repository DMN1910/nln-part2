<?php
require_once "../../config/database.php";

$id = $_GET['id'] ?? null;
if (!$id) die("Thiếu ID");

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) die("Không tìm thấy loại");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    if ($name !== '') {
        $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);

        header("Location: index.php");
        exit;
    }
}
?>

<h2>✏️ Sửa loại sản phẩm</h2>

<form method="POST">
    <label>Tên loại:</label><br>
    <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" required>
    <br><br>
    <button type="submit">Cập nhật</button>
    <a href="index.php">⬅️ Quay lại</a>
</form>
