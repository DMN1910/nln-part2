<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/config.php";

/* Kiểm tra quyền admin */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /camerashop/login.php");
    exit;
}

/* ==================== XỬ LÝ POST ==================== */

// Thêm thương hiệu mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
        $stmt->execute([$name]);
    }
    header("Location: brands.php");
    exit;
}

// Xóa thương hiệu
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: brands.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM brands ORDER BY id DESC");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý thương hiệu - Admin</title>
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
      --shadow-lg:   0 20px 60px rgba(0,0,0,.14);
      --danger:      #dc2626;
      --success:     #16a34a;

      --font-display: 'Cormorant Garamond', serif;
      --font-body:    'DM Sans', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font-body);
      background: var(--cream);
      color: var(--ink);
      min-height: 100vh;
      padding: 48px 40px 100px;
    }

    /* Toast */
    .toast {
      position: fixed; top: 24px; right: 24px; z-index: 999;
      background: var(--ink); color: white;
      padding: 14px 20px; border-radius: 10px;
      font-size: 14px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      box-shadow: var(--shadow-lg);
      transform: translateY(-80px); opacity: 0;
      transition: all .35s cubic-bezier(.34,1.56,.64,1);
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { background: var(--success); }
    .toast.error   { background: var(--danger); }

    /* Breadcrumb */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted);
      margin-bottom: 14px;
    }
    .breadcrumb a { color: var(--muted); text-decoration: none; transition: color .2s; }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white); border: 1.5px solid var(--border);
      color: var(--muted); font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      text-decoration: none; margin-bottom: 20px;
      box-shadow: var(--shadow-sm); transition: all .2s;
    }
    .btn-back:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    /* Page Header */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-bottom: 36px; padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
    }
    .gold-line {
      width: 40px; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px; margin-bottom: 8px;
    }
    .page-title { font-family: var(--font-display); font-size: 36px; font-weight: 700; color: var(--ink); }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    .btn-add {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--ink); color: var(--white);
      font-size: 13.5px; font-weight: 600;
      padding: 10px 22px; border-radius: 9px;
      border: none; cursor: pointer;
      transition: all .2s; box-shadow: var(--shadow-sm);
    }
    .btn-add:hover { background: var(--ink-soft); transform: translateY(-2px); box-shadow: var(--shadow-md); }

    /* Table Card */
    .table-card {
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .table-toolbar {
      padding: 20px 26px; border-bottom: 1.5px solid var(--border);
      background: var(--cream);
      display: flex; align-items: center; justify-content: space-between; gap: 16px;
    }
    .search-wrap { position: relative; flex: 1; max-width: 320px; }
    .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 14px; }
    .search-input {
      width: 100%; padding: 10px 14px 10px 44px;
      border: 1.5px solid var(--border); border-radius: 9px;
      background: var(--cream); font-size: 14px; outline: none; transition: all .2s;
      font-family: var(--font-body);
    }
    .search-input:focus { border-color: var(--gold); background: var(--white); }

    .row-count {
      font-size: 13px; color: var(--muted);
      background: var(--gold-pale); padding: 6px 14px;
      border-radius: 6px; border: 1px solid var(--gold-border);
      white-space: nowrap;
    }

    table { width: 100%; border-collapse: collapse; }
    thead th {
      text-align: left; font-size: 10.8px; font-weight: 600;
      letter-spacing: 1.4px; text-transform: uppercase;
      color: var(--muted); padding: 16px 26px; background: var(--cream);
    }
    thead th:last-child { text-align: right; }

    tbody tr { border-bottom: 1px solid var(--border); transition: background .2s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--gold-pale); }
    tbody td { padding: 16px 26px; font-size: 14.5px; vertical-align: middle; }

    .id-badge {
      background: var(--cream-dark); border: 1px solid var(--border);
      color: var(--muted); font-size: 11.5px; font-weight: 600;
      padding: 4px 10px; border-radius: 6px;
    }

    .brand-name { display: flex; align-items: center; gap: 12px; font-weight: 500; }
    .brand-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: var(--gold-pale); border: 1px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: var(--gold); flex-shrink: 0;
    }

    .actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
    .btn-action {
      padding: 7px 14px; border-radius: 7px; font-size: 13px; font-weight: 500;
      text-decoration: none; border: 1.5px solid transparent; cursor: pointer;
      transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
      font-family: var(--font-body);
    }
    .btn-edit { background: var(--cream); border-color: var(--border); color: var(--ink-soft); }
    .btn-edit:hover { background: var(--ink); color: white; border-color: var(--ink); }
    .btn-delete { background: #fef2f2; border-color: #fecaca; color: var(--danger); }
    .btn-delete:hover { background: var(--danger); color: white; border-color: var(--danger); }

    .empty-state { text-align: center; padding: 80px 20px; color: var(--muted); }
    .empty-icon { font-size: 48px; margin-bottom: 16px; color: var(--border-dark); }
    .empty-state h4 { font-size: 16px; margin-bottom: 6px; color: var(--ink-soft); }

    /* Modal */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(26,23,20,.48); backdrop-filter: blur(5px);
      display: none; align-items: center; justify-content: center; padding: 24px;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 16px; width: 100%; max-width: 440px;
      box-shadow: var(--shadow-lg); overflow: hidden;
      transform: translateY(20px); opacity: 0; transition: all .3s;
    }
    .modal-overlay.open .modal { transform: translateY(0); opacity: 1; }
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 22px 24px 20px; border-bottom: 1.5px solid var(--border);
    }
    .modal-header-left { display: flex; align-items: center; gap: 14px; }
    .modal-icon {
      width: 42px; height: 42px; border-radius: 11px;
      background: var(--gold-pale); border: 1.5px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; color: var(--gold);
    }
    .modal-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; }
    .modal-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .btn-close {
      width: 34px; height: 34px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--cream);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; color: var(--muted); cursor: pointer; transition: all .2s;
    }
    .btn-close:hover { color: var(--danger); border-color: var(--danger); background: #fef2f2; }
    .modal-body { padding: 26px 24px 20px; }
    .form-label {
      display: block; font-size: 11.5px; font-weight: 600;
      letter-spacing: .8px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 8px;
    }
    .form-input {
      width: 100%; padding: 12px 14px;
      border: 1.5px solid var(--border); border-radius: 9px;
      font-size: 14.5px; background: var(--cream); transition: all .2s;
      font-family: var(--font-body); outline: none;
    }
    .form-input:focus {
      border-color: var(--gold); background: var(--white);
      box-shadow: 0 0 0 3px rgba(184,134,11,.10);
    }
    .modal-footer {
      padding: 16px 24px 24px; border-top: 1.5px solid var(--border);
      display: flex; gap: 10px; justify-content: flex-end;
    }
    .btn-cancel {
      padding: 9px 20px; border-radius: 8px;
      background: var(--cream); border: 1.5px solid var(--border);
      color: var(--muted); cursor: pointer; font-family: var(--font-body);
      font-size: 14px; transition: all .2s;
    }
    .btn-cancel:hover { border-color: var(--border-dark); color: var(--ink); }
    .btn-submit {
      padding: 9px 24px; border-radius: 8px;
      background: var(--ink); color: white; border: none;
      font-weight: 600; cursor: pointer;
      display: inline-flex; align-items: center; gap: 8px;
      font-family: var(--font-body); font-size: 14px; transition: all .2s;
    }
    .btn-submit:hover { background: var(--ink-soft); }

    /* Confirm Modal */
    .confirm-overlay {
      position: fixed; inset: 0; z-index: 300;
      background: rgba(26,23,20,.55); backdrop-filter: blur(6px);
      display: none; align-items: center; justify-content: center; padding: 24px;
    }
    .confirm-overlay.open { display: flex; }
    .confirm-box {
      background: var(--white); border-radius: 14px;
      border: 1.5px solid var(--border); max-width: 380px; width: 100%;
      padding: 28px 28px 24px; box-shadow: var(--shadow-lg);
      transform: scale(.95); opacity: 0; transition: all .25s;
    }
    .confirm-overlay.open .confirm-box { transform: scale(1); opacity: 1; }
    .confirm-icon {
      width: 48px; height: 48px; border-radius: 12px;
      background: #fef2f2; border: 1.5px solid #fecaca;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; color: var(--danger); margin-bottom: 16px;
    }
    .confirm-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; margin-bottom: 8px; }
    .confirm-msg { font-size: 14px; color: var(--muted); line-height: 1.6; margin-bottom: 20px; }
    .confirm-name { color: var(--ink); font-weight: 600; }
    .confirm-btns { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-confirm-cancel {
      padding: 9px 20px; border-radius: 8px;
      background: var(--cream); border: 1.5px solid var(--border);
      color: var(--muted); cursor: pointer; font-family: var(--font-body);
      font-size: 14px; transition: all .2s;
    }
    .btn-confirm-cancel:hover { border-color: var(--border-dark); color: var(--ink); }
    .btn-confirm-delete {
      padding: 9px 20px; border-radius: 8px;
      background: var(--danger); color: white; border: none;
      font-weight: 600; cursor: pointer; font-family: var(--font-body);
      font-size: 14px; transition: all .2s;
      display: inline-flex; align-items: center; gap: 7px;
    }
    .btn-confirm-delete:hover { background: #b91c1c; }
  </style>
</head>
<body>

  <!-- Toast -->
  <div class="toast" id="toast"><i class="fas fa-check-circle"></i> <span id="toastMsg"></span></div>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Quản lý thương hiệu</span>
  </nav>

  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về Dashboard
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Quản lý thương hiệu</h1>
      <p class="page-sub">Thêm, sửa, xóa các thương hiệu camera trong hệ thống</p>
    </div>
    <button class="btn-add" onclick="openModal()">
      <i class="fas fa-plus"></i> Thêm thương hiệu
    </button>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Tìm thương hiệu..." />
      </div>
      <div class="row-count" id="rowCount"><?= count($brands) ?> thương hiệu</div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên thương hiệu</th>
          <th style="text-align:right;">Thao tác</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (empty($brands)): ?>
          <tr>
            <td colspan="3" class="empty-state">
              <div class="empty-icon"><i class="fas fa-trademark"></i></div>
              <h4>Chưa có thương hiệu nào</h4>
              <p>Nhấn "Thêm thương hiệu" để bắt đầu.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($brands as $brand): ?>
            <tr data-name="<?= strtolower(htmlspecialchars($brand['name'])) ?>">
              <td><span class="id-badge">#<?= $brand['id'] ?></span></td>
              <td>
                <div class="brand-name">
                  <div class="brand-icon"><i class="fas fa-trademark"></i></div>
                  <?= htmlspecialchars($brand['name']) ?>
                </div>
              </td>
              <td>
                <div class="actions">
                  <a href="edit_brand.php?id=<?= $brand['id'] ?>" class="btn-action btn-edit">
                    <i class="fas fa-pen"></i> Sửa
                  </a>
                  <button
                    class="btn-action btn-delete"
                    onclick="confirmDelete(<?= $brand['id'] ?>, '<?= addslashes(htmlspecialchars($brand['name'])) ?>')">
                    <i class="fas fa-trash"></i> Xóa
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Thêm -->
  <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="modal">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon"><i class="fas fa-trademark"></i></div>
          <div>
            <div class="modal-title">Thêm thương hiệu mới</div>
            <div class="modal-sub">Nhập tên thương hiệu và lưu</div>
          </div>
        </div>
        <button class="btn-close" onclick="closeModal()">×</button>
      </div>
      <form method="POST" action="brands.php" onsubmit="return validateForm()">
        <div class="modal-body">
          <label class="form-label">Tên thương hiệu</label>
          <input type="text" id="brandName" name="name" class="form-input"
                 placeholder="Ví dụ: Canon, Sony, Nikon, Fujifilm..." required autocomplete="off" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal()">Huỷ</button>
          <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Lưu thương hiệu</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirm Delete Modal -->
  <div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
      <div class="confirm-icon"><i class="fas fa-trash"></i></div>
      <div class="confirm-title">Xóa thương hiệu?</div>
      <div class="confirm-msg">
        Bạn có chắc muốn xóa thương hiệu <span class="confirm-name" id="confirmName"></span>?<br>
        Hành động này không thể hoàn tác.
      </div>
      <div class="confirm-btns">
        <button class="btn-confirm-cancel" onclick="closeConfirm()">Huỷ</button>
        <a id="confirmDeleteBtn" href="#" class="btn-confirm-delete">
          <i class="fas fa-trash"></i> Xóa
        </a>
      </div>
    </div>
  </div>

  <script>
    // ---- Toast ----
    function showToast(msg, type = 'success') {
      const toast = document.getElementById('toast');
      document.getElementById('toastMsg').textContent = msg;
      toast.className = `toast ${type} show`;
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Check URL params for feedback
    const params = new URLSearchParams(location.search);
    if (params.get('added')) showToast('Thêm thương hiệu thành công!');
    if (params.get('updated')) showToast('Cập nhật thương hiệu thành công!');
    if (params.get('deleted')) showToast('Đã xóa thương hiệu!', 'error');

    // ---- Add Modal ----
    const overlay = document.getElementById('modalOverlay');
    const nameInput = document.getElementById('brandName');

    function openModal() {
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
      setTimeout(() => nameInput.focus(), 300);
    }
    function closeModal() {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
      nameInput.value = '';
    }
    function handleOverlayClick(e) { if (e.target === overlay) closeModal(); }
    function validateForm() {
      if (!nameInput.value.trim()) { nameInput.focus(); return false; }
      return true;
    }

    // ---- Confirm Delete Modal ----
    const confirmOverlay = document.getElementById('confirmOverlay');
    function confirmDelete(id, name) {
      document.getElementById('confirmName').textContent = '«' + name + '»';
      document.getElementById('confirmDeleteBtn').href = 'brands.php?delete=' + id + '&deleted=1';
      confirmOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeConfirm() {
      confirmOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    // ---- Live Search ----
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#tableBody tr[data-name]');
    const rowCount = document.getElementById('rowCount');

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      let visible = 0;
      rows.forEach(r => {
        const match = r.dataset.name.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      rowCount.textContent = visible + ' thương hiệu';
    });

    // ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') { closeModal(); closeConfirm(); }
    });
  </script>
</body>
</html>