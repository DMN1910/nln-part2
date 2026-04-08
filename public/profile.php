<?php
// CAMERASHOP/public/profile.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user']['id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$userId  = (int) $_SESSION['user']['id'];
$success = '';
$error   = '';

$stmt = $pdo->prepare("SELECT id, name, email, password, role, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($name === '' || $email === '') {
            $error = 'Vui lòng điền đầy đủ họ tên và email.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Địa chỉ email không hợp lệ.';
        } else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $chk->execute([$email, $userId]);
            if ($chk->fetchColumn()) {
                $error = 'Email này đã được sử dụng bởi tài khoản khác.';
            } else {
                $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $userId]);
                $_SESSION['user']['name']  = $name;
                $_SESSION['user']['email'] = $email;
                $user['name']  = $name;
                $user['email'] = $email;
                $success = 'Cập nhật thông tin thành công!';
            }
        }
    }

    if ($action === 'change_password') {
        $oldPw  = $_POST['old_password']     ?? '';
        $newPw  = $_POST['new_password']     ?? '';
        $confPw = $_POST['confirm_password'] ?? '';
        if ($oldPw === '' || $newPw === '' || $confPw === '') {
            $error = 'Vui lòng điền đầy đủ tất cả các trường.';
        } elseif (!password_verify($oldPw, $user['password'])) {
            $error = 'Mật khẩu hiện tại không đúng.';
        } elseif (strlen($newPw) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } elseif ($newPw !== $confPw) {
            $error = 'Xác nhận mật khẩu không khớp.';
        } else {
            $hash = password_hash($newPw, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
            $user['password'] = $hash;
            $success = 'Đổi mật khẩu thành công!';
        }
    }

    if ($action === 'cancel_order') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $chk = $pdo->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'Chờ xác nhận'");
        $chk->execute([$orderId, $userId]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE orders SET status = 'Đã hủy' WHERE id = ?")->execute([$orderId]);
            header('Location: ?tab=orders&cancelled=1');
            exit;
        } else {
            $error = 'Không thể hủy đơn hàng này.';
        }
    }
}

$stOrders = $pdo->prepare("
    SELECT o.id, o.total_price, o.status, o.created_at,
           COUNT(oi.id) AS item_count
    FROM   orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE  o.user_id = ?
    GROUP  BY o.id
    ORDER  BY o.created_at DESC
");
$stOrders->execute([$userId]);
$orders = $stOrders->fetchAll();

$stRevs = $pdo->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at,
           p.id AS product_id, p.name AS product_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id LIMIT 1) AS product_img
    FROM   reviews r
    JOIN   products p ON p.id = r.product_id
    WHERE  r.user_id = ?
    ORDER  BY r.created_at DESC
");
$stRevs->execute([$userId]);
$reviews = $stRevs->fetchAll();

$totalSpent = 0;
foreach ($orders as $o) {
    if ($o['status'] !== 'Đã hủy') $totalSpent += (float)$o['total_price'];
}

$validTabs = ['info', 'password', 'orders', 'reviews'];
$tab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'info';

function statusInfo(string $s): array {
    return match($s) {
        'Chờ xác nhận' => ['Chờ xác nhận', 'st-pending'],
        'Đã xác nhận'  => ['Đã xác nhận',  'st-confirmed'],
        'Đang giao'    => ['Đang giao',     'st-shipping'],
        'Đã giao'      => ['Đã giao',       'st-done'],
        'Đã hủy'       => ['Đã hủy',        'st-cancel'],
        default        => [$s,              'st-default'],
    };
}

function renderStars(int $r): string {
    $o = '';
    for ($i = 1; $i <= 5; $i++)
        $o .= $i <= $r
            ? '<i class="fas fa-star" style="color:var(--gold);font-size:.85rem;"></i>'
            : '<i class="far fa-star" style="color:var(--border-dark);font-size:.85rem;"></i>';
    return $o;
}

