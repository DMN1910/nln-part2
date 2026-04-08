<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/config.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: /camerashop/public/login.php");
    exit;
}

$adminName = $_SESSION['user']['name'] ?? 'Admin';
$adminInitial = mb_substr($adminName, 0, 1, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Chat tư vấn – Admin</title>
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

      --rooms-w: 300px;
      --page-pad: 40px;
      --header-h: 56px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }

    body {
      font-family: var(--font-body);
      background: var(--cream);
      color: var(--ink);
      min-height: 100vh;
      padding: 40px var(--page-pad) 0;
    }

    /* ── Breadcrumb ── */
    .breadcrumb {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--muted);
      margin-bottom: 14px;
    }
    .breadcrumb a { color: var(--muted); text-decoration: none; transition: color .2s; }
    .breadcrumb a:hover { color: var(--gold); }
    .breadcrumb .sep { color: var(--border-dark); }
    .breadcrumb .current { color: var(--ink); font-weight: 500; }

    /* ── Back button ── */
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

    /* ── Page header ── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-bottom: 24px; padding-bottom: 20px;
      border-bottom: 1.5px solid var(--border);
      flex-shrink: 0;
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

    /* ── Stats strip ── */
    .stats-strip {
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 10px 10px 0 0;
      padding: 10px 20px;
      display: flex; gap: 20px; align-items: center;
      flex-shrink: 0;
    }
    .stat-chip { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--muted); }
    .stat-chip strong { color: var(--ink); font-weight: 600; }
    .conn-wrap { margin-left: auto; display: flex; align-items: center; gap: 12px; }
    .topbar-conn {
      font-size: 11.5px;
      padding: 4px 10px; border-radius: 20px;
      background: var(--cream-dark);
      color: var(--muted);
      display: flex; align-items: center; gap: 6px;
      border: 1px solid var(--border);
    }
    .conn-dot { width: 7px; height: 7px; border-radius: 50%; background: #9ca3af; }
    .conn-dot.online { background: #16a34a; box-shadow: 0 0 0 2px rgba(22,163,74,.25); }
    .strip-time { font-size: 12px; color: var(--muted); }
    .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot-open   { background: #16a34a; }
    .dot-closed { background: var(--border-dark); }
    .dot-unread { background: #ef4444; }

    /* ── Workspace ── */
    .workspace {
      flex: 1;
      display: flex;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-top: none;
      border-radius: 0 0 14px 14px;
      overflow: hidden;
      margin-bottom: 40px;
      box-shadow: var(--shadow-sm);
      min-height: 0;
      height: calc(100vh - 260px);
    }

    /* ── Rooms panel ── */
    .rooms-panel {
      width: var(--rooms-w);
      border-right: 1.5px solid var(--border);
      background: var(--cream);
      display: flex; flex-direction: column;
      flex-shrink: 0; overflow: hidden;
    }
    .rooms-head {
      padding: 14px 16px 10px;
      border-bottom: 1px solid var(--border);
      background: var(--white);
    }
    .rooms-head h3 {
      font-family: var(--font-display);
      font-size: 17px; font-weight: 700; color: var(--ink);
      margin-bottom: 10px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .rooms-head h3 button {
      background: none; border: none; cursor: pointer;
      color: var(--muted); font-size: 13px; padding: 4px;
      border-radius: 6px; transition: color .2s, background .2s;
    }
    .rooms-head h3 button:hover { color: var(--gold); background: var(--gold-pale); }

    .search-box {
      width: 100%;
      border: 1.5px solid var(--border);
      border-radius: 8px;
      padding: 7px 11px;
      font-size: 12.5px; font-family: var(--font-body);
      color: var(--ink); background: var(--cream);
      outline: none; transition: border-color .2s;
      margin-bottom: 8px;
    }
    .search-box:focus { border-color: var(--gold); }
    .filter-row { display: flex; gap: 5px; }
    .fbtn {
      padding: 3px 11px; border-radius: 20px;
      border: 1.5px solid var(--border);
      background: transparent;
      font-size: 11px; font-family: var(--font-body);
      color: var(--muted); cursor: pointer;
      transition: all .2s;
    }
    .fbtn.active, .fbtn:hover {
      border-color: var(--gold); background: var(--gold-pale); color: var(--gold);
    }

    .rooms-list { flex: 1; overflow-y: auto; }
    .rooms-list::-webkit-scrollbar { width: 3px; }
    .rooms-list::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 4px; }

    .room-item {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      cursor: pointer; transition: background .15s;
      position: relative;
      background: var(--white);
    }
    .room-item:hover { background: var(--gold-pale); }
    .room-item.active { background: var(--gold-pale); border-left: 3px solid var(--gold); padding-left: 11px; }
    .room-top { display: flex; align-items: center; gap: 9px; margin-bottom: 4px; }
    .r-av {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: #fff;
      flex-shrink: 0; position: relative;
    }
    .r-av .odot {
      position: absolute; bottom: 1px; right: 1px;
      width: 8px; height: 8px; background: #16a34a;
      border-radius: 50%; border: 1.5px solid var(--white);
    }
    .r-meta { flex: 1; min-width: 0; }
    .r-name { font-size: 12.5px; font-weight: 500; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .room-item.unread .r-name { font-weight: 700; }
    .r-time { font-size: 10px; color: var(--muted); flex-shrink: 0; }
    .r-preview { font-size: 11.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-left: 43px; }
    .r-tag { font-size: 9px; font-weight: 600; padding: 2px 7px; border-radius: 10px; text-transform: uppercase; }
    .tag-open   { background: #dcfce7; color: #16a34a; }
    .tag-closed { background: var(--cream-dark); color: var(--muted); }
    .r-badge {
      position: absolute; right: 12px; bottom: 12px;
      background: #ef4444; color: #fff;
      font-size: 9.5px; font-weight: 700;
      padding: 2px 6px; border-radius: 10px;
    }

    .rooms-empty { padding: 40px 20px; text-align: center; color: var(--muted); font-size: 12.5px; }
    .rooms-empty i { font-size: 28px; margin-bottom: 10px; display: block; color: var(--border-dark); }

    /* ── Chat panel ── */
    .chat-panel {
      flex: 1; display: flex; flex-direction: column;
      background: var(--cream); overflow: hidden;
    }

    .chat-header {
      padding: 12px 20px;
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      display: flex; align-items: center; gap: 12px;
      flex-shrink: 0; box-shadow: var(--shadow-sm);
    }
    .ch-av {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, #667eea, #764ba2);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .ch-info { flex: 1; }
    .ch-name { font-size: 14px; font-weight: 600; color: var(--ink); }
    .ch-sub  { font-size: 11px; color: var(--muted); margin-top: 2px; }
    .ch-actions { display: flex; gap: 7px; }

    .act-btn {
      height: 32px; border-radius: 7px;
      border: 1.5px solid var(--border); background: var(--cream);
      display: flex; align-items: center; justify-content: center; gap: 5px;
      cursor: pointer; color: var(--muted); font-size: 12px;
      padding: 0 12px;
      transition: all .2s; font-family: var(--font-body);
    }
    .act-btn:hover { border-color: var(--gold); color: var(--gold); background: var(--gold-pale); }
    .act-btn.danger:hover { border-color: var(--danger); color: var(--danger); background: #fef2f2; }

    /* ── Messages ── */
    .chat-messages {
      flex: 1; padding: 16px 20px;
      overflow-y: auto;
      display: flex; flex-direction: column; gap: 10px;
    }
    .chat-messages::-webkit-scrollbar { width: 3px; }
    .chat-messages::-webkit-scrollbar-thumb { background: var(--border-dark); border-radius: 4px; }

    .msg-group { display: flex; gap: 8px; align-items: flex-end; max-width: 70%; }
    .msg-group.admin { flex-direction: row-reverse; align-self: flex-end; }
    .msg-group.user  { align-self: flex-start; }
    .msg-av {
      width: 26px; height: 26px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 700; flex-shrink: 0;
    }
    .msg-av.u { background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; }
    .msg-av.a { background: linear-gradient(135deg,var(--gold),var(--gold-light)); color:var(--ink); }
    .msg-body { display: flex; flex-direction: column; gap: 2px; }
    .msg-sender { font-size: 10px; color: var(--muted); padding: 0 4px; }
    .msg-group.admin .msg-sender { text-align: right; }
    .bubble {
      padding: 9px 13px; border-radius: 12px;
      font-size: 13px; line-height: 1.55; word-break: break-word;
    }
    .b-user  { background: var(--white); border: 1px solid var(--border); border-radius: 0 12px 12px 12px; color: var(--ink); }
    .b-admin { background: var(--ink); color: var(--white); border-radius: 12px 0 12px 12px; }
    .msg-time { font-size: 10px; color: var(--muted); padding: 0 4px; }
    .msg-group.admin .msg-time { text-align: right; }

    .date-sep { text-align: center; margin: 6px 0; display: flex; align-items: center; gap: 10px; }
    .date-sep span { font-size: 10.5px; color: var(--muted); background: var(--cream-dark); padding: 2px 10px; border-radius: 10px; white-space: nowrap; }
    .date-sep::before, .date-sep::after { content:''; flex:1; height:1px; background: var(--border); }

    .typing-indicator {
      align-self: flex-start; padding: 9px 13px;
      background: var(--white); border: 1px solid var(--border);
      border-radius: 0 12px 12px 12px;
      font-size: 11.5px; color: var(--muted); display: none;
    }
    .dots { display: inline-flex; gap: 3px; align-items: center; margin-left: 4px; }
    .dots span { width: 4px; height: 4px; background: var(--border-dark); border-radius: 50%; animation: db .9s infinite ease-in-out; }
    .dots span:nth-child(2) { animation-delay:.15s; }
    .dots span:nth-child(3) { animation-delay:.30s; }
    @keyframes db { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-4px)} }

    /* ── Footer / Input ── */
    .chat-footer {
      padding: 12px 16px;
      background: var(--white);
      border-top: 1.5px solid var(--border);
      flex-shrink: 0;
    }
    .closed-bar {
      text-align: center; padding: 11px;
      font-size: 13px; color: var(--muted);
      background: var(--cream-dark); border-radius: 8px;
      display: none;
    }
    .input-row { display: flex; align-items: flex-end; gap: 8px; }
    .attach-btn {
      background: none; border: none; color: var(--muted);
      font-size: 17px; cursor: pointer; padding: 6px;
      transition: color .2s; flex-shrink: 0; align-self: flex-end;
    }
    .attach-btn:hover { color: var(--gold); }
    .msg-textarea {
      flex: 1; border: 1.5px solid var(--border); border-radius: 10px;
      padding: 9px 13px; font-size: 13px; font-family: var(--font-body);
      color: var(--ink); resize: none; outline: none;
      min-height: 40px; max-height: 110px; line-height: 1.45;
      transition: border-color .2s; background: var(--cream);
    }
    .msg-textarea:focus { border-color: var(--gold); background: var(--white); }
    .msg-textarea::placeholder { color: var(--muted); }
    .send-btn {
      width: 40px; height: 40px; border-radius: 9px;
      background: var(--ink); border: none; color: var(--white);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; cursor: pointer;
      transition: background .2s, transform .15s;
      flex-shrink: 0;
    }
    .send-btn:hover { background: var(--gold); transform: scale(1.05); }
    .send-btn:disabled { background: var(--border-dark); cursor: not-allowed; transform: none; }

    /* ── Placeholder ── */
    .chat-placeholder {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--muted); text-align: center; gap: 14px;
    }
    .ph-icon {
      width: 68px; height: 68px; border-radius: 18px;
      background: var(--gold-pale); border: 2px solid var(--gold-border);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; color: var(--gold);
    }
    .ph-title { font-family: var(--font-display); font-size: 20px; font-weight: 700; color: var(--ink-soft); }
    .ph-sub { font-size: 12.5px; max-width: 240px; line-height: 1.6; }

    /* ── Toast ── */
    #toast {
      position: fixed; bottom: 24px; right: 24px;
      background: var(--ink); color: #fff;
      padding: 11px 18px; border-radius: 10px;
      font-size: 13px; box-shadow: var(--shadow-lg);
      transform: translateY(70px); opacity: 0;
      transition: all .3s; z-index: 9999;
      border-left: 3px solid var(--gold-light);
      pointer-events: none;
    }
    #toast.show { transform: translateY(0); opacity: 1; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .msg-group { animation: fadeUp .18s ease; }
    .room-item  { animation: fadeUp .2s ease; }
  </style>
</head>
<body>

  <!-- ── Breadcrumb ── -->
  <nav class="breadcrumb">
    <a href="/camerashop/admin/index.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <span class="sep"><i class="fas fa-chevron-right"></i></span>
    <span class="current">Chat tư vấn</span>
  </nav>

  <!-- ── Back button ── -->
  <a class="btn-back" href="/camerashop/admin/index.php">
    <i class="fas fa-arrow-left"></i> Quay về Dashboard
  </a>

  <!-- ── Page header ── -->
  <div class="page-header">
    <div>
      <div class="gold-line"></div>
      <h1 class="page-title">Chat tư vấn</h1>
      <p class="page-sub">Quản lý và trả lời tin nhắn từ khách hàng theo thời gian thực</p>
    </div>
  </div>

  <!-- ── Stats strip (now styled as a card top-bar) ── -->
  <div class="stats-strip">
    <div class="stat-chip"><div class="dot dot-open"></div>Đang mở: <strong id="stat-open">0</strong></div>
    <div class="stat-chip"><div class="dot dot-closed"></div>Đã đóng: <strong id="stat-closed">0</strong></div>
    <div class="stat-chip"><div class="dot dot-unread"></div>Chưa đọc: <strong id="stat-unread">0</strong></div>
    <div class="conn-wrap">
      <div class="strip-time" id="js-time"></div>
      <div class="topbar-conn">
        <div class="conn-dot" id="conn-dot"></div>
        <span id="conn-status">Đang kết nối...</span>
      </div>
    </div>
  </div>

  <!-- ── Workspace card ── -->
  <div class="workspace">

    <!-- Rooms -->
    <div class="rooms-panel">
      <div class="rooms-head">
        <h3>
          Phòng chat
          <button onclick="loadRooms()" title="Làm mới"><i class="fas fa-sync-alt" id="ref-icon"></i></button>
        </h3>
        <input class="search-box" type="text" id="search-input" placeholder="🔍  Tìm kiếm..." oninput="renderRooms()">
        <div class="filter-row">
          <button class="fbtn active" onclick="setFilter('all',this)">Tất cả</button>
          <button class="fbtn" onclick="setFilter('open',this)">Mở</button>
          <button class="fbtn" onclick="setFilter('closed',this)">Đóng</button>
          <button class="fbtn" onclick="setFilter('unread',this)">Chưa đọc</button>
        </div>
      </div>
      <div class="rooms-list" id="rooms-list">
        <div class="rooms-empty"><i class="fas fa-spinner fa-spin"></i>Đang tải...</div>
      </div>
    </div>

    <!-- Chat -->
    <div class="chat-panel" id="chat-panel">

      <!-- Placeholder -->
      <div class="chat-placeholder" id="placeholder">
        <div class="ph-icon"><i class="fas fa-comments"></i></div>
        <div class="ph-title">Chọn cuộc hội thoại</div>
        <div class="ph-sub">Chọn phòng chat bên trái để bắt đầu tư vấn khách hàng.</div>
      </div>

      <!-- Active chat -->
      <div id="active-chat" style="display:none;flex-direction:column;flex:1;overflow:hidden;">

        <div class="chat-header">
          <div class="ch-av" id="ch-av">?</div>
          <div class="ch-info">
            <div class="ch-name" id="ch-name">–</div>
            <div class="ch-sub"  id="ch-sub">–</div>
          </div>
          <div class="ch-actions">
            <button class="act-btn" onclick="refreshMessages()" title="Làm mới tin nhắn">
              <i class="fas fa-sync-alt"></i> Làm mới
            </button>
            <button class="act-btn danger" onclick="closeRoom()" id="close-btn" title="Đóng phòng">
              <i class="fas fa-times-circle"></i> Đóng phòng
            </button>
          </div>
        </div>

        <div class="chat-messages" id="chat-messages">
          <div class="typing-indicator" id="typing-ind">
            Khách đang gõ<span class="dots"><span></span><span></span><span></span></span>
          </div>
        </div>

        <div class="chat-footer" id="chat-footer">
          <div class="closed-bar" id="closed-bar">
            <i class="fas fa-lock" style="margin-right:6px"></i>Cuộc trò chuyện đã kết thúc
          </div>
          <div class="input-row" id="input-row">
            <button class="attach-btn" onclick="document.getElementById('file-inp').click()" title="Đính kèm">
              <i class="fas fa-paperclip"></i>
            </button>
            <input type="file" id="file-inp" accept="image/*,.pdf,.doc,.docx" style="display:none" onchange="handleFile(this)">
            <textarea class="msg-textarea" id="msg-input"
              placeholder="Nhập tin nhắn tư vấn... (Enter để gửi, Shift+Enter xuống dòng)"
              rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
            <button class="send-btn" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>

      </div>
    </div>

  </div>

  <div id="toast"></div>

  <script src="http://localhost:3000/socket.io/socket.io.js"></script>
  <script>
    const CHAT_SERVER = 'http://localhost:3000';
    const ADMIN_NAME  = <?= json_encode($adminName) ?>;

    let socket = null, allRooms = [], activeRoom = null, filterMode = 'all', typingTimer = null;

    /* ── Clock ── */
    function tick() {
      const n = new Date();
      const pad = v => String(v).padStart(2,'0');
      document.getElementById('js-time').textContent =
        `${pad(n.getHours())}:${pad(n.getMinutes())}:${pad(n.getSeconds())}`;
    }
    tick(); setInterval(tick, 1000);

    /* ── Toast ── */
    function toast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg; t.classList.add('show');
      setTimeout(() => t.classList.remove('show'), 3000);
    }

    /* ── Socket ── */
    function initSocket() {
      socket = io(CHAT_SERVER);

      socket.on('connect', () => {
        document.getElementById('conn-dot').classList.add('online');
        document.getElementById('conn-status').textContent = 'Đã kết nối';
        socket.emit('admin:join');
        loadRooms();
      });

      socket.on('disconnect', () => {
        document.getElementById('conn-dot').classList.remove('online');
        document.getElementById('conn-status').textContent = 'Mất kết nối';
      });

      socket.on('message:new', msg => {
        const room = allRooms.find(r => r.id == msg.room_id);
        if (room) {
          room.last_message = msg.message;
          room.last_message_time = msg.created_at;
          if (msg.sender === 'user' && (!activeRoom || activeRoom.id != msg.room_id))
            room.unread_count = (room.unread_count || 0) + 1;
        }

        if (activeRoom && activeRoom.id == msg.room_id) {
          appendMsg(msg);
          if (msg.sender === 'user')
            fetch(`${CHAT_SERVER}/api/rooms/${activeRoom.id}/read`, { method:'POST' });
        } else if (msg.sender === 'user') {
          const n = room ? (room.user_name || room.guest_name || 'Khách') : 'Khách';
          toast(`💬 Tin nhắn mới từ ${n}`);
        }

        renderRooms(); updateStats();
      });

      socket.on('typing:show', ({ sender }) => {
        if (sender === 'user') {
          document.getElementById('typing-ind').style.display = 'block';
          scrollBot();
        }
      });
      socket.on('typing:hide', () => {
        document.getElementById('typing-ind').style.display = 'none';
      });

      socket.on('room:closed', () => {
        if (activeRoom) activeRoom.status = 'closed';
        toggleInput(false);
      });
    }

    /* ── Load rooms ── */
    async function loadRooms() {
      const ic = document.getElementById('ref-icon');
      ic.classList.add('fa-spin');
      try {
        const res = await fetch(`${CHAT_SERVER}/api/rooms`);
        allRooms = await res.json();
        renderRooms(); updateStats();
      } catch(e) {
        document.getElementById('rooms-list').innerHTML =
          `<div class="rooms-empty"><i class="fas fa-exclamation-circle"></i>Không thể tải</div>`;
      } finally {
        setTimeout(() => ic.classList.remove('fa-spin'), 400);
      }
    }

    /* ── Filter ── */
    function setFilter(mode, btn) {
      filterMode = mode;
      document.querySelectorAll('.fbtn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      renderRooms();
    }

    /* ── Render rooms ── */
    function renderRooms() {
      const q = document.getElementById('search-input').value.toLowerCase();
      let rooms = allRooms.filter(r => {
        const name = (r.user_name || r.guest_name || 'Khách').toLowerCase();
        const matchQ = !q || name.includes(q) || (r.last_message||'').toLowerCase().includes(q);
        const matchF = filterMode === 'all'
          || (filterMode === 'open'   && r.status === 'open')
          || (filterMode === 'closed' && r.status === 'closed')
          || (filterMode === 'unread' && r.unread_count > 0);
        return matchQ && matchF;
      });

      rooms.sort((a,b) => {
        if (a.status !== b.status) return a.status === 'open' ? -1 : 1;
        const ta = new Date(a.last_message_time || a.created_at);
        const tb = new Date(b.last_message_time || b.created_at);
        return tb - ta;
      });

      const list = document.getElementById('rooms-list');
      if (!rooms.length) {
        list.innerHTML = `<div class="rooms-empty"><i class="fas fa-inbox"></i>Không có phòng nào</div>`;
        return;
      }

      list.innerHTML = rooms.map(r => {
        const name = r.user_name || r.guest_name || 'Khách';
        const init = name.charAt(0).toUpperCase();
        const isAct = activeRoom && activeRoom.id === r.id;
        const unread = r.unread_count > 0;
        const preview = !r.last_message ? 'Chưa có tin nhắn'
          : r.last_message.startsWith('[image]') ? '📷 Hình ảnh'
          : r.last_message.startsWith('[file]')  ? '📄 File đính kèm'
          : r.last_message.substring(0,45) + (r.last_message.length > 45 ? '…' : '');
        const t = fmtTime(new Date(r.last_message_time || r.created_at));

        return `
          <div class="room-item ${isAct?'active':''} ${unread?'unread':''}" onclick="selectRoom(${r.id})">
            <div class="room-top">
              <div class="r-av">${init}${r.status==='open'?'<div class="odot"></div>':''}</div>
              <div class="r-meta"><div class="r-name">${esc(name)}</div></div>
              <span class="r-time">${t}</span>
              <span class="r-tag ${r.status==='open'?'tag-open':'tag-closed'}">${r.status==='open'?'Mở':'Đóng'}</span>
            </div>
            <div class="r-preview">${esc(preview)}</div>
            ${unread?`<span class="r-badge">${r.unread_count}</span>`:''}
          </div>`;
      }).join('');
    }

    /* ── Stats ── */
    function updateStats() {
      document.getElementById('stat-open').textContent   = allRooms.filter(r=>r.status==='open').length;
      document.getElementById('stat-closed').textContent = allRooms.filter(r=>r.status==='closed').length;
      document.getElementById('stat-unread').textContent = allRooms.reduce((s,r)=>s+(r.unread_count||0),0);
    }

    /* ── Select room ── */
    async function selectRoom(id) {
      const room = allRooms.find(r => r.id === id);
      if (!room) return;
      activeRoom = room;

      document.getElementById('placeholder').style.display = 'none';
      const ac = document.getElementById('active-chat');
      ac.style.display = 'flex';

      const name = room.user_name || room.guest_name || 'Khách';
      document.getElementById('ch-av').textContent   = name.charAt(0).toUpperCase();
      document.getElementById('ch-name').textContent = name;
      document.getElementById('ch-sub').textContent  =
        (room.user_email ? `${room.user_email} · ` : '') + `Phòng #${room.id}`;

      toggleInput(room.status !== 'closed');

      socket.emit('room:join', { room_id: id });
      room.unread_count = 0;
      fetch(`${CHAT_SERVER}/api/rooms/${id}/read`, { method:'POST' });
      renderRooms(); updateStats();

      const msgs = document.getElementById('chat-messages');
      msgs.innerHTML = `<div class="rooms-empty"><i class="fas fa-spinner fa-spin"></i>Đang tải tin nhắn...</div>
        <div class="typing-indicator" id="typing-ind">Khách đang gõ<span class="dots"><span></span><span></span><span></span></span></div>`;

      try {
        const res = await fetch(`${CHAT_SERVER}/api/messages/${id}`);
        const list = await res.json();
        let lastDate = null;
let html = '';

if (!list.length) {
  html = `<div style="text-align:center;color:var(--muted);font-size:12.5px;padding:40px 0">
    <i class="fas fa-comment-slash" style="font-size:22px;margin-bottom:8px;display:block"></i>Chưa có tin nhắn
  </div>
  <div class="typing-indicator" id="typing-ind" style="display:none">Khách đang gõ<span class="dots"><span></span><span></span><span></span></span></div>`;
} else {
  list.forEach(msg => {
    const d  = new Date(msg.created_at);
    const ds = d.toLocaleDateString('vi-VN',{weekday:'long',day:'numeric',month:'long'});
    if (ds !== lastDate) {
      html += `<div class="date-sep"><span>${ds}</span></div>`;
      lastDate = ds;
    }
    html += msgHTML(msg);
  });
  html += `<div class="typing-indicator" id="typing-ind" style="display:none">Khách đang gõ<span class="dots"><span></span><span></span><span></span></span></div>`;
}

msgs.innerHTML = html;
scrollBot();
      } catch(e) { console.error(e); }
    }

    /* ── Message HTML ── */
    function msgHTML(msg) {
      const isA  = msg.sender === 'admin';
      const name = msg.sender_name || (isA ? 'Admin' : 'Khách');
      const init = name.charAt(0).toUpperCase();
      const time = fmtTime(new Date(msg.created_at));
      const content = renderContent(msg.message, isA);
      return `
        <div class="msg-group ${isA?'admin':'user'}">
          <div class="msg-av ${isA?'a':'u'}">${init}</div>
          <div class="msg-body">
            <div class="msg-sender">${esc(name)}</div>
            <div class="bubble ${isA?'b-admin':'b-user'}">${content}</div>
            <div class="msg-time">${time}</div>
          </div>
        </div>`;
    }

    function appendMsg(msg) {
      const msgs   = document.getElementById('chat-messages');
      const typing = document.getElementById('typing-ind');
      const div    = document.createElement('div');
      div.innerHTML = msgHTML(msg);
      msgs.insertBefore(div.firstElementChild, typing);
      scrollBot();
    }

    function renderContent(text, isAdmin) {
      if (text.startsWith('[image]')) {
        const url = text.replace('[image]','').replace('[/image]','');
        return `<img src="${CHAT_SERVER}${url}" style="max-width:190px;border-radius:8px;cursor:pointer;display:block" onclick="window.open('${CHAT_SERVER}${url}')">`;
      }
      if (text.startsWith('[file]')) {
        const parts = text.replace('[file]','').replace('[/file]','').split('|');
        const bg = isAdmin ? 'rgba(255,255,255,.12)' : 'var(--cream-dark)';
        return `<a href="${CHAT_SERVER}${parts[1]}" target="_blank" style="display:flex;align-items:center;gap:6px;padding:5px 9px;background:${bg};border-radius:7px;font-size:12px;text-decoration:none;color:inherit">📄 ${esc(parts[0])}</a>`;
      }
      return esc(text).replace(/\n/g,'<br>');
    }

    /* ── Send ── */
    async function sendMsg() {
      const input = document.getElementById('msg-input');
      const msg   = input.value.trim();
      if (!activeRoom || activeRoom.status === 'closed' || !msg) return;

      socket.emit('message:send', {
        room_id:     activeRoom.id,
        message:     msg,
        sender:      'admin',
        sender_name: ADMIN_NAME
      });

      input.value = ''; input.style.height = 'auto';
      socket.emit('typing:stop', { room_id: activeRoom.id });
    }

    function handleKey(e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); return; }
      if (!socket || !activeRoom) return;
      socket.emit('typing:start', { room_id: activeRoom.id, sender: 'admin' });
      clearTimeout(typingTimer);
      typingTimer = setTimeout(() => socket.emit('typing:stop', { room_id: activeRoom.id }), 1500);
    }

    function autoResize(el) {
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 110) + 'px';
    }

    /* ── File upload ── */
    async function handleFile(inp) {
      if (!inp.files[0] || !activeRoom || activeRoom.status === 'closed') return;
      const fd = new FormData();
      fd.append('file', inp.files[0]);
      fd.append('room_id',     activeRoom.id);
      fd.append('sender',      'admin');
      fd.append('sender_name', ADMIN_NAME);
      try {
        toast('Đang gửi file...');
        await fetch(`${CHAT_SERVER}/api/upload`, { method:'POST', body:fd });
        inp.value = '';
      } catch(e) { toast('Lỗi gửi file!'); }
    }

    /* ── Close room ── */
    async function closeRoom() {
      if (!activeRoom || activeRoom.status === 'closed') return;
      if (!confirm('Đóng phòng chat này? Khách sẽ không thể gửi thêm tin nhắn.')) return;
      try {
        await fetch(`${CHAT_SERVER}/api/rooms/${activeRoom.id}/close`, { method:'POST' });
        activeRoom.status = 'closed';
        toggleInput(false);
        toast('✅ Đã đóng phòng chat');
        loadRooms();
      } catch(e) { toast('Lỗi đóng phòng!'); }
    }

    function toggleInput(open) {
      document.getElementById('input-row').style.display  = open ? 'flex'  : 'none';
      document.getElementById('closed-bar').style.display = open ? 'none'  : 'block';
      document.getElementById('close-btn').style.display  = open ? 'flex'  : 'none';
    }

    async function refreshMessages() {
      if (activeRoom) await selectRoom(activeRoom.id);
    }

    /* ── Helpers ── */
    function scrollBot() {
      const m = document.getElementById('chat-messages');
      if (m) m.scrollTop = m.scrollHeight;
    }

    function fmtTime(d) {
      const now = new Date();
      const pad = v => String(v).padStart(2,'0');
      const hm  = `${pad(d.getHours())}:${pad(d.getMinutes())}`;
      return d.toDateString() === now.toDateString()
        ? hm : `${d.getDate()}/${d.getMonth()+1} ${hm}`;
    }

    function esc(s) {
      return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Init ── */
    initSocket();
    setInterval(loadRooms, 30000);
  </script>
</body>
</html>