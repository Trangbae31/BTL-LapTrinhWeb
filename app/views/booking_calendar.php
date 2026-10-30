<?php

function calendarEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$currentUserId = (int)($_SESSION['user']['user_id'] ?? 0);
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
    'created' => 'Đã gửi yêu cầu đặt phòng.',
    'updated' => 'Đã cập nhật yêu cầu đặt phòng.',
    'cancelled' => 'Đã hủy yêu cầu đặt phòng.',
    'cancel_failed' => 'Không thể hủy yêu cầu. Có thể yêu cầu đã được xử lý.',
    'not_allowed' => 'Bạn không có quyền chỉnh sửa yêu cầu này.',
    'update_failed' => 'Không thể cập nhật yêu cầu đặt phòng.',
    'invalid' => 'Thông tin cập nhật không hợp lệ.'
];

$messageCode = $_GET['message'] ?? '';
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
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .booking-actions form {
            margin: 0;
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

        <a href="index.php?action=booking_create" class="btn-primary">
            + Đặt phòng
        </a>

    </header>

    <section class="content-card">

        <h2>Danh sách lịch phòng</h2>

        <?php if ($message !== ''): ?>
            <div class="calendar-message">
                <?= calendarEscape($message) ?>
            </div>
        <?php endif; ?>

        <p class="booking-note">
            Bạn có thể chỉnh sửa hoặc hủy yêu cầu khi trạng thái còn là
            <strong>Chờ duyệt</strong>.
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
                            <th>Người đặt</th>
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
                        $isOwner = (int)($booking['user_id'] ?? 0) === $currentUserId;
                        $isPending = ($booking['status'] ?? '') === 'pending';
                        $status = $booking['status'] ?? '';
                        ?>

                        <tr>

                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= calendarEscape($booking['user_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= calendarEscape($booking['room_code'] ?? '') ?>
                            </td>

                            <td>
                                <?= calendarEscape($booking['room_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= calendarEscape($booking['title'] ?? '') ?>
                            </td>

                            <td>
                                <?= calendarEscape(
                                    !empty($booking['start_time'])
                                        ? date('d/m/Y H:i', strtotime($booking['start_time']))
                                        : ''
                                ) ?>
                            </td>

                            <td>
                                <?= calendarEscape(
                                    !empty($booking['end_time'])
                                        ? date('d/m/Y H:i', strtotime($booking['end_time']))
                                        : ''
                                ) ?>
                            </td>

                            <td>
                                <span class="booking-status <?= calendarEscape(
                                    $statusClasses[$status] ?? ''
                                ) ?>">
                                    <?= calendarEscape(
                                        $statusLabels[$status] ?? $status
                                    ) ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($isOwner && $isPending): ?>

                                    <div class="booking-actions">

                                        <a
                                            href="index.php?action=booking_edit&id=<?= (int)$booking['booking_id'] ?>"
                                            class="btn-secondary"
                                        >
                                            Sửa
                                        </a>

                                        <form
                                            method="post"
                                            action="index.php?action=booking_cancel&id=<?= (int)$booking['booking_id'] ?>"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn hủy yêu cầu đặt phòng này?');"
                                        >

                                            <button
                                                type="submit"
                                                class="btn-secondary"
                                            >
                                                Hủy
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <span>—</span>

                                <?php endif; ?>

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