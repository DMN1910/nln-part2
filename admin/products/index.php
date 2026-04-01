<?php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/admin_auth.php";
require_once __DIR__ . "/../../models/Product.php";
require_once __DIR__ . "/../../models/ProductVariant.php";
require_once __DIR__ . "/../../models/Brand.php";
require_once __DIR__ . "/../../models/Category.php";

$productModel = new Product($pdo);
$variantModel = new ProductVariant($pdo);
$products     = $productModel->all();
$brands       = (new Brand($pdo))->all();
$categories   = (new Category($pdo))->all();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý sản phẩm</title>
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

    /* ── Breadcrumb ── */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted); margin-bottom: 14px;
    }
    .breadcrumb a {
      color: var(--muted); text-decoration: none;
      display: inline-flex; align-items: center; gap: 5px; transition: color .2s;
    }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { font-size: 10px; color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    /* ── Back button ── */
    .btn-back {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--white); border: 1.5px solid var(--border);
      color: var(--muted); font-family: var(--font-body);
      font-size: 13px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      text-decoration: none; margin-bottom: 20px;
      box-shadow: var(--shadow-sm);
      transition: border-color .2s, color .2s, background .2s;
    }
    .btn-back:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    /* ── Page header ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-bottom: 36px; padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
      animation: fadeUp .4s ease both;
    }
    .gold-line {
      width: 40px; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px; margin-bottom: 8px;
    }
    .page-title {
      font-family: var(--font-display); font-size: 36px; font-weight: 700;
      color: var(--ink); letter-spacing: .3px; line-height: 1;
    }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    .btn-add {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--ink); color: var(--white);
      font-family: var(--font-body); font-size: 13.5px; font-weight: 600;
      padding: 10px 22px; border-radius: 9px; border: none; cursor: pointer;
      transition: background .2s, transform .2s, box-shadow .2s;
      box-shadow: var(--shadow-sm);
    }
    .btn-add:hover { background: var(--ink-soft); transform: translateY(-2px); box-shadow: var(--shadow-md); }

    /* ── Summary strip ── */
    .summary-strip {
      display: flex; align-items: center; gap: 20px;
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 12px; padding: 18px 24px; margin-bottom: 24px;
      box-shadow: var(--shadow-sm); animation: fadeUp .4s .05s ease both;
    }
    .summary-value { font-family: var(--font-display); font-size: 28px; font-weight: 700; color: var(--gold); line-height: 1; }
    .summary-label { font-size: 11px; color: var(--muted); letter-spacing: .5px; margin-top: 3px; }
    .summary-divider { width: 1px; height: 40px; background: var(--border); }

    /* ── Table card ── */
    .table-card {
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 14px; overflow: hidden;
      box-shadow: var(--shadow-sm); animation: fadeUp .4s .1s ease both;
    }
    .table-toolbar {
      display: flex; align-items: center; gap: 12px;
      padding: 18px 24px; border-bottom: 1.5px solid var(--border);
    }
    .search-wrap { position: relative; flex: 1; max-width: 320px; }
    .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: var(--muted); }
    .search-input {
      width: 100%; padding: 8px 12px 8px 36px;
      border: 1.5px solid var(--border); border-radius: 8px;
      font-family: var(--font-body); font-size: 13px; color: var(--ink);
      background: var(--cream); outline: none; transition: border-color .2s;
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
      font-size: 11px; font-weight: 600; padding: 3px 9px; border-radius: 6px;
    }
    .product-name { display: flex; align-items: center; gap: 10px; font-weight: 500; color: var(--ink); }
    .product-icon {
      width: 32px; height: 32px; border-radius: 8px;
      background: var(--gold-pale); border: 1px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; color: var(--gold); flex-shrink: 0;
    }
    .cell-badge {
      display: inline-block; background: var(--gold-pale); color: var(--gold);
      border: 1px solid var(--gold-border); font-size: 11px; font-weight: 600;
      padding: 3px 10px; border-radius: 999px;
    }
    .actions { display: flex; align-items: center; gap: 8px; justify-content: flex-end; }
    .btn-action {
      display: inline-flex; align-items: center; gap: 6px;
      font-family: var(--font-body); font-size: 12.5px; font-weight: 500;
      padding: 7px 14px; border-radius: 7px;
      text-decoration: none; border: 1.5px solid transparent;
      cursor: pointer; background: none; transition: all .2s;
    }
    .btn-edit  { background: var(--cream); border-color: var(--border); color: var(--ink-soft); }
    .btn-edit:hover  { background: var(--ink); border-color: var(--ink); color: var(--white); }
    .btn-delete { background: var(--danger-pale); border-color: rgba(220,38,38,.2); color: var(--danger); }
    .btn-delete:hover { background: var(--danger); border-color: var(--danger); color: var(--white); }

    .empty-state { text-align: center; padding: 80px 24px; }
    .empty-icon { font-size: 44px; color: var(--border-dark); margin-bottom: 20px; }
    .empty-state h3 { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
    .empty-state p { font-size: 13.5px; color: var(--muted); }

    /* ══════════════════════════════════════
       MODAL
    ══════════════════════════════════════ */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(26,23,20,.48);
      backdrop-filter: blur(5px);
      display: flex; align-items: center; justify-content: center;
      padding: 24px;
      opacity: 0; pointer-events: none;
      transition: opacity .25s ease;
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }

    .modal {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 16px;
      width: 100%; max-width: 600px;
      max-height: 88vh;
      box-shadow: var(--shadow-lg);
      display: flex; flex-direction: column;
      overflow: hidden;
      transform: translateY(28px) scale(.96);
      transition: transform .32s cubic-bezier(.34,1.56,.64,1), opacity .25s ease;
      opacity: 0;
    }
    .modal-overlay.open .modal { transform: translateY(0) scale(1); opacity: 1; }

    /* Modal header */
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 22px 26px 20px;
      border-bottom: 1.5px solid var(--border);
      flex-shrink: 0;
    }
    .modal-header-left { display: flex; align-items: center; gap: 12px; }
    .modal-icon {
      width: 42px; height: 42px; border-radius: 11px;
      background: var(--gold-pale); border: 1.5px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 17px; color: var(--gold);
    }
    .modal-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: var(--ink); }
    .modal-sub { font-size: 12px; color: var(--muted); margin-top: 2px; }
    .btn-close {
      width: 34px; height: 34px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--cream);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); font-size: 13px;
      transition: border-color .2s, color .2s, background .2s; flex-shrink: 0;
    }
    .btn-close:hover { border-color: var(--danger); color: var(--danger); background: var(--danger-pale); }

    /* Modal body */
    .modal-body {
      flex: 1; overflow-y: auto; padding: 24px 26px 0;
    }
    .modal-body::-webkit-scrollbar { width: 5px; }
    .modal-body::-webkit-scrollbar-track { background: transparent; }
    .modal-body::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 3px; }

    /* Modal footer */
    .modal-footer {
      flex-shrink: 0;
      display: flex; align-items: center; justify-content: flex-end; gap: 10px;
      padding: 16px 26px 22px;
      border-top: 1.5px solid var(--border);
    }
    .btn-cancel {
      font-family: var(--font-body); font-size: 13.5px; font-weight: 500;
      padding: 9px 20px; border-radius: 8px;
      background: var(--cream); border: 1.5px solid var(--border);
      color: var(--muted); cursor: pointer; transition: border-color .2s, color .2s;
    }
    .btn-cancel:hover { border-color: var(--border-dark); color: var(--ink); }
    .btn-submit {
      font-family: var(--font-body); font-size: 13.5px; font-weight: 600;
      padding: 9px 24px; border-radius: 8px;
      background: var(--ink); border: none; color: var(--white);
      cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: var(--shadow-sm);
    }
    .btn-submit:hover { background: var(--ink-soft); transform: translateY(-1px); box-shadow: var(--shadow-md); }

    /* ── Form fields ── */
    .form-section { margin-bottom: 24px; }
    .form-section-title {
      font-size: 10.5px; font-weight: 600; letter-spacing: 1.4px;
      text-transform: uppercase; color: var(--muted);
      margin-bottom: 14px; padding-bottom: 8px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 7px;
    }
    .form-section-title i { color: var(--gold); font-size: 11px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .form-group { margin-bottom: 14px; }

    .form-label {
      display: block; font-size: 11.5px; font-weight: 600;
      letter-spacing: .7px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 7px;
    }
    .form-control {
      width: 100%; padding: 10px 13px;
      border: 1.5px solid var(--border); border-radius: 9px;
      font-family: var(--font-body); font-size: 13.5px; color: var(--ink);
      background: var(--cream); outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-control:focus {
      border-color: var(--gold); background: var(--white);
      box-shadow: 0 0 0 3px rgba(184,134,11,.10);
    }
    .form-control::placeholder { color: var(--border-dark); }
    select.form-control { cursor: pointer; }
    textarea.form-control { resize: vertical; min-height: 80px; }

    /* ── Variants ── */
    .variant-list { display: flex; flex-direction: column; gap: 10px; }
    .variant-card {
      background: var(--cream); border: 1.5px solid var(--border);
      border-radius: 10px; padding: 14px 14px 6px;
      animation: variantIn .22s ease both;
    }
    .variant-card-head {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 10px;
    }
    .variant-label { font-size: 11px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase; color: var(--muted); }
    .btn-remove-variant {
      width: 26px; height: 26px; border-radius: 6px;
      border: 1px solid rgba(220,38,38,.25); background: var(--danger-pale);
      color: var(--danger); font-size: 11px; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s, border-color .2s;
    }
    .btn-remove-variant:hover { background: var(--danger); border-color: var(--danger); color: var(--white); }
    .variant-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .variant-grid .form-group { margin-bottom: 8px; }

    .btn-add-variant {
      display: inline-flex; align-items: center; gap: 7px;
      font-family: var(--font-body); font-size: 12.5px; font-weight: 500;
      padding: 8px 16px; border-radius: 8px;
      border: 1.5px dashed var(--border-dark); background: transparent;
      color: var(--muted); cursor: pointer; width: 100%;
      justify-content: center; margin-top: 8px;
      transition: border-color .2s, color .2s, background .2s;
    }
    .btn-add-variant:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    /* File upload */
    .file-upload-area {
      border: 2px dashed var(--border-dark); border-radius: 10px;
      padding: 20px; text-align: center; cursor: pointer;
      transition: border-color .2s, background .2s;
      background: var(--cream); position: relative;
    }
    .file-upload-area:hover { border-color: var(--gold); background: var(--gold-pale); }
    .file-upload-area input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
    }
    .file-upload-icon { font-size: 24px; color: var(--border-dark); margin-bottom: 6px; }
    .file-upload-text { font-size: 13px; color: var(--muted); }
    .file-upload-text strong { color: var(--gold); }
    .file-names { font-size: 12px; color: var(--ink-soft); margin-top: 6px; }

    /* Loading state */
    .loading-state { text-align: center; padding: 50px 0; color: var(--muted); }
    .loading-state i { font-size: 28px; color: var(--gold); }
    .loading-state p { margin-top: 12px; font-size: 13px; }

    /* ── Animations ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    tbody tr { animation: fadeUp .3s ease both; }
    <?php foreach ($products as $i => $_): ?>
    tbody tr:nth-child(<?= $i + 1 ?>) { animation-delay: <?= $i * 0.04 ?>s; }
    <?php endforeach; ?>

    @keyframes variantIn {
      from { opacity: 0; transform: translateY(6px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Sản phẩm</span>
  </nav>

  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về trang Admin
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Danh sách sản phẩm</h1>
      <p class="page-sub">Quản lý toàn bộ sản phẩm trong hệ thống</p>
    </div>
    <button class="btn-add" onclick="openAddModal()">
      <i class="fas fa-plus"></i> Thêm sản phẩm
    </button>
  </div>

  <!-- Summary -->
  <div class="summary-strip">
    <div>
      <div class="summary-value"><?= count($products) ?></div>
      <div class="summary-label">Tổng sản phẩm</div>
    </div>
    <div class="summary-divider"></div>
    <div style="font-size:13px;color:var(--muted);">
      <i class="fas fa-camera" style="color:var(--gold);margin-right:6px;"></i>
      Đang quản lý danh sách sản phẩm
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input class="search-input" type="text" id="searchInput" placeholder="Tìm sản phẩm..." />
      </div>
      <span class="row-count" id="rowCount"><?= count($products) ?> sản phẩm</span>
    </div>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên sản phẩm</th>
          <th>Hãng</th>
          <th>Loại</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php if (count($products) === 0): ?>
          <tr>
            <td colspan="5">
              <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-camera"></i></div>
                <h3>Chưa có sản phẩm</h3>
                <p>Nhấn «Thêm sản phẩm» để bắt đầu.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <tr data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
              <td><span class="id-badge">#<?= $p['id'] ?></span></td>
              <td>
                <div class="product-name">
                  <div class="product-icon"><i class="fas fa-camera"></i></div>
                  <?= htmlspecialchars($p['name']) ?>
                </div>
              </td>
              <td><span class="cell-badge"><?= htmlspecialchars($p['brand']) ?></span></td>
              <td><?= htmlspecialchars($p['category']) ?></td>
              <td>
                <div class="actions">
                  <button class="btn-action btn-edit" type="button"
                          onclick="openEditModal(<?= $p['id'] ?>)">
                    <i class="fas fa-pen"></i> Sửa
                  </button>
                  <a class="btn-action btn-delete"
                     href="delete.php?id=<?= $p['id'] ?>"
                     onclick="return confirm('Xóa sản phẩm «<?= htmlspecialchars($p['name']) ?>»?')">
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

  <!-- ══════════════ MODAL ══════════════ -->
  <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="modal" role="dialog" aria-modal="true">

      <!-- Shared header -->
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon"><i id="modalIcon" class="fas fa-camera"></i></div>
          <div>
            <div class="modal-title" id="modalTitle">Thêm sản phẩm</div>
            <div class="modal-sub"   id="modalSub">Điền thông tin và lưu</div>
          </div>
        </div>
        <button class="btn-close" onclick="closeModal()" type="button">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- ── ADD FORM ── -->
      <form id="addForm" action="store.php" method="post" enctype="multipart/form-data"
            style="display:none; flex:1; flex-direction:column; overflow:hidden;">
        <div class="modal-body">

          <div class="form-section">
            <div class="form-section-title"><i class="fas fa-info-circle"></i> Thông tin chung</div>
            <div class="form-group">
              <label class="form-label">Tên sản phẩm</label>
              <input class="form-control" name="name" placeholder="Ví dụ: Canon EOS R50" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Hãng</label>
                <select class="form-control" name="brand_id">
                  <?php foreach ($brands as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?></option>
                  <?php endforeach ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Danh mục</label>
                <select class="form-control" name="category_id">
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                  <?php endforeach ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Mô tả</label>
              <textarea class="form-control" name="description" placeholder="Mô tả sản phẩm..."></textarea>
            </div>
          </div>

          <div class="form-section">
            <div class="form-section-title"><i class="fas fa-layer-group"></i> Biến thể</div>
            <div class="variant-list" id="addVariantList">
              <div class="variant-card">
                <div class="variant-card-head">
                  <span class="variant-label">Biến thể 1</span>
                  <button type="button" class="btn-remove-variant"
                          onclick="removeVariant(this,'addVariantList')">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <div class="variant-grid">
                  <div class="form-group">
                    <label class="form-label">Tình trạng</label>
                    <input class="form-control" name="condition[]" placeholder="Mới / Cũ 95%" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Tồn kho</label>
                    <input class="form-control" name="stock[]" type="number" min="0" placeholder="0" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Giá gốc</label>
                    <input class="form-control" name="cost_price[]" type="number" min="0" placeholder="0" />
                  </div>
                  <div class="form-group">
                    <label class="form-label">Giá bán</label>
                    <input class="form-control" name="sell_price[]" type="number" min="0" placeholder="0" />
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn-add-variant" onclick="addVariant('addVariantList')">
              <i class="fas fa-plus"></i> Thêm biến thể
            </button>
          </div>

          <div class="form-section">
            <div class="form-section-title"><i class="fas fa-images"></i> Hình ảnh</div>
            <div class="file-upload-area">
              <input type="file" name="images[]" multiple accept="image/*"
                     onchange="showFileNames(this,'addFileNames')" />
              <div class="file-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
              <div class="file-upload-text">Kéo thả hoặc <strong>chọn ảnh</strong></div>
              <div class="file-names" id="addFileNames"></div>
            </div>
          </div>

        </div><!-- /.modal-body -->
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal()">Huỷ</button>
          <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Lưu sản phẩm</button>
        </div>
      </form>

      <!-- ── EDIT FORM ── -->
      <form id="editForm" action="update.php" method="post"
            style="display:none; flex:1; flex-direction:column; overflow:hidden;">
        <div class="modal-body" id="editModalBody">
          <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Đang tải...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal()">Huỷ</button>
          <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Cập nhật</button>
        </div>
      </form>

    </div><!-- /.modal -->
  </div><!-- /.modal-overlay -->

  <!-- Variant template (hidden) -->
  <template id="variantTpl">
    <div class="variant-card">
      <div class="variant-card-head">
        <span class="variant-label">Biến thể</span>
        <button type="button" class="btn-remove-variant">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="variant-grid">
        <div class="form-group">
          <label class="form-label">Tình trạng</label>
          <input class="form-control" name="condition[]" placeholder="Mới / Cũ 95%" />
        </div>
        <div class="form-group">
          <label class="form-label">Tồn kho</label>
          <input class="form-control" name="stock[]" type="number" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label class="form-label">Giá gốc</label>
          <input class="form-control" name="cost_price[]" type="number" min="0" placeholder="0" />
        </div>
        <div class="form-group">
          <label class="form-label">Giá bán</label>
          <input class="form-control" name="sell_price[]" type="number" min="0" placeholder="0" />
        </div>
      </div>
    </div>
  </template>

  <script>
    const brandsData     = <?= json_encode($brands,     JSON_UNESCAPED_UNICODE) ?>;
    const categoriesData = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
    const overlay        = document.getElementById('modalOverlay');

    /* ── open / close ── */
    function openModal() {
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeModal() {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }
    function handleOverlayClick(e) {
      if (e.target === overlay) closeModal();
    }
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
    });

    function showForm(which) {
      document.getElementById('addForm').style.display  = which === 'add'  ? 'flex' : 'none';
      document.getElementById('editForm').style.display = which === 'edit' ? 'flex' : 'none';
    }

    /* ── ADD modal ── */
    function openAddModal() {
      document.getElementById('modalIcon').className    = 'fas fa-plus';
      document.getElementById('modalTitle').textContent = 'Thêm sản phẩm';
      document.getElementById('modalSub').textContent   = 'Điền thông tin và lưu';
      showForm('add');
      openModal();
      setTimeout(() => document.querySelector('#addForm input[name="name"]').focus(), 320);
    }

    /* ── EDIT modal ── */
    function openEditModal(id) {
      document.getElementById('modalIcon').className    = 'fas fa-pen';
      document.getElementById('modalTitle').textContent = 'Sửa sản phẩm';
      document.getElementById('modalSub').textContent   = 'Cập nhật thông tin sản phẩm';

      document.getElementById('editModalBody').innerHTML = `
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Đang tải...</p>
        </div>`;
      showForm('edit');
      openModal();

      fetch(`get_product.php?id=${id}`)
        .then(r => r.json())
        .then(data => buildEditForm(data))
        .catch(() => {
          document.getElementById('editModalBody').innerHTML =
            `<p style="color:var(--danger);padding:20px;">Không thể tải dữ liệu. Vui lòng thử lại.</p>`;
        });
    }

    function buildEditForm({ product, variants }) {
      const bOpts = brandsData.map(b =>
        `<option value="${b.id}" ${b.id == product.brand_id ? 'selected' : ''}>${esc(b.name)}</option>`
      ).join('');
      const cOpts = categoriesData.map(c =>
        `<option value="${c.id}" ${c.id == product.category_id ? 'selected' : ''}>${esc(c.name)}</option>`
      ).join('');

      const varCards = variants.map((v, i) => `
        <div class="variant-card">
          <div class="variant-card-head">
            <span class="variant-label">Biến thể ${i + 1}</span>
            <button type="button" class="btn-remove-variant"
                    onclick="removeVariant(this,'editVariantList')">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <input type="hidden" name="variant_id[]" value="${v.id}" />
          <div class="variant-grid">
            <div class="form-group">
              <label class="form-label">Tình trạng</label>
              <input class="form-control" name="condition[]"  value="${esc(v.condition)}" />
            </div>
            <div class="form-group">
              <label class="form-label">Tồn kho</label>
              <input class="form-control" name="stock[]"      type="number" value="${v.stock}" />
            </div>
            <div class="form-group">
              <label class="form-label">Giá gốc</label>
              <input class="form-control" name="cost_price[]" type="number" value="${v.cost_price}" />
            </div>
            <div class="form-group">
              <label class="form-label">Giá bán</label>
              <input class="form-control" name="sell_price[]" type="number" value="${v.sell_price}" />
            </div>
          </div>
        </div>`).join('');

      document.getElementById('editModalBody').innerHTML = `
        <input type="hidden" name="id" value="${product.id}" />

        <div class="form-section">
          <div class="form-section-title"><i class="fas fa-info-circle"></i> Thông tin chung</div>
          <div class="form-group">
            <label class="form-label">Tên sản phẩm</label>
            <input class="form-control" name="name" value="${esc(product.name)}" required />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Hãng</label>
              <select class="form-control" name="brand_id">${bOpts}</select>
            </div>
            <div class="form-group">
              <label class="form-label">Danh mục</label>
              <select class="form-control" name="category_id">${cOpts}</select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Mô tả</label>
            <textarea class="form-control" name="description">${esc(product.description ?? '')}</textarea>
          </div>
        </div>

        <div class="form-section">
          <div class="form-section-title"><i class="fas fa-layer-group"></i> Biến thể</div>
          <div class="variant-list" id="editVariantList">${varCards}</div>
          <button type="button" class="btn-add-variant" onclick="addVariant('editVariantList')">
            <i class="fas fa-plus"></i> Thêm biến thể
          </button>
        </div>`;

      setTimeout(() => document.querySelector('#editForm input[name="name"]')?.focus(), 50);
    }

    /* ── Variants ── */
    function addVariant(listId) {
      const list = document.getElementById(listId);
      const node = document.getElementById('variantTpl').content.cloneNode(true);
      const btn  = node.querySelector('.btn-remove-variant');
      btn.addEventListener('click', function() { removeVariant(this, listId); });
      list.appendChild(node);
      renumber(listId);
    }

    function removeVariant(btn, listId) {
      const list = document.getElementById(listId);
      if (list.children.length <= 1) return;
      btn.closest('.variant-card').remove();
      renumber(listId);
    }

    function renumber(listId) {
      document.querySelectorAll(`#${listId} .variant-label`).forEach((el, i) => {
        el.textContent = `Biến thể ${i + 1}`;
      });
    }

    /* ── Live search ── */
    document.getElementById('searchInput').addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      let visible = 0;
      document.querySelectorAll('#tableBody tr[data-name]').forEach(r => {
        const match = r.dataset.name.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visible++;
      });
      document.getElementById('rowCount').textContent = `${visible} sản phẩm`;
    });

    /* ── Helpers ── */
    function showFileNames(input, targetId) {
      document.getElementById(targetId).textContent =
        Array.from(input.files).map(f => f.name).join(', ');
    }
    function esc(str) {
      return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
  </script>
</body>
</html>