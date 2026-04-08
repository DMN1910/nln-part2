(function() {
    const CHAT_SERVER = 'http://localhost:3000';
    const USER_ID = window.CHAT_USER_ID || 0;
    const USER_NAME = window.CHAT_USER_NAME || 'Khách';

    const style = document.createElement('style');
    style.textContent = `
        #rc-widget{position:fixed;bottom:100px;right:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-end;gap:12px;font-family:Inter,sans-serif}
        #rc-box{display:none;width:360px;height:520px;background:#fff;border-radius:16px;border:1px solid #e2e8f0;flex-direction:column;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.15)}
        #rc-header{background:linear-gradient(135deg,#667eea,#764ba2);padding:12px 16px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
        #rc-header .info{display:flex;align-items:center;gap:10px}
        #rc-header .avatar{width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px}
        #rc-header .name{color:#fff;font-weight:600;font-size:14px}
        #rc-header .status{color:rgba(255,255,255,0.8);font-size:11px}
        #rc-close{background:rgba(255,255,255,0.15);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:16px}
        #rc-messages{flex:1;padding:12px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;background:#f8f9fa}
        .rc-msg{display:flex;gap:8px;align-items:flex-end;max-width:85%}
        .rc-msg.user{flex-direction:row-reverse;align-self:flex-end}
        .rc-msg .bubble{padding:8px 12px;border-radius:12px;font-size:13px;line-height:1.5;word-break:break-word}
        .rc-msg.admin .bubble{background:#fff;border:1px solid #e2e8f0;border-radius:0 12px 12px 12px;color:#333}
        .rc-msg.user .bubble{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border-radius:12px 0 12px 12px}
        .rc-msg .time{font-size:10px;color:#999;flex-shrink:0}
        .rc-msg .av{width:24px;height:24px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600}
        .rc-msg.admin .av{background:#667eea;color:#fff}
        .rc-msg.user .av{background:#e2e8f0;color:#666}
        .rc-img{max-width:200px;border-radius:8px;cursor:pointer;display:block;margin-top:4px}
        .rc-file{display:flex;align-items:center;gap:6px;padding:6px 10px;background:rgba(255,255,255,0.15);border-radius:8px;font-size:12px;text-decoration:none;color:inherit;margin-top:4px}
        #rc-typing{font-size:12px;color:#999;padding:4px 12px;display:none;flex-shrink:0}
        #rc-footer{padding:8px 10px;border-top:1px solid #e2e8f0;display:flex;flex-direction:column;gap:6px;background:#fff;flex-shrink:0}
        #rc-toolbar{display:flex;align-items:center;gap:6px}
        #rc-input{flex:1;border:1px solid #e2e8f0;border-radius:20px;padding:8px 14px;font-size:13px;outline:none;font-family:Inter,sans-serif}
        #rc-input:focus{border-color:#667eea}
        #rc-send{background:linear-gradient(135deg,#667eea,#764ba2);border:none;color:#fff;width:36px;height:36px;border-radius:50%;cursor:pointer;font-size:14px;flex-shrink:0}
        #rc-attach{background:none;border:none;color:#999;cursor:pointer;font-size:18px;padding:4px;flex-shrink:0}
        #rc-attach:hover{color:#667eea}
        #rc-preview{display:none;padding:6px 10px;background:#f0f2ff;border-radius:8px;font-size:12px;color:#667eea;align-items:center;justify-content:space-between}
        #rc-preview span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        #rc-preview button{background:none;border:none;color:#999;cursor:pointer;font-size:14px;flex-shrink:0}
        #rc-btn{width:56px;height:56px;background:linear-gradient(135deg,#667eea,#764ba2);border:none;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 4px 16px rgba(102,126,234,0.45);transition:transform 0.2s}
        #rc-btn:hover{transform:scale(1.08)}
        #rc-badge{position:absolute;top:-4px;right:-4px;background:#e53e3e;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:700;display:none;align-items:center;justify-content:center;border:2px solid #fff}
        #rc-closed-notice{padding:10px;text-align:center;font-size:13px;color:#999;background:#fff8f0;border-top:1px solid #ffe0b2;flex-shrink:0;display:none}
    `;
    document.head.appendChild(style);

    const widget = document.createElement('div');
    widget.id = 'rc-widget';
    widget.innerHTML = `
        <div id="rc-box">
            <div id="rc-header">
                <div class="info">
                    <div class="avatar">🛒</div>
                    <div>
                        <div class="name">Camera Shop</div>
                        <div class="status" id="rc-status">Đang kết nối...</div>
                    </div>
                </div>
                <button id="rc-close">✕</button>
            </div>
            <div id="rc-messages"></div>
            <div id="rc-typing">Nhân viên đang gõ...</div>
            <div id="rc-closed-notice">Cuộc trò chuyện đã kết thúc</div>
            <div id="rc-footer">
                <div id="rc-preview">
                    <span id="rc-preview-name"></span>
                    <button onclick="clearFile()">✕</button>
                </div>
                <div id="rc-toolbar">
                    <button id="rc-attach" title="Gửi ảnh/file">📎</button>
                    <input type="file" id="rc-file-input" accept="image/*,.pdf,.doc,.docx" style="display:none">
                    <input id="rc-input" type="text" placeholder="Nhập tin nhắn...">
                    <button id="rc-send">➤</button>
                </div>
            </div>
        </div>
        <div style="position:relative;display:inline-block;">
            <button id="rc-btn">💬</button>
            <span id="rc-badge">0</span>
        </div>
    `;
    document.body.appendChild(widget);

    let socket = null, roomId = null, isOpen = false, unreadCount = 0;
    let typingTimer = null, selectedFile = null;

    function formatTime(d) {
        const dt = new Date(d);
        return dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0');
    }

    function renderMessage(msg) {
        const text = msg.message;
        if (text.startsWith('[image]')) {
            const url = text.replace('[image]','').replace('[/image]','');
            return `<img src="${CHAT_SERVER}${url}" class="rc-img" onclick="window.open('${CHAT_SERVER}${url}')">`;
        }
        if (text.startsWith('[file]')) {
            const parts = text.replace('[file]','').replace('[/file]','').split('|');
            return `<a href="${CHAT_SERVER}${parts[1]}" target="_blank" class="rc-file">📄 ${parts[0]}</a>`;
        }
        return text.replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function addMessage(msg) {
        const el = document.getElementById('rc-messages');
        if (!el) return;
        const isUser = msg.sender === 'user';
        const div = document.createElement('div');
        div.className = `rc-msg ${isUser ? 'user' : 'admin'}`;
        div.innerHTML = `
            <div class="av">${isUser ? USER_NAME.charAt(0).toUpperCase() : 'CS'}</div>
            <div>
                <div class="bubble">${renderMessage(msg)}</div>
                <div class="time">${formatTime(msg.created_at)}</div>
            </div>
        `;
        el.appendChild(div);
        el.scrollTop = el.scrollHeight;
    }

    function updateBadge(count) {
        unreadCount = count;
        const badge = document.getElementById('rc-badge');
        if (count > 0 && !isOpen) { badge.textContent = count; badge.style.display = 'flex'; }
        else badge.style.display = 'none';
    }

    function clearFile() {
        selectedFile = null;
        document.getElementById('rc-file-input').value = '';
        document.getElementById('rc-preview').style.display = 'none';
    }

    async function initRoom() {
        try {
            const res = await fetch(`${CHAT_SERVER}/api/rooms`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: USER_ID || null, guest_name: USER_NAME })
            });
            const room = await res.json();
            roomId = room.id;

            if (room.status === 'closed') {
                document.getElementById('rc-footer').style.display = 'none';
                document.getElementById('rc-closed-notice').style.display = 'block';
            }

            const msgRes = await fetch(`${CHAT_SERVER}/api/messages/${roomId}`);
            const messages = await msgRes.json();
            messages.forEach(addMessage);
            socket.emit('room:join', { room_id: roomId });
        } catch (e) {}
    }

    async function sendMessage() {
        const input = document.getElementById('rc-input');
        const msg = input.value.trim();
        if (!roomId || !socket) return;

        if (selectedFile) {
            const fd = new FormData();
            fd.append('file', selectedFile);
            fd.append('room_id', roomId);
            fd.append('sender', 'user');
            fd.append('sender_name', USER_NAME);
            try {
                await fetch(`${CHAT_SERVER}/api/upload`, { method: 'POST', body: fd });
            } catch(e) {}
            clearFile();
        }

        if (msg) {
            socket.emit('message:send', { room_id: roomId, message: msg, sender: 'user', sender_name: USER_NAME });
            input.value = '';
            socket.emit('typing:stop', { room_id: roomId });
        }
    }

    function connectSocket() {
        const script = document.createElement('script');
        script.src = `${CHAT_SERVER}/socket.io/socket.io.js`;
        script.onload = function() {
            socket = io(CHAT_SERVER);
            socket.on('connect', () => {
                document.getElementById('rc-status').textContent = 'Nhân viên hỗ trợ';
                if (roomId) socket.emit('room:join', { room_id: roomId });
            });
            socket.on('disconnect', () => {
                document.getElementById('rc-status').textContent = 'Mất kết nối...';
            });
            socket.on('message:new', msg => {
                if (msg.room_id != roomId) return;
                addMessage(msg);
                if (msg.sender !== 'user' && !isOpen) updateBadge(unreadCount + 1);
            });
            socket.on('room:closed', () => {
                document.getElementById('rc-footer').style.display = 'none';
                document.getElementById('rc-closed-notice').style.display = 'block';
            });
            socket.on('typing:show', () => document.getElementById('rc-typing').style.display = 'block');
            socket.on('typing:hide', () => document.getElementById('rc-typing').style.display = 'none');
            if (isOpen) initRoom();
        };
        document.head.appendChild(script);
    }

    function toggle() {
        isOpen = !isOpen;
        const box = document.getElementById('rc-box');
        const btn = document.getElementById('rc-btn');
        box.style.display = isOpen ? 'flex' : 'none';
        btn.textContent = isOpen ? '✕' : '💬';
        updateBadge(0);
        if (isOpen && !socket) { connectSocket(); setTimeout(initRoom, 500); }
        else if (isOpen && roomId) {
            fetch(`${CHAT_SERVER}/api/rooms/${roomId}/read`, { method: 'POST' });
            const msgs = document.getElementById('rc-messages');
            if (msgs) msgs.scrollTop = msgs.scrollHeight;
        }
    }

    document.getElementById('rc-close').onclick = toggle;
    document.getElementById('rc-btn').onclick = toggle;
    document.getElementById('rc-send').onclick = sendMessage;
    document.getElementById('rc-attach').onclick = () => document.getElementById('rc-file-input').click();

    document.getElementById('rc-file-input').onchange = function() {
        if (this.files[0]) {
            selectedFile = this.files[0];
            document.getElementById('rc-preview-name').textContent = '📎 ' + selectedFile.name;
            document.getElementById('rc-preview').style.display = 'flex';
        }
    };

    document.getElementById('rc-input').onkeydown = function(e) {
        if (e.key === 'Enter') { sendMessage(); return; }
        if (!socket || !roomId) return;
        socket.emit('typing:start', { room_id: roomId, sender: 'user' });
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => socket.emit('typing:stop', { room_id: roomId }), 1500);
    };

    setTimeout(() => { if (!isOpen) updateBadge(1); }, 3000);
})();