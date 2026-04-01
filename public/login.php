<?php
require_once "../config/database.php";
require_once "../controllers/AuthController.php";

$auth = new AuthController($pdo);
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->login($_POST['email'], $_POST['password']);

    if ($result === true) {
        if ($_SESSION['user']['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: ../public/index.php");
        }
        exit;
    } else {
        $error = $result;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Đăng nhập - CameraShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <style>
    :root {
      --gold:        #b8860b;
      --gold-light:  #d4a017;
      --gold-pale:   #fdf8ee;
      --gold-border: rgba(184,134,11,.25);
      --cream:       #faf7f2;
      --ink:         #1a1714;
      --muted:       #8a8078;
      --white:       #ffffff;
      --border:      #e8e2d9;
      --font-display: 'Cormorant Garamond', serif;
      --font-body:    'DM Sans', sans-serif;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: var(--font-body);
      background: linear-gradient(135deg, #f5f0e8 0%, #e8e0d0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-box {
      width: 100%;
      max-width: 420px;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 18px;
      box-shadow: 0 20px 60px rgba(0,0,0,.12);
      overflow: hidden;
    }

    .login-header {
      background: var(--ink);
      color: white;
      padding: 35px 30px;
      text-align: center;
    }

    .logo {
      width: 65px; height: 65px;
      background: linear-gradient(135deg, var(--gold), var(--gold-light));
      border-radius: 50%;
      margin: 0 auto 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: var(--ink);
    }

    .login-header h2 {
      font-family: var(--font-display);
      font-size: 26px;
      margin: 0;
    }

    .login-body {
      padding: 40px 35px;
    }

    .form-label {
      font-size: 11.5px;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--muted);
      margin-bottom: 8px;
    }

    .form-control {
      width: 100%;
      padding: 14px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: 15px;
      background: var(--cream);
      transition: all 0.25s;
    }

    .form-control:focus {
      border-color: var(--gold);
      background: var(--white);
      box-shadow: 0 0 0 4px rgba(184,134,11,.08);
      outline: none;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      margin-top: 10px;
      background: var(--ink);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 15.5px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-login:hover {
      background: #3d3730;
    }

    .error {
      color: #dc2626;
      text-align: center;
      margin-top: 12px;
      font-size: 14px;
    }

    .switch {
      text-align: center;
      margin-top: 25px;
      font-size: 14px;
    }
    .switch a {
      color: var(--gold);
      text-decoration: none;
    }
    .switch a:hover { text-decoration: underline; }
  </style>
</head>
<body>

  <div class="login-box">
    <div class="login-header">
      <div class="logo"><i class="fas fa-camera"></i></div>
      <h2>Đăng nhập</h2>
    </div>

    <div class="login-body">
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="Nhập email" required>
        </div>

        <div class="mb-4">
          <label class="form-label">Mật khẩu</label>
          <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
        </div>

        <button type="submit" class="btn-login">ĐĂNG NHẬP</button>

        <?php if ($error): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
      </form>

      <div class="switch">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
      </div>
    </div>
  </div>

</body>
</html>