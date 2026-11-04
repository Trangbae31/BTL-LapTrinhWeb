
<?php

function calendarEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$currentUserId = (int)($_SESSION['user']['user_id'] ?? 0);

$roles = $_SESSION['user']['roles'] ?? [];
if (!is_array($roles)) {
    $roles = [];
}

$role = '';
foreach (['admin', 'manager', 'teacher', 'technician'] as $candidate) {
    if (in_array($candidate, $roles, true)) {
        $role = $candidate;
        break;
    }
}

$isManager = in_array($role, ['admin', 'manager'], true);
$isTeacher = $role === 'teacher';
$canCreate = in_array($role, ['admin', 'manager', 'teacher'], true);

$keyword = $keyword ?? ($_GET['keyword'] ?? '');

$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Từ chối',
    'cancelled' => 'Đã hủy'
];

$statusClasses = [
    'pending' => 'status-pending',
    'approved' => 'status-approved',
    'rejected' => 'status-rejected',
    'cancelled' => 'status-cancelled'
];

$messageLabels = [
    'created' => 'Đã gửi yêu cầu đặt phòng. Yêu cầu đang chờ duyệt.',
    'created_approved' => 'Đã tạo lịch đặt phòng và xác nhận thành công.',
    'updated' => 'Đã cập nhật yêu cầu đặt phòng.',
    'cancelled' => 'Đã hủy lịch đặt phòng.',
    'cancel_failed' => 'Không thể hủy lịch. Có thể lịch đã được xử lý hoặc bạn không có quyền.',
    'cancel_reason_required' => 'Manager/Admin cần nhập lý do khi hủy lịch.',
    'not_allowed' => 'Bạn không có quyền chỉnh sửa yêu cầu này.',
    'cancellation_request_sent' => 'Đã gửi yêu cầu hủy lịch. Lịch vẫn được giữ cho đến khi Manager/Admin xử lý.',
    'cancellation_request_failed' => 'Không thể gửi yêu cầu hủy. Có thể lịch không còn hợp lệ hoặc đã có yêu cầu đang chờ.',
    'cancellation_request_invalid' => 'Vui lòng nhập đầy đủ mã lịch và lý do hủy.'
];

