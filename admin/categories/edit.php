<?php
require_once "../../config/database.php";

$id = $_GET['id'] ?? null;
if (!$id) die("Thiếu ID");

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) die("Không tìm thấy loại");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sửa danh mục</title>
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
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, .18);
            --danger: #dc2626;
            --danger-pale: #fef2f2;
            --font-display: 'Cormorant Garamond', serif;
            --font-body: 'DM Sans', sans-serif;
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
        }

        /* ── Blurred backdrop page ── */
        .page-backdrop {
            position: fixed;
            inset: 0;
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .backdrop-decoration {
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(184, 134, 11, .07) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }

        /* MODAL */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            background: rgba(26, 23, 20, .48);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: overlayIn .25s ease both;
        }

        .modal {
            background: var(--white);
            border: 1.5px solid var(--border);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: modalIn .32s cubic-bezier(.34, 1.56, .64, 1) both;
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 24px 20px;
            border-bottom: 1.5px solid var(--border);
        }

        .modal-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: var(--gold-pale);
            border: 1.5px solid var(--gold-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            color: var(--gold);
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
        }

        .modal-sub {
            font-size: 12px;
            color: var(--muted);
            margin-top: 1px;
        }

        .btn-close {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1.5px solid var(--border);
            background: var(--cream);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
            transition: border-color .2s, color .2s, background .2s;
            flex-shrink: 0;
        }

        .btn-close:hover {
            border-color: var(--danger);
            color: var(--danger);
            background: var(--danger-pale);
        }

        .modal-body {
            padding: 26px 24px 20px;
        }

        /* Current name */
        .current-name-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .7px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .current-name-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--gold-pale);
            border: 1px solid var(--gold-border);
            color: var(--gold);
            font-size: 13px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-family: var(--font-body);
            font-size: 14px;
            color: var(--ink);
            background: var(--cream);
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-input:focus {
            border-color: var(--gold);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(184, 134, 11, .10);
        }

        .form-input::placeholder {
            color: var(--border-dark);
        }

        .form-hint {
            font-size: 11.5px;
            color: var(--muted);
            margin-top: 7px;
        }

        .modal-footer {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 24px 22px;
            border-top: 1.5px solid var(--border);
        }

        .btn-cancel {
            font-family: var(--font-body);
            font-size: 13.5px;
            font-weight: 500;
            padding: 9px 20px;
            border-radius: 8px;
            background: var(--cream);
            border: 1.5px solid var(--border);
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: border-color .2s, color .2s;
        }

        .btn-cancel:hover {
            border-color: var(--border-dark);
            color: var(--ink);
        }

        .btn-submit {
            font-family: var(--font-body);
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 24px;
            border-radius: 8px;
            background: var(--ink);
            border: none;
            color: var(--white);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background .2s, transform .15s, box-shadow .2s;
            box-shadow: var(--shadow-sm);
        }

        .btn-submit:hover {
            background: var(--ink-soft);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        @keyframes overlayIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(24px) scale(.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
    </style>
</head>

<body>

    <div class="page-backdrop">
        <div class="backdrop-decoration"></div>
    </div>

    <!-- Modal -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-icon"><i class="fas fa-pen"></i></div>
                    <div>
                        <div class="modal-title" id="modalTitle">Sửa danh mục</div>
                        <div class="modal-sub">Cập nhật tên danh mục và lưu</div>
                    </div>
                </div>
                <a class="btn-close" href="index.php" title="Đóng">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <form method="POST" action="edit.php?id=<?= (int)$id ?>" onsubmit="return validateForm()">
                <div class="modal-body">

                    <div class="current-name-label">Tên hiện tại</div>
                    <div class="current-name-pill">
                        <i class="fas fa-tag" style="font-size:11px;"></i>
                        <?= htmlspecialchars($category['name']) ?>
                    </div>

                    <label class="form-label" for="catName">Tên mới</label>
                    <input
                        class="form-input"
                        type="text"
                        id="catName"
                        name="name"
                        value="<?= htmlspecialchars($category['name']) ?>"
                        placeholder="Nhập tên danh mục mới..."
                        autocomplete="off"
                        autofocus />
                    <div class="form-hint">Tên danh mục sẽ hiển thị trên trang cửa hàng.</div>
                </div>

                <div class="modal-footer">
                    <a class="btn-cancel" href="index.php">
                        <i class="fas fa-arrow-left" style="font-size:11px;"></i> Huỷ
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check"></i> Cập nhật
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function validateForm() {
            const input = document.getElementById('catName');
            if (!input.value.trim()) {
                input.focus();
                return false;
            }
            return true;
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') window.location.href = 'index.php';
        });

        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) window.location.href = 'index.php';
        });
    </script>
</body>

</html>