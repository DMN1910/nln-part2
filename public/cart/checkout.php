<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../config/config.php";

/* ══════════════════════════════════════════════
   Toàn bộ logic PHP (redirect, DB) phải xử lý
   TRƯỚC KHI include header/navbar để tránh
   "headers already sent"
   ══════════════════════════════════════════════ */

/* ── Kiểm tra đăng nhập ── */
if (!isset($_SESSION['user']['id'])) {
    header("Location: " . BASE_URL . "/pages/auth/login.php");
    exit;
}
$user_id = (int)$_SESSION['user']['id'];

/* ── Lấy giỏ hàng ── */
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity,
           pv.id AS variant_id, pv.condition, pv.sell_price, pv.stock,
           p.name AS product_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS image
    FROM cart c
    JOIN product_variants pv ON c.product_variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header("Location: " . BASE_URL . "/pages/cart/cart.php");
    exit;
}

$total = array_sum(array_map(fn($i) => $i['sell_price'] * $i['quantity'], $items));

/* ── Xử lý các action POST (redirect nằm ở đây — trước output) ── */
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* --- Thêm địa chỉ mới --- */
    if ($action === 'add_address') {
        $label     = trim($_POST['label'] ?? 'Nhà');
        $name      = trim($_POST['recipient_name'] ?? '');
        $phone     = trim($_POST['recipient_phone'] ?? '');
        $addr      = trim($_POST['address_line'] ?? '');
        $ward      = trim($_POST['ward'] ?? '');
        $dist      = trim($_POST['district'] ?? '');
        $prov      = trim($_POST['province'] ?? '');
        $notes     = trim($_POST['delivery_notes'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        if (!$name)  $errors[] = "Vui lòng nhập họ tên người nhận.";
        if (!$phone) $errors[] = "Vui lòng nhập số điện thoại.";
        if (!$addr)  $errors[] = "Vui lòng nhập số nhà, tên đường.";
        if (!$ward)  $errors[] = "Vui lòng nhập phường/xã.";
        if (!$dist)  $errors[] = "Vui lòng nhập quận/huyện.";
        if (!$prov)  $errors[] = "Vui lòng nhập tỉnh/thành phố.";

        if (empty($errors)) {
            if ($isDefault) {
                $pdo->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?")
                    ->execute([$user_id]);
            }
            $pdo->prepare("
                INSERT INTO shipping_addresses
                    (user_id, label, recipient_name, recipient_phone, address_line, ward, district, province, delivery_notes, is_default)
                VALUES (?,?,?,?,?,?,?,?,?,?)
            ")->execute([$user_id, $label, $name, $phone, $addr, $ward, $dist, $prov, $notes ?: null, $isDefault]);

            header("Location: checkout.php?added=1");
            exit;
        }
    }

    /* --- Xóa địa chỉ --- */
    if ($action === 'delete_address') {
        $addr_id = (int)($_POST['addr_id'] ?? 0);
        $pdo->prepare("DELETE FROM shipping_addresses WHERE id = ? AND user_id = ?")
            ->execute([$addr_id, $user_id]);
        header("Location: checkout.php");
        exit;
    }

    /* --- Đặt làm mặc định --- */
    if ($action === 'set_default') {
        $addr_id = (int)($_POST['addr_id'] ?? 0);
        $pdo->prepare("UPDATE shipping_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        $pdo->prepare("UPDATE shipping_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")->execute([$addr_id, $user_id]);
        header("Location: checkout.php");
        exit;
    }
}

if (isset($_GET['added'])) $success = "Đã thêm địa chỉ mới thành công!";

/* ── Lấy danh sách địa chỉ (sau khi đã xử lý POST xong) ── */
$addrStmt = $pdo->prepare("
    SELECT * FROM shipping_addresses
    WHERE user_id = ?
    ORDER BY is_default DESC, id DESC
");
$addrStmt->execute([$user_id]);
$addresses = $addrStmt->fetchAll(PDO::FETCH_ASSOC);

/* ── Địa chỉ đang được chọn ── */
$selectedAddr = null;
foreach ($addresses as $a) {
    if ($a['is_default']) { $selectedAddr = $a; break; }
}
if (!$selectedAddr && !empty($addresses)) $selectedAddr = $addresses[0];

/* ── Chỉ include header/navbar SAU KHI mọi redirect đã xong ── */
include __DIR__ . "/../../includes/header.php";
include __DIR__ . "/../../includes/navbar.php";
?>

<style>
.page-wrapper {
  max-width: 1360px; margin: 0 auto;
  padding: 48px 32px 100px;
}
.page-header {
  display: flex; align-items: flex-end; gap: 16px;
  margin-bottom: 36px; padding-bottom: 24px;
  border-bottom: 1.5px solid var(--border);
}
.page-header h1 {
  font-family: var(--font-display);
  font-size: 38px; font-weight: 700;
  color: var(--ink); letter-spacing: .3px;
}
.gold-line {
  width: 40px; height: 3px;
  background: linear-gradient(90deg, var(--gold), #d4a017);
  border-radius: 2px; margin-bottom: 8px;
}

/* ── Steps ── */
.steps {
  display: flex; align-items: center; gap: 0;
  margin-bottom: 36px;
}
.step {
  display: flex; align-items: center; gap: 10px;
  font-size: 13px; font-weight: 600; color: var(--muted);
}
.step.active { color: var(--ink); }
.step.done   { color: var(--gold); }
.step-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: var(--border); color: var(--muted);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700;
}
.step.active .step-num { background: var(--ink); color: #fff; }
.step.done   .step-num { background: var(--gold); color: #fff; }
.step-line {
  flex: 1; height: 2px; background: var(--border);
  margin: 0 12px; min-width: 40px;
}

/* ── Grid layout ── */
.checkout-grid {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 32px; align-items: start;
}

/* ── Cards ── */
.card {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 14px; overflow: hidden;
  margin-bottom: 24px;
}
.card-header {
  display: flex; align-items: center; gap: 12px;
  padding: 16px 22px;
  background: var(--cream);
  border-bottom: 1.5px solid var(--border);
}
.card-icon {
  width: 32px; height: 32px; border-radius: 8px;
  background: #fdf8ee;
  border: 1.5px solid rgba(184,134,11,.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; color: var(--gold);
}
.card-title {
  font-size: 13px; font-weight: 600;
  letter-spacing: .5px; text-transform: uppercase; color: var(--muted);
}
.card-body { padding: 22px; }

/* ── Address list ── */
.addr-list { display: flex; flex-direction: column; gap: 12px; }

.addr-item {
  border: 1.5px solid var(--border);
  border-radius: 10px; padding: 16px 18px;
  cursor: pointer; transition: all .2s;
  position: relative;
  display: flex; align-items: flex-start; gap: 14px;
}
.addr-item:hover { border-color: var(--gold); background: #fdf8ee; }
.addr-item.selected {
  border-color: var(--gold);
  background: #fdf8ee;
  box-shadow: 0 0 0 3px rgba(184,134,11,.1);
}
.addr-radio {
  width: 18px; height: 18px; border-radius: 50%;
  border: 2px solid var(--border);
  flex-shrink: 0; margin-top: 3px;
  display: flex; align-items: center; justify-content: center;
  transition: all .2s;
}
.addr-item.selected .addr-radio {
  border-color: var(--gold); background: var(--gold);
}
.addr-item.selected .addr-radio::after {
  content: ''; width: 6px; height: 6px;
  border-radius: 50%; background: white;
}
.addr-info { flex: 1; min-width: 0; }
.addr-label {
  display: inline-block; font-size: 11px; font-weight: 700;
  padding: 2px 8px; border-radius: 4px;
  background: var(--cream); border: 1px solid var(--border);
  color: var(--muted); margin-bottom: 6px;
}
.addr-item.selected .addr-label {
  background: var(--gold); border-color: var(--gold); color: white;
}
.addr-name { font-weight: 600; font-size: 14.5px; color: var(--ink); }
.addr-phone { font-size: 13px; color: var(--muted); margin: 2px 0; }
.addr-line { font-size: 13px; color: var(--ink-soft); line-height: 1.5; }
.addr-notes { font-size: 12px; color: var(--muted); font-style: italic; margin-top: 4px; }
.badge-default {
  display: inline-block; font-size: 10px; font-weight: 700;
  padding: 2px 7px; border-radius: 3px;
  background: #ecfdf5; color: #16a34a;
  border: 1px solid rgba(22,163,74,.2);
  margin-left: 8px; vertical-align: middle;
}

.addr-actions {
  display: flex; gap: 6px; flex-shrink: 0; align-items: flex-start;
}
.btn-sm {
  font-size: 12px; font-weight: 500; padding: 5px 10px;
  border-radius: 6px; border: 1.5px solid var(--border);
  background: var(--white); color: var(--muted);
  cursor: pointer; transition: all .2s; text-decoration: none;
  display: inline-flex; align-items: center; gap: 4px;
}
.btn-sm:hover { border-color: var(--gold); color: var(--gold); background: #fdf8ee; }
.btn-sm.danger:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

/* ── Add address form ── */
.add-addr-toggle {
  display: flex; align-items: center; gap: 8px;
  background: none; border: 1.5px dashed var(--border);
  border-radius: 10px; width: 100%; padding: 14px 18px;
  font-size: 14px; font-weight: 500; color: var(--muted);
  cursor: pointer; transition: all .2s; margin-top: 4px;
}
.add-addr-toggle:hover { border-color: var(--gold); color: var(--gold); background: #fdf8ee; }

.add-form {
  display: none; margin-top: 16px;
  border: 1.5px solid var(--border); border-radius: 12px;
  padding: 22px; background: var(--cream);
}
.add-form.open { display: block; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-grid .full { grid-column: 1 / -1; }

.form-group { display: flex; flex-direction: column; gap: 5px; }
.form-label {
  font-size: 12px; font-weight: 600; color: var(--muted);
  letter-spacing: .4px; text-transform: uppercase;
}
.form-input, .form-textarea, .form-select {
  padding: 10px 13px;
  border: 1.5px solid var(--border);
  border-radius: 8px; font-size: 14px;
  font-family: var(--font-body);
  color: var(--ink); background: var(--white);
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
.form-input:focus, .form-textarea:focus, .form-select:focus {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(184,134,11,.12);
}
.form-textarea { resize: vertical; min-height: 72px; }

.checkbox-row {
  display: flex; align-items: center; gap: 8px;
  font-size: 13.5px; color: var(--ink); cursor: pointer;
}
.checkbox-row input { width: 16px; height: 16px; cursor: pointer; accent-color: var(--gold); }

.btn-add-save {
  padding: 11px 24px; background: var(--ink); color: white;
  border: none; border-radius: 8px; font-size: 14px;
  font-weight: 600; cursor: pointer; transition: all .25s;
}
.btn-add-save:hover { background: var(--gold); color: var(--ink); }
.btn-cancel {
  padding: 11px 20px; background: none;
  border: 1.5px solid var(--border); border-radius: 8px;
  font-size: 14px; font-weight: 500; color: var(--muted);
  cursor: pointer; transition: all .2s;
}
.btn-cancel:hover { border-color: var(--ink); color: var(--ink); }

/* ── Alert ── */
.alert {
  padding: 13px 18px; border-radius: 10px;
  font-size: 14px; margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px;
}
.alert-success { background: #f0fdf4; border: 1px solid rgba(22,163,74,.2); color: #15803d; }
.alert-error   { background: #fef2f2; border: 1px solid rgba(220,38,38,.2); color: #dc2626; }

/* ── Order summary card (right) ── */
.summary-card {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 14px; overflow: hidden;
  position: sticky; top: 100px;
}
.summary-items { max-height: 320px; overflow-y: auto; }
.summary-item {
  display: flex; gap: 12px; align-items: center;
  padding: 12px 20px; border-bottom: 1px solid var(--border);
}
.summary-item:last-child { border-bottom: none; }
.sitem-img {
  width: 48px; height: 48px; border-radius: 7px;
  border: 1px solid var(--border); object-fit: cover; flex-shrink: 0;
}
.sitem-no-img {
  width: 48px; height: 48px; border-radius: 7px;
  border: 1px solid var(--border); background: var(--cream);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; color: var(--border-dark); flex-shrink: 0;
}
.sitem-name { font-size: 13px; font-weight: 500; color: var(--ink); }
.sitem-meta { font-size: 12px; color: var(--muted); margin-top: 2px; }
.sitem-price { font-size: 13px; font-weight: 600; color: var(--gold); margin-left: auto; white-space: nowrap; }

.summary-footer { padding: 18px 20px; }
.sum-row { display: flex; justify-content: space-between; font-size: 13.5px; padding: 6px 0; }
.sum-row .lbl { color: var(--muted); }
.sum-row .val { font-weight: 600; }
.sum-total {
  display: flex; justify-content: space-between; align-items: baseline;
  padding-top: 12px; margin-top: 8px;
  border-top: 2px solid var(--border);
}
.sum-total .lbl { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
.sum-total .val { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--gold); }

/* ── Checkout button ── */
.btn-checkout-main {
  display: block; width: 100%;
  padding: 16px; background: var(--ink);
  color: white; border: none; border-radius: 10px;
  font-size: 15px; font-weight: 700;
  cursor: pointer; transition: all .25s;
  margin-top: 16px; text-align: center;
  letter-spacing: .3px;
}
.btn-checkout-main:hover { background: var(--gold); color: var(--ink); }
.btn-checkout-main:disabled {
  background: var(--border); color: var(--muted); cursor: not-allowed;
}
</style>

<div class="page-wrapper">

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1>Thanh toán</h1>
    </div>
    <a href="<?= BASE_URL ?>/pages/cart/cart.php" style="color: var(--muted); font-size: 13px; text-decoration: none;">
      ← Quay lại giỏ hàng
    </a>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step done">
      <div class="step-num">✓</div> Giỏ hàng
    </div>
    <div class="step-line"></div>
    <div class="step active">
      <div class="step-num">2</div> Địa chỉ giao hàng
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-num">3</div> Xác nhận & Đặt hàng
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <div>
        <?php foreach ($errors as $e): ?>
          <div>❌ <?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="checkout-grid">

    <!-- LEFT: Địa chỉ giao hàng -->
    <div>
      <div class="card">
        <div class="card-header">
          <div class="card-icon">📍</div>
          <div class="card-title">Địa chỉ giao hàng</div>
        </div>
        <div class="card-body">

          <?php if (empty($addresses)): ?>
            <p style="color: var(--muted); font-size: 14px; margin-bottom: 16px;">
              Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ giao hàng đầu tiên.
            </p>
          <?php else: ?>
            <form method="POST" id="setDefaultForm">
              <input type="hidden" name="action" value="set_default">
              <input type="hidden" name="addr_id" id="selectedAddrId" value="<?= $selectedAddr['id'] ?? '' ?>">
            </form>

            <div class="addr-list" id="addrList">
              <?php foreach ($addresses as $a):
                $isSelected = ($selectedAddr && $a['id'] === $selectedAddr['id']);
              ?>
                <div class="addr-item <?= $isSelected ? 'selected' : '' ?>"
                     onclick="selectAddr(<?= $a['id'] ?>, this)">
                  <div class="addr-radio"></div>
                  <div class="addr-info">
                    <span class="addr-label"><?= htmlspecialchars($a['label']) ?></span>
                    <?php if ($a['is_default']): ?>
                      <span class="badge-default">Mặc định</span>
                    <?php endif; ?>
                    <div class="addr-name"><?= htmlspecialchars($a['recipient_name']) ?>
                      <span style="font-size:13px; font-weight:400; color: var(--muted);">
                        · <?= htmlspecialchars($a['recipient_phone']) ?>
                      </span>
                    </div>
                    <div class="addr-line">
                      <?= htmlspecialchars($a['address_line']) ?>,
                      <?= htmlspecialchars($a['ward']) ?>,
                      <?= htmlspecialchars($a['district']) ?>,
                      <?= htmlspecialchars($a['province']) ?>
                    </div>
                    <?php if ($a['delivery_notes']): ?>
                      <div class="addr-notes">📝 <?= htmlspecialchars($a['delivery_notes']) ?></div>
                    <?php endif; ?>
                  </div>
                  <div class="addr-actions" onclick="event.stopPropagation()">
                    <?php if (!$a['is_default']): ?>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="set_default">
                        <input type="hidden" name="addr_id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn-sm">⭐ Mặc định</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Xóa địa chỉ này?')">
                      <input type="hidden" name="action" value="delete_address">
                      <input type="hidden" name="addr_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn-sm danger">✕</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Toggle thêm mới -->
          <button type="button" class="add-addr-toggle" onclick="toggleAddForm()">
            ＋ Thêm địa chỉ giao hàng mới
          </button>

          <!-- Form thêm địa chỉ -->
          <div class="add-form" id="addForm">
            <form method="POST">
              <input type="hidden" name="action" value="add_address">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label">Nhãn địa chỉ</label>
                  <select name="label" class="form-select">
                    <option value="Nhà">🏠 Nhà</option>
                    <option value="Công ty">🏢 Công ty</option>
                    <option value="Nhà bố mẹ">👨‍👩‍👧 Nhà bố mẹ</option>
                    <option value="Khác">📍 Khác</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Họ tên người nhận *</label>
                  <input type="text" name="recipient_name" class="form-input"
                         placeholder="Nguyễn Văn A"
                         value="<?= htmlspecialchars($_POST['recipient_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Số điện thoại *</label>
                  <input type="tel" name="recipient_phone" class="form-input"
                         placeholder="0901 234 567"
                         value="<?= htmlspecialchars($_POST['recipient_phone'] ?? '') ?>">
                </div>
                <div class="form-group full">
                  <label class="form-label">Số nhà, tên đường *</label>
                  <input type="text" name="address_line" class="form-input"
                         placeholder="123 Đường Lê Lợi"
                         value="<?= htmlspecialchars($_POST['address_line'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Phường/Xã *</label>
                  <input type="text" name="ward" class="form-input"
                         placeholder="Phường Bến Nghé"
                         value="<?= htmlspecialchars($_POST['ward'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Quận/Huyện *</label>
                  <input type="text" name="district" class="form-input"
                         placeholder="Quận 1"
                         value="<?= htmlspecialchars($_POST['district'] ?? '') ?>">
                </div>
                <div class="form-group full">
                  <label class="form-label">Tỉnh/Thành phố *</label>
                  <input type="text" name="province" class="form-input"
                         placeholder="TP. Hồ Chí Minh"
                         value="<?= htmlspecialchars($_POST['province'] ?? '') ?>">
                </div>
                <div class="form-group full">
                  <label class="form-label">Ghi chú giao hàng</label>
                  <textarea name="delivery_notes" class="form-textarea"
                            placeholder="Gọi trước khi giao, để trước cửa..."><?= htmlspecialchars($_POST['delivery_notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                  <label class="checkbox-row">
                    <input type="checkbox" name="is_default" <?= empty($addresses) ? 'checked' : '' ?>>
                    Đặt làm địa chỉ mặc định
                  </label>
                </div>
              </div>
              <div style="display: flex; gap: 10px; margin-top: 18px;">
                <button type="submit" class="btn-add-save">💾 Lưu địa chỉ</button>
                <button type="button" class="btn-cancel" onclick="toggleAddForm()">Hủy</button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </div>

    <!-- RIGHT: Tóm tắt đơn hàng -->
    <div>
      <div class="summary-card">
        <div class="card-header">
          <div class="card-icon">🧾</div>
          <div class="card-title">Đơn hàng · <?= count($items) ?> sản phẩm</div>
        </div>

        <div class="summary-items">
          <?php foreach ($items as $item): ?>
            <div class="summary-item">
              <?php if ($item['image']): ?>
                <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($item['image']) ?>"
                     class="sitem-img" alt="">
              <?php else: ?>
                <div class="sitem-no-img">📷</div>
              <?php endif; ?>
              <div style="flex:1; min-width:0;">
                <div class="sitem-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="sitem-meta"><?= htmlspecialchars($item['condition']) ?> · ×<?= $item['quantity'] ?></div>
              </div>
              <div class="sitem-price">
                <?= number_format($item['sell_price'] * $item['quantity'], 0, ',', '.') ?>₫
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="summary-footer">
          <div class="sum-row">
            <span class="lbl">Tạm tính</span>
            <span class="val"><?= number_format($total, 0, ',', '.') ?>₫</span>
          </div>
          <div class="sum-row">
            <span class="lbl">Phí vận chuyển</span>
            <span class="val" style="color:#16a34a;">Miễn phí</span>
          </div>
          <div class="sum-total">
            <span class="lbl">Tổng cộng</span>
            <span class="val"><?= number_format($total, 0, ',', '.') ?>₫</span>
          </div>

          <!-- Nút đặt hàng — submit tới place_order.php -->
          <form action="place_order.php" method="POST" id="placeOrderForm">
            <input type="hidden" name="selected_address_id" id="formAddrId"
                   value="<?= $selectedAddr['id'] ?? '' ?>">
            <button type="submit" class="btn-checkout-main"
                    <?= empty($addresses) ? 'disabled title="Vui lòng thêm địa chỉ giao hàng"' : '' ?>>
              ✅ Đặt hàng ngay
            </button>
          </form>

          <?php if (empty($addresses)): ?>
            <p style="text-align:center; font-size:12px; color:#dc2626; margin-top:8px;">
              ⚠ Vui lòng thêm địa chỉ giao hàng trước
            </p>
          <?php else: ?>
            <p style="text-align:center; font-size:12px; color:var(--muted); margin-top:10px;">
              🔒 Thông tin của bạn được bảo mật
            </p>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /checkout-grid -->
</div>

<script>
/* Chọn địa chỉ */
function selectAddr(id, el) {
  document.querySelectorAll('.addr-item').forEach(i => i.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('formAddrId').value = id;
  const btn = document.querySelector('.btn-checkout-main');
  if (btn) btn.disabled = false;
}

/* Toggle form thêm địa chỉ */
function toggleAddForm() {
  const form = document.getElementById('addForm');
  form.classList.toggle('open');
}

/* Nếu có lỗi validation → mở lại form */
<?php if (!empty($errors)): ?>
  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('addForm').classList.add('open');
  });
<?php endif; ?>
</script>

<?php include __DIR__ . "/../../includes/footer.php"; ?>