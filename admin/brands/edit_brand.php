<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/config.php";

/* Kiểm tra quyền admin */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /camerashop/login.php");
    exit;
}

/* Lấy ID từ URL */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: brands.php");
    exit;
}

$id = (int)$_GET['id'];

/* Lấy thông tin thương hiệu */
$stmt = $pdo->prepare("SELECT * FROM brands WHERE id = ?");
$stmt->execute([$id]);
$brand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$brand) {
    header("Location: brands.php");
    exit;
}

$error = '';
$success = '';

/* Xử lý cập nhật */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $name = trim($_POST['name']);
    if ($name === '') {
        $error = 'Tên thương hiệu không được để trống.';
    } else {
        // Kiểm tra trùng tên (trừ chính nó)
        $check = $pdo->prepare("SELECT id FROM brands WHERE name = ? AND id != ?");
        $check->execute([$name, $id]);
        if ($check->fetch()) {
            $error = 'Thương hiệu "' . htmlspecialchars($name) . '" đã tồn tại.';
        } else {
            $stmt = $pdo->prepare("UPDATE brands SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            header("Location: brands.php?updated=1");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sửa thương hiệu - Admin</title>
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

    /* Breadcrumb */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted); margin-bottom: 14px;
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
      text-decoration: none; margin-bottom: 32px;
      box-shadow: var(--shadow-sm); transition: all .2s;
    }
    .btn-back:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }

    /* Page Header */
    .page-header {
      display: flex; align-items: flex-start; gap: 20px;
      margin-bottom: 36px; padding-bottom: 24px;
      border-bottom: 1.5px solid var(--border);
    }
    .header-icon {
      width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
      background: var(--gold-pale); border: 1.5px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; color: var(--gold);
    }
    .gold-line {
      width: 40px; height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px; margin-bottom: 8px;
    }
    .page-title { font-family: var(--font-display); font-size: 36px; font-weight: 700; color: var(--ink); }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    /* Form Card */
    .form-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      max-width: 600px;
    }

    .form-card-header {
      padding: 20px 28px;
      background: var(--cream);
      border-bottom: 1.5px solid var(--border);
      display: flex; align-items: center; gap: 12px;
    }
    .form-card-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: var(--gold-pale); border: 1px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      color: var(--gold); font-size: 15px;
    }
    .form-card-title { font-size: 15px; font-weight: 600; color: var(--ink); }
    .form-card-sub { font-size: 12px; color: var(--muted); margin-top: 1px; }

    .form-body { padding: 28px; }

    /* ID Info Badge */
    .id-info {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--cream-dark); border: 1px solid var(--border);
      color: var(--muted); font-size: 12.5px; font-weight: 500;
      padding: 8px 14px; border-radius: 8px; margin-bottom: 24px;
    }
    .id-info i { color: var(--gold); }

    .form-group { margin-bottom: 22px; }
    .form-label {
      display: block; font-size: 11.5px; font-weight: 600;
      letter-spacing: .8px; text-transform: uppercase;
      color: var(--muted); margin-bottom: 8px;
    }
    .form-label span { color: var(--danger); }

    .input-wrap { position: relative; }
    .input-wrap i {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--muted); font-size: 14px; pointer-events: none;
    }
    .form-input {
      width: 100%; padding: 13px 14px 13px 44px;
      border: 1.5px solid var(--border); border-radius: 10px;
      font-size: 15px; background: var(--cream);
      font-family: var(--font-body); outline: none; transition: all .2s;
      color: var(--ink);
    }
    .form-input:focus {
      border-color: var(--gold); background: var(--white);
      box-shadow: 0 0 0 3px rgba(184,134,11,.10);
    }
    .form-input.is-error { border-color: var(--danger); background: #fef2f2; }
    .form-input.is-error:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.10); }

    /* Alert */
    .alert {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px 16px; border-radius: 10px; margin-bottom: 22px;
      font-size: 14px; line-height: 1.5;
    }
    .alert-error { background: #fef2f2; border: 1.5px solid #fecaca; color: #991b1b; }
    .alert-success { background: #f0fdf4; border: 1.5px solid #bbf7d0; color: #166534; }
    .alert i { margin-top: 1px; flex-shrink: 0; }

    /* Hint */
    .form-hint { font-size: 12px; color: var(--muted); margin-top: 6px; }

    /* Divider */
    .divider { height: 1px; background: var(--border); margin: 24px 0; }

    /* Form Footer */
    .form-footer {
      padding: 20px 28px;
      border-top: 1.5px solid var(--border);
      background: var(--cream);
      display: flex; align-items: center; justify-content: space-between; gap: 12px;
    }
    .btn-cancel {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 10px 20px; border-radius: 9px;
      background: var(--white); border: 1.5px solid var(--border);
      color: var(--muted); font-family: var(--font-body);
      font-size: 14px; font-weight: 500; cursor: pointer;
      text-decoration: none; transition: all .2s;
    }
    .btn-cancel:hover { border-color: var(--border-dark); color: var(--ink); }

    .btn-save {
      display: inline-flex; align-items: center; gap: 8px;
      padding: 10px 28px; border-radius: 9px;
      background: var(--ink); color: white; border: none;
      font-family: var(--font-body); font-size: 14px; font-weight: 600;
      cursor: pointer; transition: all .2s; box-shadow: var(--shadow-sm);
    }
    .btn-save:hover { background: var(--ink-soft); transform: translateY(-1px); box-shadow: var(--shadow-md); }
    .btn-save:active { transform: translateY(0); }

    /* Changed indicator */
    .changed-badge {
      display: none; align-items: center; gap: 6px;
      font-size: 12px; color: var(--gold); font-weight: 500;
    }
    .changed-badge.visible { display: flex; }
    .changed-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--gold); }
  </style>
