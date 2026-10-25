<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart Campus Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar điều hướng màu xanh rừng -->
    <aside class="sidebar">
        <div class="sidebar-brand">Smart Campus</div>
        <ul class="sidebar-menu">
            <li class="active"><a href="index.php?action=dashboard">Trang chủ</a></li>
            <li><a href="#">Lịch phòng thực hành</a></li>
            <li><a href="#">Quản lý thiết bị</a></li>
            <li><a href="#">Báo hỏng & Bảo trì</a></li>
        </ul>
    </aside>

    <div class="main-wrapper">
        <!-- Header -->
        <header class="header">
            <div><strong>Hệ Thống Quản Lý Phòng Thực Hành & Thiết Bị</strong></div>
            <div>
                Xin chào, <strong><?= htmlspecialchars($_SESSION['user']['fullname'] ?? 'User') ?></strong>!
                <a href="index.php?action=logout" class="btn btn-danger" style="padding: 6px 12px; margin-left: 12px;">Đăng xuất</a>
            </div>
        </header>

        <!-- Content Area nền Ivory -->
        <main class="content-area">
            <h1 class="page-title">Bảng Điều Khiển Tổng Quan</h1>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: var(--space-md);">
                <div class="card">
                    <h2 class="section-title">Lịch Phòng Thực Hành</h2>
                    <p style="color: var(--text-sub);">Tra cứu danh sách phòng trống, xem lịch và đăng ký lịch sử dụng.</p>
                    <a href="#" class="btn btn-primary" style="margin-top: var(--space-sm);">Xem chi tiết</a>
                </div>

                <div class="card">
                    <h2 class="section-title">Quản Lý Thiết Bị</h2>
                    <p style="color: var(--text-sub);">Theo dõi danh mục thiết bị, trạng thái hoạt động theo từng phòng.</p>
                    <a href="#" class="btn btn-primary" style="margin-top: var(--space-sm);">Xem chi tiết</a>
                </div>

                <div class="card">
                    <h2 class="section-title">Báo Hỏng & Bảo Trì</h2>
                    <p style="color: var(--text-sub);">Gửi báo cáo sự cố thiết bị hư hỏng và cập nhật tiến độ sửa chữa.</p>
                    <a href="#" class="btn btn-primary" style="margin-top: var(--space-sm);">Gửi báo cáo</a>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>