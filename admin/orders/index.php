<?php
session_start();
require_once "../../config/database.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

/* ── Xử lý cập nhật trạng thái inline (POST) ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $allowed = ['Chờ xác nhận', 'Đang xử lý', 'Đang giao', 'Hoàn tất', 'Đã hủy'];
    $newStatus = $_POST['status'];
    if (in_array($newStatus, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
            ->execute([$newStatus, (int)$_POST['order_id']]);
    }
    header("Location: index.php?updated=" . (int)$_POST['order_id']);
    exit;
}

/* ── Lấy danh sách đơn hàng ── */
$sql = "
    SELECT o.id, o.total_price, o.status, o.created_at,
           o.recipient_name, o.recipient_phone,
           u.name AS customer_name, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
";
$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$countAll        = count($orders);
$countPending    = count(array_filter($orders, fn($o) => $o['status'] === 'Chờ xác nhận'));
$countProcessing = count(array_filter($orders, fn($o) => $o['status'] === 'Đang xử lý' || $o['status'] === 'Đang giao'));
$countDone       = count(array_filter($orders, fn($o) => $o['status'] === 'Hoàn tất'));
$countCancel     = count(array_filter($orders, fn($o) => $o['status'] === 'Đã hủy'));

function statusInfo(string $s): array {
    return match($s) {
        'Chờ xác nhận' => ['Chờ xác nhận', 'status-pending'],
        'Đang xử lý'   => ['Đang xử lý',   'status-processing'],
        'Đang giao'    => ['Đang giao',     'status-shipping'],
        'Hoàn tất'     => ['Hoàn tất',      'status-done'],
        'Đã hủy'       => ['Đã hủy',        'status-cancel'],
        default        => [$s,              'status-default'],
    };
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý đơn hàng</title>
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
      --purple:      #7c3aed;
      --purple-pale: #f5f3ff;
      --font-display:'Cormorant Garamond', serif;
      --font-body:   'DM Sans', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--font-body);
      background: var(--cream); color: var(--ink);
      min-height: 100vh; padding: 48px 40px 100px;
    }

    /* ── Breadcrumb ── */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted); margin-bottom: 14px;
    }
    .breadcrumb a { color: var(--muted); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: color .2s; }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { font-size: 10px; color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white); border: 1.5px solid var(--border);
      color: var(--muted); font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px; text-decoration: none;
      margin-bottom: 20px; box-shadow: var(--shadow-sm);
      transition: border-color .2s, color .2s, background .2s;
    }
    .btn-back:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    /* ── Page header ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-bottom: 28px; padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
    }
    .gold-line { width: 40px; height: 3px; background: linear-gradient(90deg, var(--gold), var(--gold-light)); border-radius: 2px; margin-bottom: 8px; }
    .page-title { font-family: var(--font-display); font-size: 36px; font-weight: 700; color: var(--ink); letter-spacing: .3px; line-height: 1; }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    /* ── Stats ── */
    .stats-row {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
      gap: 16px; margin-bottom: 28px;
    }
    .stat-card { background: var(--white); border: 1.5px solid var(--border); border-radius: 12px; padding: 18px 20px; box-shadow: var(--shadow-sm); }
    .stat-card.gold { border-color: var(--gold-border); background: var(--gold-pale); }
    .stat-icon { font-size: 16px; margin-bottom: 10px; }
    .stat-icon.c-gold   { color: var(--gold); }
    .stat-icon.c-warn   { color: var(--warn); }
    .stat-icon.c-blue   { color: var(--blue); }
    .stat-icon.c-green  { color: var(--green); }
    .stat-icon.c-danger { color: var(--danger); }
    .stat-value { font-family: var(--font-body); font-size: 30px; font-weight: 700; color: var(--ink); line-height: 1; }
    .stat-label { font-size: 11px; color: var(--muted); letter-spacing: .5px; margin-top: 4px; }

    /* ── Toast ── */
    .toast {
      position: fixed; top: 24px; right: 24px; z-index: 9999;
      background: var(--green); color: white;
      padding: 12px 20px; border-radius: 10px;
      font-size: 13.5px; font-weight: 500;
      display: flex; align-items: center; gap: 8px;
      box-shadow: var(--shadow-md);
      animation: toastIn .3s ease both;
    }
    @keyframes toastIn {
      from { opacity: 0; transform: translateX(40px); }
      to   { opacity: 1; transform: translateX(0); }
    }

    /* ── Table card ── */
    .table-card {
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .table-toolbar {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 18px 24px; border-bottom: 1.5px solid var(--border);
    }
    .search-wrap { position: relative; flex: 1; min-width: 200px; max-width: 320px; }
    .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: var(--muted); }
    .search-input {
      width: 100%; padding: 8px 12px 8px 36px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-family: var(--font-body); font-size: 13px; color: var(--ink);
      background: var(--cream); outline: none; transition: border-color .2s;
    }
    .search-input:focus { border-color: var(--gold); }
    .search-input::placeholder { color: var(--border-dark); }

    .filter-select {
      padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 8px;
      font-family: var(--font-body); font-size: 13px; color: var(--ink);
      background: var(--cream); outline: none; cursor: pointer; transition: border-color .2s;
    }
    .filter-select:focus { border-color: var(--gold); }
    .row-count {
      margin-left: auto; font-size: 12px; color: var(--muted);
      background: var(--cream); border: 1px solid var(--border);
      border-radius: 6px; padding: 5px 12px; white-space: nowrap;
    }

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
    tbody td { padding: 14px 20px; font-size: 13.5px; vertical-align: middle; }

    .id-badge {
      display: inline-block; background: var(--cream-dark);
      border: 1px solid var(--border); color: var(--muted);
      font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 6px;
    }

    .customer-cell { display: flex; flex-direction: column; gap: 2px; }
    .customer-name { font-weight: 600; color: var(--ink); font-size: 13.5px; }
    .customer-email { font-size: 12px; color: var(--muted); }
    .customer-phone { font-size: 11.5px; color: var(--gold); margin-top: 1px; }

    .price-cell { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--gold); }

    /* Status badges */
    .status-badge {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 11.5px; font-weight: 600; letter-spacing: .2px;
      padding: 4px 11px; border-radius: 999px; border: 1px solid transparent;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .status-pending    { background: var(--warn-pale);   color: var(--warn);   border-color: rgba(217,119,6,.2); }
    .status-processing { background: var(--blue-pale);   color: var(--blue);   border-color: rgba(37,99,235,.2); }
    .status-shipping   { background: var(--purple-pale); color: var(--purple); border-color: rgba(124,58,237,.2); }
    .status-done       { background: var(--green-pale);  color: var(--green);  border-color: rgba(22,163,74,.2); }
    .status-cancel     { background: var(--danger-pale); color: var(--danger); border-color: rgba(220,38,38,.2); }
    .status-default    { background: var(--cream-dark);  color: var(--muted);  border-color: var(--border); }

    .date-cell { font-size: 12.5px; color: var(--muted); white-space: nowrap; }

    /* ── Inline status form ── */
    .status-form { display: flex; align-items: center; gap: 6px; }
    .status-select {
      padding: 6px 10px; border: 1.5px solid var(--border); border-radius: 7px;
      font-family: var(--font-body); font-size: 12px; color: var(--ink);
      background: var(--cream); outline: none; cursor: pointer;
      transition: border-color .2s; max-width: 140px;
    }
    .status-select:focus { border-color: var(--gold); }
    .btn-confirm {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: 12px; font-weight: 600;
      padding: 6px 13px; border-radius: 7px;
      background: var(--green-pale);
      border: 1.5px solid rgba(22,163,74,.25);
      color: var(--green); cursor: pointer;
      transition: all .2s; white-space: nowrap;
      font-family: var(--font-body);
    }
    .btn-confirm:hover { background: var(--green); border-color: var(--green); color: white; }

    /* Actions */
    .actions { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 5px;
      font-family: var(--font-body); font-size: 12px; font-weight: 500;
      padding: 6px 12px; border-radius: 7px;
      text-decoration: none; border: 1.5px solid transparent;
      cursor: pointer; transition: all .2s; white-space: nowrap;
    }
    .btn-view  { background: var(--blue-pale);   border-color: rgba(37,99,235,.2);  color: var(--blue); }
    .btn-view:hover { background: var(--blue); border-color: var(--blue); color: white; }
    .btn-del   { background: var(--danger-pale); border-color: rgba(220,38,38,.2);  color: var(--danger); }
    .btn-del:hover { background: var(--danger); border-color: var(--danger); color: white; }

    .empty-state { text-align: center; padding: 80px 24px; }
    .empty-icon { font-size: 48px; color: var(--border-dark); margin-bottom: 20px; }
    .empty-state h3 { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
    .empty-state p { font-size: 13.5px; color: var(--muted); }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .table-card { animation: fadeUp .35s ease both; }

    /* Highlight row vừa update */
    tbody tr.just-updated { background: #f0fdf4 !important; }
  </style>
</head>
<body>

  <!-- Toast thông báo -->
  <?php if (isset($_GET['updated'])): ?>
    <div class="toast" id="toast">
      <i class="fas fa-check-circle"></i>
      Đã cập nhật trạng thái đơn #<?= (int)$_GET['updated'] ?>
    </div>
    <script>setTimeout(() => document.getElementById('toast')?.remove(), 3000);</script>
  <?php endif; ?>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Quản lý đơn hàng</span>
  </nav>

  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về trang Admin
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Đơn hàng</h1>
      <p class="page-sub">Theo dõi và xử lý toàn bộ đơn hàng của khách</p>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card gold">
      <div class="stat-icon c-gold"><i class="fas fa-box"></i></div>
      <div class="stat-value"><?= $countAll ?></div>
      <div class="stat-label">Tổng đơn hàng</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon c-warn"><i class="fas fa-clock"></i></div>
      <div class="stat-value"><?= $countPending ?></div>
      <div class="stat-label">Chờ xác nhận</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon c-blue"><i class="fas fa-spinner"></i></div>
      <div class="stat-value"><?= $countProcessing ?></div>
      <div class="stat-label">Đang xử lý / giao</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon c-green"><i class="fas fa-check-circle"></i></div>
      <div class="stat-value"><?= $countDone ?></div>
      <div class="stat-label">Hoàn tất</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon c-danger"><i class="fas fa-times-circle"></i></div>
      <div class="stat-value"><?= $countCancel ?></div>
      <div class="stat-label">Đã hủy</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input class="search-input" type="text" id="searchInput" placeholder="Tìm khách hàng, email, SĐT..." />
      </div>
      <select class="filter-select" id="filterStatus">
        <option value="">Tất cả trạng thái</option>
        <option value="Chờ xác nhận">Chờ xác nhận</option>
        <option value="Đang xử lý">Đang xử lý</option>
        <option value="Đang giao">Đang giao</option>
        <option value="Hoàn tất">Hoàn tất</option>
        <option value="Đã hủy">Đã hủy</option>
      </select>
      <span class="row-count" id="rowCount"><?= $countAll ?> đơn hàng</span>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Khách hàng</th>
          <th>Tổng tiền</th>
          <th>Xác nhận đơn hàng</th>
          <th>Ngày đặt</th>
          <th style="text-align:right;">Hành động</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                <h3>Chưa có đơn hàng</h3>
                <p>Khi có đơn hàng mới, chúng sẽ xuất hiện ở đây.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $order):
            [$label, $cls] = statusInfo($order['status']);
            $justUpdated = isset($_GET['updated']) && (int)$_GET['updated'] === (int)$order['id'];
          ?>
            <tr
              data-name="<?= htmlspecialchars(strtolower($order['customer_name'] . ' ' . $order['email'] . ' ' . ($order['recipient_phone'] ?? ''))) ?>"
              data-status="<?= htmlspecialchars($order['status']) ?>"
              <?= $justUpdated ? 'class="just-updated"' : '' ?>
            >
              <!-- ID -->
              <td><span class="id-badge">#<?= $order['id'] ?></span></td>

              <!-- Khách hàng -->
              <td>
                <div class="customer-cell">
                  <span class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></span>
                  <span class="customer-email"><?= htmlspecialchars($order['email']) ?></span>
                  <?php if (!empty($order['recipient_name'])): ?>
                    <span class="customer-phone">
                      <i class="fas fa-map-marker-alt" style="font-size:10px;"></i>
                      <?= htmlspecialchars($order['recipient_name']) ?>
                      · <?= htmlspecialchars($order['recipient_phone']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Giá -->
              <td>
                <span class="price-cell"><?= number_format($order['total_price'], 0, ',', '.') ?>₫</span>
              </td>

              <!-- Inline status update -->
              <td>
                <form method="POST" class="status-form">
                  <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                  <select name="status" class="status-select"
                          onchange="highlightConfirm(this)">
                    <?php
                    // $statuses = ['Chờ xác nhận', 'Đang xử lý', 'Đang giao', 'Hoàn tất', 'Đã hủy'];
                    $statuses = ['Hoàn tất'];
                    foreach ($statuses as $st):
                    ?>
                      <option value="<?= $st ?>" <?= $order['status'] === $st ? 'selected' : '' ?>>
                        <?= $st ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-confirm">
                    <i class="fas fa-check"></i> Xác nhận
                  </button>
                </form>
              </td>

              <!-- Ngày đặt -->
              <td>
                <span class="date-cell">
                  <i class="fas fa-calendar-alt" style="margin-right:4px;color:var(--border-dark);"></i>
                  <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                </span>
              </td>

              <!-- Actions -->
              <td>
                <div class="actions">
                  <a class="btn-action btn-view" href="view.php?id=<?= $order['id'] ?>">
                    <i class="fas fa-eye"></i> Xem
                  </a>
                  
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <script>
    /* ── Filter / search ── */
    const searchInput  = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const rows         = document.querySelectorAll('#tableBody tr[data-name]');
    const rowCount     = document.getElementById('rowCount');

    function applyFilters() {
      const q  = searchInput.value.toLowerCase().trim();
      const st = filterStatus.value;
      let visible = 0;
      rows.forEach(r => {
        const matchQ  = !q  || r.dataset.name.includes(q);
        const matchSt = !st || r.dataset.status === st;
        const show = matchQ && matchSt;
        r.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      rowCount.textContent = `${visible} đơn hàng`;
    }

    searchInput.addEventListener('input', applyFilters);
    filterStatus.addEventListener('change', applyFilters);

    /* ── Highlight nút Xác nhận khi dropdown thay đổi ── */
    function highlightConfirm(select) {
      const btn = select.closest('.status-form').querySelector('.btn-confirm');
      btn.style.background  = 'var(--green)';
      btn.style.color       = 'white';
      btn.style.borderColor = 'var(--green)';
    }

    /* ── Tự cuộn tới row vừa update ── */
    const updatedRow = document.querySelector('.just-updated');
    if (updatedRow) {
      updatedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => updatedRow.classList.remove('just-updated'), 2500);
    }
  </script>
</body>
</html>