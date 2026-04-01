<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/config.php";

/* ✅ Chặn nếu không phải admin */
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /camerashop/login.php");
    exit;
}

/* ✅ Xử lý đổi role */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role'])) {
    $user_id = (int)$_POST['user_id'];
    $role = $_POST['role'];

    if (in_array($role, ['user', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$role, $user_id]);

        if ($user_id === $_SESSION['user']['id']) {
            $_SESSION['user']['role'] = $role;
        }
    }
    header("Location: user.php");
    exit;
}

/* ✅ Xử lý xóa người dùng */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Không cho xóa chính mình
    if ($delete_id !== $_SESSION['user']['id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$delete_id]);
    }
    header("Location: user.php");
    exit;
}

/* Load danh sách user */
$stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quản lý người dùng - Admin</title>
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

    /* Breadcrumb & Back */
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
    .btn-back:hover {
      border-color: var(--gold); color: var(--gold); background: var(--gold-pale);
    }

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
    .page-title {
      font-family: var(--font-display);
      font-size: 36px; font-weight: 700; color: var(--ink);
    }
    .page-sub { font-size: 13px; color: var(--muted); margin-top: 6px; }

    /* Table Card */
    .table-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
    }
    .table-toolbar {
      padding: 20px 26px;
      border-bottom: 1.5px solid var(--border);
      background: var(--cream);
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 12px;
    }
    .search-wrap {
      position: relative; flex: 1; max-width: 340px;
    }
    .search-wrap i {
      position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
      color: var(--muted); font-size: 14px;
    }
    .search-input {
      width: 100%; padding: 10px 14px 10px 44px;
      border: 1.5px solid var(--border); border-radius: 9px;
      background: var(--cream); font-size: 14px;
      outline: none; transition: all .2s;
    }
    .search-input:focus {
      border-color: var(--gold); background: var(--white);
    }

    .row-count {
      font-size: 13px; color: var(--muted);
      background: var(--gold-pale); padding: 6px 14px;
      border-radius: 6px; border: 1px solid var(--gold-border);
    }

    table { width: 100%; border-collapse: collapse; }
    thead th {
      text-align: left; font-size: 10.8px; font-weight: 600;
      letter-spacing: 1.4px; text-transform: uppercase;
      color: var(--muted); padding: 16px 26px;
      background: var(--cream);
    }
    thead th:last-child { text-align: right; }

    tbody tr { border-bottom: 1px solid var(--border); transition: background .2s; }
    tbody tr:hover { background: var(--gold-pale); }
    tbody td { padding: 16px 26px; font-size: 14px; vertical-align: middle; }

    .id-badge {
      background: var(--cream-dark); border: 1px solid var(--border);
      color: var(--muted); font-size: 11.5px; font-weight: 600;
      padding: 4px 10px; border-radius: 6px;
    }

    .role-badge {
      padding: 6px 16px; border-radius: 20px; font-size: 12.8px; font-weight: 500;
    }
    .role-admin { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
    .role-user  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

    .actions {
      display: flex; align-items: center; gap: 8px; justify-content: flex-end;
    }
    .btn-action {
      padding: 7px 14px; border-radius: 7px; font-size: 13px; font-weight: 500;
      text-decoration: none; border: 1.5px solid transparent; cursor: pointer;
      transition: all .2s;
    }
    .btn-edit {
      background: var(--cream); border-color: var(--border); color: var(--ink-soft);
    }
    .btn-edit:hover {
      background: var(--ink); color: white; border-color: var(--ink);
    }
    .btn-delete {
      background: #fef2f2; border-color: #fecaca; color: var(--danger);
    }
    .btn-delete:hover {
      background: var(--danger); color: white; border-color: var(--danger);
    }

    .self-row {
      color: var(--muted); font-style: italic; font-size: 13.5px;
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
    <span class="current">Quản lý người dùng</span>
  </nav>

  <!-- Back button -->
  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về Dashboard
  </a>

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Quản lý người dùng</h1>
      <p class="page-sub">Quản lý tài khoản khách hàng và phân quyền hệ thống</p>
    </div>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-toolbar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Tìm theo tên hoặc email..." />
      </div>
      <div class="row-count" id="rowCount"><?= count($users) ?> tài khoản</div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên người dùng</th>
          <th>Email</th>
          <th>Vai trò</th>
          <th style="text-align: right;">Thao tác</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <?php foreach ($users as $u): ?>
          <tr data-name="<?= strtolower(htmlspecialchars($u['name'])) ?>" data-email="<?= strtolower(htmlspecialchars($u['email'])) ?>">
            <td><span class="id-badge">#<?= $u['id'] ?></span></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="role-badge <?= $u['role'] === 'admin' ? 'role-admin' : 'role-user' ?>">
                <?= strtoupper($u['role']) ?>
              </span>
            </td>
            <td>
              <div class="actions">
                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" class="form-select" style="width:auto; padding:6px 10px; font-size:13px;">
                      <option value="user"  <?= $u['role'] === 'user'  ? 'selected' : '' ?>>User</option>
                      <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                    <button type="submit" class="btn-action btn-edit">Lưu</button>
                  </form>

                  <a href="user.php?delete=<?= $u['id'] ?>" 
                     class="btn-action btn-delete"
                     onclick="return confirm('Xóa người dùng <?= htmlspecialchars($u['name']) ?>?\nHành động này không thể hoàn tác!')">
                    <i class="fas fa-trash"></i> Xóa
                  </a>
                <?php else: ?>
                  <span class="self-row">root</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script>
    // Live Search
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('#tableBody tr');
    const rowCount = document.getElementById('rowCount');

    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase().trim();
      let visible = 0;

      rows.forEach(row => {
        const name = row.dataset.name || '';
        const email = row.dataset.email || '';
        
        if (name.includes(query) || email.includes(query)) {
          row.style.display = '';
          visible++;
        } else {
          row.style.display = 'none';
        }
      });

      rowCount.textContent = `${visible} tài khoản`;
    });
  </script>
</body>
</html>