function initials(string $name): string {
    return mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trang cá nhân – Camera Shop</title>
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
      --danger-pale: #fef2f2;
      --warn:        #d97706;
      --warn-pale:   #fffbeb;
      --blue:        #2563eb;
      --blue-pale:   #eff6ff;
      --green:       #16a34a;
      --green-pale:  #f0fdf4;
      --font-display:'Cormorant Garamond', serif;
      --font-body:   'DM Sans', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-body); background: var(--cream); color: var(--ink); min-height: 100vh; }

    /* HERO */
    .hero { background: var(--ink); padding: 48px 0 88px; position: relative; overflow: hidden; }
    .hero::before {
      content: ''; position: absolute; inset: 0;
      background: radial-gradient(circle at 80% 50%, rgba(184,134,11,.18) 0%, transparent 55%),
                  radial-gradient(circle at 15% 30%, rgba(212,160,23,.07) 0%, transparent 40%);
      pointer-events: none;
    }
    .hero::after {
      content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 56px;
      background: var(--cream); clip-path: ellipse(55% 100% at 50% 100%);
    }
    .hero-inner { position: relative; z-index: 2; max-width: 1200px; margin: 0 auto; padding: 0 32px; }
    .hero-eyebrow { font-size: 11px; font-weight: 600; letter-spacing: 2.5px; text-transform: uppercase; color: var(--gold-light); margin-bottom: 8px; }
    .hero-title { font-family: var(--font-display); font-size: 36px; font-weight: 700; color: var(--white); line-height: 1; }
    .hero-sub { font-size: 13px; color: rgba(255,255,255,.45); margin-top: 6px; }

    /* LAYOUT */
    .page-wrap { max-width: 1200px; margin: -3.2rem auto 0; padding: 0 32px 80px; position: relative; z-index: 10; }

    /* PROFILE CARD */
    .profile-card {
      background: var(--white); border: 1.5px solid var(--border); border-radius: 16px;
      padding: 28px 32px; margin-bottom: 24px; box-shadow: var(--shadow-md);
      display: flex; align-items: center; gap: 24px; animation: fadeUp .4s ease both;
    }
    .av-circle {
      width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, var(--ink), var(--ink-soft));
      border: 3px solid var(--gold-light);
      display: flex; align-items: center; justify-content: center;
      font-family: var(--font-display); font-size: 32px; font-weight: 700; color: var(--gold-light);
      position: relative; user-select: none; box-shadow: 0 4px 16px rgba(184,134,11,.25);
    }
    .av-dot { position: absolute; bottom: 4px; right: 4px; width: 13px; height: 13px; border-radius: 50%; background: var(--green); border: 2px solid var(--white); }
    .av-info { flex: 1; min-width: 0; }
    .av-name { font-family: var(--font-display); font-size: 24px; font-weight: 700; color: var(--ink); line-height: 1; }
    .av-email { font-size: 13px; color: var(--muted); margin-top: 4px; }
    .av-role { display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; background: var(--gold-pale); border: 1px solid var(--gold-border); color: var(--gold); font-size: 11px; font-weight: 600; padding: 3px 12px; border-radius: 999px; letter-spacing: .5px; text-transform: uppercase; }
    .av-stats { display: flex; gap: 32px; flex-shrink: 0; }
    .av-stat { text-align: center; }
    .av-stat .n { font-family: var(--font-display); font-size: 28px; font-weight: 700; color: var(--gold); line-height: 1; }
    .av-stat .l { font-size: 11px; color: var(--muted); margin-top: 4px; }
    .av-divider { width: 1px; height: 48px; background: var(--border); align-self: center; }

    /* FLASH */
    .flash { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 500; margin-bottom: 20px; animation: fadeUp .3s ease both; }
    .flash.ok  { background: var(--green-pale);  color: var(--green);  border: 1px solid rgba(22,163,74,.2); }
    .flash.err { background: var(--danger-pale); color: var(--danger); border: 1px solid rgba(220,38,38,.2); }
    .flash i   { font-size: 15px; flex-shrink: 0; }

    /* TABS */
    .tabs-wrap { background: var(--white); border: 1.5px solid var(--border); border-radius: 12px; margin-bottom: 20px; overflow: hidden; box-shadow: var(--shadow-sm); animation: fadeUp .4s .04s ease both; }
    .tabs-nav { display: flex; }
    .tab-link { display: flex; align-items: center; gap: 8px; padding: 14px 22px; font-size: 13.5px; font-weight: 600; color: var(--muted); text-decoration: none; border-bottom: 3px solid transparent; transition: color .2s, border-color .2s, background .2s; white-space: nowrap; }
    .tab-link i { font-size: 14px; }
    .tab-link:hover { color: var(--ink); background: var(--cream); }
    .tab-link.active { color: var(--gold); border-bottom-color: var(--gold); background: var(--gold-pale); }
    .tab-badge { background: var(--ink); color: var(--white); font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 999px; line-height: 1.7; }
    .tab-link.active .tab-badge { background: var(--gold); }

    /* CONTENT CARD */
    .content-card { background: var(--white); border: 1.5px solid var(--border); border-radius: 14px; padding: 32px; box-shadow: var(--shadow-sm); animation: fadeUp .4s .08s ease both; }
    .sec-title { display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 28px; padding-bottom: 16px; border-bottom: 1.5px solid var(--border); }
    .sec-title .sec-icon { width: 32px; height: 32px; border-radius: 8px; background: var(--gold-pale); border: 1px solid var(--gold-border); display: flex; align-items: center; justify-content: center; font-size: 13px; color: var(--gold); }

    /* FORM */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 7px; }
    .form-group.full { grid-column: 1/-1; }
    .form-label { font-size: 11.5px; font-weight: 600; letter-spacing: .6px; text-transform: uppercase; color: var(--muted); }
    .form-label .req { color: var(--gold); }
    .form-input { padding: 11px 14px; border: 1.5px solid var(--border); border-radius: 9px; font-family: var(--font-body); font-size: 14px; color: var(--ink); background: var(--cream); outline: none; transition: border-color .2s, box-shadow .2s, background .2s; }
    .form-input:focus { border-color: var(--gold); background: var(--white); box-shadow: 0 0 0 3px rgba(184,134,11,.10); }
    .form-input:disabled { color: var(--border-dark); cursor: not-allowed; }
    .pw-wrap { position: relative; }
    .pw-eye { position: absolute; right: 13px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--border-dark); font-size: 14px; transition: color .2s; }
    .pw-eye:hover { color: var(--muted); }
    .str-bar  { height: 3px; background: var(--border); border-radius: 3px; overflow: hidden; margin-top: 6px; }
    .str-fill { height: 100%; border-radius: 3px; width: 0; transition: all .35s; }
    .str-label { font-size: 11.5px; color: var(--muted); margin-top: 5px; }
    .security-note { background: var(--gold-pale); border: 1px solid var(--gold-border); border-radius: 10px; padding: 16px 18px; margin-top: 24px; max-width: 460px; }
    .security-note p { font-size: 12px; font-weight: 600; color: var(--gold); margin-bottom: 6px; }
    .security-note ul { font-size: 12px; color: var(--muted); padding-left: 16px; line-height: 2; margin: 0; }
    .btn-submit { display: inline-flex; align-items: center; gap: 8px; background: var(--ink); color: var(--white); font-family: var(--font-body); font-size: 13.5px; font-weight: 600; padding: 10px 26px; border-radius: 9px; border: none; cursor: pointer; box-shadow: var(--shadow-sm); transition: background .2s, transform .15s, box-shadow .2s; }
    .btn-submit:hover { background: var(--ink-soft); transform: translateY(-2px); box-shadow: var(--shadow-md); }
    .form-meta { font-size: 12px; color: var(--border-dark); }

    /* STATUS */
    .st-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; padding: 4px 11px; border-radius: 999px; border: 1px solid transparent; }
    .st-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; }
    .st-pending   { background:var(--warn-pale);   color:var(--warn);   border-color:rgba(217,119,6,.2); }
    .st-confirmed { background:var(--blue-pale);   color:var(--blue);   border-color:rgba(37,99,235,.2); }
    .st-shipping  { background:var(--green-pale);  color:var(--green);  border-color:rgba(22,163,74,.15); }
    .st-done      { background:var(--gold-pale);   color:var(--gold);   border-color:var(--gold-border); }
    .st-cancel    { background:var(--danger-pale); color:var(--danger); border-color:rgba(220,38,38,.2); }
    .st-default   { background:var(--cream-dark);  color:var(--muted);  border-color:var(--border); }

    /* FILTER */
    .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .fbtn { font-family: var(--font-body); font-size: 12.5px; font-weight: 500; padding: 6px 14px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--white); color: var(--muted); cursor: pointer; transition: border-color .2s, color .2s, background .2s; }
    .fbtn:hover  { border-color: var(--gold); color: var(--gold); }
    .fbtn.active { background: var(--ink); border-color: var(--ink); color: var(--white); }

    /* ORDER CARDS */
    .ord-card { border: 1.5px solid var(--border); border-radius: 12px; overflow: hidden; margin-bottom: 12px; transition: border-color .2s, box-shadow .2s; }
    .ord-card:hover { border-color: var(--gold-border); box-shadow: var(--shadow-sm); }
    .ord-head { background: var(--cream); padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; border-bottom: 1.5px solid var(--border); }
    .ord-id { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--ink); }
    .ord-date { font-size: 12px; color: var(--muted); margin-left: 8px; }
    .ord-body { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .ord-items-note { font-size: 13px; color: var(--muted); }
    .ord-items-note i { color: var(--gold); margin-right: 5px; }
    .ord-price { font-family: var(--font-display); font-size: 20px; font-weight: 700; color: var(--gold); }
    .btn-detail { display: inline-flex; align-items: center; gap: 6px; font-family: var(--font-body); font-size: 12.5px; font-weight: 500; padding: 7px 14px; border-radius: 7px; background: var(--cream); border: 1.5px solid var(--border); color: var(--muted); cursor: pointer; transition: all .2s; }
    .btn-detail:hover { background: var(--ink); border-color: var(--ink); color: var(--white); }

    /* HỦY ĐƠN */
    .btn-cancel-order { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 7px; background: var(--danger-pale); border: 1.5px solid #fecaca; color: var(--danger); font-family: var(--font-body); font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-cancel-order:hover { background: var(--danger); color: white; border-color: var(--danger); }
    .btn-cancel-order-lg { padding: 9px 18px; font-size: 13px; border-radius: 8px; }

    /* MODAL */
    .modal-overlay { position: fixed; inset: 0; z-index: 300; background: rgba(26,23,20,.5); backdrop-filter: blur(5px); display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; pointer-events: none; transition: opacity .25s; }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box { background: var(--white); border: 1.5px solid var(--border); border-radius: 16px; width: 100%; max-width: 600px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); transform: translateY(20px) scale(.97); opacity: 0; transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .25s; }
    .modal-overlay.open .modal-box { transform: translateY(0) scale(1); opacity: 1; }
    .modal-hd { display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1.5px solid var(--border); flex-shrink: 0; }
    .modal-hd-left { display: flex; align-items: center; gap: 12px; }
    .modal-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--gold-pale); border: 1px solid var(--gold-border); display: flex; align-items: center; justify-content: center; font-size: 15px; color: var(--gold); }
    .modal-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; color: var(--ink); }
    .modal-sub   { font-size: 12px; color: var(--muted); }
    .btn-close-modal { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--cream); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); font-size: 13px; transition: border-color .2s, color .2s, background .2s; }
    .btn-close-modal:hover { border-color: var(--danger); color: var(--danger); background: var(--danger-pale); }
    .modal-body { overflow-y: auto; padding: 24px; flex: 1; }
    .modal-item { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--border); }
    .modal-item:last-child { border-bottom: none; }
    .modal-thumb { width: 56px; height: 56px; border-radius: 8px; border: 1.5px solid var(--border); overflow: hidden; flex-shrink: 0; background: var(--cream-dark); display: flex; align-items: center; justify-content: center; }
    .modal-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .modal-thumb .ph { font-size: 20px; color: var(--border-dark); }
    .modal-item-name  { font-weight: 600; font-size: 13.5px; color: var(--ink); margin-bottom: 3px; }
    .modal-item-meta  { font-size: 12px; color: var(--muted); }
    .modal-item-price { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: var(--gold); margin-left: auto; white-space: nowrap; }
    .modal-ft { padding: 16px 24px; border-top: 1.5px solid var(--border); display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
    .modal-ft-lbl { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); }
    .modal-ft-total { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--gold); }

    /* CONFIRM MODAL */
    .confirm-overlay { position: fixed; inset: 0; z-index: 400; background: rgba(26,23,20,.55); backdrop-filter: blur(6px); display: flex; align-items: center; justify-content: center; padding: 24px; opacity: 0; pointer-events: none; transition: opacity .25s; }
    .confirm-overlay.open { opacity: 1; pointer-events: all; }
    .confirm-box { background: var(--white); border-radius: 14px; border: 1.5px solid var(--border); max-width: 400px; width: 100%; padding: 28px; box-shadow: var(--shadow-lg); transform: scale(.95); opacity: 0; transition: all .25s; }
    .confirm-overlay.open .confirm-box { transform: scale(1); opacity: 1; }
    .confirm-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--danger-pale); border: 1.5px solid #fecaca; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--danger); margin-bottom: 16px; }
    .confirm-title { font-family: var(--font-display); font-size: 22px; font-weight: 700; margin-bottom: 8px; }
    .confirm-msg { font-size: 13.5px; color: var(--muted); line-height: 1.6; margin-bottom: 22px; }
    .confirm-msg strong { color: var(--ink); }
    .confirm-btns { display: flex; gap: 10px; justify-content: flex-end; }
    .btn-cfm-cancel { padding: 9px 20px; border-radius: 8px; background: var(--cream); border: 1.5px solid var(--border); color: var(--muted); cursor: pointer; font-family: var(--font-body); font-size: 14px; transition: all .2s; }
    .btn-cfm-cancel:hover { border-color: var(--border-dark); color: var(--ink); }
    .btn-cfm-delete { padding: 9px 20px; border-radius: 8px; background: var(--danger); color: white; border: none; font-weight: 600; cursor: pointer; font-family: var(--font-body); font-size: 14px; transition: all .2s; display: inline-flex; align-items: center; gap: 7px; }
    .btn-cfm-delete:hover { background: #b91c1c; }

    /* REVIEWS */
    .rev-card { border: 1.5px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 12px; transition: border-color .2s, box-shadow .2s; display: flex; gap: 16px; align-items: flex-start; }
    .rev-card:hover { border-color: var(--gold-border); box-shadow: var(--shadow-sm); }
    .rev-thumb { width: 52px; height: 52px; border-radius: 9px; border: 1.5px solid var(--border); overflow: hidden; flex-shrink: 0; background: var(--cream-dark); display: flex; align-items: center; justify-content: center; }
    .rev-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .rev-thumb .ph { font-size: 18px; color: var(--border-dark); }
    .rev-product { font-weight: 600; font-size: 14px; color: var(--ink); text-decoration: none; }
    .rev-product:hover { color: var(--gold); }
    .rev-stars  { margin: 6px 0; }
    .rev-comment { font-size: 13px; color: var(--muted); line-height: 1.6; font-style: italic; }
    .rev-date   { font-size: 11.5px; color: var(--border-dark); margin-top: 8px; }

    /* EMPTY */
    .empty-state { text-align: center; padding: 64px 24px; }
    .empty-icon  { font-size: 48px; color: var(--border-dark); margin-bottom: 16px; }
    .empty-state h3 { font-family: var(--font-display); font-size: 24px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
    .empty-state p  { font-size: 13.5px; color: var(--muted); }
    .empty-state a  { color: var(--gold); font-weight: 600; text-decoration: none; }
    .empty-state a:hover { text-decoration: underline; }

    @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
      .profile-card { flex-wrap: wrap; }
      .av-stats { width: 100%; justify-content: center; }
      .av-divider { display: none; }
      .form-grid { grid-template-columns: 1fr; }
      .form-group.full { grid-column: 1; }
      .content-card { padding: 20px; }
      .page-wrap { padding: 0 16px 60px; }
      .tab-link .tab-lbl { display: none; }
      .confirm-btns { flex-direction: column; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="hero">
  <div class="hero-inner">
    <div class="hero-eyebrow">Tài khoản của bạn</div>
    <h1 class="hero-title">Trang cá nhân</h1>
    <p class="hero-sub">Quản lý thông tin, đơn hàng và đánh giá</p>
  </div>
</div>

<div class="page-wrap">

  <!-- Profile card -->
  <div class="profile-card">
    <div class="av-circle">
      <?= initials($user['name']) ?>
      <span class="av-dot"></span>
    </div>
    <div class="av-info">
      <div class="av-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="av-email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="av-role">
        <i class="fas fa-<?= $user['role']==='admin' ? 'crown' : 'shopping-bag' ?>"></i>
        <?= $user['role']==='admin' ? 'Quản trị viên' : 'Khách hàng' ?>
      </div>
    </div>
    <div class="av-divider"></div>
    <div class="av-stats">
      <div class="av-stat">
        <div class="n"><?= count($orders) ?></div>
        <div class="l">Đơn hàng</div>
      </div>
      <div class="av-divider"></div>
      <div class="av-stat">
        <div class="n"><?= count($reviews) ?></div>
        <div class="l">Đánh giá</div>
      </div>
      <div class="av-divider"></div>
      <div class="av-stat">
        <div class="n" style="font-size:1.1rem;">
          <?= $totalSpent >= 1e6 ? number_format($totalSpent/1e6, 1).'M' : number_format($totalSpent/1e3, 0).'K' ?>
        </div>
        <div class="l">Đã chi (₫)</div>
      </div>
    </div>
  </div>

  <!-- Flash -->
  <?php if ($success): ?>
    <div class="flash ok" id="flashMsg"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
  <?php elseif ($error): ?>
    <div class="flash err" id="flashMsg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs-wrap">
    <nav class="tabs-nav">
      <a class="tab-link <?= $tab==='info'     ? 'active':'' ?>" href="?tab=info"><i class="fas fa-user"></i><span class="tab-lbl">Thông tin</span></a>
      <a class="tab-link <?= $tab==='password' ? 'active':'' ?>" href="?tab=password"><i class="fas fa-lock"></i><span class="tab-lbl">Mật khẩu</span></a>
      <a class="tab-link <?= $tab==='orders'   ? 'active':'' ?>" href="?tab=orders">
        <i class="fas fa-box"></i><span class="tab-lbl">Đơn hàng</span>
        <?php if (count($orders)): ?><span class="tab-badge"><?= count($orders) ?></span><?php endif; ?>
      </a>
      <a class="tab-link <?= $tab==='reviews'  ? 'active':'' ?>" href="?tab=reviews">
        <i class="fas fa-star"></i><span class="tab-lbl">Đánh giá</span>
        <?php if (count($reviews)): ?><span class="tab-badge"><?= count($reviews) ?></span><?php endif; ?>
      </a>
    </nav>
  </div>

  <!-- TAB: THÔNG TIN -->
  <?php if ($tab === 'info'): ?>
  <div class="content-card">
    <div class="sec-title"><div class="sec-icon"><i class="fas fa-id-card"></i></div>Thông tin cá nhân</div>
    <form method="POST" action="?tab=info">
      <input type="hidden" name="action" value="update_profile" />
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Họ và tên <span class="req">*</span></label>
          <input class="form-input" type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required maxlength="100" />
        </div>
        <div class="form-group">
          <label class="form-label">Email <span class="req">*</span></label>
          <input class="form-input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required maxlength="150" />
        </div>
        <div class="form-group">
          <label class="form-label">Vai trò</label>
          <input class="form-input" type="text" value="<?= $user['role']==='admin' ? 'Quản trị viên' : 'Khách hàng' ?>" disabled />
        </div>
        <div class="form-group">
          <label class="form-label">Ngày tham gia</label>
          <input class="form-input" type="text" value="<?= date('d/m/Y', strtotime($user['created_at'])) ?>" disabled />
        </div>
        <div class="form-group full" style="display:flex;align-items:center;gap:16px;padding-top:4px;">
          <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Lưu thay đổi</button>
          <span class="form-meta">ID tài khoản: #<?= $user['id'] ?></span>
        </div>
      </div>
    </form>
  </div>

  <!-- TAB: MẬT KHẨU -->
  <?php elseif ($tab === 'password'): ?>
  <div class="content-card">
    <div class="sec-title"><div class="sec-icon"><i class="fas fa-shield-alt"></i></div>Đổi mật khẩu</div>
    <form method="POST" action="?tab=password" style="max-width:460px;">
      <input type="hidden" name="action" value="change_password" />
      <div class="form-group" style="margin-bottom:18px;">
        <label class="form-label">Mật khẩu hiện tại <span class="req">*</span></label>
        <div class="pw-wrap">
          <input class="form-input" style="padding-right:42px;" type="password" name="old_password" id="pw0" required autocomplete="current-password" />
          <span class="pw-eye" onclick="togglePw('pw0',this)"><i class="fas fa-eye"></i></span>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:18px;">
        <label class="form-label">Mật khẩu mới <span class="req">*</span></label>
        <div class="pw-wrap">
          <input class="form-input" style="padding-right:42px;" type="password" name="new_password" id="pw1" required autocomplete="new-password" oninput="checkStr(this.value)" />
          <span class="pw-eye" onclick="togglePw('pw1',this)"><i class="fas fa-eye"></i></span>
        </div>
        <div class="str-bar"><div class="str-fill" id="strFill"></div></div>
        <div class="str-label" id="strLabel"></div>
      </div>
      <div class="form-group" style="margin-bottom:24px;">
        <label class="form-label">Xác nhận mật khẩu mới <span class="req">*</span></label>
        <div class="pw-wrap">
          <input class="form-input" style="padding-right:42px;" type="password" name="confirm_password" id="pw2" required autocomplete="new-password" />
          <span class="pw-eye" onclick="togglePw('pw2',this)"><i class="fas fa-eye"></i></span>
        </div>
      </div>
      <button type="submit" class="btn-submit"><i class="fas fa-key"></i> Đổi mật khẩu</button>
    </form>
    <div class="security-note" style="margin-top:28px;">
      <p><i class="fas fa-info-circle" style="margin-right:6px;"></i>Lưu ý bảo mật</p>
      <ul>
        <li>Mật khẩu tối thiểu 6 ký tự</li>
        <li>Kết hợp chữ hoa, chữ số và ký tự đặc biệt</li>
        <li>Không chia sẻ mật khẩu với bất kỳ ai</li>
      </ul>
    </div>
  </div>

  <!-- TAB: ĐƠN HÀNG -->
  <?php elseif ($tab === 'orders'): ?>
  <div class="content-card">
    <div class="sec-title"><div class="sec-icon"><i class="fas fa-shopping-bag"></i></div>Lịch sử đơn hàng</div>

    <?php if (empty($orders)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-box-open"></i></div>
        <h3>Chưa có đơn hàng</h3>
        <p>Bạn chưa đặt hàng lần nào.<br><a href="<?= BASE_URL ?>/index.php">Khám phá sản phẩm →</a></p>
      </div>
    <?php else:
      $allStatuses = array_values(array_unique(array_column($orders, 'status')));
    ?>
      <div class="filter-bar">
        <button class="fbtn active" onclick="filterOrds(this,'')">Tất cả (<?= count($orders) ?>)</button>
        <?php foreach ($allStatuses as $s):
          $cnt = count(array_filter($orders, fn($o) => $o['status']===$s));
        ?>
          <button class="fbtn" onclick="filterOrds(this,'<?= htmlspecialchars($s, ENT_QUOTES) ?>')">
            <?= htmlspecialchars($s) ?> (<?= $cnt ?>)
          </button>
        <?php endforeach; ?>
      </div>

      <div id="ordList">
        <?php foreach ($orders as $ord):
          $iSt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
          $iSt->execute([$ord['id']]);
          $itsItems = $iSt->fetchAll();
          [$stText, $stCls] = statusInfo($ord['status']);
          $mid = 'md_' . $ord['id'];
          $canCancel = $ord['status'] === 'Chờ xác nhận';
          $ordNum = str_pad($ord['id'], 6, '0', STR_PAD_LEFT);
        ?>
          <div class="ord-card" data-status="<?= htmlspecialchars($ord['status']) ?>">
            <div class="ord-head">
              <div>
                <span class="ord-id">#<?= $ordNum ?></span>
                <span class="ord-date"><i class="far fa-clock" style="margin:0 4px 0 8px;"></i><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></span>
              </div>
              <div style="display:flex;align-items:center;gap:8px;">
                <span class="st-badge <?= $stCls ?>"><?= $stText ?></span>
                <?php if ($canCancel): ?>
                  <button class="btn-cancel-order" onclick="openConfirm(<?= $ord['id'] ?>,'<?= $ordNum ?>')">
                    <i class="fas fa-times-circle"></i> Hủy đơn
                  </button>
                <?php endif; ?>
              </div>
            </div>
            <div class="ord-body">
              <span class="ord-items-note"><i class="fas fa-cube"></i><?= $ord['item_count'] ?> sản phẩm</span>
              <div style="display:flex;align-items:center;gap:10px;">
                <span class="ord-price"><?= number_format($ord['total_price']) ?>₫</span>
                <button class="btn-detail" onclick="openModal('<?= $mid ?>')"><i class="fas fa-eye"></i> Chi tiết</button>
              </div>
            </div>
          </div>

          <!-- Modal chi tiết -->
          <div class="modal-overlay" id="<?= $mid ?>" onclick="handleOverlayClick(event,'<?= $mid ?>')">
            <div class="modal-box">
              <div class="modal-hd">
                <div class="modal-hd-left">
                  <div class="modal-icon"><i class="fas fa-receipt"></i></div>
                  <div>
                    <div class="modal-title">Đơn #<?= $ordNum ?></div>
                    <div class="modal-sub"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?> &nbsp;·&nbsp; <span class="st-badge <?= $stCls ?>" style="font-size:10.5px;padding:2px 9px;"><?= $stText ?></span></div>
                  </div>
                </div>
                <button class="btn-close-modal" onclick="closeModal('<?= $mid ?>')"><i class="fas fa-times"></i></button>
              </div>

              <div class="modal-body">
                <?php if (empty($itsItems)): ?>
                  <p style="text-align:center;color:var(--muted);padding:24px 0;font-size:13.5px;">Không có sản phẩm trong đơn này.</p>
                <?php else: ?>
                  <?php foreach ($itsItems as $it): ?>
                    <div class="modal-item">
                      <div class="modal-thumb">
                        <?php if (!empty($it['product_image'])): ?>
                          <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($it['product_image']) ?>" alt="">
                        <?php else: ?>
                          <span class="ph"><i class="fas fa-camera"></i></span>
                        <?php endif; ?>
                      </div>
                      <div style="flex:1;min-width:0;">
                        <div class="modal-item-name"><?= htmlspecialchars($it['product_name']) ?></div>
                        <?php if (!empty($it['variant_condition'])): ?>
                          <div class="modal-item-meta">Tình trạng: <?= htmlspecialchars($it['variant_condition']) ?></div>
                        <?php endif; ?>
                        <div class="modal-item-meta">Số lượng: <?= $it['quantity'] ?></div>
                      </div>
                      <div class="modal-item-price"><?= number_format($it['price'] * $it['quantity']) ?>₫</div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="modal-ft">
                <span class="modal-ft-lbl">Tổng cộng</span>
                <div style="display:flex;align-items:center;gap:14px;">
                  <?php if ($canCancel): ?>
                    <button class="btn-cancel-order btn-cancel-order-lg"
                      onclick="closeModal('<?= $mid ?>'); openConfirm(<?= $ord['id'] ?>,'<?= $ordNum ?>')">
                      <i class="fas fa-times-circle"></i> Hủy đơn
                    </button>
                  <?php endif; ?>
                  <span class="modal-ft-total"><?= number_format($ord['total_price']) ?>₫</span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- TAB: ĐÁNH GIÁ -->
  <?php elseif ($tab === 'reviews'): ?>
  <div class="content-card">
    <div class="sec-title"><div class="sec-icon"><i class="fas fa-star"></i></div>Đánh giá của tôi</div>
    <?php if (empty($reviews)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-star"></i></div>
        <h3>Chưa có đánh giá</h3>
        <p>Mua hàng xong hãy để lại đánh giá nhé!</p>
      </div>
    <?php else: ?>
      <p style="font-size:12px;color:var(--muted);margin-bottom:20px;"><?= count($reviews) ?> đánh giá</p>
      <?php foreach ($reviews as $rv): ?>
        <div class="rev-card">
          <div class="rev-thumb">
            <?php if (!empty($rv['product_img'])): ?>
              <img src="<?= BASE_URL ?>/../uploads/products/<?= htmlspecialchars($rv['product_img']) ?>" alt="">
            <?php else: ?>
              <span class="ph"><i class="fas fa-camera"></i></span>
            <?php endif; ?>
          </div>
          <div style="flex:1;min-width:0;">
            <a class="rev-product" href="<?= BASE_URL ?>/product.php?id=<?= $rv['product_id'] ?>"><?= htmlspecialchars($rv['product_name']) ?></a>
            <div class="rev-stars"><?= renderStars((int)$rv['rating']) ?> <span style="font-size:11.5px;color:var(--muted);margin-left:5px;">(<?= $rv['rating'] ?>/5)</span></div>
            <?php if (!empty($rv['comment'])): ?>
              <p class="rev-comment">"<?= htmlspecialchars($rv['comment']) ?>"</p>
            <?php endif; ?>
            <div class="rev-date"><i class="far fa-calendar-alt" style="margin-right:5px;"></i><?= date('d/m/Y', strtotime($rv['created_at'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<!-- Confirm hủy đơn -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div class="confirm-icon"><i class="fas fa-times-circle"></i></div>
    <div class="confirm-title">Hủy đơn hàng?</div>
    <div class="confirm-msg">
      Bạn có chắc muốn hủy đơn <strong id="confirmOrderNum"></strong>?<br>
      Hành động này <strong>không thể hoàn tác</strong>.
    </div>
    <div class="confirm-btns">
      <button class="btn-cfm-cancel" onclick="closeConfirm()"><i class="fas fa-arrow-left"></i> Quay lại</button>
      <form method="POST" action="?tab=orders" style="display:inline;">
        <input type="hidden" name="action"   value="cancel_order" />
        <input type="hidden" name="order_id" id="confirmOrderId" value="" />
        <button type="submit" class="btn-cfm-delete"><i class="fas fa-times-circle"></i> Xác nhận hủy</button>
      </form>
    </div>
  </div>
</div>

<script>
function togglePw(id, el) {
  var inp = document.getElementById(id), ico = el.querySelector('i');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
}

function checkStr(v) {
  var s = 0;
  if (v.length >= 6) s++; if (v.length >= 10) s++;
  if (/[A-Z]/.test(v)) s++; if (/[0-9]/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
  var idx = Math.min(s, 5);
  var widths = ['0%','20%','40%','60%','80%','100%'];
  var colors = ['transparent','#ef4444','#f97316','#eab308','#22c55e','#16a34a'];
  var labels = ['','Rất yếu','Yếu','Trung bình','Mạnh','Rất mạnh'];
  document.getElementById('strFill').style.width = widths[idx];
  document.getElementById('strFill').style.background = colors[idx];
  document.getElementById('strLabel').textContent = labels[idx];
  document.getElementById('strLabel').style.color = colors[idx];
}

function filterOrds(btn, status) {
  document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#ordList .ord-card').forEach(c => {
    c.style.display = (status === '' || c.dataset.status === status) ? '' : 'none';
  });
}

function openModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
function handleOverlayClick(e, id) {
  if (e.target === document.getElementById(id)) closeModal(id);
}

function openConfirm(orderId, orderNum) {
  document.getElementById('confirmOrderId').value = orderId;
  document.getElementById('confirmOrderNum').textContent = '#' + orderNum;
  document.getElementById('confirmOverlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeConfirm() {
  document.getElementById('confirmOverlay').classList.remove('open');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    closeConfirm();
    document.body.style.overflow = '';
  }
});
var fm = document.getElementById('flashMsg');
if (fm) {
  setTimeout(() => { fm.style.transition='opacity .5s'; fm.style.opacity='0'; setTimeout(()=>fm.remove(),500); }, 3500);
}

if (new URLSearchParams(location.search).get('cancelled')) {
  var f = document.createElement('div');
  f.className = 'flash ok'; f.id = 'flashMsg';
  f.innerHTML = '<i class="fas fa-check-circle"></i> Đã hủy đơn hàng thành công!';
  var wrap = document.querySelector('.page-wrap');
  wrap.insertBefore(f, document.querySelector('.tabs-wrap'));
  setTimeout(() => { f.style.transition='opacity .5s'; f.style.opacity='0'; setTimeout(()=>f.remove(),500); }, 3500);
}
</script>
</body>
</html>