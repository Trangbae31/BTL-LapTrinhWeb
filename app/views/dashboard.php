<?php
require_once __DIR__ . '/../core/Authorization.php';

$userName = $_SESSION['user']['full_name']
    ?? $_SESSION['user']['fullname']
    ?? 'Người dùng';

$isAdmin = Authorization::hasRole('admin');
$isManager = Authorization::hasAnyRole(['admin', 'manager']);
$isTeacher = Authorization::hasRole('teacher');
$isTechnician = Authorization::hasRole('technician');

if ($isAdmin) {
    $roleLabel = 'Quản trị viên';
} elseif ($isManager) {
    $roleLabel = 'Quản lý';
} elseif ($isTeacher) {
    $roleLabel = 'Giảng viên';
} elseif ($isTechnician) {
    $roleLabel = 'Nhân viên kỹ thuật';
} else {
    $roleLabel = 'Người dùng hệ thống';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Campus</title>

    <style>
        :root {
            --green: #176b4d;
            --green-dark: #104d39;
            --green-light: #e8f5ee;
            --bg: #f5f7f5;
            --text: #23352d;
            --muted: #78867e;
            --border: #e7ece8;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            flex-shrink: 0;
            background: var(--green-dark);
            color: white;
            padding: 28px 18px;
            display: flex;
            flex-direction: column;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 10px 30px;
            border-bottom: 1px solid rgba(255,255,255,.14);
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: rgba(255,255,255,.15);
            font-size: 23px;
        }

        .brand-name {
            font-size: 19px;
            font-weight: 750;
        }

        .brand-sub {
            font-size: 11px;
            color: #c1dfd0;
            margin-top: 4px;
        }

        .menu-label {
            margin: 28px 12px 12px;
            color: #a9cdbb;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.3px;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 14px;
            border-radius: 10px;
            color: #d7e9df;
            font-size: 14px;
            transition: .2s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu .active a {
            background: #277b5a;
            color: white;
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding: 18px 12px 0;
            border-top: 1px solid rgba(255,255,255,.14);
            color: #c1dfd0;
            font-size: 12px;
            line-height: 1.7;
        }

        /* MAIN */
        .main-wrapper {
            flex: 1;
            min-width: 0;
        }

        .header {
            min-height: 78px;
            padding: 18px 34px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .header-title {
            font-size: 17px;
            font-weight: 700;
        }

        .header-subtitle {
            margin-top: 5px;
            font-size: 12px;
            color: var(--muted);
        }

        .user-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--green-light);
            color: var(--green);
            font-weight: 750;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
        }

        .user-role {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        .logout {
            display: inline-block;
            padding: 9px 13px;
            background: #fff1ef;
            color: #c44e43;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .logout:hover {
            background: #ffe2de;
        }

        .content-area {
            padding: 32px 34px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .welcome {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .welcome h1 {
            font-size: 27px;
            letter-spacing: -.5px;
        }

        .welcome p {
            margin-top: 9px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .date-label {
            background: white;
            border: 1px solid var(--border);
            padding: 11px 15px;
            border-radius: 10px;
            color: #64736a;
            font-size: 12px;
            white-space: nowrap;
        }

        /* WELCOME BANNER */
        .hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(115deg, #176b4d, #2f956b);
            color: white;
            padding: 30px;
            border-radius: 18px;
            margin-bottom: 28px;
            box-shadow: 0 10px 25px rgba(23,107,77,.12);
        }

        .hero h2 {
            font-size: 23px;
            margin-bottom: 11px;
        }

        .hero p {
            max-width: 580px;
            font-size: 14px;
            line-height: 1.8;
            color: #e1f3e8;
        }

        .hero-decoration {
            position: absolute;
            right: 45px;
            top: -30px;
            font-size: 145px;
            opacity: .13;
            transform: rotate(-12deg);
        }

        /* STAT CARDS */
        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 17px;
        }

        .section-heading h2 {
            font-size: 18px;
        }

        .section-heading span {
            font-size: 12px;
            color: var(--muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: white;
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 15px;
            transition: transform .2s, box-shadow .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 24px rgba(20,60,40,.06);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            font-size: 22px;
        }

        .icon-green { background: #e8f5ee; }
        .icon-blue { background: #eaf2ff; }
        .icon-orange { background: #fff2e4; }

        .stat-tag {
            font-size: 10px;
            padding: 6px 8px;
            border-radius: 20px;
            background: #f1f5f2;
            color: #607468;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 750;
            margin-bottom: 7px;
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
        }

        /* FEATURE CARDS */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 23px;
            display: flex;
            flex-direction: column;
            min-height: 240px;
            transition: transform .2s, box-shadow .2s;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 9px 24px rgba(20,60,40,.06);
        }

        .feature-icon {
            width: 49px;
            height: 49px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            font-size: 24px;
            margin-bottom: 19px;
        }

        .feature-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .feature-card p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
            flex: 1;
        }

        .feature-link {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            color: var(--green);
            font-size: 13px;
            font-weight: 700;
        }

        .feature-link:hover {
            color: #27996a;
        }

        .footer {
            text-align: center;
            padding: 28px 10px 5px;
            color: #929d96;
            font-size: 11px;
        }

        /* RESPONSIVE */
        @media (max-width: 1100px) {
            .stats-grid,
            .features-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .content-area {
                padding: 26px 22px;
            }
        }

        @media (max-width: 760px) {
            .app-layout {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                padding: 16px;
            }

            .brand {
                padding-bottom: 15px;
            }

            .menu-label,
            .sidebar-bottom {
                display: none;
            }

            .sidebar-menu {
                display: flex;
                gap: 6px;
                overflow-x: auto;
                margin-top: 14px;
            }

            .sidebar-menu li {
                flex-shrink: 0;
            }

            .sidebar-menu a {
                padding: 10px;
                font-size: 12px;
            }

            .header {
                padding: 15px 18px;
                flex-wrap: wrap;
            }

            .header-title {
                font-size: 14px;
            }

            .welcome {
                align-items: flex-start;
                flex-direction: column;
            }

            .welcome h1 {
                font-size: 23px;
            }

            .hero {
                padding: 24px;
            }

            .hero-decoration {
                right: 10px;
                font-size: 90px;
            }

            .stats-grid,
            .features-grid {
                grid-template-columns: 1fr;
            }

            .user-name,
            .user-role {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="app-layout">

    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">🏫</div>
            <div>
                <div class="brand-name">Smart Campus</div>
                <div class="brand-sub">QUẢN LÝ PHÒNG THỰC HÀNH</div>
            </div>
        </div>

        <div class="menu-label">MENU CHÍNH</div>

        <ul class="sidebar-menu">
            <li class="active">
                <a href="index.php?action=dashboard">
                    <span class="menu-icon">⌂</span>
                    Trang chủ
                </a>
            </li>

            <?php if ($isManager): ?>
                <li>
                    <a href="index.php?action=rooms">
                        <span class="menu-icon">🏢</span>
                        Quản lý phòng
                    </a>
                </li>
                <li>
                    <a href="index.php?action=booking_approvals">
                        <span class="menu-icon">📋</span>
                        Duyệt đặt phòng
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($isTeacher): ?>
                <li>
                    <a href="index.php?action=calendar">
                        <span class="menu-icon">▦</span>
                        Lịch phòng thực hành
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($isManager || $isTechnician): ?>
                <li>
                    <a href="index.php?action=equipment">
                        <span class="menu-icon">▣</span>
                        Quản lý thiết bị
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($isManager || $isTeacher || $isTechnician): ?>
                <li>
                    <a href="index.php?action=report_list">
                        <span class="menu-icon">⚙</span>
                        Báo hỏng & Bảo trì
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-bottom">
            <div>SMART CAMPUS MANAGEMENT</div>
            <div>Hệ thống quản lý thiết bị và phòng học</div>
        </div>
    </aside>

    <div class="main-wrapper">

        <header class="header">
            <div>
                <div class="header-title">
                    Hệ thống quản lý phòng thực hành
                </div>
                <div class="header-subtitle">
                    Quản lý không gian học tập và thiết bị
                </div>
            </div>

            <div class="user-area">
                <div class="avatar">👤</div>

                <div>
                    <div class="user-name">
                        <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="user-role">
                        <?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <a href="index.php?action=logout" class="logout">
                    Đăng xuất ↗
                </a>
            </div>
        </header>

        <main class="content-area">

            <section class="welcome">
                <div>
                    <h1>Bảng điều khiển</h1>
                    <p>
                        Chào mừng bạn đến với Smart Campus.
                        Hãy quản lý các hoạt động của hệ thống tại đây.
                    </p>
                </div>

                <div class="date-label">✦ Không gian quản lý tập trung</div>
            </section>

            <section class="hero">
                <div class="hero-decoration">⌂</div>
                <h2>Xin chào, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>! 👋</h2>
                <p>
                    Theo dõi lịch sử dụng phòng, quản lý thiết bị và xử lý
                    các sự cố nhanh chóng trên cùng một hệ thống.
                </p>
            </section>

            <div class="section-heading">
                <h2>Tổng quan hệ thống</h2>
                <span>Các chức năng chính</span>
            </div>

            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon icon-green">🏢</div>
                        <span class="stat-tag">Phòng học</span>
                    </div>
                    <div class="stat-number">Phòng</div>
                    <div class="stat-label">Tra cứu phòng thực hành</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon icon-blue">💻</div>
                        <span class="stat-tag">Thiết bị</span>
                    </div>
                    <div class="stat-number">Thiết bị</div>
                    <div class="stat-label">Theo dõi danh mục và trạng thái</div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div class="stat-icon icon-orange">🛠️</div>
                        <span class="stat-tag">Bảo trì</span>
                    </div>
                    <div class="stat-number">Sự cố</div>
                    <div class="stat-label">Tiếp nhận và xử lý báo hỏng</div>
                </div>
            </section>

            <div class="section-heading">
                <h2>Truy cập chức năng</h2>
                <span>Chọn chức năng bạn cần sử dụng</span>
            </div>

            <section class="features-grid">

                <?php if ($isManager): ?>
                    <article class="feature-card">
                        <div class="feature-icon icon-green">🏢</div>
                        <h3>Quản lý phòng</h3>
                        <p>
                            Quản lý danh sách phòng thực hành, cập nhật thông tin
                            và trạng thái hoạt động của phòng.
                        </p>
                        <a href="index.php?action=rooms" class="feature-link">
                            Quản lý phòng <span>→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon icon-blue">📋</div>
                        <h3>Duyệt đặt phòng</h3>
                        <p>
                            Kiểm tra yêu cầu đặt phòng, phê duyệt hoặc từ chối
                            yêu cầu của giảng viên.
                        </p>
                        <a href="index.php?action=booking_approvals" class="feature-link">
                            Duyệt yêu cầu <span>→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon icon-orange">💻</div>
                        <h3>Quản lý thiết bị</h3>
                        <p>
                            Theo dõi danh mục, tình trạng và thông tin thiết bị
                            trong các phòng thực hành.
                        </p>
                        <a href="index.php?action=equipment" class="feature-link">
                            Quản lý thiết bị <span>→</span>
                        </a>
                    </article>
                <?php endif; ?>

                <?php if ($isTeacher): ?>
                    <article class="feature-card">
                        <div class="feature-icon icon-green">📅</div>
                        <h3>Lịch phòng thực hành</h3>
                        <p>
                            Xem lịch sử dụng phòng, kiểm tra lịch trống
                            và theo dõi yêu cầu đặt phòng của bạn.
                        </p>
                        <a href="index.php?action=calendar" class="feature-link">
                            Xem lịch phòng <span>→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon icon-blue">➕</div>
                        <h3>Đặt phòng thực hành</h3>
                        <p>
                            Tạo yêu cầu sử dụng phòng phục vụ hoạt động
                            giảng dạy và thực hành.
                        </p>
                        <a href="index.php?action=booking_create" class="feature-link">
                            Đặt phòng ngay <span>→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon icon-orange">🔧</div>
                        <h3>Báo hỏng thiết bị</h3>
                        <p>
                            Gửi thông tin sự cố thiết bị để bộ phận kỹ thuật
                            tiếp nhận và xử lý.
                        </p>
                        <a href="index.php?action=report_form" class="feature-link">
                            Tạo phiếu báo hỏng <span>→</span>
                        </a>
                    </article>
                <?php endif; ?>

                <?php if ($isTechnician): ?>
                    <article class="feature-card">
                        <div class="feature-icon icon-orange">🛠️</div>
                        <h3>Xử lý sự cố</h3>
                        <p>
                            Theo dõi các phiếu bảo trì được phân công,
                            cập nhật tiến độ và kết quả xử lý.
                        </p>
                        <a href="index.php?action=report_list" class="feature-link">
                            Xử lý sự cố <span>→</span>
                        </a>
                    </article>

                    <article class="feature-card">
                        <div class="feature-icon icon-blue">💻</div>
                        <h3>Tra cứu thiết bị</h3>
                        <p>
                            Kiểm tra thông tin và tình trạng thiết bị
                            trong hệ thống.
                        </p>
                        <a href="index.php?action=equipment" class="feature-link">
                            Xem thiết bị <span>→</span>
                        </a>
                    </article>
                <?php endif; ?>

            </section>

            <footer class="footer">
                © Smart Campus Management · Hệ thống quản lý phòng thực hành và thiết bị
            </footer>

        </main>
    </div>
</div>

</body>
</html>