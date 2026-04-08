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

$orderStmt = $pdo->prepare("
    SELECT o.*, u.name AS customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$orderStmt->execute([$order_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die("Đơn hàng không tồn tại");

$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemStmt->execute([$order_id]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

function statusLabel(string $s): array {
    return match($s) {
        'Chờ xác nhận' => ['Chờ xác nhận', 'status-pending'],
        'Đang xử lý'   => ['Đang xử lý',   'status-processing'],
        'Đang giao'    => ['Đang giao',     'status-processing'],
        'Hoàn thành'   => ['Hoàn thành',    'status-done'],
        'Đã huỷ'       => ['Đã huỷ',        'status-cancel'],
        default        => [$s,              'status-default'],
    };
}
[$statusText, $statusCls] = statusLabel($order['status']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chi tiết đơn hàng #<?= $order['id'] ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    :root {
      --gold:        #b8860b;
      --gold-light:  #d4a017;
      --gold-pale:   #fdf8ee;
      --gold-border: rgba(184,134,11,.22);
      --cream:       #faf7f2;
      --cream-dark:  #f2ede4;
      --ink:         #1a1714;
      --ink-soft:    #3d3730;
      --muted:       #8a8078;
      --white:       #ffffff;
      --border:      #e8e2d9;
      --border-dark: #cec6bb;
      --shadow-sm:   0 1px 4px rgba(0,0,0,.06);
      --shadow-md:   0 6px 24px rgba(0,0,0,.10);
      --danger:      #dc2626;
      --danger-pale: #fef2f2;
      --warn:        #d97706;
      --warn-pale:   #fffbeb;
      --blue:        #2563eb;
      --blue-pale:   #eff6ff;
      --green:       #16a34a;
      --green-pale:  #f0fdf4;
      --font-display:'Cormorant Garamond', serif;
      --font-body:   'DM Sans', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font-body);
      background: var(--cream);
      color: var(--ink);
      min-height: 100vh;
      padding: 48px 40px 100px;
    }

    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted);
      margin-bottom: 14px;
      animation: fadeUp .3s ease both;
    }
    .breadcrumb a {
      color: var(--muted); text-decoration: none;
      display: inline-flex; align-items: center; gap: 5px;
      transition: color .2s;
    }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { font-size: 10px; color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white); border: 1.5px solid var(--border);
      color: var(--muted); font-family: var(--font-body);
      font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      text-decoration: none; margin-bottom: 28px;
      box-shadow: var(--shadow-sm);
      transition: border-color .2s, color .2s, background .2s;
      animation: fadeUp .3s .02s ease both;
    }
    .btn-back:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 20px;
      margin-bottom: 32px; padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
      animation: fadeUp .35s .04s ease both;
    }
    .gold-line {
      width: 40px; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px; margin-bottom: 8px;
    }
    .page-title {
      font-family: var(--font-display);
      font-size: 34px; font-weight: 700;
      color: var(--ink); letter-spacing: .3px; line-height: 1;
    }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    .header-actions { display: flex; gap: 10px; align-items: center; padding-top: 6px; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      font-family: var(--font-body); font-size: 13px; font-weight: 500;
      padding: 8px 18px; border-radius: 8px;
      text-decoration: none; border: 1.5px solid transparent;
      cursor: pointer; transition: all .2s;
    }
    .btn-status { background: var(--warn-pale);  border-color: rgba(217,119,6,.2);  color: var(--warn); }
    .btn-status:hover { background: var(--warn);  border-color: var(--warn);  color: var(--white); }
    .btn-del    { background: var(--danger-pale); border-color: rgba(220,38,38,.2); color: var(--danger); }
    .btn-del:hover    { background: var(--danger); border-color: var(--danger); color: var(--white); }

    .layout {
      display: grid;
      grid-template-columns: 1fr 320px;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } }

    .card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    .card-header {
      display: flex; align-items: center; gap: 12px;
      padding: 18px 24px;
      border-bottom: 1.5px solid var(--border);
      background: var(--cream);
    }
    .card-icon {
      width: 34px; height: 34px; border-radius: 9px;
      background: var(--gold-pale); border: 1.5px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; color: var(--gold); flex-shrink: 0;
    }
    .card-title {
      font-size: 13px; font-weight: 600;
      letter-spacing: .5px; text-transform: uppercase;
      color: var(--muted);
    }
    .card-body { padding: 24px; }

    .info-row {
      display: flex; align-items: flex-start;
      gap: 12px; padding: 12px 0;
      border-bottom: 1px solid var(--border);
    }
    .info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .info-row:first-child { padding-top: 0; }
    .info-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--cream-dark);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; color: var(--muted); flex-shrink: 0; margin-top: 1px;
    }
    .info-label { font-size: 11px; color: var(--muted); font-weight: 600; letter-spacing: .5px; margin-bottom: 2px; }
    .info-value { font-size: 14px; color: var(--ink); font-weight: 500; }

    /* ── Shipping address highlight box ── */
    .ship-box {
      background: linear-gradient(135deg, #fdf8ee 0%, #faf7f2 100%);
      border: 1.5px solid var(--gold-border);
      border-radius: 10px;
      padding: 16px 18px;
    }
    .ship-box-header {
      display: flex; align-items: center; gap: 7px;
      font-size: 11px; font-weight: 700;
      letter-spacing: .6px; text-transform: uppercase;
      color: var(--gold); margin-bottom: 12px;
    }
    .ship-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    }
    .ship-item {}
    .ship-label { font-size: 10.5px; font-weight: 600; color: var(--muted); letter-spacing: .4px; margin-bottom: 3px; }
    .ship-val { font-size: 13.5px; font-weight: 500; color: var(--ink); }
    .ship-full { grid-column: 1 / -1; }
    .ship-notes {
      grid-column: 1 / -1;
      font-size: 12.5px; color: var(--muted); font-style: italic;
      background: rgba(184,134,11,.06); border-radius: 6px;
      padding: 8px 12px; border-left: 3px solid var(--gold-border);
    }

    .status-badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 12px; font-weight: 600;
      padding: 4px 12px; border-radius: 999px;
      border: 1px solid transparent;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .status-pending    { background: var(--warn-pale);   color: var(--warn);   border-color: rgba(217,119,6,.2); }
    .status-processing { background: var(--blue-pale);   color: var(--blue);   border-color: rgba(37,99,235,.2); }
    .status-done       { background: var(--green-pale);  color: var(--green);  border-color: rgba(22,163,74,.2); }
    .status-cancel     { background: var(--danger-pale); color: var(--danger); border-color: rgba(220,38,38,.2); }
    .status-default    { background: var(--cream-dark);  color: var(--muted);  border-color: var(--border); }

    .items-card { animation: fadeUp .35s .08s ease both; }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: var(--cream); border-bottom: 1.5px solid var(--border); }
    thead th {
      text-align: left; font-size: 10.5px; font-weight: 600;
      letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--muted); padding: 13px 20px;
    }
    thead th:last-child { text-align: right; }
    tbody tr { border-bottom: 1px solid var(--border); transition: background .18s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--gold-pale); }
    tbody td { padding: 16px 20px; font-size: 13.5px; vertical-align: middle; }

    .product-cell { display: flex; align-items: center; gap: 14px; }
    .product-thumb {
      width: 60px; height: 60px; border-radius: 8px;
      overflow: hidden; flex-shrink: 0;
      border: 1.5px solid var(--border);
      background: var(--cream-dark);
      display: flex; align-items: center; justify-content: center;
    }
    .product-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .product-thumb .no-img { font-size: 20px; color: var(--border-dark); }
    .product-name { font-weight: 600; color: var(--ink); font-size: 13.5px; margin-bottom: 3px; }
    .product-cond {
      display: inline-block; font-size: 11px; font-weight: 600;
      padding: 2px 8px; border-radius: 4px;
      background: var(--cream-dark); color: var(--muted);
      border: 1px solid var(--border);
    }
    .price-cell  { font-size: 13px; color: var(--muted); }
    .qty-cell    { font-size: 14px; font-weight: 600; color: var(--ink); text-align: center; }
    .total-cell  {
      font-family: var(--font-display);
      font-size: 18px; font-weight: 700; color: var(--gold);
      text-align: right;
    }

    .summary-card { animation: fadeUp .35s .1s ease both; }
    .summary-line {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0; border-bottom: 1px solid var(--border);
      font-size: 13.5px;
    }
    .summary-line:last-child { border-bottom: none; }
    .summary-line .lbl { color: var(--muted); }
    .summary-line .val { font-weight: 600; color: var(--ink); }
    .summary-total {
      display: flex; justify-content: space-between; align-items: baseline;
      margin-top: 16px; padding-top: 16px;
      border-top: 2px solid var(--border);
    }
    .summary-total .lbl { font-size: 13px; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; color: var(--muted); }
    .summary-total .val { font-family: var(--font-display); font-size: 28px; font-weight: 700; color: var(--gold); }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .info-card    { animation: fadeUp .35s .06s ease both; }
  </style>
