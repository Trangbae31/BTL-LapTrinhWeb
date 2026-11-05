<?php
require_once __DIR__ . '/../core/CSRF.php';

function calendarEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
    'cancelled' => 'Đã hủy'
];

$typeLabels = [
    'personal' => 'Cá nhân',
    'substitute' => 'Đặt thay giảng viên',
    'event' => 'Hoạt động chung'
];

$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserRole = $_SESSION['role'] ?? null;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lịch phòng thực hành - Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: #ffffff;
            border-bottom: 1px solid #eef2f0;
            margin-bottom: var(--space-md);
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .brand-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--primary-color);
            margin: 0;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 11px;
            color: var(--text-sub);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .nav-back {
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .nav-back:hover {
            color: var(--primary-color);
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px 20px;
        }

        .page-header {
            margin-bottom: var(--space-md);
        }

        .page-title {
            font-size: 26px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .page-desc {
            color: var(--text-sub);
            font-size: 14px;
            margin: 0;
        }

        .filter-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 600px;
        }

        .data-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }

        .data-card-header {
            margin-bottom: 16px;
        }

        .data-card-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 4px 0;
        }

        .data-card-subtitle {
            font-size: 13px;
            color: var(--text-sub);
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 950px;
        }

        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #f2f4f3;
            text-align: left;
            font-size: 14px;
            vertical-align: middle;
        }

        th {
            background: #f8faf9;
            color: #6c757d;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr:hover {
            background-color: #fbfdfc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending { background: #FFF1D6; color: #986500; }
        .status-approved { background: #E8F5EE; color: #216B42; }
        .status-rejected { background: #FDF2F1; color: var(--danger-color); }
        .status-cancelled { background: #EEE8E4; color: #76665D; }

        .type-subtext {
            display: block;
            font-size: 12px;
            color: var(--text-sub);
            margin-top: 2px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 170px;
        }

        .actions form {
            margin: 0;
        }

        .action-input {
            width: 100%;
            padding: 6px 10px;
            font-size: 12px;
            border: 1px solid #d5c5b6;
            border-radius: 6px;
            box-sizing: border-box;
            margin-bottom: 4px;
        }

        .flash {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
        }

        .flash.success {
            background: #E8F5EE;
            color: #216B42;
            border: 1px solid #C9E9D2;
        }

        .flash.error {
            background: #FDF2F1;
            color: var(--danger-color);
            border: 1px solid #F2D0D0;
        }

        .footer-text {
            text-align: center;
            color: #9aa29e;
            font-size: 12px;
            margin-top: 40px;
        }
    </style>
</head>

<body>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <div class="brand-logo">
        <div class="brand-icon">📅</div>
        <div>
            <h2 class="brand-title">Smart Campus</h2>
            <p class="brand-subtitle">Quản lý lịch phòng thực hành</p>
        </div>
    </div>
    <a href="index.php?action=dashboard" class="nav-back">← Quay lại Dashboard</a>
</div>

<div class="main-container">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1 class="page-title">Lịch phòng thực hành</h1>
        <p class="page-desc">Theo dõi lịch sử dụng và kiểm tra tình trạng phòng.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= calendarEscape($flash['type'] ?? 'success') ?>">
            <?= calendarEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form class="filter-form" method="get" action="index.php">
            <input type="hidden" name="action" value="calendar">
            <input
                type="text"
                name="keyword"
                class="form-control"
                placeholder="Tìm theo phòng, nội dung..."
                value="<?= calendarEscape($_GET['keyword'] ?? '') ?>"
            >
            <button class="btn btn-primary" type="submit">Tìm kiếm</button>
            <?php if (!empty($_GET['keyword'])): ?>
                <a class="btn btn-secondary" href="index.php?action=calendar">Làm mới</a>
            <?php endif; ?>
        </form>

        <a class="btn btn-primary" href="index.php?action=booking_form">+ Đặt phòng</a>
    </div>

    <!-- DATA TABLE CARD -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 class="data-card-title">Danh sách lịch phòng</h3>
            <p class="data-card-subtitle">Bạn chỉ được sửa hoặc hủy trực tiếp yêu cầu đang chờ duyệt của mình. Với lịch đã duyệt, bạn cần gửi yêu cầu hủy và chờ Manager/Admin xử lý.</p>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Người sử dụng</th>
                        <th>Mã phòng</th>
                        <th>Tên phòng</th>
                        <th>Nội dung</th>
                        <th>Bắt đầu</th>
                        <th>Kết thúc</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 24px; color: var(--text-sub);">
                            Chưa có lịch đặt phòng nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bookings as $index => $booking): ?>
                        <?php 
                            $status = $booking['status'] ?? 'pending';
                            $statusClass = 'status-' . $status;
                            $bookingId = (int)($booking['booking_id'] ?? 0);
                            $bookingUserId = (int)($booking['user_id'] ?? 0);
                            
                            // Logic gốc: So sánh với ID user đăng nhập hiện tại
                            // (nếu thiếu ID thì mặc định true cho các dòng giảng viên để hiển thị đúng giao diện cũ)
                            $isOwner = ($currentUserId === null) ? true : ($bookingUserId === (int)$currentUserId);
                            $isEvent = (($booking['booking_type'] ?? '') === 'event') || (($booking['user_name'] ?? '') === 'Hoạt động chung');
                        ?>
                        <tr>
                            <td><strong><?= $index + 1 ?></strong></td>
                            <td><?= calendarEscape($booking['user_name'] ?? 'Hoạt động chung') ?></td>
                            <td><strong><?= calendarEscape($booking['room_code'] ?? '') ?></strong></td>
                            <td><?= calendarEscape($booking['room_name'] ?? '') ?></td>
                            <td>
                                <strong><?= calendarEscape($booking['purpose'] ?? '') ?></strong>
                                <span class="type-subtext"><?= calendarEscape($typeLabels[$booking['booking_type'] ?? 'personal'] ?? '') ?></span>
                            </td>
                            <td><?= calendarEscape($booking['start_time'] ?? '') ?></td>
                            <td><?= calendarEscape($booking['end_time'] ?? '') ?></td>
                            <td>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= calendarEscape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if ($isEvent): ?>
                                        <span style="color: var(--text-sub); font-size: 13px;">Không có thao tác</span>

                                    <?php elseif ($status === 'pending'): ?>
                                        <a class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; text-align: center;" href="index.php?action=booking_edit&id=<?= $bookingId ?>">Sửa</a>
                                        <form method="post" action="index.php?action=booking_cancel" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu này?')">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                                            <button class="btn btn-danger" style="width: 100%; padding: 6px 12px; font-size: 13px;" type="submit">Hủy yêu cầu</button>
                                        </form>

                                    <?php elseif ($status === 'approved'): ?>
                                        <form method="post" action="index.php?action=request_cancel_approved">
                                            <?= CSRF::field() ?>
                                            <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
                                            <input type="text" name="reason" class="action-input" placeholder="Nhập lý do xin hủy..." required>
                                            <button class="btn btn-danger" style="width: 100%; padding: 6px 12px; font-size: 13px;" type="submit">Gửi yêu cầu hủy</button>
                                        </form>

                                    <?php else: ?>
                                        <span style="color: var(--text-sub); font-size: 13px;">Không có thao tác</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer-text">
        © Smart Campus Management · Quản lý phòng thực hành và thiết bị
    </div>

</div>

</body>
</html>