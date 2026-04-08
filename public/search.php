<?php
require_once "../config/config.php";
require_once "../config/database.php";
include "../includes/header.php";
include "../includes/navbar.php";

$q           = trim($_GET['search']      ?? '');
$category_id = (int)($_GET['category_id'] ?? 0) ?: null;
$brand_id    = (int)($_GET['brand_id']    ?? 0) ?: null;
$price_min   = (int)($_GET['price_min']   ?? 0) ?: null;
$price_max   = (int)($_GET['price_max']   ?? 0) ?: null;
$sort        = $_GET['sort'] ?? 'newest';

$allCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")
                     ->fetchAll(PDO::FETCH_ASSOC);

if ($category_id) {
    $bStmt = $pdo->prepare("
        SELECT DISTINCT b.id, b.name FROM brands b
        JOIN products p ON p.brand_id = b.id
        WHERE p.category_id = ?
        ORDER BY b.name
    ");
    $bStmt->execute([$category_id]);
} else {
    $bStmt = $pdo->query("
        SELECT DISTINCT b.id, b.name FROM brands b
        JOIN products p ON p.brand_id = b.id
        ORDER BY b.name
    ");
}
$allBrands = $bStmt->fetchAll(PDO::FETCH_ASSOC);

$maxPriceDB = (int)($pdo->query("SELECT COALESCE(MAX(sell_price),50000000) FROM product_variants")->fetchColumn());

$sql = "
    SELECT
        p.id, p.name, p.description,
        b.name AS brand_name,
        c.name AS category_name,
        MIN(pv.sell_price) AS min_price,
        MAX(pv.sell_price) AS max_price,
        SUM(pv.stock)      AS total_stock,
        (SELECT pi.image_path FROM product_images pi
         WHERE pi.product_id = p.id LIMIT 1) AS thumbnail
    FROM products p
    LEFT JOIN brands    b  ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE 1=1
";
$params = [];

if ($q !== '') {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR b.name LIKE ? OR c.name LIKE ?)";
    $like = "%$q%";
    array_push($params, $like, $like, $like, $like);
}
if ($category_id) { $sql .= " AND p.category_id = ?"; $params[] = $category_id; }
if ($brand_id)    { $sql .= " AND p.brand_id = ?";    $params[] = $brand_id; }

$sql .= " GROUP BY p.id, p.name, p.description, b.name, c.name";

if ($price_min) { $sql .= " HAVING min_price >= ?"; $params[] = $price_min; }
if ($price_max) { $sql .= ($price_min ? " AND" : " HAVING") . " min_price <= ?"; $params[] = $price_max; }

$orderMap = [
    'newest'    => 'p.id DESC',
    'price_asc' => 'min_price ASC',
    'price_desc'=> 'min_price DESC',
    'name_asc'  => 'p.name ASC',
];
$sql .= " ORDER BY " . ($orderMap[$sort] ?? 'p.id DESC');

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Label cho category / brand đang chọn
$categoryName = '';
if ($category_id) {
    foreach ($allCategories as $c) {
        if ($c['id'] == $category_id) { $categoryName = $c['name']; break; }
    }
}
$brandName = '';
if ($brand_id) {
    foreach ($allBrands as $b) {
        if ($b['id'] == $brand_id) { $brandName = $b['name']; break; }
    }
    // fallback nếu brand không thuộc category filter
    if (!$brandName) {
        $fb = $pdo->prepare("SELECT name FROM brands WHERE id = ?");
        $fb->execute([$brand_id]);
        $brandName = $fb->fetchColumn() ?: '';
    }
}

// Giá hiển thị trên slider (mặc định = max DB)
$sliderMax = $price_max ?: $maxPriceDB;
$sliderMin = $price_min ?: 0;
?>

<style>
  /* ── Layout ── */
  .search-wrapper {
    max-width: 1360px;
    margin: 0 auto;
    padding: 48px 32px 100px;
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
    align-items: start;
  }

  /* ── Sidebar ── */
  .sidebar { position: sticky; top: calc(var(--total-h, 112px) + 20px); }

  .sidebar-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    margin-bottom: 16px;
  }
  .sidebar-head {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border);
    background: var(--cream);
    display: flex; align-items: center; gap: 8px;
  }
  .sidebar-head-title {
    font-size: 11px; font-weight: 600;
    letter-spacing: 1.4px; text-transform: uppercase; color: var(--muted);
  }
  .sidebar-head-title i { color: var(--gold); font-size: 10px; }
  .sidebar-body { padding: 14px 18px; }

  /* Category list */
  .filter-list { list-style: none; display: flex; flex-direction: column; gap: 2px; }
  .filter-list a {
    display: flex; align-items: center; justify-content: space-between;
    padding: 7px 10px; border-radius: 7px;
    text-decoration: none; font-size: 13px; color: var(--muted);
    transition: background .15s, color .15s;
  }
  .filter-list a:hover { background: var(--cream); color: var(--ink); }
  .filter-list a.active {
    background: var(--gold-pale); color: var(--gold); font-weight: 600;
    border: 1px solid rgba(184,134,11,.2);
  }
  .filter-count {
    font-size: 11px; color: var(--border-dark);
    background: var(--cream-dark); border-radius: 99px;
    padding: 1px 7px; min-width: 24px; text-align: center;
  }

  /* Price range */
  .price-inputs { display: flex; gap: 8px; margin-bottom: 14px; }
  .price-input-wrap { flex: 1; }
  .price-input-label {
    font-size: 10.5px; font-weight: 600; letter-spacing: .6px;
    text-transform: uppercase; color: var(--muted); margin-bottom: 5px;
  }
  .price-input {
    width: 100%; padding: 8px 10px;
    border: 1.5px solid var(--border); border-radius: 7px;
    font-family: var(--font-body); font-size: 12.5px; color: var(--ink);
    background: var(--cream); outline: none;
    transition: border-color .2s;
  }
  .price-input:focus { border-color: var(--gold); background: var(--white); }

  .range-wrap { position: relative; height: 20px; margin: 8px 0 16px; }
  .range-track {
    position: absolute; top: 50%; transform: translateY(-50%);
    left: 0; right: 0; height: 4px;
    background: var(--border); border-radius: 99px;
  }
  .range-fill {
    position: absolute; height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
  }
  input[type="range"] {
    position: absolute; width: 100%; top: 50%; transform: translateY(-50%);
    -webkit-appearance: none; appearance: none;
    background: transparent; pointer-events: none;
  }
  input[type="range"]::-webkit-slider-thumb {
    -webkit-appearance: none; appearance: none;
    width: 18px; height: 18px; border-radius: 50%;
    background: var(--white); border: 2.5px solid var(--gold);
    box-shadow: 0 1px 6px rgba(0,0,0,.15);
    cursor: pointer; pointer-events: all;
    transition: transform .15s;
  }
  input[type="range"]::-webkit-slider-thumb:hover { transform: scale(1.15); }

  .price-display {
    display: flex; justify-content: space-between;
    font-size: 12px; color: var(--muted); margin-top: 4px;
  }
  .price-display span { color: var(--ink); font-weight: 600; }

  .btn-apply {
    width: 100%; padding: 9px;
    background: var(--ink); color: var(--white);
    border: none; border-radius: 8px;
    font-family: var(--font-body); font-size: 13px; font-weight: 600;
    cursor: pointer; margin-top: 10px;
    transition: background .2s;
  }
  .btn-apply:hover { background: var(--ink-soft); }

  .btn-reset {
    display: block; text-align: center; margin-top: 8px;
    font-size: 12px; color: var(--muted); text-decoration: none;
    transition: color .2s;
  }
  .btn-reset:hover { color: var(--danger, #dc2626); }

  /* ── Main content ── */
  .main-content {}

  /* Toolbar */
  .toolbar {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 24px; flex-wrap: wrap;
  }
  .toolbar-title {
    font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--ink);
  }
  .toolbar-sub { font-size: 13px; color: var(--muted); margin-top: 2px; }
  .gold-line {
    width: 40px; height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    border-radius: 2px; margin-bottom: 6px;
  }

  .sort-wrap { margin-left: auto; }
  .sort-select {
    padding: 8px 12px; border: 1.5px solid var(--border); border-radius: 8px;
    font-family: var(--font-body); font-size: 13px; color: var(--ink);
    background: var(--white); outline: none; cursor: pointer;
    transition: border-color .2s;
  }
  .sort-select:focus { border-color: var(--gold); }

  /* Active filters */
  .filter-bar {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;
  }
  .filter-tag {
    display: flex; align-items: center; gap: 6px;
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 7px; padding: 4px 10px;
    font-size: 12px; color: var(--muted);
  }
  .filter-tag span { color: var(--ink); font-weight: 500; }
  .filter-tag a {
    color: var(--muted); text-decoration: none; font-size: 12px;
    width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
    border-radius: 50%; transition: background .2s, color .2s;
  }
  .filter-tag a:hover { background: var(--border); color: var(--ink); }

  /* Product grid */
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
    gap: 20px;
  }
  .product-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 12px; overflow: hidden;
    display: flex; flex-direction: column;
    text-decoration: none; color: inherit;
    transition: border-color .25s, transform .25s, box-shadow .25s;
    box-shadow: var(--shadow-sm);
  }
  .product-card:hover { border-color: var(--gold); transform: translateY(-5px); box-shadow: var(--shadow-md); }
  .card-thumb {
    position: relative; aspect-ratio: 4/3;
    overflow: hidden; background: var(--cream-dark);
  }
  .card-thumb img { width:100%; height:100%; object-fit:cover; transition:transform .45s ease; }
  .product-card:hover .card-thumb img { transform: scale(1.06); }
  .no-img { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:44px; color:var(--border-dark); }
  .stock-badge {
    position:absolute; top:10px; right:10px;
    font-size:10px; font-weight:600; padding:3px 9px; border-radius:999px; letter-spacing:.3px;
  }
  .stock-badge.in-stock { background:rgba(22,163,74,.1); color:#16a34a; border:1px solid rgba(22,163,74,.25); }
  .stock-badge.out-stock { background:rgba(220,38,38,.08); color:#dc2626; border:1px solid rgba(220,38,38,.2); }
  .card-brand {
    position:absolute; bottom:10px; left:10px;
    font-size:9.5px; font-weight:600; letter-spacing:1px; text-transform:uppercase;
    background:rgba(255,255,255,.85); backdrop-filter:blur(6px);
    border:1px solid var(--border); color:var(--ink-soft);
    padding:3px 8px; border-radius:4px;
  }
  .card-body { padding:16px 16px 10px; flex:1; display:flex; flex-direction:column; gap:7px; }
  .card-name { font-size:13.5px; font-weight:600; color:var(--ink); line-height:1.45; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .card-desc { font-size:12px; color:var(--muted); line-height:1.55; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; flex:1; }
  .card-price { display:flex; align-items:baseline; gap:5px; margin-top:4px; }
  .price-from { font-size:11px; color:var(--muted); }
  .price-value { font-family:var(--font-display); font-size:20px; font-weight:700; color:var(--gold); }
  .price-none { font-size:13px; color:var(--muted); font-style:italic; }
  .card-footer { padding:0 16px 16px; }
  .btn-view {
    display:block; text-align:center; background:var(--cream);
    border:1.5px solid var(--border); color:var(--muted); border-radius:8px;
    padding:8px 12px; font-size:13px; font-weight:500; text-decoration:none;
    transition:background .2s, border-color .2s, color .2s;
  }
  .product-card:hover .btn-view { background:var(--ink); border-color:var(--ink); color:var(--white); font-weight:600; }

  /* Empty state */
  .empty-state { text-align:center; padding:80px 24px; }
  .empty-icon { font-size:52px; color:var(--border-dark); margin-bottom:24px; }
  .empty-state h3 { font-family:var(--font-display); font-size:28px; font-weight:700; color:var(--ink); margin-bottom:10px; }
  .empty-state p { font-size:14px; color:var(--muted); }
  .btn-back {
    display:inline-block; margin-top:24px; background:var(--ink); color:var(--white);
    font-weight:600; padding:11px 28px; border-radius:8px; text-decoration:none;
    font-size:13.5px; transition:background .2s;
  }
  .btn-back:hover { background:var(--ink-soft); }

  @media (max-width: 900px) {
    .search-wrapper { grid-template-columns: 1fr; }
    .sidebar { position: static; }
  }
</style>

<div class="search-wrapper">

  <!--  SIDEBAR  -->
  <aside class="sidebar">

    <!-- Danh mục -->
    <div class="sidebar-card">
      <div class="sidebar-head">
        <div class="sidebar-head-title"><i class="fas fa-th-large"></i> Danh mục</div>
      </div>
      <div class="sidebar-body">
        <ul class="filter-list">
          <li>
            <a href="<?= BASE_URL ?>/search.php?search=<?= urlencode($q) ?>&sort=<?= $sort ?><?= $price_min ? '&price_min='.$price_min : '' ?><?= $price_max ? '&price_max='.$price_max : '' ?>"
               class="<?= !$category_id ? 'active' : '' ?>">
              Tất cả <span class="filter-count">—</span>
            </a>
          </li>
          <?php foreach ($allCategories as $cat): ?>
            <li>
              <a href="<?= BASE_URL ?>/search.php?search=<?= urlencode($q) ?>&category_id=<?= $cat['id'] ?>&sort=<?= $sort ?><?= $price_min ? '&price_min='.$price_min : '' ?><?= $price_max ? '&price_max='.$price_max : '' ?>"
                 class="<?= $category_id == $cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <!-- Thương hiệu -->
    <?php if (!empty($allBrands)): ?>
    <div class="sidebar-card">
      <div class="sidebar-head">
        <div class="sidebar-head-title"><i class="fas fa-tag"></i> Thương hiệu</div>
      </div>
      <div class="sidebar-body">
        <ul class="filter-list">
          <?php foreach ($allBrands as $br): ?>
            <li>
              <a href="<?= BASE_URL ?>/search.php?search=<?= urlencode($q) ?><?= $category_id ? '&category_id='.$category_id : '' ?>&brand_id=<?= $br['id'] ?>&sort=<?= $sort ?><?= $price_min ? '&price_min='.$price_min : '' ?><?= $price_max ? '&price_max='.$price_max : '' ?>"
                 class="<?= $brand_id == $br['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($br['name']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- Lọc giá -->
    <div class="sidebar-card">
      <div class="sidebar-head">
        <div class="sidebar-head-title"><i class="fas fa-coins"></i> Khoảng giá</div>
      </div>
      <div class="sidebar-body">
        <form method="GET" action="<?= BASE_URL ?>/search.php" id="priceForm">
          <input type="hidden" name="search"      value="<?= htmlspecialchars($q) ?>">
          <?php if ($category_id): ?>
            <input type="hidden" name="category_id" value="<?= $category_id ?>">
          <?php endif; ?>
          <?php if ($brand_id): ?>
            <input type="hidden" name="brand_id" value="<?= $brand_id ?>">
          <?php endif; ?>
          <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
          <input type="hidden" name="price_min" id="hiddenMin" value="<?= $sliderMin ?>">
          <input type="hidden" name="price_max" id="hiddenMax" value="<?= $sliderMax ?>">

          <!-- Dual range slider -->
          <div class="range-wrap">
            <div class="range-track">
              <div class="range-fill" id="rangeFill"></div>
            </div>
            <input type="range" id="rangeMin" min="0" max="<?= $maxPriceDB ?>"
                   value="<?= $sliderMin ?>" step="500000">
            <input type="range" id="rangeMax" min="0" max="<?= $maxPriceDB ?>"
                   value="<?= $sliderMax ?>" step="500000">
          </div>

          <div class="price-display">
            <span id="displayMin"><?= number_format($sliderMin, 0, ',', '.') ?>₫</span>
            <span id="displayMax"><?= number_format($sliderMax, 0, ',', '.') ?>₫</span>
          </div>

          <button type="submit" class="btn-apply">
            <i class="fas fa-filter"></i> Áp dụng
          </button>

          <?php if ($price_min || $price_max): ?>
            <a class="btn-reset" href="<?= BASE_URL ?>/search.php?search=<?= urlencode($q) ?><?= $category_id ? '&category_id='.$category_id : '' ?><?= $brand_id ? '&brand_id='.$brand_id : '' ?>&sort=<?= $sort ?>">
              ✕ Xoá lọc giá
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

  </aside>

  <!-- ══ MAIN ══ -->
  <main class="main-content">

    <!-- Toolbar -->
    <div class="toolbar">
      <div>
        <div class="gold-line"></div>
        <div class="toolbar-title">
          <?= $q !== '' ? 'Kết quả: "' . htmlspecialchars($q) . '"' : 'Tìm kiếm sản phẩm' ?>
        </div>
        <div class="toolbar-sub"><?= count($products) ?> sản phẩm tìm thấy</div>
      </div>
      <div class="sort-wrap">
        <select class="sort-select" onchange="applySort(this.value)">
          <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Mới nhất</option>
          <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Giá tăng dần</option>
          <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Giá giảm dần</option>
          <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Tên A → Z</option>
        </select>
      </div>
    </div>

    <!-- Active filter tags -->
    <?php $hasFilter = $q !== '' || $category_id || $brand_id || $price_min || $price_max; ?>
    <?php if ($hasFilter): ?>
      <div class="filter-bar">
        <?php if ($q !== ''): ?>
          <div class="filter-tag">
            Từ khoá: <span>"<?= htmlspecialchars($q) ?>"</span>
            <a href="<?= BASE_URL ?>/search.php?<?= http_build_query(array_filter(['category_id'=>$category_id,'brand_id'=>$brand_id,'sort'=>$sort,'price_min'=>$price_min,'price_max'=>$price_max])) ?>">✕</a>
          </div>
        <?php endif; ?>
        <?php if ($category_id && $categoryName): ?>
          <div class="filter-tag">
            Danh mục: <span><?= htmlspecialchars($categoryName) ?></span>
            <a href="<?= BASE_URL ?>/search.php?<?= http_build_query(array_filter(['search'=>$q,'brand_id'=>$brand_id,'sort'=>$sort,'price_min'=>$price_min,'price_max'=>$price_max])) ?>">✕</a>
          </div>
        <?php endif; ?>
        <?php if ($brand_id && $brandName): ?>
          <div class="filter-tag">
            Thương hiệu: <span><?= htmlspecialchars($brandName) ?></span>
            <a href="<?= BASE_URL ?>/search.php?<?= http_build_query(array_filter(['search'=>$q,'category_id'=>$category_id,'sort'=>$sort,'price_min'=>$price_min,'price_max'=>$price_max])) ?>">✕</a>
          </div>
        <?php endif; ?>
        <?php if ($price_min || $price_max): ?>
          <div class="filter-tag">
            Giá: <span>
              <?= $price_min ? number_format($price_min,0,',','.') . '₫' : '0₫' ?>
              —
              <?= $price_max ? number_format($price_max,0,',','.') . '₫' : 'tất cả' ?>
            </span>
            <a href="<?= BASE_URL ?>/search.php?<?= http_build_query(array_filter(['search'=>$q,'category_id'=>$category_id,'brand_id'=>$brand_id,'sort'=>$sort])) ?>">✕</a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Grid -->
    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-camera-slash"></i></div>
        <h3>Không tìm thấy sản phẩm</h3>
        <p>Thử từ khoá khác hoặc bỏ bớt bộ lọc.</p>
        <a class="btn-back" href="<?= BASE_URL ?>/index.php">← Xem tất cả sản phẩm</a>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $p): ?>
          <a class="product-card" href="<?= BASE_URL ?>/product.php?id=<?= $p['id'] ?>">
            <div class="card-thumb">
              <?php if ($p['thumbnail']): ?>
                <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($p['thumbnail']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
              <?php else: ?>
                <div class="no-img"><i class="fas fa-camera"></i></div>
              <?php endif; ?>
              <span class="stock-badge <?= $p['total_stock'] > 0 ? 'in-stock' : 'out-stock' ?>">
                <?= $p['total_stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
              </span>
              <?php if ($p['brand_name']): ?>
                <span class="card-brand"><?= htmlspecialchars($p['brand_name']) ?></span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <div class="card-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="card-desc"><?= htmlspecialchars($p['description'] ?? '') ?></div>
              <div class="card-price">
                <?php if ($p['min_price']): ?>
                  <span class="price-from">Từ</span>
                  <span class="price-value"><?= number_format($p['min_price'], 0, ',', '.') ?>₫</span>
                <?php else: ?>
                  <span class="price-none">Liên hệ</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-footer">
              <span class="btn-view">Xem chi tiết →</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</div>

<script>
  const maxPriceDB = <?= $maxPriceDB ?>;
  const rangeMin   = document.getElementById('rangeMin');
  const rangeMax   = document.getElementById('rangeMax');
  const hiddenMin  = document.getElementById('hiddenMin');
  const hiddenMax  = document.getElementById('hiddenMax');
  const displayMin = document.getElementById('displayMin');
  const displayMax = document.getElementById('displayMax');
  const fill       = document.getElementById('rangeFill');

  function fmt(n) {
    return Number(n).toLocaleString('vi-VN') + '₫';
  }

  function updateSlider() {
    let lo = parseInt(rangeMin.value);
    let hi = parseInt(rangeMax.value);
    if (lo > hi) { [lo, hi] = [hi, lo]; rangeMin.value = lo; rangeMax.value = hi; }
    hiddenMin.value  = lo;
    hiddenMax.value  = hi;
    displayMin.textContent = fmt(lo);
    displayMax.textContent = fmt(hi);
    const pctLo = lo / maxPriceDB * 100;
    const pctHi = hi / maxPriceDB * 100;
    fill.style.left  = pctLo + '%';
    fill.style.width = (pctHi - pctLo) + '%';
  }

  rangeMin.addEventListener('input', updateSlider);
  rangeMax.addEventListener('input', updateSlider);
  updateSlider();

  function applySort(val) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', val);
    window.location.href = url.toString();
  }
</script>

<?php include "../includes/footer.php"; ?>