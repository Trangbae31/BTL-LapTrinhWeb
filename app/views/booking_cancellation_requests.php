<?php

function cancellationEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'pending' => 'Chờ xử lý',
    'approved' => 'Đã chấp thuận',
    'rejected' => 'Đã từ chối'
];

$statusClasses = [
    'pending' => 'status-pending',
    'approved' => 'status-approved',
    'rejected' => 'status-rejected'
];

$currentStatus = $_GET['status'] ?? 'pending';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yêu cầu hủy lịch phòng</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .request-status {
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

        .request-reason {
            min-width: 180px;
            max-width: 280px;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .request-actions {
            min-width: 220px;
        }

        .request-actions form {
            margin-bottom: 8px;
        }

        .request-actions textarea {
            width: 100%;
            min-height: 55px;
            margin-bottom: 6px;
            box-sizing: border-box;
        }

        .request-actions button {
            margin-right: 4px;
            cursor: pointer;
        }

        .flash-message {
            padding: 12px 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .flash-success {
            background: #e5f3e8;
            color: #28643a;
        }

        .flash-error {
            background: #fbe8e7;
            color: #a12e2a;
        }

        .empty-message {
            padding: 25px;
            text-align: center;
        }
    </style>
</head>

<body>

<main class="page-container">

    <header class="page-header">
        <div>
            <p class="page-label">SMART CAMPUS</p>
            <h1>Yêu cầu hủy lịch phòng</h1>
            <p class="page-description">
                Xem xét các yêu cầu hủy lịch đã được duyệt từ giảng viên.
            </p>
        </div>

        <a href="index.php?action=calendar" class="btn-secondary">
            Quay lại lịch phòng
        </a>
    </header>

    <section class="content-card">

        <h2>Danh sách yêu cầu</h2>

        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="flash-message <?= ($flash['type'] ?? '') === 'success' ? 'flash-success' : 'flash-error' ?>">
                <?= cancellationEscape($flash['message']) ?>
            </div>
        <?php endif; ?>

        <form method="get" action="index.php" class="search-form">
            <input
                type="hidden"
                name="action"
                value="booking_cancellation_requests"
            >

            <select name="status">
                <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>
                    Chờ xử lý
                </option>
                <option value="approved" <?= $currentStatus === 'approved' ? 'selected' : '' ?>>
                    Đã chấp thuận
                </option>
                <option value="rejected" <?= $currentStatus === 'rejected' ? 'selected' : '' ?>>
                    Đã từ chối
                </option>
                <option value="all" <?= $currentStatus === 'all' ? 'selected' : '' ?>>
                    Tất cả
                </option>
            </select>

            <button type="submit" class="btn-primary">
                Lọc
            </button>
        </form>

        <?php if (empty($requests)): ?>

            <div class="empty-message">
                Không có yêu cầu hủy lịch nào.
            </div>

        <?php else: ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Người yêu cầu</th>
                            <th>Phòng</th>
                            <th>Nội dung đặt phòng</th>
                            <th>Thời gian sử dụng</th>
                            <th>Lý do hủy</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($requests as $index => $request): ?>
                        <?php
                        $status = $request['status'] ?? 'pending';
                        $isPending = $status === 'pending';
                        ?>

                        <tr>
                            <td><?= $index + 1 ?></td>

                            <td>
                                <?= cancellationEscape($request['requester_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= cancellationEscape($request['room_code'] ?? '') ?>
                                -
                                <?= cancellationEscape($request['room_name'] ?? '') ?>
                            </td>

                            <td>
                                <?= cancellationEscape($request['title'] ?? '') ?>
                            </td>

                            <td>
                                <?= cancellationEscape($request['start_time'] ?? '') ?>
                                <br>
                                đến
                                <br>
                                <?= cancellationEscape($request['end_time'] ?? '') ?>
                            </td>

                            <td class="request-reason">
                                <?= nl2br(cancellationEscape($request['reason'] ?? '')) ?>
                            </td>

                            <td>
                                <?= cancellationEscape($request['requested_at'] ?? '') ?>
                            </td>

                            <td>
                                <span class="request-status <?= $statusClasses[$status] ?? '' ?>">
                                    <?= cancellationEscape($statusLabels[$status] ?? $status) ?>
                                </span>
                            </td>

                            <td class="request-actions">
                                <?php if ($isPending): ?>

                                    <form
                                        method="post"
                                        action="index.php?action=booking_cancellation_approve"
                                        onsubmit="return confirm('Bạn có chắc muốn chấp thuận yêu cầu hủy lịch này?');"
                                    >
                                        <?= CSRF::field() ?>

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int)$request['request_id'] ?>"
                                        >

                                        <textarea
                                            name="comment"
                                            placeholder="Ghi chú xử lý (không bắt buộc)"
                                        ></textarea>

                                        <button type="submit" class="btn-primary">
                                            Chấp thuận hủy
                                        </button>
                                    </form>

                                    <form
                                        method="post"
                                        action="index.php?action=booking_cancellation_reject"
                                        onsubmit="return confirm('Bạn có chắc muốn từ chối yêu cầu hủy này?');"
                                    >
                                        <?= CSRF::field() ?>

                                        <input
                                            type="hidden"
                                            name="request_id"
                                            value="<?= (int)$request['request_id'] ?>"
                                        >

                                        <textarea
                                            name="comment"
                                            placeholder="Nhập lý do từ chối..."
                                            required
                                        ></textarea>

                                        <button type="submit" class="btn-secondary">
                                            Từ chối
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <div>
                                        <strong>Người xử lý:</strong>
                                        <?= cancellationEscape($request['processor_name'] ?? '') ?>
                                    </div>

                                    <div>
                                        <strong>Ghi chú:</strong>
                                        <?= cancellationEscape($request['processing_comment'] ?? '') ?>
                                    </div>

                                    <div>
                                        <strong>Thời điểm:</strong>
                                        <?= cancellationEscape($request['processed_at'] ?? '') ?>
                                    </div>

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
