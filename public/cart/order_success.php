<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";
include __DIR__ . "/../../includes/header.php";
include __DIR__ . "/../../includes/navbar.php";

if (!isset($_SESSION['user']['id'])) {
    header("Location: " . BASE_URL . "/pages/auth/login.php");
    exit;
}

$user_id  = (int)$_SESSION['user']['id'];
$order_id = (int)($_GET['id'] ?? 0);

/* Lấy đơn hàng — chỉ cho xem đơn của chính mình */
$stmt = $pdo->prepare("
    SELECT * FROM orders WHERE id = ? AND user_id = ?
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);
$orderItems = $items->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.page-wrapper { max-width: 760px; margin: 0 auto; padding: 60px 24px 100px; }

.success-hero {
  text-align: center; margin-bottom: 40px;
}
.success-icon {
  width: 80px; height: 80px; border-radius: 50%;
  background: #f0fdf4; border: 3px solid rgba(22,163,74,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 38px; margin: 0 auto 20px;
  animation: popIn .4s cubic-bezier(.175,.885,.32,1.275) both;
}
@keyframes popIn {
  from { opacity:0; transform: scale(.5); }
  to   { opacity:1; transform: scale(1); }
}
.success-title {
  font-family: var(--font-display);
  font-size: 36px; font-weight: 700; color: var(--ink);
}
.success-sub { color: var(--muted); font-size: 15px; margin-top: 8px; }
.order-num {
  display: inline-block; font-size: 22px; font-weight: 700;
  color: var(--gold); font-family: var(--font-display);
  margin-top: 6px;
}

.card {
  background: var(--white); border: 1.5px solid var(--border);
  border-radius: 14px; overflow: hidden; margin-bottom: 20px;
}
.card-header {
  display: flex; align-items: center; gap: 10px;
  padding: 15px 22px; background: var(--cream);
  border-bottom: 1.5px solid var(--border);
  font-size: 12px; font-weight: 600;
  letter-spacing: .5px; text-transform: uppercase; color: var(--muted);
}
.card-body { padding: 22px; }

/* Shipping info */
.ship-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ship-item {}
.ship-label { font-size: 11px; font-weight: 600; color: var(--muted); letter-spacing: .4px; margin-bottom: 3px; }
.ship-val { font-size: 14px; font-weight: 500; color: var(--ink); }

/* Items */
.order-item {
  display: flex; gap: 14px; align-items: center;
  padding: 13px 0; border-bottom: 1px solid var(--border);
}
.order-item:last-child { border-bottom: none; padding-bottom: 0; }
.order-item:first-child { padding-top: 0; }
.oitem-img {
  width: 54px; height: 54px; border-radius: 8px;
  object-fit: cover; border: 1px solid var(--border); flex-shrink: 0;
}
.oitem-no-img {
  width: 54px; height: 54px; border-radius: 8px;
  background: var(--cream); border: 1px solid var(--border);
  display: flex; align-items: center; justify-content: center;
  font-size: 20px; color: var(--border-dark); flex-shrink: 0;
}
.oitem-name { font-size: 14px; font-weight: 500; color: var(--ink); }
.oitem-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.oitem-price { margin-left: auto; font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--gold); white-space: nowrap; }

.total-row {
  display: flex; justify-content: space-between; align-items: baseline;
  padding-top: 16px; margin-top: 8px; border-top: 2px solid var(--border);
}
.total-row .lbl { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
.total-row .val { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--gold); }

.btn-home {
  display: block; text-align: center;
  padding: 14px; background: var(--ink); color: white;
  border-radius: 10px; text-decoration: none;
  font-size: 15px; font-weight: 600; transition: all .25s; margin-top: 6px;
}
.btn-home:hover { background: var(--gold); color: var(--ink); }
.btn-orders {
  display: block; text-align: center;
  padding: 13px; background: none; color: var(--muted);
  border: 1.5px solid var(--border); border-radius: 10px;
  text-decoration: none; font-size: 14px; font-weight: 500;
  transition: all .2s; margin-top: 10px;
}
.btn-orders:hover { border-color: var(--ink); color: var(--ink); }
</style>

<div class="page-wrapper">

  <!-- Hero -->
  <div class="success-hero">
    <div class="success-icon">✅</div>
    <h1 class="success-title">Đặt hàng thành công!</h1>
    <p class="success-sub">Cảm ơn bạn đã tin tưởng mua sắm. Đơn hàng của bạn đang được xử lý.</p>
    <div class="order-num">#<?= $order_id ?></div>
  </div>

  <!-- Thông tin giao hàng -->
  <div class="card">
    <div class="card-header">📍 &nbsp;Thông tin giao hàng</div>
    <div class="card-body">
      <div class="ship-grid">
        <div class="ship-item">
          <div class="ship-label">Người nhận</div>
          <div class="ship-val"><?= htmlspecialchars($order['recipient_name']) ?></div>
        </div>
        <div class="ship-item">
          <div class="ship-label">Số điện thoại</div>
          <div class="ship-val"><?= htmlspecialchars($order['recipient_phone']) ?></div>
        </div>
        <div class="ship-item" style="grid-column: 1/-1;">
          <div class="ship-label">Địa chỉ</div>
          <div class="ship-val">
            <?= htmlspecialchars($order['address_line']) ?>,
            <?= htmlspecialchars($order['ward']) ?>,
            <?= htmlspecialchars($order['district']) ?>,
            <?= htmlspecialchars($order['province']) ?>
          </div>
        </div>
        <?php if ($order['delivery_notes']): ?>
          <div class="ship-item" style="grid-column: 1/-1;">
            <div class="ship-label">Ghi chú</div>
            <div class="ship-val" style="font-style:italic; color: var(--muted);">
              <?= htmlspecialchars($order['delivery_notes']) ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sản phẩm -->
  <div class="card">
    <div class="card-header">🛍 &nbsp;Sản phẩm đặt mua</div>
    <div class="card-body">
      <?php foreach ($orderItems as $item): ?>
        <div class="order-item">
          <?php if ($item['product_image']): ?>
            <img class="oitem-img"
                 src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($item['product_image']) ?>"
                 alt="">
          <?php else: ?>
            <div class="oitem-no-img">📷</div>
          <?php endif; ?>
          <div>
            <div class="oitem-name"><?= htmlspecialchars($item['product_name']) ?></div>
            <div class="oitem-meta">
              <?= htmlspecialchars($item['variant_condition']) ?> · ×<?= $item['quantity'] ?>
            </div>
          </div>
          <div class="oitem-price">
            <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
          </div>
        </div>
      <?php endforeach; ?>

      <div class="total-row">
        <span class="lbl">Tổng cộng</span>
        <span class="val"><?= number_format($order['total_price'], 0, ',', '.') ?>₫</span>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <a href="<?= BASE_URL ?>/index.php" class="btn-home">← Tiếp tục mua sắm</a>
  <a href="<?= BASE_URL ?>/profile.php?tab=orders" class="btn-orders">📋 Xem lịch sử đơn hàng</a>

</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>