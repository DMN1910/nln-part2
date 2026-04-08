<?php
require_once __DIR__ . "/../config/database.php";

$stmtCat = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
$categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

$stmtBrands = $pdo->query("
    SELECT DISTINCT
        c.id   AS category_id,
        b.id   AS brand_id,
        b.name AS brand_name
    FROM categories c
    JOIN products  p ON p.category_id = c.id
    JOIN brands    b ON b.id = p.brand_id
    ORDER BY c.id, b.name
");
$rows = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);

$brandsByCategory = [];
foreach ($rows as $row) {
    $brandsByCategory[$row['category_id']][] = [
        'id'   => $row['brand_id'],
        'name' => $row['brand_name'],
    ];
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap');

:root {
  --cream:        #faf8f4;
  --cream-dark:   #f2ede4;
  --white:        #ffffff;
  --ink:          #1a1714;
  --ink-soft:     #3d3830;
  --muted:        #9a9189;
  --border:       #e4ddd3;
  --border-dark:  #cec5b8;
  --gold:         #b8860b;
  --gold-light:   #d4a017;
  --gold-pale:    #f5ecd0;
  --shadow-sm:    0 1px 4px rgba(26,23,20,.06);
  --shadow-md:    0 4px 20px rgba(26,23,20,.10);
  --shadow-lg:    0 12px 40px rgba(26,23,20,.14);
  --font-display: 'Cormorant Garamond', serif;
  --font-body:    'Outfit', sans-serif;
  --nav-h:        68px;
  --sub-h:        44px;
  --total-h:      calc(var(--nav-h) + var(--sub-h));
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: var(--font-body);
  background: var(--cream);
  color: var(--ink);
  padding-top: var(--total-h);
  -webkit-font-smoothing: antialiased;
}

/* ════ TOP NAV ════ */
.topnav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 1000;
  height: var(--nav-h);
  background: var(--white);
  border-bottom: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}
.topnav .inner {
  max-width: 1360px;
  margin: 0 auto;
  padding: 0 32px;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 28px;
}

/* Logo */
.logo {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  flex-shrink: 0;
}
.logo-icon {
  width: 36px; height: 36px;
  background: var(--ink);
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  color: var(--gold-light);
  font-size: 15px;
}
.logo-text {
  font-family: var(--font-display);
  font-size: 23px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: .5px;
  line-height: 1;
}
.logo-text em { font-style: normal; color: var(--gold); }

/* Search */
.search-wrap { flex: 1; max-width: 400px; margin-left: 8px; }
.search-wrap form {
  display: flex;
  align-items: center;
  background: var(--cream);
  border: 1.5px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
  transition: border-color .2s, box-shadow .2s;
}
.search-wrap form:focus-within {
  border-color: var(--gold);
  box-shadow: 0 0 0 3px rgba(184,134,11,.10);
}
.search-wrap input {
  flex: 1; background: none; border: none; outline: none;
  padding: 10px 14px;
  font-family: var(--font-body);
  font-size: 13.5px;
  color: var(--ink);
}
.search-wrap input::placeholder { color: var(--muted); }
.search-wrap button {
  background: none; border: none;
  padding: 0 14px;
  color: var(--muted); cursor: pointer;
  font-size: 13px;
  transition: color .2s;
}
.search-wrap button:hover { color: var(--gold); }

/* Actions */
.nav-actions {
  margin-left: auto;
  display: flex; align-items: center; gap: 4px;
}
.nav-btn {
  display: flex; align-items: center; gap: 6px;
  padding: 8px 13px;
  border-radius: 7px;
  text-decoration: none;
  font-size: 13px; font-weight: 500;
  color: var(--muted);
  transition: background .2s, color .2s;
  white-space: nowrap;
}
.nav-btn:hover { background: var(--cream); color: var(--ink); }

.nav-btn.cart {
  position: relative;
  background: var(--cream-dark);
  border: 1.5px solid var(--border);
  color: var(--ink-soft);
  font-size: 15px; padding: 8px 14px;
}
.nav-btn.cart:hover {
  border-color: var(--gold);
  color: var(--gold);
  background: var(--gold-pale);
}
.cart-badge {
  position: absolute; top: 3px; right: 3px;
  background: var(--gold); color: var(--white);
  font-size: 9px; font-weight: 700;
  min-width: 16px; height: 16px;
  border-radius: 999px;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
}

.user-greet { font-size: 13px; color: var(--muted); padding: 0 4px; }
.user-greet strong { color: var(--ink); font-weight: 600; }

.btn-primary {
  background: var(--ink); color: var(--white) !important;
  font-weight: 600; padding: 9px 20px;
  border-radius: 7px; text-decoration: none;
  font-size: 13px;
  transition: background .2s, transform .15s;
}
.btn-primary:hover { background: var(--ink-soft); transform: translateY(-1px); }

.nav-divider { width: 1px; height: 22px; background: var(--border); margin: 0 6px; }

/* ════ CATEGORY BAR ════ */
.catbar {
  position: fixed;
  top: var(--nav-h); left: 0; right: 0;
  z-index: 999;
  height: var(--sub-h);
  background: var(--cream-dark);
  border-bottom: 1px solid var(--border);
}
.catbar .inner {
  max-width: 1360px; margin: 0 auto;
  padding: 0 32px; height: 100%;
  display: flex; align-items: stretch;
}

.cat-item { position: relative; }

