<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập | Hệ thống Quản lý phòng thực hành và thiết bị</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-family, Arial, sans-serif);
            color: var(--text-main, #26352f);
            background: #f3f5f2;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            max-width: 960px;
            min-height: 540px;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e1e7e2;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(37, 60, 47, 0.09);
        }

        .login-intro {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 42px;
            color: #fff;
            background: linear-gradient(145deg, #234b3c, #356c52);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 13px;
        }

        .brand-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 25px;
        }

        .brand-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.6;
            letter-spacing: 0.3px;
        }

        .intro-content {
            margin: 48px 0;
        }

        .intro-label {
            margin-bottom: 14px;
            color: #c7dfce;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .intro-content h1 {
            margin: 0 0 20px;
            font-size: clamp(27px, 3vw, 36px);
            line-height: 1.4;
            letter-spacing: -0.6px;
        }

        .intro-content p {
            margin: 0;
            color: #e0ebe4;
            font-size: 14px;
            line-height: 1.9;
        }

        .intro-footer {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            color: #d2e1d7;
            font-size: 12px;
            line-height: 1.8;
        }

        .login-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 48px 44px;
        }

        .login-heading {
            margin-bottom: 30px;
        }

        .login-heading h2 {
            margin: 0 0 10px;
            color: #244b3b;
            font-size: 27px;
            font-weight: 700;
        }

        .login-heading p {
            margin: 0;
            color: #78857d;
            font-size: 14px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            color: #34483d;
            font-size: 13px;
            font-weight: 600;
        }

        .form-group input {
            display: block;
            width: 100%;
            min-height: 47px;
            padding: 12px 14px;
            border: 1px solid #d8e0da;
            border-radius: 8px;
            outline: none;
            background: #fbfcfb;
            color: #26352f;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input::placeholder {
            color: #a2aca5;
        }

        .form-group input:focus {
            border-color: #397455;
            box-shadow: 0 0 0 3px rgba(57, 116, 85, 0.12);
            background: #fff;
        }

        .form-group input.input-error {
            border-color: #c94b4b;
        }

        .error-text {
            margin-top: 7px;
            color: #c03939;
            font-size: 12px;
            line-height: 1.5;
        }

        .login-error {
            margin-bottom: 20px;
            padding: 12px 14px;
            border: 1px solid #f1cccc;
            border-radius: 8px;
            background: #fff4f4;
            color: #a52c2c;
            font-size: 13px;
            line-height: 1.6;
        }

        .btn-login {
            width: 100%;
            min-height: 48px;
            margin-top: 5px;
            border: none;
            border-radius: 8px;
            background: #2e6048;
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-login:hover {
            background: #234b38;
        }

        .btn-login:active {
            transform: scale(0.99);
        }

        .login-note {
            margin: 24px 0 0;
            color: #879189;
            font-size: 12px;
            line-height: 1.8;
            text-align: center;
        }

        @media (max-width: 700px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 440px;
            }

            .login-intro {
                padding: 28px;
            }

            .intro-content {
                margin: 30px 0;
            }

            .intro-content h1 {
                font-size: 26px;
            }

            .intro-footer {
                padding-top: 14px;
            }

            .login-panel {
                padding: 34px 28px;
            }
        }
    </style>
</head>

<body>
<div class="login-wrapper">
    <main class="login-container">

        <section class="login-intro">
            <div class="brand">
                <div class="brand-icon" aria-hidden="true">&#127979;</div>
                <div class="brand-name">
                    HỆ THỐNG QUẢN LÝ<br>
                    PHÒNG THỰC HÀNH
                </div>
            </div>

            <div class="intro-content">
                <div class="intro-label">Quản lý cơ sở vật chất</div>

                <h1>Hệ thống Quản lý phòng thực hành và thiết bị</h1>

                <p>
                    Nền tảng hỗ trợ quản lý phòng thực hành, thiết bị,
                    lịch đặt phòng và các yêu cầu bảo trì trong một hệ thống thống nhất.
                </p>
            </div>

            <div class="intro-footer">
                Hệ thống phục vụ công tác quản lý và sử dụng cơ sở vật chất.
            </div>
        </section>

        <section class="login-panel">
            <div class="login-heading">
                <h2>Đăng nhập</h2>
                <p>Đăng nhập bằng tài khoản đã được cấp để tiếp tục.</p>
            </div>

            <?php if (isset($errors['login'])): ?>
                <div class="login-error" role="alert">
                    <?= htmlspecialchars($errors['login'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="index.php?action=login" method="POST">
                <div class="form-group">
                    <label for="username">Email tài khoản</label>

                    <input
                        type="email"
                        id="username"
                        name="username"
                        class="<?= isset($errors['username']) ? 'input-error' : '' ?>"
                        value="<?= htmlspecialchars($username ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nhập email đã đăng ký"
                        autocomplete="username"
                        required
                    >

                    <?php if (isset($errors['username'])): ?>
                        <div class="error-text">
                            <?= htmlspecialchars($errors['username'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Mật khẩu</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="<?= isset($errors['password']) ? 'input-error' : '' ?>"
                        placeholder="Nhập mật khẩu"
                        autocomplete="current-password"
                        required
                    >

                    <?php if (isset($errors['password'])): ?>
                        <div class="error-text">
                            <?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-login">
                    Đăng nhập hệ thống
                </button>
            </form>

            <p class="login-note">
                Vui lòng sử dụng tài khoản được cấp bởi người quản trị hệ thống.
            </p>
        </section>

    </main>
</div>
</body>
</html>