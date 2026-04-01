<?php
require_once "../config/config.php";
require_once "../config/database.php";
include "../includes/header.php";
include "../includes/navbar.php";

$category_id = $_GET['category_id'] ?? null;
$brand_id    = $_GET['brand_id']    ?? null;
$search      = $_GET['search']      ?? null;

$categoryName = null;
if ($category_id) {
    $s = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $s->execute([$category_id]);
    $categoryName = $s->fetchColumn();
}

$brandName = null;
if ($brand_id) {
    $s = $pdo->prepare("SELECT name FROM brands WHERE id = ?");
    $s->execute([$brand_id]);
    $brandName = $s->fetchColumn();
}

$sql = "
    SELECT
        p.id, p.name, p.description,
        b.name                     AS brand_name,
        c.name                     AS category_name,
        MIN(pv.sell_price)         AS min_price,
        SUM(pv.stock)              AS total_stock,
        (SELECT pi.image_path FROM product_images pi
         WHERE pi.product_id = p.id LIMIT 1) AS thumbnail
    FROM products p
    LEFT JOIN brands    b  ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    WHERE 1=1
";
$params = [];
if ($category_id) { $sql .= " AND p.category_id = ?"; $params[] = $category_id; }
if ($brand_id)    { $sql .= " AND p.brand_id = ?";    $params[] = $brand_id; }
if ($search)      {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " GROUP BY p.id ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* ── Page wrapper ───────────────────────────────────── */
.page-wrapper {
  max-width: 1360px;
  margin: 0 auto;
  padding: 48px 32px 100px;
}

/* ── Page header ────────────────────────────────────── */
.page-header {
  display: flex;
  align-items: flex-end;
  gap: 16px;
  margin-bottom: 36px;
  padding-bottom: 24px;
  border-bottom: 1.5px solid var(--border);
}
.page-header h1 {
  font-family: var(--font-display);
  font-size: 38px;
  font-weight: 700;
  color: var(--ink);
  letter-spacing: .3px;
  line-height: 1;
}
.header-badge {
  background: var(--gold-pale);
  color: var(--gold);
  border: 1px solid rgba(184,134,11,.25);
  font-size: 11.5px;
  font-weight: 600;
  padding: 4px 12px;
  border-radius: 999px;
  letter-spacing: .4px;
  margin-bottom: 2px;
}
.header-count {
  margin-left: auto;
  font-size: 13px;
  color: var(--muted);
  font-weight: 400;
  padding-bottom: 3px;
}

/* ── Filter tags ────────────────────────────────────── */
.filter-bar {
  display: flex; align-items: center;
  gap: 8px; flex-wrap: wrap;
  margin-bottom: 32px;
}
.filter-tag {
  display: flex; align-items: center; gap: 7px;
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 7px;
  padding: 5px 12px;
  font-size: 12.5px;
  color: var(--muted);
}
.filter-tag span { color: var(--ink); font-weight: 500; }
.filter-tag a {
  color: var(--muted); text-decoration: none;
  font-size: 13px; line-height: 1;
  transition: color .2s;
}
.filter-tag a:hover { color: var(--gold); }

/* ── Product grid ───────────────────────────────────── */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 24px;
}

/* ── Product card ───────────────────────────────────── */
.product-card {
  background: var(--white);
  border: 1.5px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  display: flex; flex-direction: column;
  text-decoration: none; color: inherit;
  transition: border-color .25s, transform .25s, box-shadow .25s;
  box-shadow: var(--shadow-sm);
}
.product-card:hover {
  border-color: var(--gold);
  transform: translateY(-5px);
  box-shadow: var(--shadow-md);
}

/* Thumbnail */
.card-thumb {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
  background: var(--cream-dark);
}
.card-thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform .45s ease;
}
.product-card:hover .card-thumb img { transform: scale(1.06); }
.no-img {
  width: 100%; height: 100%;
  display: flex; align-items: center; justify-content: center;
  font-size: 44px;
  color: var(--border-dark);
}

/* Stock badge */
.stock-badge {
  position: absolute; top: 10px; right: 10px;
  font-size: 10px; font-weight: 600;
  padding: 3px 9px; border-radius: 999px;
  letter-spacing: .3px;
}
.stock-badge.in-stock {
  background: rgba(22,163,74,.1);
  color: #16a34a;
  border: 1px solid rgba(22,163,74,.25);
}
.stock-badge.out-stock {
  background: rgba(220,38,38,.08);
  color: #dc2626;
  border: 1px solid rgba(220,38,38,.2);
}

