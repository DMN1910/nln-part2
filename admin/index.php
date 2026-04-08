<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    :root {
      --gold: #b8860b;
      --gold-light: #d4a017;
      --gold-pale: #fdf8ee;
      --gold-border: rgba(184, 134, 11, .22);

      --cream: #faf7f2;
      --cream-dark: #f2ede4;

      --ink: #1a1714;
      --ink-soft: #3d3730;
      --muted: #8a8078;

      --white: #ffffff;
      --border: #e8e2d9;
      --border-dark: #cec6bb;

      --shadow-sm: 0 1px 4px rgba(0, 0, 0, .06);
      --shadow-md: 0 6px 24px rgba(0, 0, 0, .10);
      --shadow-lg: 0 16px 48px rgba(0, 0, 0, .13);

      --font-display: 'Cormorant Garamond', serif;
      --font-body: 'DM Sans', sans-serif;

      --sidebar-w: 260px;
      --header-h: 64px;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-body);
      background: var(--cream);
      color: var(--ink);
      min-height: 100vh;
      display: flex;
    }

    /*  SIDEBAR */
    .sidebar {
      width: var(--sidebar-w);
      min-height: 100vh;
      background: var(--ink);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      z-index: 100;
    }

    /* Logo area */
    .sidebar-logo {
      padding: 28px 28px 24px;
      border-bottom: 1px solid rgba(255, 255, 255, .07);
    }

    .logo-label {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--gold-light);
      margin-bottom: 4px;
    }

    .logo-title {
      font-family: var(--font-display);
      font-size: 26px;
      font-weight: 700;
      color: var(--white);
      letter-spacing: .5px;
      line-height: 1;
    }

    .logo-gold-line {
      width: 32px;
      height: 2px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      border-radius: 2px;
      margin-top: 10px;
    }

    /* Admin badge */
    .sidebar-user {
      padding: 16px 28px;
      border-bottom: 1px solid rgba(255, 255, 255, .07);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      font-weight: 700;
      color: var(--ink);
      flex-shrink: 0;
    }

    .user-info {
      line-height: 1.3;
    }

    .user-name {
      font-size: 13px;
      font-weight: 600;
      color: var(--white);
    }

    .user-role {
      font-size: 10.5px;
      color: var(--gold-light);
      letter-spacing: .5px;
    }

    /* Nav */
    .sidebar-nav {
      flex: 1;
      padding: 20px 16px;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .nav-section-label {
      font-size: 9.5px;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: rgba(255, 255, 255, .28);
      padding: 14px 12px 6px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 8px;
      text-decoration: none;
      color: rgba(255, 255, 255, .62);
      font-size: 13.5px;
      font-weight: 500;
      transition: background .2s, color .2s;
      position: relative;
    }

    .nav-item i {
      width: 18px;
      text-align: center;
      font-size: 14px;
      transition: color .2s;
    }

    .nav-item:hover {
      background: rgba(255, 255, 255, .07);
      color: var(--white);
    }

    .nav-item.active {
      background: rgba(184, 134, 11, .15);
      color: var(--gold-light);
      font-weight: 600;
    }

    .nav-item.active i {
      color: var(--gold-light);
    }

    .nav-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 60%;
      background: var(--gold-light);
      border-radius: 0 2px 2px 0;
    }

    /* Logout at bottom */
    .sidebar-footer {
      padding: 16px 16px 28px;
      border-top: 1px solid rgba(255, 255, 255, .07);
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 8px;
      text-decoration: none;
      color: rgba(255, 255, 255, .42);
      font-size: 13px;
      transition: color .2s, background .2s;
    }

    .btn-logout:hover {
      background: rgba(220, 38, 38, .12);
      color: #f87171;
    }

    /* ═══════════════════════════════════════════════════
       MAIN CONTENT
    ═══════════════════════════════════════════════════ */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* Top header bar */
    .topbar {
      height: var(--header-h);
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      padding: 0 36px;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 50;
      box-shadow: var(--shadow-sm);
    }

    .topbar-title {
      font-family: var(--font-display);
      font-size: 22px;
      font-weight: 700;
      color: var(--ink);
      letter-spacing: .3px;
    }

    .topbar-breadcrumb {
      font-size: 12px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 6px;
      margin-left: 2px;
    }

    .topbar-breadcrumb i {
      font-size: 9px;
    }

    .topbar-right {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .topbar-time {
      font-size: 12px;
      color: var(--muted);
    }

    .topbar-bell {
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 1.5px solid var(--border);
      background: var(--cream);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--muted);
      font-size: 14px;
      transition: border-color .2s, color .2s;
      position: relative;
    }

    .topbar-bell:hover {
      border-color: var(--gold);
      color: var(--gold);
    }

    .bell-dot {
      position: absolute;
      top: 7px;
      right: 7px;
      width: 7px;
      height: 7px;
      background: #ef4444;
      border-radius: 50%;
      border: 1.5px solid var(--white);
    }

    /* Page content */
    .content {
      padding: 40px 36px 80px;
      flex: 1;
    }

    /* Welcome banner */
    .welcome-banner {
      background: linear-gradient(135deg, var(--ink) 0%, var(--ink-soft) 100%);
      border-radius: 14px;
      padding: 32px 36px;
      margin-bottom: 36px;
      position: relative;
      overflow: hidden;
    }

    .welcome-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 80% 50%, rgba(184, 134, 11, .18) 0%, transparent 55%),
        radial-gradient(circle at 20% 20%, rgba(212, 160, 23, .08) 0%, transparent 40%);
      pointer-events: none;
    }

    .welcome-banner::after {
      content: '';
      position: absolute;
      right: -20px;
      top: -20px;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      border: 1px solid rgba(184, 134, 11, .12);
    }

    .welcome-eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: var(--gold-light);
      margin-bottom: 8px;
    }

    .welcome-heading {
      font-family: var(--font-display);
      font-size: 34px;
      font-weight: 700;
      color: var(--white);
      line-height: 1.1;
      margin-bottom: 10px;
    }

    .welcome-sub {
      font-size: 13.5px;
      color: rgba(255, 255, 255, .52);
      max-width: 440px;
    }

    .welcome-date-pill {
      position: absolute;
      right: 36px;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, .07);
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 10px;
      padding: 14px 24px;
      text-align: center;
    }

    .date-day {
      font-family: var(--font-body);
      font-size: 42px;
      font-weight: 700;
      color: var(--gold-light);
      line-height: 1;
    }

    .date-month {
      font-size: 12px;
      color: rgba(255, 255, 255, .5);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-top: 4px;
    }

    /* ── Section heading ── */
    .section-heading {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 20px;
    }

    .section-heading h2 {
      font-family: var(--font-display);
      font-size: 22px;
      font-weight: 700;
      color: var(--ink);
      letter-spacing: .3px;
    }

    .section-gold-line {
      flex: 1;
      height: 1.5px;
      background: linear-gradient(90deg, var(--border), transparent);
    }

    /* ── Nav cards grid ── */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 48px;
    }

    .nav-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      text-decoration: none;
      color: inherit;
      box-shadow: var(--shadow-sm);
      transition: border-color .25s, transform .25s, box-shadow .25s;
      position: relative;
      overflow: hidden;
    }

    .nav-card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--gold), var(--gold-light));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .3s ease;
    }

    .nav-card:hover {
      border-color: var(--gold-border);
      transform: translateY(-4px);
      box-shadow: var(--shadow-md);
    }

    .nav-card:hover::after {
      transform: scaleX(1);
    }

    .card-icon-wrap {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: var(--gold-pale);
      border: 1.5px solid rgba(184, 134, 11, .18);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--gold);
      transition: background .25s, color .25s;
    }

    .nav-card:hover .card-icon-wrap {
      background: var(--ink);
      color: var(--gold-light);
      border-color: var(--ink);
    }

    .card-info {
      flex: 1;
    }

    .card-title {
      font-size: 15px;
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 5px;
    }

    .card-desc {
      font-size: 12.5px;
      color: var(--muted);
      line-height: 1.5;
    }

    .card-arrow {
      align-self: flex-end;
      font-size: 12px;
      color: var(--border-dark);
      transition: color .2s, transform .2s;
    }

    .nav-card:hover .card-arrow {
      color: var(--gold);
      transform: translateX(4px);
    }

    /* ── Quick stats row ── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 16px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      padding: 20px 22px;
      box-shadow: var(--shadow-sm);
    }

    .stat-label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 10px;
    }

    .stat-value {
      font-family: var(--font-body);
      font-size: 18px;
      font-weight: 700;
      color: var(--ink);
      line-height: 1;
    }

    .stat-sub {
      font-size: 11.5px;
      color: var(--muted);
      margin-top: 6px;
    }

    .stat-up {
      color: #16a34a;
      font-weight: 600;
    }

    .stat-down {
      color: #dc2626;
      font-weight: 600;
    }

    /* Animations */
    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(18px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .welcome-banner {
      animation: fadeUp .45s ease both;
    }

    .stat-card {
      animation: fadeUp .45s ease both;
    }

    .nav-card {
      animation: fadeUp .45s ease both;
    }

    .stat-card:nth-child(1) {
      animation-delay: .05s;
    }

    .stat-card:nth-child(2) {
      animation-delay: .10s;
    }

    .stat-card:nth-child(3) {
      animation-delay: .15s;
    }

    .stat-card:nth-child(4) {
      animation-delay: .20s;
    }

    .nav-card:nth-child(1) {
      animation-delay: .08s;
    }

    .nav-card:nth-child(2) {
      animation-delay: .14s;
    }

    .nav-card:nth-child(3) {
      animation-delay: .20s;
    }

    .nav-card:nth-child(4) {
      animation-delay: .26s;
    }

    .nav-card:nth-child(5) {
      animation-delay: .32s;
    }

    .nav-card:nth-child(6) {
      animation-delay: .38s;
    }
  </style>
</head>

<body>

  <!--  SIDEBAR  -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-label">Hệ thống quản trị</div>
      <div class="logo-title">AdminPanel</div>
      <div class="logo-gold-line"></div>
    </div>

    <div class="sidebar-user">
      <div class="user-avatar">A</div>
      <div class="user-info">
        <div class="user-name">Admin</div>
        <div class="user-role">Quản trị viên</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Tổng quan</div>
      <a class="nav-item active" href="#">
        <i class="fas fa-th-large"></i> Dashboard
      </a>

      <div class="nav-section-label">Quản lý</div>
      <a class="nav-item" href="./products/index.php">
        <i class="fas fa-camera"></i> Sản phẩm
      </a>
      <a class="nav-item" href="./categories/index.php">
        <i class="fas fa-tags"></i> Danh mục
      </a>
      <a class="nav-item" href="./brands/index.php">
        <i class="fas fa-trademark"></i> Thương hiệu
      </a>
      <a class="nav-item" href="./orders/index.php">
        <i class="fas fa-box"></i> Đơn hàng
      </a>
      <a class="nav-item" href="./users/user.php">
        <i class="fas fa-users"></i> Người dùng
      </a>
      <a class="nav-item" href="./revenue/index.php">
        <i class="fas fa-chart-line"></i> Doanh thu
      </a>

      <a class="nav-item" href="./chat/chat.php">
        <i class="fas fa-comments"></i> Chat tư vấn
      </a>
    </nav>



    <div class="sidebar-footer">
      <a class="btn-logout" href="../public/logout.php">
        <i class="fas fa-sign-out-alt"></i> Đăng xuất
      </a>
    </div>
  </aside>

  <!--  MAIN  -->
  <div class="main">

    <!-- Topbar -->
    <header class="topbar">
      <div>
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-breadcrumb">
          <i class="fas fa-home"></i> Trang chủ
          <i class="fas fa-chevron-right"></i> Tổng quan
        </div>
      </div>
      <div class="topbar-right">
        <span class="topbar-time" id="js-time"></span>
        <div class="topbar-bell">
          <i class="fas fa-bell"></i>
          <span class="bell-dot"></span>
        </div>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <!-- Welcome banner -->
      <div class="welcome-banner">
        <div class="welcome-eyebrow">Chào mừng trở lại</div>
        <div class="welcome-heading">Xin chào, Admin!</div>
        <div class="welcome-sub">Quản lý toàn bộ hệ thống bán hàng từ một nơi duy nhất.</div>
        <div class="welcome-date-pill">
          <div class="date-day" id="js-day">27</div>
          <div class="date-month" id="js-month">Tháng 3, 2026</div>
        </div>
      </div>

      <!-- Nav cards -->
      <div class="section-heading">
        <h2>Quản lý hệ thống</h2>
        <div class="section-gold-line"></div>
      </div>
      <div class="cards-grid">

        <a class="nav-card" href="./products/index.php">
          <div class="card-icon-wrap"><i class="fas fa-camera"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý sản phẩm</div>
            <div class="card-desc">Thêm, sửa, xoá sản phẩm, biến thể và hình ảnh.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

        <a class="nav-card" href="./users/user.php">
          <div class="card-icon-wrap"><i class="fas fa-users"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý người dùng</div>
            <div class="card-desc">Xem danh sách và phân quyền tài khoản khách hàng.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

        <a class="nav-card" href="./orders/index.php">
          <div class="card-icon-wrap"><i class="fas fa-box"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý đơn hàng</div>
            <div class="card-desc">Theo dõi trạng thái, xử lý và xác nhận đơn hàng.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

        <a class="nav-card" href="./categories/index.php">
          <div class="card-icon-wrap"><i class="fas fa-tags"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý danh mục</div>
            <div class="card-desc">Phân loại sản phẩm theo danh mục một cách có hệ thống.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

        <a class="nav-card" href="./revenue/index.php">
          <div class="card-icon-wrap"><i class="fas fa-chart-line"></i></div>
          <div class="card-info">
            <div class="card-title">Theo dõi doanh thu</div>
            <div class="card-desc">Báo cáo doanh số, thống kê theo ngày, tháng, năm.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

        <a class="nav-card" href="./brands/index.php">
          <div class="card-icon-wrap"><i class="fas fa-trademark"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý thương hiệu</div>
            <div class="card-desc">Thêm và quản lý các thương hiệu sản phẩm trong hệ thống.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>


        <a class="nav-card" href="./chat/chat.php">
          <div class="card-icon-wrap"><i class="fas fa-comments"></i></div>
          <div class="card-info">
            <div class="card-title">Quản lý tư vấn khách hàng</div>
            <div class="card-desc">Xem và xử lý các yêu cầu tư vấn từ khách hàng.</div>
          </div>
          <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>

      </div>
    </div>
  </div>

  <script>
    // Live clock + date
    function updateTime() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      const s = String(now.getSeconds()).padStart(2, '0');
      const el = document.getElementById('js-time');
      if (el) el.textContent = `${h}:${m}:${s}`;

      const months = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
        'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
      ];
      const dayEl = document.getElementById('js-day');
      const monEl = document.getElementById('js-month');
      if (dayEl) dayEl.textContent = now.getDate();
      if (monEl) monEl.textContent = `${months[now.getMonth()]}, ${now.getFullYear()}`;
    }
    updateTime();
    setInterval(updateTime, 1000);
  </script>
</body>

</html>