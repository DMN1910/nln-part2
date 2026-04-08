<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/config.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("Bạn không có quyền truy cập");
}

/* 1. Tổng doanh thu */
$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(total_price),0)
    FROM orders
    WHERE status != 'Đã hủy'
")->fetchColumn();

/* 2. Tổng số đơn */
$totalOrders = $pdo->query("
    SELECT COUNT(*)
    FROM orders
")->fetchColumn();

/* 3. Tổng sản phẩm đã bán */
$totalSold = $pdo->query("
    SELECT COALESCE(SUM(quantity),0)
    FROM order_items
")->fetchColumn();

/* 4. Doanh thu theo ngày (7 ngày gần nhất) */
$dailyRevenueStmt = $pdo->query("
    SELECT DATE(created_at) AS order_date,
           SUM(total_price) AS revenue
    FROM orders
    WHERE status != 'Đã hủy'
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC
    LIMIT 7
");
$dailyRevenues = $dailyRevenueStmt->fetchAll(PDO::FETCH_ASSOC);

/* 5. Đơn hàng gần nhất */
$recentOrders = $pdo->query("
    SELECT o.id, u.name, o.total_price, o.status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thống kê - Dashboard Admin</title>
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
      --shadow-lg:   0 20px 60px rgba(0,0,0,.18);
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

    /* Page header */
    .page-header {
      display: flex; align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 36px;
      padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
    }
    .gold-line {
      width: 40px; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px; margin-bottom: 8px;
    }
    .page-title {
      font-family: var(--font-display);
      font-size: 36px; font-weight: 700;
      color: var(--ink); letter-spacing: .3px; line-height: 1;
    }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    /* Breadcrumb */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted);
      margin-bottom: 14px;
    }
    .breadcrumb a {
      color: var(--muted); text-decoration: none;
      display: inline-flex; align-items: center; gap: 5px;
      transition: color .2s;
    }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb i { font-size: 10px; }
    .breadcrumb .sep { font-size: 10px; color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    /* Back button */
    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white);
      border: 1.5px solid var(--border);
      color: var(--muted);
      font-family: var(--font-body);
      font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      text-decoration: none; cursor: pointer;
      transition: all .2s;
      margin-bottom: 20px;
      box-shadow: var(--shadow-sm);
    }
    .btn-back:hover {
      border-color: var(--gold);
      color: var(--gold);
      background: var(--gold-pale);
    }

    /* Summary Cards */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }

    .summary-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      padding: 28px 26px;
      box-shadow: var(--shadow-sm);
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }
    .summary-icon {
      font-size: 32px;
      margin-bottom: 16px;
      color: var(--gold);
    }
    .summary-value {
      font-family: var(--font-body);
      font-size: 32px;
      font-weight: 700;
      color: var(--ink);
      line-height: 1;
      margin-bottom: 6px;
    }
    .summary-label {
      font-size: 12.5px;
      color: var(--muted);
      letter-spacing: .6px;
      text-transform: uppercase;
    }

    /* Section Title */
    .section-title {
      font-family: var(--font-display);
      font-size: 24px;
      font-weight: 700;
      color: var(--ink);
      margin: 40px 0 18px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .section-title i {
      color: var(--gold);
    }

    /* Table Card */
    .table-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      margin-bottom: 40px;
    }
    .table-header {
      padding: 20px 26px;
      border-bottom: 1.5px solid var(--border);
      background: var(--cream);
      font-weight: 600;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }
    thead th {
      text-align: left;
      font-size: 10.5px;
      font-weight: 600;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: var(--muted);
      padding: 16px 26px;
      background: var(--cream);
    }
    tbody td {
      padding: 16px 26px;
      font-size: 13.8px;
      border-bottom: 1px solid var(--border);
    }
    tbody tr:last-child td {
      border-bottom: none;
    }
    tbody tr:hover {
      background: var(--gold-pale);
    }

    .id-badge {
      background: var(--cream-dark);
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 11px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 6px;
    }

    .status {
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12.5px;
      font-weight: 500;
    }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-completed { background: #d1fae5; color: #10b981; }
    .status-cancelled { background: #fee2e2; color: #ef4444; }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--muted);
    }
  </style>
</head>
<body>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php">
      <i class="fas fa-th-large"></i> Dashboard
    </a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Thống kê doanh thu</span>
  </nav>

  <!-- Back button -->
  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về trang Admin
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Theo dõi doanh thu</h1>
      <p class="page-sub">Tổng quan tình hình kinh doanh của cửa hàng</p>
    </div>
  </div>

  <!-- Summary Cards -->
  <div class="summary-grid">
    <div class="summary-card">
      <div class="summary-icon"><i class="fas fa-coins"></i></div>
      <div class="summary-value"><?= number_format($totalRevenue) ?> ₫</div>
      <div class="summary-label">Tổng doanh thu</div>
    </div>

    <div class="summary-card">
      <div class="summary-icon"><i class="fas fa-shopping-bag"></i></div>
      <div class="summary-value"><?= number_format($totalOrders) ?></div>
      <div class="summary-label">Tổng đơn hàng</div>
    </div>

    <div class="summary-card">
      <div class="summary-icon"><i class="fas fa-box"></i></div>
      <div class="summary-value"><?= number_format($totalSold) ?></div>
      <div class="summary-label">Sản phẩm đã bán</div>
    </div>
  </div>

  <!-- Doanh thu 7 ngày gần nhất -->
  <div class="section-title">
    <i class="fas fa-chart-line"></i>
    Doanh thu 7 ngày gần nhất
  </div>

  <div class="table-card">
    <div class="table-header">
      <i class="fas fa-calendar-alt"></i>
      <span>Bảng thống kê doanh thu theo ngày</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>Ngày</th>
          <th style="text-align: right;">Doanh thu</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($dailyRevenues) > 0): ?>
          <?php foreach ($dailyRevenues as $row): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($row['order_date'])) ?></td>
              <td style="text-align: right; font-weight: 600; color: var(--gold);">
                <?= number_format($row['revenue']) ?> ₫
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="2" class="empty-state">
              <i class="fas fa-chart-bar" style="font-size:42px; margin-bottom:12px; display:block;"></i>
              Chưa có dữ liệu doanh thu
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Đơn hàng gần nhất -->
  <div class="section-title">
    <i class="fas fa-list-check"></i>
    Đơn hàng gần nhất
  </div>

  <div class="table-card">
    <div class="table-header">
      <i class="fas fa-clock"></i>
      <span>10 đơn hàng mới nhất</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Khách hàng</th>
          <th>Tổng tiền</th>
          <th>Trạng thái</th>
          <th>Ngày tạo</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($recentOrders) > 0): ?>
          <?php foreach ($recentOrders as $order): ?>
            <tr>
              <td><span class="id-badge">#<?= $order['id'] ?></span></td>
              <td><?= htmlspecialchars($order['name']) ?></td>
              <td style="font-weight: 600; color: var(--gold);">
                <?= number_format($order['total_price']) ?> ₫
              </td>
              <td>
                <span class="status 
                  <?php 
                    if ($order['status'] === 'Hoàn thành' || $order['status'] === 'Đã giao') echo 'status-completed';
                    elseif ($order['status'] === 'Đã hủy') echo 'status-cancelled';
                    else echo 'status-pending';
                  ?>">
                  <?= htmlspecialchars($order['status']) ?>
                </span>
              </td>
              <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="empty-state">
              Chưa có đơn hàng nào
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>