<?php
require_once "../../config/database.php";
require_once "../../config/config.php";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }
    header("Location: index.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý danh mục</title>
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
      --danger:      #dc2626;
      --danger-pale: #fef2f2;
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

    /* ── Page header ── */
    .page-header {
      display: flex; align-items: flex-end;
      justify-content: space-between;
      margin-bottom: 36px;
      padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
      animation: fadeUp .4s ease both;
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

    /* breadcrumb */
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

    /* back button */
    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white);
      border: 1.5px solid var(--border);
      color: var(--muted);
      font-family: var(--font-body);
      font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      text-decoration: none; cursor: pointer;
      transition: border-color .2s, color .2s, background .2s;
      margin-bottom: 20px;
      box-shadow: var(--shadow-sm);
    }
    .btn-back:hover {
      border-color: var(--gold);
      color: var(--gold);
      background: var(--gold-pale);
    }
    .btn-back i { font-size: 11px; }

    .btn-add {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--ink); color: var(--white);
      font-family: var(--font-body);
      font-size: 13.5px; font-weight: 600;
      padding: 10px 22px; border-radius: 9px;
      border: none; cursor: pointer;
      transition: background .2s, transform .2s, box-shadow .2s;
      box-shadow: var(--shadow-sm);
    }
    .btn-add:hover {
      background: var(--ink-soft);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* ── Summary strip ── */
    .summary-strip {
      display: flex; align-items: center; gap: 20px;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 18px 24px;
      margin-bottom: 24px;
      box-shadow: var(--shadow-sm);
      animation: fadeUp .4s .05s ease both;
    }
    .summary-value {
      font-family: var(--font-display);
      font-size: 28px; font-weight: 700; color: var(--gold); line-height: 1;
    }
    .summary-label { font-size: 11px; color: var(--muted); letter-spacing: .5px; margin-top: 3px; }
    .summary-divider { width: 1px; height: 40px; background: var(--border); }

    /* ── Table card ── */
    .table-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      animation: fadeUp .4s .1s ease both;
    }
    .table-toolbar {
      display: flex; align-items: center; gap: 12px;
      padding: 18px 24px;
      border-bottom: 1.5px solid var(--border);
    }
    .search-wrap { position: relative; flex: 1; max-width: 320px; }
    .search-wrap i {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      font-size: 13px; color: var(--muted);
    }
    .search-input {
      width: 100%;
      padding: 8px 12px 8px 36px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-family: var(--font-body); font-size: 13px; color: var(--ink);
      background: var(--cream); outline: none;
      transition: border-color .2s;
    }
    .search-input:focus { border-color: var(--gold); }
    .search-input::placeholder { color: var(--border-dark); }
    .row-count {
      margin-left: auto; font-size: 12px; color: var(--muted);
      background: var(--cream); border: 1px solid var(--border);
      border-radius: 6px; padding: 5px 12px;
    }

    table { width: 100%; border-collapse: collapse; }
    thead tr { background: var(--cream); border-bottom: 1.5px solid var(--border); }
    thead th {
      text-align: left; font-size: 10.5px; font-weight: 600;
      letter-spacing: 1.5px; text-transform: uppercase;
      color: var(--muted); padding: 13px 24px;
    }
    thead th:last-child { text-align: right; }
    tbody tr { border-bottom: 1px solid var(--border); transition: background .18s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: var(--gold-pale); }
    tbody td { padding: 15px 24px; font-size: 13.5px; vertical-align: middle; }

    .id-badge {
      display: inline-block; background: var(--cream-dark);
      border: 1px solid var(--border); color: var(--muted);
      font-size: 11px; font-weight: 600;
      padding: 3px 9px; border-radius: 6px; letter-spacing: .3px;
    }
    .cat-name { display: flex; align-items: center; gap: 10px; font-weight: 500; color: var(--ink); }
    .cat-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--gold-pale); border: 1px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; color: var(--gold); flex-shrink: 0;
    }

    .actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      font-family: var(--font-body); font-size: 12.5px; font-weight: 500;
      padding: 7px 14px; border-radius: 7px;
      text-decoration: none; border: 1.5px solid transparent;
      cursor: pointer; transition: all .2s;
    }
    .btn-edit { background: var(--cream); border-color: var(--border); color: var(--ink-soft); }
    .btn-edit:hover { background: var(--ink); border-color: var(--ink); color: var(--white); }
    .btn-delete { background: var(--danger-pale); border-color: rgba(220,38,38,.2); color: var(--danger); }
    .btn-delete:hover { background: var(--danger); border-color: var(--danger); color: var(--white); }

    .empty-state { text-align: center; padding: 80px 24px; }
    .empty-icon { font-size: 44px; color: var(--border-dark); margin-bottom: 20px; }
    .empty-state h3 { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
    .empty-state p { font-size: 13.5px; color: var(--muted); }

    /* MODAL */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(26,23,20,.48);
      backdrop-filter: blur(5px);
      display: flex; align-items: center; justify-content: center;
      padding: 24px;
      opacity: 0; pointer-events: none;
      transition: opacity .25s ease;
    }
    .modal-overlay.open {
      opacity: 1; pointer-events: all;
    }

    .modal {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 16px;
      width: 100%; max-width: 440px;
      box-shadow: var(--shadow-lg);
      overflow: hidden;
      transform: translateY(24px) scale(.96);
      transition: transform .32s cubic-bezier(.34,1.56,.64,1), opacity .25s ease;
      opacity: 0;
    }
    .modal-overlay.open .modal {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 22px 24px 20px;
      border-bottom: 1.5px solid var(--border);
    }
    .modal-header-left { display: flex; align-items: center; gap: 12px; }
    .modal-icon {
      width: 42px; height: 42px; border-radius: 11px;
      background: var(--gold-pale); border: 1.5px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 17px; color: var(--gold);
    }
    .modal-title {
      font-family: var(--font-display);
      font-size: 22px; font-weight: 700; color: var(--ink);
    }
    .modal-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }

    .btn-close {
      width: 32px; height: 32px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--cream);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); font-size: 13px;
      transition: border-color .2s, color .2s, background .2s;
      flex-shrink: 0;
    }
    .btn-close:hover { border-color: var(--danger); color: var(--danger); background: var(--danger-pale); }

    .modal-body { padding: 26px 24px 20px; }

    .form-label {
      display: block;
      font-size: 11.5px; font-weight: 600;
      letter-spacing: .8px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 8px;
    }
    .form-input {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid var(--border); border-radius: 9px;
      font-family: var(--font-body); font-size: 14px; color: var(--ink);
      background: var(--cream); outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-input:focus {
      border-color: var(--gold);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(184,134,11,.10);
    }
    .form-input::placeholder { color: var(--border-dark); }
    .form-hint { font-size: 11.5px; color: var(--muted); margin-top: 7px; }

    .modal-footer {
      display: flex; align-items: center; justify-content: flex-end;
      gap: 10px;
      padding: 16px 24px 22px;
      border-top: 1.5px solid var(--border);
    }
    .btn-cancel {
      font-family: var(--font-body); font-size: 13.5px; font-weight: 500;
      padding: 9px 20px; border-radius: 8px;
      background: var(--cream); border: 1.5px solid var(--border);
      color: var(--muted); cursor: pointer;
      transition: border-color .2s, color .2s;
    }
    .btn-cancel:hover { border-color: var(--border-dark); color: var(--ink); }
    .btn-submit {
      font-family: var(--font-body); font-size: 13.5px; font-weight: 600;
      padding: 9px 24px; border-radius: 8px;
      background: var(--ink); border: none; color: var(--white);
      cursor: pointer;
      display: inline-flex; align-items: center; gap: 8px;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: var(--shadow-sm);
    }
    .btn-submit:hover {
      background: var(--ink-soft);
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    /* ── Animations ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    tbody tr { animation: fadeUp .3s ease both; }
    <?php foreach ($categories as $i => $_): ?>
    tbody tr:nth-child(<?= $i + 1 ?>) { animation-delay: <?= $i * 0.04 ?>s; }
    <?php endforeach; ?>
  </style>
</head>
<body>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php">
      <i class="fas fa-th-large"></i> Dashboard
    </a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Danh mục sản phẩm</span>
  </nav>

  <!-- Back button -->
  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về trang Admin
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Danh mục sản phẩm</h1>
      <p class="page-sub">Quản lý và phân loại sản phẩm trong hệ thống</p>
    </div>
    <button class="btn-add" onclick="openModal()">
      <i class="fas fa-plus"></i> Thêm danh mục
    </button>
  </div>

  <!-- Summary -->
  <div class="summary-strip">
    <div>
      <div class="summary-value"><?= count($categories) ?></div>
      <div class="summary-label">Tổng danh mục</div>
    </div>
    <div class="summary-divider"></div>
    <div style="font-size:13px;color:var(--muted);">
      <i class="fas fa-tags" style="color:var(--gold);margin-right:6px;"></i>
      Đang quản lý danh mục sản phẩm
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input class="search-input" type="text" id="searchInput" placeholder="Tìm danh mục..." />
      </div>
      <span class="row-count" id="rowCount"><?= count($categories) ?> danh mục</span>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên danh mục</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (count($categories) === 0): ?>
          <tr>
            <td colspan="3">
              <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-tags"></i></div>
                <h3>Chưa có danh mục</h3>
                <p>Nhấn «Thêm danh mục» để bắt đầu.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($categories as $cat): ?>
            <tr data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
              <td><span class="id-badge">#<?= $cat['id'] ?></span></td>
              <td>
                <div class="cat-name">
                  <div class="cat-icon"><i class="fas fa-tag"></i></div>
                  <?= htmlspecialchars($cat['name']) ?>
                </div>
              </td>
              <td>
                <div class="actions">
                  <a class="btn-action btn-edit" href="edit.php?id=<?= $cat['id'] ?>">
                    <i class="fas fa-pen"></i> Sửa
                  </a>
                  <a class="btn-action btn-delete"
                     href="delete.php?id=<?= $cat['id'] ?>"
                     onclick="return confirm('Xóa danh mục «<?= htmlspecialchars($cat['name']) ?>»?')">
                    <i class="fas fa-trash"></i> Xóa
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!--  MODAL  -->
  <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon"><i class="fas fa-tag"></i></div>
          <div>
            <div class="modal-title" id="modalTitle">Thêm danh mục</div>
            <div class="modal-sub">Điền tên và lưu để thêm mới</div>
          </div>
        </div>
        <button class="btn-close" onclick="closeModal()" title="Đóng" type="button">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form method="POST" action="index.php" onsubmit="return validateForm()">
        <div class="modal-body">
          <label class="form-label" for="catName">Tên danh mục</label>
          <input class="form-input" type="text" id="catName" name="name"
                 placeholder="Ví dụ: Máy ảnh DSLR, Ống kính..." autocomplete="off" />
          <div class="form-hint">Tên danh mục sẽ hiển thị trên trang cửa hàng.</div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal()">Huỷ</button>
          <button type="submit" class="btn-submit">
            <i class="fas fa-check"></i> Lưu danh mục
          </button>
        </div>
      </form>

    </div>
  </div>

  <script>
    const overlay   = document.getElementById('modalOverlay');
    const nameInput = document.getElementById('catName');

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

    function handleOverlayClick(e) {
      if (e.target === overlay) closeModal();
    }

    function validateForm() {
      if (!nameInput.value.trim()) { nameInput.focus(); return false; }
      return true;
    }

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    // Live search
    const searchInput = document.getElementById('searchInput');
    const rows        = document.querySelectorAll('#tableBody tr[data-name]');
    const rowCount    = document.getElementById('rowCount');

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase().trim();
      let visible = 0;
      rows.forEach(r => {
        const match = r.dataset.name.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      rowCount.textContent = `${visible} danh mục`;
    });
  </script>
</body>
</html>