/* Brand tag */
.card-brand {
  position: absolute; bottom: 10px; left: 10px;
  font-size: 9.5px; font-weight: 600;
  letter-spacing: 1px; text-transform: uppercase;
  background: rgba(255,255,255,.85);
  backdrop-filter: blur(6px);
  border: 1px solid var(--border);
  color: var(--ink-soft);
  padding: 3px 8px; border-radius: 4px;
}

/* Card body */
.card-body {
  padding: 18px 18px 12px;
  flex: 1; display: flex; flex-direction: column; gap: 8px;
}
.card-name {
  font-size: 14px; font-weight: 600;
  color: var(--ink); line-height: 1.45;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.card-desc {
  font-size: 12.5px; color: var(--muted);
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp:  2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}
.card-price {
  display: flex; align-items: baseline; gap: 5px;
  margin-top: 6px;
}
.price-from { font-size: 11px; color: var(--muted); }
.price-value {
  font-family: var(--font-display);
  font-size: 22px; font-weight: 700;
  color: var(--gold);
  letter-spacing: .3px;
}
.price-none { font-size: 13px; color: var(--muted); font-style: italic; }

/* Card footer / CTA */
.card-footer { padding: 0 18px 18px; }
.btn-view {
  display: block; text-align: center;
  background: var(--cream);
  border: 1.5px solid var(--border);
  color: var(--muted);
  border-radius: 8px;
  padding: 9px 12px;
  font-size: 13px; font-weight: 500;
  text-decoration: none;
  transition: background .2s, border-color .2s, color .2s;
}
.product-card:hover .btn-view {
  background: var(--ink);
  border-color: var(--ink);
  color: var(--white);
  font-weight: 600;
}

/* ── Gold divider accent ────────────────────────────── */
.gold-line {
  width: 48px; height: 3px;
  background: linear-gradient(90deg, var(--gold), var(--gold-light));
  border-radius: 2px;
  margin-bottom: 2px;
}

/* ── Empty state ────────────────────────────────────── */
.empty-state {
  text-align: center;
  padding: 100px 24px;
}
.empty-icon {
  font-size: 52px; color: var(--border-dark);
  margin-bottom: 24px;
}
.empty-state h3 {
  font-family: var(--font-display);
  font-size: 28px; font-weight: 700;
  color: var(--ink); margin-bottom: 10px;
}
.empty-state p { font-size: 14px; color: var(--muted); }
.empty-state .btn-back {
  display: inline-block; margin-top: 24px;
  background: var(--ink); color: var(--white);
  font-weight: 600; padding: 11px 28px;
  border-radius: 8px; text-decoration: none;
  font-size: 13.5px;
  transition: background .2s;
}
.empty-state .btn-back:hover { background: var(--ink-soft); }
</style>

<div class="page-wrapper">

  <!-- Header -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1>
        <?php if ($search): ?>
          Kết quả tìm kiếm
        <?php elseif ($categoryName): ?>
          <?= htmlspecialchars($categoryName) ?>
        <?php else: ?>
          Tất cả sản phẩm
        <?php endif; ?>
      </h1>
    </div>
    <?php if ($brandName): ?>
      <span class="header-badge"><?= htmlspecialchars($brandName) ?></span>
    <?php endif; ?>
    <span class="header-count"><?= count($products) ?> sản phẩm</span>
  </div>

  <!-- Active filters -->
  <?php if ($category_id || $brand_id || $search): ?>
    <div class="filter-bar">
      <?php if ($search): ?>
        <div class="filter-tag">
          Tìm kiếm: <span>"<?= htmlspecialchars($search) ?>"</span>
          <a href="<?= BASE_URL ?>/index.php">✕</a>
        </div>
      <?php endif; ?>
      <?php if ($category_id): ?>
        <div class="filter-tag">
          Danh mục: <span><?= htmlspecialchars($categoryName) ?></span>
          <a href="<?= BASE_URL ?>/index.php">✕</a>
        </div>
      <?php endif; ?>
      <?php if ($brand_id): ?>
        <div class="filter-tag">
          Thương hiệu: <span><?= htmlspecialchars($brandName) ?></span>
          <a href="<?= BASE_URL ?>/index.php?category_id=<?= $category_id ?>">✕</a>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Product grid -->
  <?php if (count($products) === 0): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fas fa-camera"></i></div>
      <h3>Không tìm thấy sản phẩm</h3>
      <p>Thử tìm kiếm với từ khóa khác hoặc xem tất cả sản phẩm.</p>
      <a class="btn-back" href="<?= BASE_URL ?>/index.php">← Xem tất cả</a>
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
            <div class="card-desc">
              <?= htmlspecialchars($p['description'] ?? 'Chưa có mô tả sản phẩm.') ?>
            </div>
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

</div>

<?php include "../includes/footer.php"; ?>