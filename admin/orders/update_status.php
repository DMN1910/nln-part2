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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    header("Location: index.php");
    exit;
}

/* LẤY TRẠNG THÁI HIỆN TẠI */
$stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Đơn hàng không tồn tại");
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Cập nhật trạng thái</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5" style="max-width: 500px">
    <h3>🔄 Cập nhật trạng thái đơn #<?= $order_id ?></h3>

    <form method="post">
        <label class="form-label">Trạng thái</label>
        <select name="status" class="form-select">
            <?php
            $statuses = ['Chờ xác nhận', 'Đang xử lý', 'Đang giao', 'Hoàn tất', 'Đã hủy'];
            foreach ($statuses as $st):
            ?>
                <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>>
                    <?= $st ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn btn-primary mt-3">Cập nhật</button>
        <a href="index.php" class="btn btn-secondary mt-3">Hủy</a>
    </form>
</div>

</body>
</html>