</head>
<body>

  <!-- Breadcrumb -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <a href="brands.php"><i class="fas fa-trademark"></i> Thương hiệu</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Sửa thương hiệu</span>
  </nav>

  <a class="btn-back" href="brands.php">
    <i class="fas fa-arrow-left"></i> Quay lại danh sách
  </a>

  <!-- Header -->
  <div class="page-header">
    <div class="header-icon"><i class="fas fa-pen"></i></div>
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Sửa thương hiệu</h1>
      <p class="page-sub">Cập nhật thông tin thương hiệu trong hệ thống</p>
    </div>
  </div>

  <!-- Form Card -->
  <div class="form-card">
    <div class="form-card-header">
      <div class="form-card-icon"><i class="fas fa-trademark"></i></div>
      <div>
        <div class="form-card-title">Thông tin thương hiệu</div>
        <div class="form-card-sub">Chỉnh sửa tên thương hiệu bên dưới</div>
      </div>
    </div>

    <div class="form-body">

      <!-- ID Badge -->
      <div class="id-info">
        <i class="fas fa-hashtag"></i>
        ID thương hiệu: <strong>#<?= $brand['id'] ?></strong>
      </div>

      <!-- Alert lỗi -->
      <?php if ($error): ?>
        <div class="alert alert-error">
          <i class="fas fa-circle-exclamation"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="editForm">
        <div class="form-group">
          <label class="form-label" for="brandName">Tên thương hiệu <span>*</span></label>
          <div class="input-wrap">
            <i class="fas fa-trademark"></i>
            <input
              type="text"
              id="brandName"
              name="name"
              class="form-input <?= $error ? 'is-error' : '' ?>"
              value="<?= htmlspecialchars($error ? ($_POST['name'] ?? '') : $brand['name']) ?>"
              placeholder="Ví dụ: Canon, Sony, Nikon..."
              required
              autocomplete="off"
            />
          </div>
          <div class="form-hint">Tên sẽ hiển thị trong danh sách sản phẩm và bộ lọc.</div>
        </div>
      </form>
    </div>

    <div class="form-footer">
      <div class="changed-badge" id="changedBadge">
        <div class="changed-dot"></div> Có thay đổi chưa lưu
      </div>
      <div style="display:flex; gap:10px; margin-left:auto;">
        <a href="brands.php" class="btn-cancel">
          <i class="fas fa-xmark"></i> Huỷ
        </a>
        <button type="submit" form="editForm" class="btn-save">
          <i class="fas fa-floppy-disk"></i> Lưu thay đổi
        </button>
      </div>
    </div>
  </div>

  <script>
    const originalName = <?= json_encode($brand['name']) ?>;
    const input = document.getElementById('brandName');
    const badge = document.getElementById('changedBadge');

    input.addEventListener('input', () => {
      if (input.value.trim() !== originalName) {
        badge.classList.add('visible');
      } else {
        badge.classList.remove('visible');
      }
    });

    // Focus input on load
    input.focus();
    input.select();
  </script>
</body>
</html>