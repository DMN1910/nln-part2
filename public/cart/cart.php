<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";
include __DIR__ . "/../../includes/header.php";
include __DIR__ . "/../../includes/navbar.php";

/* Check đăng nhập */
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    die("Bạn cần đăng nhập để xem giỏ hàng.");
}

$user_id = (int)$_SESSION['user']['id'];

// Lấy dữ liệu giỏ hàng
$sql = "
SELECT 
    c.id AS cart_id,
    c.quantity,
    pv.id AS variant_id,
    pv.condition,
    pv.sell_price,
    pv.stock,
    p.name AS product_name,
    p.id AS product_id,
    (
        SELECT image_path 
        FROM product_images 
        WHERE product_id = p.id 
        LIMIT 1
    ) AS image
FROM cart c
JOIN product_variants pv ON c.product_variant_id = pv.id
JOIN products p ON pv.product_id = p.id
WHERE c.user_id = ?
ORDER BY c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['sell_price'] * $item['quantity'];
}
?>

<style>
/* ── Giữ nguyên style chung từ các trang trước ── */
.page-wrapper {
  max-width: 1360px;
  margin: 0 auto;
  padding: 48px 32px 100px;
}

.page-header {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  margin-bottom: 36px;
  padding-bottom: 24px;
  border-bottom: 1.5px solid var(--border);
}

.page-header h1 {
  font-family: var(--font-display);
  font-size: 38px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: .3px;
}

/* Cart Table */
.cart-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 40px;
}

.cart-table th {
  background: var(--cream);
  color: var(--ink);
  font-weight: 600;
  padding: 16px 18px;
  text-align: left;
  font-size: 13.5px;
  letter-spacing: .4px;
}

.cart-table td {
  padding: 18px;
  border-top: 1.5px solid var(--border);
  vertical-align: middle;
}

.cart-table tr:last-child td {
  border-bottom: none;
}

.product-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--border);
}

.cart-table input[type="number"] {
  width: 80px;
  padding: 8px 10px;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  text-align: center;
  font-size: 15px;
}

.remove-btn {
  color: #dc2626;
  font-size: 18px;
  text-decoration: none;
  transition: color .2s;
}

.remove-btn:hover {
  color: #b91c1c;
}

/* Summary Box */
.cart-summary {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  padding: 28px;
  position: sticky;
  top: 100px;
}

.cart-summary h3 {
  font-family: var(--font-display);
  font-size: 24px;
  margin-bottom: 20px;
  color: var(--ink);
}

.total-amount {
  font-size: 20px;
  font-weight: 700;
  color: var(--gold);
  font-family: var(--font-body);
  margin: 20px 0;
  letter-spacing: .5px;
}

.btn-continue {
  display: inline-block;
  padding: 12px 24px;
  background: var(--cream);
  color: var(--muted);
  border: 1.5px solid var(--border);
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  margin-right: 12px;
  transition: all .25s;
}

.btn-continue:hover {
  background: var(--white);
  border-color: var(--ink);
  color: var(--ink);
}

.btn-checkout {
  width: 100%;
  padding: 16px;
  background: var(--ink);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all .25s;
  margin-top: 16px;
}

.btn-checkout:hover {
  background: var(--gold);
  color: var(--ink);
}

/* Empty state */
.empty-cart {
  text-align: center;
  padding: 120px 40px;
}

.empty-cart .empty-icon {
  font-size: 68px;
  color: var(--border-dark);
  margin-bottom: 24px;
}
</style>

<div class="page-wrapper">
  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1>🛒 Giỏ hàng của bạn</h1>
    </div>
    <span class="header-count"><?= count($items) ?> sản phẩm</span>
  </div>

  <?php if (empty($items)): ?>
    <div class="empty-cart">
      <div class="empty-icon">🛒</div>
      <h3>Giỏ hàng của bạn đang trống</h3>
      <p style="color: var(--muted); font-size: 15px; max-width: 420px; margin: 0 auto 32px;">
        Bạn chưa có sản phẩm nào trong giỏ hàng.<br>
        Hãy khám phá các sản phẩm tuyệt vời của chúng tôi nhé!
      </p>
      <a href="<?= BASE_URL ?>/index.php" class="btn-back" style="background: var(--ink); color: white; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: 600;">
        ← Xem tất cả sản phẩm
      </a>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px;">
      
      <!-- Danh sách sản phẩm trong giỏ -->
      <div>
        <form action="update_cart.php" method="POST" id="cartForm">
          <table class="cart-table">
            <thead>
              <tr>
                <th style="width: 90px;">Ảnh</th>
                <th>Sản phẩm</th>
                <th>Loại</th>
                <th>Giá</th>
                <th style="width: 120px;">Số lượng</th>
                <th>Thành tiền</th>
                <th style="width: 60px;">Xóa</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item):
                  $subtotal = $item['sell_price'] * $item['quantity'];
              ?>
                <tr>
                  <td>
                    <?php if ($item['image']): ?>
                      <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($item['image']) ?>" 
                           class="product-img" alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <?php else: ?>
                      <div class="no-img" style="width:80px;height:80px;background:#f5f0e8;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:28px;color:#ccc;">
                        📷
                      </div>
                    <?php endif; ?>
                  </td>
                  <td style="font-weight: 500;"><?= htmlspecialchars($item['product_name']) ?></td>
                  <td><?= htmlspecialchars($item['condition']) ?></td>
                  <td><?= number_format($item['sell_price'], 0, ',', '.') ?>₫</td>
                  <td>
                    <input type="number"
                           name="qty[<?= $item['cart_id'] ?>]"
                           value="<?= $item['quantity'] ?>"
                           min="1"
                           max="<?= $item['stock'] ?>"
                           style="width: 100%;">
                  </td>
                  <td style="font-weight: 600; color: var(--gold);">
                    <?= number_format($subtotal, 0, ',', '.') ?>₫
                  </td>
                  <td>
                    <a href="remove_cart.php?id=<?= $item['cart_id'] ?>" 
                       class="remove-btn" 
                       onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">✕</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div style="display: flex; gap: 12px; margin-top: 20px;">
            <a href="<?= BASE_URL ?>/index.php" class="btn-continue">← Tiếp tục mua sắm</a>
            <button type="submit" class="btn-continue" style="background: var(--gold); color: var(--ink); border-color: var(--gold);">
              🔄 Cập nhật giỏ hàng
            </button>
          </div>
        </form>
      </div>

      <!-- Thanh toán summary -->
      <div>
        <div class="cart-summary">
          <h3>Thông tin thanh toán</h3>
          
          <div style="display: flex; justify-content: space-between; font-size: 15px; margin: 16px 0;">
            <span>Tạm tính:</span>
            <span><?= number_format($total, 0, ',', '.') ?>₫</span>
          </div>
          
          <hr style="border: none; border-top: 1.5px solid var(--border); margin: 20px 0;">
          
          <div style="display: flex; justify-content: space-between; align-items: baseline;">
            <span style="font-size: 17px; font-weight: 600;">Tổng cộng</span>
            <span class="total-amount"><?= number_format($total, 0, ',', '.') ?>₫</span>
          </div>

          <form action="checkout.php" method="POST">
            <button type="submit" class="btn-checkout">
              ✅ Tiến hành thanh toán
            </button>
          </form>

          <p style="text-align: center; margin-top: 20px; font-size: 13px; color: var(--muted);">
            Phí vận chuyển sẽ được tính ở bước thanh toán
          </p>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . "/../../includes/footer.php"; ?>