</head>
<body>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <a href="index.php">Đơn hàng</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Đơn #<?= $order['id'] ?></span>
  </nav>

  <a class="btn-back" href="index.php">
    <i class="fas fa-arrow-left"></i> Quay lại danh sách
  </a>

  <!-- Page header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Đơn hàng #<?= $order['id'] ?></h1>
      <p class="page-sub">
        Đặt lúc <?= date('H:i, d/m/Y', strtotime($order['created_at'])) ?>
      </p>
    </div>
    <div class="header-actions">
      <a class="btn-action btn-status" href="update_status.php?id=<?= $order['id'] ?>">
        <i class="fas fa-sync-alt"></i> Cập nhật trạng thái
      </a>
      <a class="btn-action btn-del"
         href="delete.php?id=<?= $order['id'] ?>"
         onclick="return confirm('Xóa đơn hàng #<?= $order['id'] ?>?')">
        <i class="fas fa-trash"></i> Xóa
      </a>
    </div>
  </div>

  <div class="layout">

    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Items table -->
      <div class="card items-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-list"></i></div>
          <div class="card-title">Sản phẩm trong đơn &nbsp;·&nbsp; <?= count($items) ?> món</div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Sản phẩm</th>
              <th style="text-align:center;">SL</th>
              <th>Đơn giá</th>
              <th>Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div class="product-cell">
                    <div class="product-thumb">
                      <?php if (!empty($item['product_image'])): ?>
                        <img src="../../uploads/products/<?= htmlspecialchars($item['product_image']) ?>"
                             alt="<?= htmlspecialchars($item['product_name']) ?>" />
                      <?php else: ?>
                        <span class="no-img"><i class="fas fa-camera"></i></span>
                      <?php endif; ?>
                    </div>
                    <div>
                      <div class="product-name"><?= htmlspecialchars($item['product_name']) ?></div>
                      <?php if (!empty($item['variant_condition'])): ?>
                        <span class="product-cond"><?= htmlspecialchars($item['variant_condition']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td class="qty-cell"><?= $item['quantity'] ?></td>
                <td class="price-cell"><?= number_format($item['price'], 0, ',', '.') ?>₫</td>
                <td class="total-cell">
                  <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Summary totals -->
      <div class="card summary-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-receipt"></i></div>
          <div class="card-title">Tổng kết</div>
        </div>
        <div class="card-body">
          <div class="summary-line">
            <span class="lbl">Tạm tính</span>
            <span class="val"><?= number_format($order['total_price'], 0, ',', '.') ?>₫</span>
          </div>
          <div class="summary-line">
            <span class="lbl">Phí vận chuyển</span>
            <span class="val">Miễn phí</span>
          </div>
          <div class="summary-total">
            <span class="lbl">Tổng cộng</span>
            <span class="val"><?= number_format($order['total_price'], 0, ',', '.') ?>₫</span>
          </div>
        </div>
      </div>

    </div>

    <div style="display:flex;flex-direction:column;gap:24px;">

      <!-- Order info -->
      <div class="card info-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-info-circle"></i></div>
          <div class="card-title">Thông tin đơn</div>
        </div>
        <div class="card-body">
          <div class="info-row">
            <div class="info-icon"><i class="fas fa-hashtag"></i></div>
            <div>
              <div class="info-label">Mã đơn hàng</div>
              <div class="info-value">#<?= $order['id'] ?></div>
            </div>
          </div>
          <div class="info-row">
            <div class="info-icon"><i class="fas fa-tag"></i></div>
            <div>
              <div class="info-label">Trạng thái</div>
              <div class="info-value" style="margin-top:2px;">
                <span class="status-badge <?= $statusCls ?>"><?= $statusText ?></span>
              </div>
            </div>
          </div>
          <div class="info-row">
            <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
            <div>
              <div class="info-label">Ngày đặt</div>
              <div class="info-value"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Customer info -->
      <div class="card info-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-user"></i></div>
          <div class="card-title">Khách hàng</div>
        </div>
        <div class="card-body">
          <div class="info-row">
            <div class="info-icon"><i class="fas fa-user-circle"></i></div>
            <div>
              <div class="info-label">Họ tên</div>
              <div class="info-value"><?= htmlspecialchars($order['customer_name']) ?></div>
            </div>
          </div>
          <div class="info-row">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <div>
              <div class="info-label">Email</div>
              <div class="info-value" style="word-break:break-all;">
                <?= htmlspecialchars($order['email']) ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
      $hasShipping = !empty($order['recipient_name']) && !empty($order['recipient_phone']);
      ?>
      <?php if ($hasShipping): ?>
      <div class="card info-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="card-title">Địa chỉ giao hàng</div>
        </div>
        <div class="card-body" style="padding: 18px;">
          <div class="ship-box">
            <div class="ship-box-header">
              <i class="fas fa-shipping-fast"></i> Thông tin người nhận
            </div>
            <div class="ship-grid">
              <div class="ship-item">
                <div class="ship-label">Người nhận</div>
                <div class="ship-val"><?= htmlspecialchars($order['recipient_name']) ?></div>
              </div>
              <div class="ship-item">
                <div class="ship-label">Điện thoại</div>
                <div class="ship-val">
                  <a href="tel:<?= htmlspecialchars($order['recipient_phone']) ?>"
                     style="color: var(--gold); text-decoration: none;">
                    <?= htmlspecialchars($order['recipient_phone']) ?>
                  </a>
                </div>
              </div>
              <div class="ship-item ship-full">
                <div class="ship-label">Địa chỉ</div>
                <div class="ship-val">
                  <?= htmlspecialchars($order['address_line']) ?>,
                  <?= htmlspecialchars($order['ward']) ?>,
                  <?= htmlspecialchars($order['district']) ?>,
                  <?= htmlspecialchars($order['province']) ?>
                </div>
              </div>
              <?php if (!empty($order['delivery_notes'])): ?>
                <div class="ship-notes">
                  <i class="fas fa-sticky-note" style="margin-right:4px;"></i>
                  <?= htmlspecialchars($order['delivery_notes']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php else: ?>
      <div class="card info-card">
        <div class="card-header">
          <div class="card-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="card-title">Địa chỉ giao hàng</div>
        </div>
        <div class="card-body">
          <p style="color: var(--muted); font-size: 13px; font-style: italic;">
            Đơn hàng này chưa có thông tin giao hàng.
          </p>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

</body>
</html>