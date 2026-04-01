<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
$user_name = isset($_SESSION['user']['name']) ? htmlspecialchars($_SESSION['user']['name']) : 'Khách';
$chat_session_id = '';
if (isset($_SESSION['user'])) {
    if (!isset($_SESSION['chat_session_id'])) {
        $_SESSION['chat_session_id'] = uniqid('chat_', true);
    }
    $chat_session_id = $_SESSION['chat_session_id'];
}

// $server_host = $_SERVER['HTTP_HOST']; // tự lấy host hiện tại
// $iframe_src = "http://{$server_host}:7860/?user_id={$user_id}&session_id=" . urlencode($chat_session_id);
$iframe_src = "http://localhost:7860/?user_id={$user_id}&session_id=" . urlencode($chat_session_id);
?>
<style>
#cs-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
    font-family: Inter, sans-serif;
}
#cs-box {
    display: none;
    width: 380px;
    height: 520px;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}
#cs-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
#cs-header .info { display: flex; align-items: center; gap: 10px; }
#cs-header .avatar {
    width: 36px; height: 36px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}
#cs-header .name { color: #fff; font-weight: 600; font-size: 14px; }
#cs-header .status { color: rgba(255,255,255,0.8); font-size: 11px; }
#cs-close {
    background: rgba(255,255,255,0.15);
    border: none; color: #fff;
    width: 28px; height: 28px;
    border-radius: 50%;
    cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
}
#cs-iframe { flex: 1; border: none; width: 100%; }
#cs-btn {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none; border-radius: 50%;
    cursor: pointer; font-size: 26px;
    box-shadow: 0 4px 16px rgba(102,126,234,0.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.2s;
}
#cs-btn:hover { transform: scale(1.08); }
#cs-badge {
    position: absolute;
    top: -4px; right: -4px;
    background: #e53e3e;
    color: #fff;
    border-radius: 50%;
    width: 18px; height: 18px;
    font-size: 11px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    border: 2px solid #fff;
}
</style>

<div id="cs-widget">
    <div id="cs-box">
        <div id="cs-header">
            <div class="info">
                <div class="avatar"></div>
                <div>
                    <div class="name">Chat tư vấn Camerashop
                    </div>
                    <div class="status">
                        <?php if ($user_id > 0): ?>
                            Xin chào, <?= $user_name ?>!
                        <?php else: ?>
                            Đang hoạt động
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <button id="cs-close" onclick="csToggle()">✕</button>
        </div>
        <iframe id="cs-iframe" src="about:blank"></iframe>
    </div>

    <div style="position:relative;display:inline-block;">
        <button id="cs-btn" onclick="csToggle()">💬</button>
        <span id="cs-badge" style="display:none;">1</span>
    </div>
</div>

<script>
var csOpen = false;
var csLoaded = false;
var iframeSrc = "<?= $iframe_src ?>";

function csToggle() {
    csOpen = !csOpen;
    var box = document.getElementById('cs-box');
    var btn = document.getElementById('cs-btn');
    var badge = document.getElementById('cs-badge');

    box.style.display = csOpen ? 'flex' : 'none';
    btn.textContent = csOpen ? '✕' : '💬';
    badge.style.display = 'none';

    if (csOpen && !csLoaded) {
        document.getElementById('cs-iframe').src = iframeSrc;
        csLoaded = true;
    }
}

setTimeout(function() {
    document.getElementById('cs-badge').style.display = 'flex';
}, 3000);
</script>