<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý phòng & thiết bị</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 32px;
        }
        .login-box h2 {
            text-align: center;
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 22px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: var(--text-main);
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #D0D7D3;
            border-radius: 6px;
            box-sizing: border-box;
            font-family: var(--font-family);
            font-size: 14px;
            color: var(--text-main);
            background-color: #FFFFFF;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            font-size: 15px;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-box">
        <h2>ĐẮNG NHẬP HỆ THỐNG</h2>

        <form action="index.php?action=login" method="POST" novalidate>
            <div class="form-group">
                <label for="username">Tên tài khoản:</label>
                <input type="text" id="username" name="username" 
                       class="<?= isset($errors['username']) ? 'input-error' : '' ?>" 
                       value="<?= htmlspecialchars($username) ?>"
                       placeholder="Nhập tên tài khoản...">
                <?php if (isset($errors['username'])): ?>
                    <div class="error-text"><?= $errors['username'] ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" 
                       class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
                       placeholder="Nhập mật khẩu...">
                <?php if (isset($errors['password'])): ?>
                    <div class="error-text"><?= $errors['password'] ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-primary btn-login">Đăng nhập</button>
        </form>
    </div>
</div>

</body>
</html>