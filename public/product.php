<?php
require_once "../config/config.php";
require_once "../config/database.php";
include "../includes/header.php";  // gọi session_start() ở đây
include "../includes/navbar.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Sản phẩm không tồn tại.");
}

$id = (int)$_GET['id'];

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare("
    SELECT p.*, b.name AS brand_name, c.name AS category_name
    FROM products p
    LEFT JOIN brands b ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) die("Không tìm thấy sản phẩm.");

// Variants
$varStmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY sell_price ASC");
$varStmt->execute([$id]);
$product_variants = $varStmt->fetchAll(PDO::FETCH_ASSOC);

// Hình ảnh
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
$imgStmt->execute([$id]);
$product_images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

// Reviews kèm tên user
$reviewStmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
");
$reviewStmt->execute([$id]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

// Ảnh từng review
$reviewImages = [];
$reviewIds = array_column($reviews, 'id');
if (!empty($reviewIds)) {
    $ph = implode(',', array_fill(0, count($reviewIds), '?'));
    $riStmt = $pdo->prepare("SELECT * FROM review_images WHERE review_id IN ($ph)");
    $riStmt->execute($reviewIds);
    foreach ($riStmt->fetchAll(PDO::FETCH_ASSOC) as $ri) {
        $reviewImages[$ri['review_id']][] = $ri;
    }
}

// Thống kê rating
$ratingStats  = array_fill(1, 5, 0);
$totalRating  = 0;
foreach ($reviews as $r) {
    $ratingStats[$r['rating']]++;
    $totalRating += $r['rating'];
}
$avgRating    = count($reviews) ? round($totalRating / count($reviews), 1) : 0;
$totalReviews = count($reviews);

// ── Kiểm tra quyền đánh giá (hoàn toàn backend) ──────────────────
// Session key đúng là $_SESSION['user'] (xem AuthController / login.php)
$canReview       = false;
$alreadyReviewed = false;
$notPurchased    = false;

$isLoggedIn = isset($_SESSION['user']);

if ($isLoggedIn) {
    $userId = (int)$_SESSION['user']['id'];

    // Đã đánh giá chưa?
    $chkReview = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
    $chkReview->execute([$userId, $id]);
    $alreadyReviewed = (bool)$chkReview->fetch();

    if (!$alreadyReviewed) {
        // Đã mua chưa? — khớp theo product_name trong order_items
        $chkBought = $pdo->prepare("
            SELECT oi.id
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.user_id = ?
              AND oi.product_name = ?
              AND o.status != 'Đã huỷ'
            LIMIT 1
        ");
        $chkBought->execute([$userId, $p['name']]);
        $bought = $chkBought->fetch();

        if ($bought) {
            $canReview = true;
        } else {
            $notPurchased = true;
        }
    }
}
?>

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
    --success:     #16a34a;
    --font-display:'Cormorant Garamond', serif;
    --font-body:   'DM Sans', sans-serif;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: var(--font-body); background: var(--cream); color: var(--ink); }
  .page-wrapper { max-width: 1200px; margin: 0 auto; padding: 48px 32px 100px; }

  .page-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 20px; }
  .gold-line {
    width: 40px; height: 3px;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    border-radius: 2px; margin-bottom: 8px;
  }
  .page-header h1 {
    font-family: var(--font-display); font-size: 34px; font-weight: 700;
    color: var(--ink); line-height: 1.15;
  }

  .product-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 20px; }

  .image-gallery { display: flex; flex-direction: column; gap: 16px; }
  .main-image {
    border: 1.5px solid var(--border); border-radius: 12px;
    overflow: hidden; background: var(--cream-dark); aspect-ratio: 1/1;
  }
  .main-image img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
  .main-image:hover img { transform:scale(1.03); }
  .thumbnail-list { display:flex; gap:12px; flex-wrap:wrap; }
  .thumbnail {
    width:80px; height:80px; border:2px solid var(--border);
    border-radius:8px; overflow:hidden; cursor:pointer; transition:all .2s;
  }
  .thumbnail.active, .thumbnail:hover { border-color:var(--gold); transform:scale(1.05); }
  .thumbnail img { width:100%; height:100%; object-fit:cover; }

  .brand-category { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
  .badge {
    background:var(--gold-pale); color:var(--gold);
    font-size:12px; font-weight:600; padding:4px 12px;
    border-radius:999px; border:1px solid rgba(184,134,11,.25);
  }

  .variant-list { display:flex; flex-direction:column; gap:12px; margin:20px 0; }
  .variant-item {
    padding:14px 18px; border:1.5px solid var(--border);
    border-radius:10px; cursor:pointer; transition:all .25s; background:var(--white);
  }
  .variant-item:hover, .variant-item.selected { border-color:var(--gold); background:var(--gold-pale); }
  .variant-item label {
    display:flex; justify-content:space-between; align-items:center;
    width:100%; cursor:pointer; font-size:15px;
  }
  .price { font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--gold); }

  .quantity-group { display:flex; align-items:center; gap:16px; margin:24px 0; }
  .quantity-group label { font-weight:500; }
  .quantity-input {
    width:100px; padding:10px 14px; border:1.5px solid var(--border);
    border-radius:8px; font-size:16px; text-align:center; font-family:var(--font-body);
  }
  .add-to-cart-btn {
    background:var(--ink); color:#fff; border:none;
    padding:14px 32px; font-size:15.5px; font-weight:600;
    border-radius:8px; cursor:pointer; width:100%; margin-top:12px;
    font-family:var(--font-body); transition:all .25s;
  }
  .add-to-cart-btn:hover { background:var(--gold); color:var(--ink); transform:translateY(-2px); }

  .description-block { margin-top:28px; padding-top:24px; border-top:1px solid var(--border); }
  .description-block h3 {
    font-family:var(--font-display); font-size:20px; font-weight:700;
    color:var(--ink); margin-bottom:12px;
  }
  .description-block p { font-size:14.5px; color:var(--muted); line-height:1.7; }

  /* ── Reviews ── */
  .reviews-section { margin-top:72px; padding-top:48px; border-top:1.5px solid var(--border); }
  .section-heading { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:32px; }
  .section-title { font-family:var(--font-display); font-size:30px; font-weight:700; color:var(--ink); line-height:1; }
  .section-count { font-size:13px; color:var(--muted); margin-top:5px; }

  .rating-overview {
    display:flex; align-items:center; gap:48px;
    background:var(--white); border:1.5px solid var(--border);
    border-radius:14px; padding:28px 32px; margin-bottom:32px; box-shadow:var(--shadow-sm);
  }
  .rating-big { text-align:center; flex-shrink:0; }
  .rating-number { font-family:var(--font-display); font-size:56px; font-weight:700; color:var(--gold); line-height:1; }
  .rating-stars { display:flex; gap:4px; justify-content:center; margin:6px 0 4px; }
  .star { font-size:18px; color:var(--border-dark); }
  .star.filled { color:#f59e0b; }
  .rating-total { font-size:12px; color:var(--muted); }
  .rating-bars { flex:1; display:flex; flex-direction:column; gap:8px; }
  .rating-bar-row { display:flex; align-items:center; gap:12px; }
  .bar-label { font-size:12px; color:var(--muted); width:28px; text-align:right; flex-shrink:0; }
  .bar-label i { font-size:10px; color:#f59e0b; }
  .bar-track { flex:1; height:7px; background:var(--cream-dark); border-radius:99px; overflow:hidden; }
  .bar-fill { height:100%; border-radius:99px; background:linear-gradient(90deg,#f59e0b,var(--gold-light)); transition:width .6s ease; }
  .bar-count { font-size:12px; color:var(--muted); width:22px; flex-shrink:0; }

  .review-list { display:flex; flex-direction:column; gap:16px; margin-bottom:32px; }
  .review-card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:12px; padding:20px 22px; box-shadow:var(--shadow-sm);
    animation:fadeUp .35s ease both;
  }
  .review-card-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
  .reviewer-info { display:flex; align-items:center; gap:12px; }
  .reviewer-avatar {
    width:38px; height:38px; border-radius:50%;
    background:var(--gold-pale); border:1.5px solid var(--gold-border);
    display:flex; align-items:center; justify-content:center;
    font-size:15px; font-weight:700; color:var(--gold); flex-shrink:0;
  }
  .reviewer-name { font-weight:600; font-size:14px; color:var(--ink); }
  .review-date { font-size:11.5px; color:var(--muted); margin-top:2px; }
  .review-stars { display:flex; gap:3px; }
  .review-stars .star { font-size:14px; }
  .review-comment { font-size:14px; color:var(--ink-soft); line-height:1.65; margin-bottom:12px; }
  .review-images { display:flex; gap:10px; flex-wrap:wrap; }
  .review-img-thumb {
    width:80px; height:80px; border-radius:8px;
    border:1.5px solid var(--border); overflow:hidden; cursor:pointer;
    transition:border-color .2s, transform .2s;
  }
  .review-img-thumb:hover { border-color:var(--gold); transform:scale(1.04); }
  .review-img-thumb img { width:100%; height:100%; object-fit:cover; }

  .no-reviews {
    text-align:center; padding:60px 24px; margin-bottom:32px;
    background:var(--white); border:1.5px solid var(--border); border-radius:12px;
  }
  .no-reviews i { font-size:40px; color:var(--border-dark); margin-bottom:16px; }
  .no-reviews h4 { font-family:var(--font-display); font-size:22px; font-weight:700; color:var(--ink); margin-bottom:6px; }
  .no-reviews p { font-size:13.5px; color:var(--muted); }

  /* Review box */
  .review-box {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:14px; overflow:hidden; box-shadow:var(--shadow-sm);
  }
  .review-box-header {
    display:flex; align-items:center; gap:12px;
    padding:20px 24px; border-bottom:1.5px solid var(--border); background:var(--cream);
  }
  .review-box-icon {
    width:38px; height:38px; border-radius:10px;
    background:var(--gold-pale); border:1.5px solid var(--gold-border);
    display:flex; align-items:center; justify-content:center; font-size:15px; color:var(--gold);
  }
  .review-box-title { font-family:var(--font-display); font-size:20px; font-weight:700; color:var(--ink); }
  .review-box-sub { font-size:12px; color:var(--muted); margin-top:1px; }
  .review-box-body { padding:24px; }

  .notice {
    display:flex; align-items:flex-start; gap:14px;
    padding:18px 20px; border-radius:10px; font-size:14px; line-height:1.6;
  }
  .notice i { font-size:18px; flex-shrink:0; margin-top:1px; }
  .notice-info  { background:var(--gold-pale); border:1px solid var(--gold-border); color:var(--ink-soft); }
  .notice-info i { color:var(--gold); }
  .notice-done  { background:#f0fdf4; border:1px solid rgba(22,163,74,.2); color:#14532d; }
  .notice-done i { color:var(--success); }
  .notice-login { background:var(--cream-dark); border:1px solid var(--border); color:var(--ink-soft); }
  .notice-login i { color:var(--muted); }
  .notice a { color:var(--gold); font-weight:600; text-decoration:none; }
  .notice a:hover { text-decoration:underline; }

  .form-group { margin-bottom:18px; }
  .form-label {
    display:block; font-size:11.5px; font-weight:600;
    letter-spacing:.7px; text-transform:uppercase; color:var(--muted); margin-bottom:8px;
  }
  .form-control {
    width:100%; padding:10px 13px; border:1.5px solid var(--border); border-radius:9px;
    font-family:var(--font-body); font-size:13.5px; color:var(--ink);
    background:var(--cream); outline:none;
    transition:border-color .2s, box-shadow .2s, background .2s;
  }
  .form-control:focus { border-color:var(--gold); background:var(--white); box-shadow:0 0 0 3px rgba(184,134,11,.10); }
  .form-control::placeholder { color:var(--border-dark); }
  textarea.form-control { resize:vertical; min-height:110px; }

  .star-rate { display:flex; gap:4px; }
  .star-rate span {
    font-size:30px; cursor:pointer; color:var(--border-dark);
    transition:color .12s, transform .12s; user-select:none;
  }
  .star-rate span:hover { transform:scale(1.18); }
  .star-rate span.active { color:#f59e0b; }
  .star-rate-hint { font-size:12px; color:var(--muted); margin-top:5px; min-height:16px; }

  .file-upload-area {
    border:2px dashed var(--border-dark); border-radius:10px;
    padding:20px; text-align:center; cursor:pointer;
    transition:border-color .2s, background .2s;
    background:var(--cream); position:relative;
  }
  .file-upload-area:hover { border-color:var(--gold); background:var(--gold-pale); }
  .file-upload-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
  .file-upload-icon { font-size:22px; color:var(--border-dark); margin-bottom:5px; }
  .file-upload-text { font-size:12.5px; color:var(--muted); }
  .file-upload-text strong { color:var(--gold); }
  .file-preview { display:flex; gap:8px; flex-wrap:wrap; margin-top:10px; }
  .file-preview img { width:70px; height:70px; object-fit:cover; border-radius:7px; border:1.5px solid var(--border); }

  .btn-submit-review {
    font-family:var(--font-body); font-size:14px; font-weight:600;
    padding:11px 28px; border-radius:9px;
    background:var(--ink); border:none; color:var(--white);
    cursor:pointer; display:inline-flex; align-items:center; gap:8px;
    transition:background .2s, transform .15s, box-shadow .2s; box-shadow:var(--shadow-sm);
  }
  .btn-submit-review:hover { background:var(--ink-soft); transform:translateY(-1px); box-shadow:var(--shadow-md); }
  .btn-submit-review:disabled { opacity:.5; cursor:not-allowed; transform:none; }

  .lightbox {
    position:fixed; inset:0; z-index:500;
    background:rgba(0,0,0,.85); backdrop-filter:blur(6px);
    display:flex; align-items:center; justify-content:center;
    padding:24px; opacity:0; pointer-events:none; transition:opacity .25s;
  }
  .lightbox.open { opacity:1; pointer-events:all; }
  .lightbox img {
    max-width:90vw; max-height:88vh; border-radius:10px;
    box-shadow:0 24px 80px rgba(0,0,0,.5);
    transform:scale(.94); transition:transform .3s cubic-bezier(.34,1.56,.64,1);
  }
  .lightbox.open img { transform:scale(1); }
  .lightbox-close {
    position:absolute; top:20px; right:24px; width:38px; height:38px; border-radius:50%;
    background:rgba(255,255,255,.15); border:1.5px solid rgba(255,255,255,.3);
    color:#fff; font-size:15px; cursor:pointer;
    display:flex; align-items:center; justify-content:center; transition:background .2s;
  }
  .lightbox-close:hover { background:rgba(255,255,255,.3); }

  @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
  <?php foreach ($reviews as $i => $_): ?>
  .review-card:nth-child(<?= $i+1 ?>) { animation-delay:<?= $i*0.05 ?>s; }
  <?php endforeach; ?>

  @media (max-width:992px) { .product-detail{grid-template-columns:1fr;gap:40px} .rating-overview{flex-direction:column;gap:24px} }
  @media (max-width:600px) { .page-wrapper{padding:32px 16px 80px} }
</style>

<div class="page-wrapper">

  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1><?= htmlspecialchars($p['name']) ?></h1>
    </div>
    <?php if ($p['brand_name']): ?>
      <span class="badge"><?= htmlspecialchars($p['brand_name']) ?></span>
    <?php endif; ?>
  </div>

  <div class="product-detail">

    <!-- Hình ảnh -->
    <div class="image-gallery">
      <div class="main-image">
        <?php if (!empty($product_images)): ?>
          <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($product_images[0]['image_path']) ?>"
               alt="<?= htmlspecialchars($p['name']) ?>" id="mainImg">
        <?php else: ?>
          <div style="height:100%;display:flex;align-items:center;justify-content:center;font-size:80px;color:var(--border-dark);">
            <i class="fas fa-camera"></i>
          </div>
        <?php endif; ?>
      </div>
      <?php if (count($product_images) > 1): ?>
        <div class="thumbnail-list">
          <?php foreach ($product_images as $idx => $img): ?>
            <div class="thumbnail <?= $idx === 0 ? 'active' : '' ?>"
                 onclick="changeImage(this,'<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($img['image_path']) ?>')">
              <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($img['image_path']) ?>" alt="">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Thông tin + mua hàng -->
    <div class="product-info">
      <div class="brand-category">
        <?php if ($p['brand_name']): ?>
          <span class="badge"><?= htmlspecialchars($p['brand_name']) ?></span>
        <?php endif; ?>
        <?php if ($p['category_name']): ?>
          <span class="badge"><?= htmlspecialchars($p['category_name']) ?></span>
        <?php endif; ?>
      </div>

      <?php if ($totalReviews > 0): ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:18px;">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="fas fa-star" style="font-size:13px;color:<?= $s <= round($avgRating) ? '#f59e0b' : 'var(--border-dark)' ?>;"></i>
          <?php endfor; ?>
          <span style="font-size:13.5px;font-weight:600;color:var(--ink);"><?= $avgRating ?></span>
          <span style="font-size:12.5px;color:var(--muted);">(<?= $totalReviews ?> đánh giá)</span>
        </div>
      <?php endif; ?>

      <form action="./cart/add_to_cart.php" method="POST">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <p style="font-size:11.5px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--muted);margin-bottom:10px;">
          Chọn loại sản phẩm
        </p>
        <div class="variant-list">
          <?php foreach ($product_variants as $v): ?>
            <div class="variant-item" onclick="selectVariant(this)">
              <label>
                <input type="radio" name="variant_id" value="<?= $v['id'] ?>" required style="margin-right:12px;">
                <span><?= htmlspecialchars($v['condition']) ?></span>
                <span class="price"><?= number_format($v['sell_price'], 0, ',', '.') ?>₫</span>
              </label>
              <small style="color:<?= $v['stock'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;margin-left:32px;font-size:12px;">
                <?= $v['stock'] > 0 ? "Còn {$v['stock']} sản phẩm" : "Hết hàng" ?>
              </small>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="quantity-group">
          <label for="quantity">Số lượng:</label>
          <input type="number" name="quantity" id="quantity" value="1" min="1" class="quantity-input" required>
        </div>
        <button type="submit" class="add-to-cart-btn">🛒 Thêm vào giỏ hàng</button>
      </form>

      <div class="description-block">
        <h3>Mô tả sản phẩm</h3>
        <p><?= nl2br(htmlspecialchars($p['description'] ?? 'Chưa có mô tả cho sản phẩm này.')) ?></p>
      </div>
    </div>
  </div>

  <!-- ══════════════ REVIEWS ══════════════ -->
  <div class="reviews-section">

    <div class="section-heading">
      <div>
        <div class="gold-line"></div>
        <div class="section-title">Đánh giá sản phẩm</div>
        <div class="section-count"><?= $totalReviews ?> đánh giá từ khách hàng</div>
      </div>
    </div>

    <?php if ($totalReviews > 0): ?>
      <div class="rating-overview">
        <div class="rating-big">
          <div class="rating-number"><?= $avgRating ?></div>
          <div class="rating-stars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <i class="fas fa-star star <?= $s <= round($avgRating) ? 'filled' : '' ?>"></i>
            <?php endfor; ?>
          </div>
          <div class="rating-total">trên <?= $totalReviews ?> đánh giá</div>
        </div>
        <div class="rating-bars">
          <?php for ($star = 5; $star >= 1; $star--): ?>
            <?php $pct = $totalReviews ? round($ratingStats[$star] / $totalReviews * 100) : 0; ?>
            <div class="rating-bar-row">
              <div class="bar-label"><?= $star ?> <i class="fas fa-star"></i></div>
              <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
              <div class="bar-count"><?= $ratingStats[$star] ?></div>
            </div>
          <?php endfor; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($totalReviews > 0): ?>
      <div class="review-list">
        <?php foreach ($reviews as $rev): ?>
          <div class="review-card">
            <div class="review-card-header">
              <div class="reviewer-info">
                <div class="reviewer-avatar">
                  <?= mb_strtoupper(mb_substr($rev['user_name'] ?? 'K', 0, 1)) ?>
                </div>
                <div>
                  <div class="reviewer-name"><?= htmlspecialchars($rev['user_name'] ?? 'Khách') ?></div>
                  <div class="review-date"><?= date('d/m/Y', strtotime($rev['created_at'])) ?></div>
                </div>
              </div>
              <div class="review-stars">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="fas fa-star star <?= $s <= $rev['rating'] ? 'filled' : '' ?>"></i>
                <?php endfor; ?>
              </div>
            </div>
            <?php if (!empty($rev['comment'])): ?>
              <p class="review-comment"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($reviewImages[$rev['id']])): ?>
              <div class="review-images">
                <?php foreach ($reviewImages[$rev['id']] as $ri): ?>
                  <div class="review-img-thumb"
                       onclick="openLightbox('<?= BASE_URL ?>/../uploads/reviews/<?= htmlspecialchars($ri['image_path']) ?>')">
                    <img src="<?= BASE_URL ?>/../uploads/reviews/<?= htmlspecialchars($ri['image_path']) ?>" alt="">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="no-reviews">
        <i class="fas fa-comment-slash"></i>
        <h4>Chưa có đánh giá nào</h4>
        <p>Hãy là người đầu tiên chia sẻ cảm nhận về sản phẩm này!</p>
      </div>
    <?php endif; ?>

    <!-- Review box -->
    <div class="review-box">
      <div class="review-box-header">
        <div class="review-box-icon">
          <?php if ($canReview): ?>
            <i class="fas fa-pen"></i>
          <?php elseif ($alreadyReviewed): ?>
            <i class="fas fa-check-circle"></i>
          <?php else: ?>
            <i class="fas fa-comment"></i>
          <?php endif; ?>
        </div>
        <div>
          <div class="review-box-title">
            <?php if ($canReview): ?>Viết đánh giá
            <?php elseif ($alreadyReviewed): ?>Đánh giá của bạn
            <?php else: ?>Đánh giá sản phẩm
            <?php endif; ?>
          </div>
          <div class="review-box-sub">
            <?php if ($canReview): ?>Chia sẻ cảm nhận sau khi mua hàng
            <?php elseif ($alreadyReviewed): ?>Bạn đã gửi đánh giá cho sản phẩm này
            <?php elseif ($notPurchased): ?>Chỉ khách hàng đã mua mới có thể đánh giá
            <?php else: ?>Đăng nhập để xem điều kiện đánh giá
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="review-box-body">

        <?php if ($canReview): ?>
          <form action="./reviews/store.php" method="POST" enctype="multipart/form-data"
                onsubmit="return validateReview()">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="rating" id="ratingValue" value="0">

            <div class="form-group">
              <label class="form-label">Đánh giá của bạn <span style="color:var(--danger)">*</span></label>
              <div class="star-rate" id="starRate">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <span data-value="<?= $s ?>" title="<?= $s ?> sao">&#9733;</span>
                <?php endfor; ?>
              </div>
              <div class="star-rate-hint" id="starHint">Nhấn để chọn số sao</div>
            </div>

            <div class="form-group">
              <label class="form-label">Nhận xét</label>
              <textarea class="form-control" name="comment"
                        placeholder="Bạn cảm thấy thế nào về sản phẩm này?"></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">
                Hình ảnh
                <span style="color:var(--muted);font-weight:400;text-transform:none;letter-spacing:0">(tuỳ chọn, tối đa 5 ảnh)</span>
              </label>
              <div class="file-upload-area">
                <input type="file" name="images[]" multiple accept="image/*" onchange="previewImages(this)" />
                <div class="file-upload-icon"><i class="fas fa-images"></i></div>
                <div class="file-upload-text">Kéo thả hoặc <strong>chọn ảnh</strong></div>
                <div class="file-preview" id="filePreview"></div>
              </div>
            </div>

            <button type="submit" class="btn-submit-review" id="submitBtn">
              <i class="fas fa-paper-plane"></i> Gửi đánh giá
            </button>
          </form>

        <?php elseif ($alreadyReviewed): ?>
          <div class="notice notice-done">
            <i class="fas fa-check-circle"></i>
            <span>Bạn đã gửi đánh giá cho sản phẩm này. Cảm ơn vì đã chia sẻ cảm nhận!</span>
          </div>

        <?php elseif ($notPurchased): ?>
          <div class="notice notice-info">
            <i class="fas fa-shopping-bag"></i>
            <span>Chỉ khách hàng đã <strong>mua sản phẩm</strong> mới có thể đánh giá. Hãy mua sản phẩm này để chia sẻ cảm nhận nhé!</span>
          </div>

        <?php else: ?>
          <div class="notice notice-login">
            <i class="fas fa-lock"></i>
            <span>
              <a href="/camerashop/public/login.php">Đăng nhập</a> để xem bạn có đủ điều kiện đánh giá sản phẩm này không.
            </span>
          </div>
        <?php endif; ?>

      </div>
    </div>

  </div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" type="button" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
  <img src="" id="lightboxImg" alt="">
</div>

<script>
  function changeImage(thumb, src) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
  }
  function selectVariant(el) {
    document.querySelectorAll('.variant-item').forEach(i => i.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type="radio"]').checked = true;
  }

  const starLabels  = ['','Rất tệ','Tệ','Bình thường','Tốt','Tuyệt vời'];
  const stars       = document.querySelectorAll('#starRate span');
  const ratingInput = document.getElementById('ratingValue');
  const starHint    = document.getElementById('starHint');

  if (stars.length) {
    stars.forEach(star => {
      star.addEventListener('mouseenter', () => {
        const v = +star.dataset.value;
        stars.forEach(s => s.classList.toggle('active', +s.dataset.value <= v));
        starHint.textContent = starLabels[v];
      });
      star.addEventListener('click', () => {
        const v = +star.dataset.value;
        ratingInput.value = v;
        stars.forEach(s => s.classList.toggle('active', +s.dataset.value <= v));
        starHint.textContent = starLabels[v] + ' — đã chọn';
      });
    });
    document.getElementById('starRate').addEventListener('mouseleave', () => {
      const sel = +ratingInput.value;
      stars.forEach(s => s.classList.toggle('active', +s.dataset.value <= sel));
      starHint.textContent = sel ? starLabels[sel] + ' — đã chọn' : 'Nhấn để chọn số sao';
    });
  }

  function validateReview() {
    if (+ratingInput.value === 0) {
      starHint.textContent  = '⚠ Vui lòng chọn số sao trước khi gửi';
      starHint.style.color  = 'var(--danger)';
      return false;
    }
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
    return true;
  }

  function previewImages(input) {
    const preview = document.getElementById('filePreview');
    preview.innerHTML = '';
    Array.from(input.files).slice(0, 5).forEach(file => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = document.createElement('img');
        img.src = e.target.result;
        preview.appendChild(img);
      };
      reader.readAsDataURL(file);
    });
  }

  function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

<?php include "../includes/footer.php"; ?>