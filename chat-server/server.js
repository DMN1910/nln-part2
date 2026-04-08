const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const mysql = require('mysql2/promise');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const app = express();
const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: '*', methods: ['GET', 'POST'] }
});

app.use(cors());
app.use(express.json());

// Thư mục upload
const uploadDir = path.join(__dirname, '../public/chat_uploads');
if (!fs.existsSync(uploadDir)) fs.mkdirSync(uploadDir, { recursive: true });

const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, uploadDir),
    filename: (req, file, cb) => {
        const ext = path.extname(file.originalname);
        cb(null, Date.now() + '-' + Math.round(Math.random() * 1000) + ext);
    }
});
const upload = multer({
    storage,
    limits: { fileSize: 5 * 1024 * 1024 }, // 5MB
    fileFilter: (req, file, cb) => {
        const allowed = /jpeg|jpg|png|gif|webp|pdf|doc|docx/;
        const ok = allowed.test(path.extname(file.originalname).toLowerCase());
        cb(null, ok);
    }
});

app.use('/chat_uploads', express.static(uploadDir));

const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'camerashop'
};

async function getDb() {
    return await mysql.createConnection(dbConfig);
}

// API lấy danh sách phòng chat
app.get('/api/rooms', async (req, res) => {
    try {
        const db = await getDb();
        const [rows] = await db.execute(`
            SELECT r.*, u.name as user_name, u.email as user_email,
                (SELECT message FROM chat_messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM chat_messages WHERE room_id = r.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM chat_messages WHERE room_id = r.id AND sender = 'user' AND is_read = 0) as unread_count
            FROM chat_rooms r
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY last_message_time DESC
        `);
        await db.end();
        res.json(rows);
    } catch (e) {
        console.error('❌ /api/rooms error:', e);
        res.status(500).json({ error: e.message });
    }
});

// API lấy tin nhắn theo phòng
app.get('/api/messages/:roomId', async (req, res) => {
    try {
        const db = await getDb();
        const [rows] = await db.execute(
            'SELECT * FROM chat_messages WHERE room_id = ? ORDER BY created_at ASC',
            [req.params.roomId]
        );
        await db.end();
        res.json(rows);
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// API tạo hoặc lấy phòng chat
app.post('/api/rooms', async (req, res) => {
    try {
        const { user_id, guest_name } = req.body;
        const db = await getDb();
        if (user_id) {
            const [existing] = await db.execute(
                'SELECT * FROM chat_rooms WHERE user_id = ? AND status != "closed"',
                [user_id]
            );
            if (existing.length > 0) { await db.end(); return res.json(existing[0]); }
        }
        const [result] = await db.execute(
            'INSERT INTO chat_rooms (user_id, guest_name, status) VALUES (?, ?, "open")',
            [user_id || null, guest_name || 'Khách']
        );
        const [room] = await db.execute('SELECT * FROM chat_rooms WHERE id = ?', [result.insertId]);
        await db.end();
        res.json(room[0]);
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// API đánh dấu đã đọc
app.post('/api/rooms/:roomId/read', async (req, res) => {
    try {
        const db = await getDb();
        await db.execute(
            'UPDATE chat_messages SET is_read = 1 WHERE room_id = ? AND sender = "user"',
            [req.params.roomId]
        );
        await db.end();
        res.json({ success: true });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// API đóng phòng chat
app.post('/api/rooms/:roomId/close', async (req, res) => {
    try {
        const db = await getDb();
        await db.execute('UPDATE chat_rooms SET status = "closed" WHERE id = ?', [req.params.roomId]);
        await db.end();
        io.to(`room:${req.params.roomId}`).emit('room:closed');
        res.json({ success: true });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// API upload file/ảnh
app.post('/api/upload', upload.single('file'), async (req, res) => {
    try {
        if (!req.file) return res.status(400).json({ error: 'Không có file' });
        const { room_id, sender, sender_name } = req.body;
        const ext = path.extname(req.file.originalname).toLowerCase();
        const isImage = /\.(jpg|jpeg|png|gif|webp)$/.test(ext);
        const fileUrl = `/chat_uploads/${req.file.filename}`;
        const message = isImage
            ? `[image]${fileUrl}[/image]`
            : `[file]${req.file.originalname}|${fileUrl}[/file]`;

        const db = await getDb();
        const [result] = await db.execute(
            'INSERT INTO chat_messages (room_id, sender, sender_name, message, is_read) VALUES (?, ?, ?, ?, 0)',
            [room_id, sender, sender_name, message]
        );
        const [rows] = await db.execute('SELECT * FROM chat_messages WHERE id = ?', [result.insertId]);
        await db.end();

        const msg = rows[0];
        io.to(`room:${room_id}`).emit('message:new', msg);
        if (sender === 'user') io.to('admins').emit('room:updated', { room_id: parseInt(room_id), message: msg });
        res.json(msg);
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// API thống kê
app.get('/api/stats', async (req, res) => {
    try {
        const db = await getDb();
        const [[total]] = await db.execute('SELECT COUNT(*) as v FROM chat_rooms');
        const [[open]] = await db.execute('SELECT COUNT(*) as v FROM chat_rooms WHERE status = "open"');
        const [[closed]] = await db.execute('SELECT COUNT(*) as v FROM chat_rooms WHERE status = "closed"');
        const [[msgs]] = await db.execute('SELECT COUNT(*) as v FROM chat_messages');
        const [[unread]] = await db.execute('SELECT COUNT(*) as v FROM chat_messages WHERE is_read = 0 AND sender = "user"');
        const [[today]] = await db.execute('SELECT COUNT(*) as v FROM chat_rooms WHERE DATE(created_at) = CURDATE()');

        // Thống kê 7 ngày
        const [daily] = await db.execute(`
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM chat_rooms
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        `);

        await db.end();
        res.json({
            total: total.v, open: open.v, closed: closed.v,
            messages: msgs.v, unread: unread.v, today: today.v,
            daily
        });
    } catch (e) {
        res.status(500).json({ error: e.message });
    }
});

// Socket.IO
io.on('connection', (socket) => {
    socket.on('admin:join', () => {
        socket.join('admins');
    });

    socket.on('room:join', async ({ room_id }) => {
        socket.join(`room:${room_id}`);
        try {
            const db = await getDb();
            await db.execute(
                'UPDATE chat_messages SET is_read = 1 WHERE room_id = ? AND sender != "user"',
                [room_id]
            );
            await db.end();
        } catch (e) {}
    });

    socket.on('message:send', async ({ room_id, message, sender, sender_name }) => {
        try {
            const db = await getDb();
            const [result] = await db.execute(
                'INSERT INTO chat_messages (room_id, sender, sender_name, message, is_read) VALUES (?, ?, ?, ?, 0)',
                [room_id, sender, sender_name || sender, message]
            );
            const [rows] = await db.execute('SELECT * FROM chat_messages WHERE id = ?', [result.insertId]);
            await db.end();
            const msg = rows[0];
            io.to(`room:${room_id}`).emit('message:new', msg);
            if (sender === 'user') io.to('admins').emit('room:updated', { room_id, message: msg });
        } catch (e) {}
    });

    socket.on('typing:start', ({ room_id, sender }) => {
        socket.to(`room:${room_id}`).emit('typing:show', { sender });
    });
    socket.on('typing:stop', ({ room_id }) => {
        socket.to(`room:${room_id}`).emit('typing:hide');
    });
});

const PORT = 3000;

server.listen(PORT, '0.0.0.0', () => console.log(`Chat server chạy tại http://0.0.0.0:${PORT}`));