$messageCode = $_GET['message'] ?? '';
$messageCode = is_string($messageCode) ? $messageCode : '';
$message = $messageLabels[$messageCode] ?? '';

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lịch phòng thực hành</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .booking-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff1d6;
            color: #986500;
        }

        .status-approved {
            background: #e2f3e7;
            color: #287a45;
        }

        .status-rejected {
            background: #fbe8e7;
            color: #a12e2a;
        }

        .status-cancelled {
            background: #eee8e4;
            color: #76665d;
        }

        .booking-actions {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            min-width: 180px;
        }

        .booking-actions form {
            margin: 0;
        }

        .booking-actions textarea {
            width: 100%;
            min-height: 55px;
            box-sizing: border-box;
            margin-bottom: 5px;
        }

        .booking-actions button {
            cursor: pointer;
        }

        .calendar-message {
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 6px;
            background: #e5f3e8;
            color: #28643a;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
        }

        .booking-note {
            color: #796b60;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .calendar-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>

<main class="page-container">

    <header class="page-header">

        <div>
            <p class="page-label">SMART CAMPUS</p>

            <h1>Lịch phòng thực hành</h1>

            <p class="page-description">
                Theo dõi lịch sử dụng và kiểm tra tình trạng phòng.
            </p>
        </div>

        <?php if ($canCreate): ?>
            <a href="index.php?action=booking_create" class="btn-primary">
                + Đặt phòng
            </a>
        <?php endif; ?>

    </header>

    <section class="content-card">

        <h2>Danh sách lịch phòng</h2>

        <?php if ($message !== ''): ?>
            <div class="calendar-message">
                <?= calendarEscape($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($isManager): ?>
            <div class="calendar-links">
                <a href="index.php?action=booking_approvals" class="btn-secondary">
                    Duyệt yêu cầu đặt phòng
                </a>

                <a href="index.php?action=booking_cancellation_requests" class="btn-secondary">
                    Xử lý yêu cầu hủy lịch
                </a>
            </div>
        <?php endif; ?>

        <p class="booking-note">
            <?php if ($isTeacher): ?>
                Bạn chỉ được sửa hoặc hủy trực tiếp yêu cầu đang chờ duyệt của mình.
                Với lịch đã duyệt, bạn cần gửi yêu cầu hủy và chờ Manager/Admin xử lý.
            <?php elseif ($isManager): ?>
                Bạn có thể hủy trực tiếp lịch đang chờ duyệt hoặc đã duyệt.
                Vui lòng nhập lý do khi thực hiện hủy.
            <?php else: ?>
                Bạn đang ở chế độ xem lịch đã được duyệt.
            <?php endif; ?>
        </p>

        <form method="get" action="index.php" class="search-form">

            <input type="hidden" name="action" value="calendar">

            <input
                type="text"
                name="keyword"
                placeholder="Tìm theo phòng, nội dung hoặc người đặt..."
                value="<?= calendarEscape($keyword) ?>"
            >

            <button type="submit" class="btn-primary">
                Tìm kiếm
            </button>

            <a href="index.php?action=calendar" class="btn-secondary">
                Làm mới
            </a>

        </form>

        <?php if (empty($bookings)): ?>

            <div class="empty-message">
                Không tìm thấy lịch đặt phòng nào.
            </div>

        <?php else: ?>

            <div class="table-container">

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

                    <?php foreach ($bookings as $index => $booking): ?>

                        <?php
                        $ownerId = (int)($booking['user_id'] ?? 0);
                        $isOwner = $ownerId === $currentUserId;

                        $status = $booking['status'] ?? '';
                        $isPending = $status === 'pending';
                        $isApproved = $status === 'approved';

                        $displayName = $booking['user_name'] ?? '';
                        if ($displayName === '' || $displayName === null) {
                            $displayName = 'Hoạt động chung của nhà trường';
                        }

                        $bookingId = (int)$booking['booking_id'];
                        ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td><?= calendarEscape($displayName) ?></td>

                            <td><?= calendarEscape($booking['room_code'] ?? '') ?></td>

                            <td><?= calendarEscape($booking['room_name'] ?? '') ?></td>

                            <td>
                                <strong><?= calendarEscape($booking['title'] ?? '') ?></strong>
                                <?php if (!empty($booking['booking_type'])): ?>
                                    <br>
                                    <small>
                                        <?php
                                        $typeLabels = [
                                            'personal' => 'Cá nhân',
                                            'on_behalf' => 'Đặt thay giảng viên',
                                            'school_wide' => 'Hoạt động chung'
                                        ];
                                        echo calendarEscape(
                                            $typeLabels[$booking['booking_type']] ?? $booking['booking_type']
                                        );
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </td>

                            <td><?= calendarEscape($booking['start_time'] ?? '') ?></td>

                            <td><?= calendarEscape($booking['end_time'] ?? '') ?></td>

                            <td>
                                <span class="booking-status <?= $statusClasses[$status] ?? '' ?>">
                                    <?= calendarEscape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>

                            <td>
                                <div class="booking-actions">

                                    <?php if ($isTeacher && $isOwner && $isPending): ?>

                                        <a
                                            href="index.php?action=booking_edit&id=<?= $bookingId ?>"
                                            class="btn-secondary"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="post"
                                            action="index.php?action=booking_cancel"
                                            onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu đặt phòng này?');"
                                        >
                                            <?= CSRF::field() ?>

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?= $bookingId ?>"
                                            >

                                            <button type="submit" class="btn-secondary">
                                                Hủy yêu cầu
                                            </button>
                                        </form>

                                    <?php elseif ($isTeacher && $isOwner && $isApproved): ?>

                                        <form
                                            method="post"
                                            action="index.php?action=booking_cancellation_request"
                                            onsubmit="return confirm('Gửi yêu cầu hủy lịch đã được duyệt?');"
                                        >
                                            <?= CSRF::field() ?>

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?= $bookingId ?>"
                                            >

                                            <textarea
                                                name="reason"
                                                placeholder="Nhập lý do xin hủy lịch..."
                                                required
                                            ></textarea>

                                            <button type="submit" class="btn-secondary">
                                                Gửi yêu cầu hủy
                                            </button>
                                        </form>

                                    <?php elseif ($isManager && ($isPending || $isApproved)): ?>

                                        <form
                                            method="post"
                                            action="index.php?action=booking_cancel"
                                            onsubmit="return confirm('Bạn có chắc muốn hủy lịch này?');"
                                        >
                                            <?= CSRF::field() ?>

                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?= $bookingId ?>"
                                            >

                                            <textarea
                                                name="reason"
                                                placeholder="Nhập lý do hủy lịch..."
                                                required
                                            ></textarea>

                                            <button type="submit" class="btn-secondary">
                                                Hủy lịch
                                            </button>
                                        </form>

                                    <?php else: ?>

                                        <span>Không có thao tác</span>

                                    <?php endif; ?>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