.cat-link {
  display: flex; align-items: center; gap: 5px;
  height: 100%; padding: 0 15px;
  text-decoration: none;
  font-size: 12.5px; font-weight: 500;
  color: var(--muted);
  white-space: nowrap;
  border-bottom: 2px solid transparent;
  transition: color .2s, border-color .2s;
}
.cat-link .chevron { font-size: 9px; transition: transform .2s; }
.cat-link:hover, .cat-item:hover .cat-link {
  color: var(--ink); border-bottom-color: var(--gold);
}
.cat-item:hover .cat-link .chevron { transform: rotate(180deg); }
.cat-link.all { color: var(--ink); font-weight: 600; }

/* Dropdown */
.cat-dropdown {
  display: none;
  position: absolute;
  top: calc(100% + 1px); left: 0;
  min-width: 220px;
  background: var(--white);
  border: 1px solid var(--border);
  border-top: 2px solid var(--gold);
  border-radius: 0 0 10px 10px;
  padding: 8px 0;
  box-shadow: var(--shadow-lg);
  z-index: 1100;
  animation: dropIn .15s ease;
}
@keyframes dropIn {
  from { opacity:0; transform:translateY(-5px); }
  to   { opacity:1; transform:translateY(0); }
}
.cat-item:hover .cat-dropdown { display: block; }

.dropdown-header {
  padding: 6px 16px 4px;
  font-size: 9.5px; font-weight: 600;
  letter-spacing: 1.8px; text-transform: uppercase;
  color: var(--gold);
}
.brand-link {
  display: flex; align-items: center; gap: 10px;
  padding: 8px 16px;
  text-decoration: none; font-size: 13px;
  color: var(--ink-soft);
  transition: background .15s, color .15s;
}
.brand-link:hover { background: var(--gold-pale); color: var(--ink); }
.brand-link .dot {
  width: 4px; height: 4px; border-radius: 50%;
  background: var(--border-dark); flex-shrink: 0;
  transition: background .15s;
}
.brand-link:hover .dot { background: var(--gold); }
.dropdown-footer {
  padding: 5px 16px 2px;
  border-top: 1px solid var(--border);
  margin-top: 4px;
}
.dropdown-footer .brand-link { color: var(--gold); font-weight: 600; font-size: 12.5px; }
.no-brands { padding: 8px 16px 10px; font-size: 12px; color: var(--muted); font-style: italic; }
</style>

<nav class="topnav">
  <div class="inner">
    <a class="logo" href="<?= BASE_URL ?>/index.php">
      <div class="logo-icon"><i class="fa-solid fa-camera"></i></div>
      <span class="logo-text">LENS<em>&</em>CLICK</span>
    </a>

    <div class="search-wrap">
      <form action="<?= BASE_URL ?>/search.php" method="get">
        <input type="text" name="search" placeholder="Tìm máy ảnh, ống kính...">
        <button type="submit"><i class="fas fa-search"></i></button>
      </form>
    </div>

    <div class="nav-actions">
      <a href="<?= BASE_URL ?>/cart/cart.php" class="nav-btn cart">
        <i class="fas fa-shopping-bag"></i>
        <span class="cart-badge" id="cart-count">
          <?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0 ?>
        </span>
      </a>
      <div class="nav-divider"></div>

      <?php if (isset($_SESSION['user'])): ?>
        <a href="<?= BASE_URL ?>/profile.php" class="nav-btn user-greet" style="padding: 8px 12px;">
          Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'User') ?></strong>
        </a>
        
        <a class="nav-btn" href="<?= BASE_URL ?>/logout.php">
          <i class="fas fa-right-from-bracket"></i> Đăng xuất
        </a>
      <?php else: ?>
        <a class="nav-btn" href="<?= BASE_URL ?>/login.php">
          <i class="fas fa-right-to-bracket"></i> Đăng nhập
        </a>
        <a class="btn-primary" href="<?= BASE_URL ?>/register.php">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!--  CATEGORY BAR  -->
<div class="catbar">
  <div class="inner">
    <div class="cat-item">
      <a class="cat-link all" href="<?= BASE_URL ?>/index.php">
        <i class="fa-solid fa-border-all" style="font-size:11px;"></i> Tất cả
      </a>
    </div>

    <?php foreach ($categories as $cat): ?>
      <div class="cat-item">
        <a class="cat-link" href="<?= BASE_URL ?>/index.php?category_id=<?= $cat['id'] ?>">
          <?= htmlspecialchars($cat['name']) ?>
          <?php if (!empty($brandsByCategory[$cat['id']])): ?>
            <i class="fas fa-chevron-down chevron"></i>
          <?php endif; ?>
        </a>
        <div class="cat-dropdown">
          <?php if (!empty($brandsByCategory[$cat['id']])): ?>
            <div class="dropdown-header">Thương hiệu</div>
            <?php foreach ($brandsByCategory[$cat['id']] as $brand): ?>
              <a class="brand-link"
                 href="<?= BASE_URL ?>/index.php?category_id=<?= $cat['id'] ?>&brand_id=<?= $brand['id'] ?>">
                <span class="dot"></span><?= htmlspecialchars($brand['name']) ?>
              </a>
            <?php endforeach; ?>
            <div class="dropdown-footer">
              <a class="brand-link" href="<?= BASE_URL ?>/index.php?category_id=<?= $cat['id'] ?>">
                <i class="fas fa-arrow-right" style="font-size:10px;"></i> Xem tất cả
              </a>
            </div>
          <?php else: ?>
            <div class="no-brands">Chưa có thương hiệu</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>