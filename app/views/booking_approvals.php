<?php
$bookings = $bookings ?? [];

$statusLabels = [
    'pending' => 'Chờ duyệt',
    'approved' => 'Đã duyệt',
    'rejected' => 'Đã từ chối',
    'cancelled' => 'Đã hủy'
];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function approvalEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt đặt phòng - Smart Campus</title>

    <style>
        :root {
            --green: #176b4d;
            --green-dark: #104d39;
            --green-light: #e8f5ee;
            --bg: #f5f7f5;
            --text: #24372e;
            --muted: #78867e;
            --border: #e5ebe6;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        a { text-decoration: none; }

        .topbar {
            min-height: 76px;
            padding: 15px 35px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: var(--green-light);
            font-size: 23px;
        }

        .brand-name {
            color: var(--green-dark);
            font-size: 18px;
            font-weight: 750;
        }

        .brand-sub {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
        }

        .back-link {
            color: var(--green);
            font-size: 13px;
            font-weight: 650;
        }

        .container {
            max-width: 1400px;
            padding: 32px;
            margin: auto;
        }

        .page-heading {
            margin-bottom: 24px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin: 0;
        }

        .page-heading p {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .alert {
            padding: 13px 16px;
            margin-bottom: 20px;
            border-radius: 9px;
            font-size: 13px;
        }

        .alert.success {
            color: #216b42;
            background: #e7f5eb;
            border: 1px solid #c9e9d2;
        }

        .alert.error {
            color: #a33a3a;
            background: #fff0f0;
            border: 1px solid #f2d0d0;
        }

        .filter-card,
        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .filter-card {
            padding: 20px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-card label {
            font-size: 13px;
            font-weight: 650;
            margin-right: 8px;
        }

        select {
            min-width: 180px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            color: var(--text);
        }

        .table-heading {
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
        }

        .table-heading h2 {
            margin: 0;
            font-size: 17px;
        }

        .table-heading p {
            color: var(--muted);
            font-size: 12px;
            margin: 6px 0 0;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1100px;
            border-collapse: collapse;
            text-align: left;
        }

        thead { background: #f5f8f6; }

        th {
            color: #65766b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 15px 13px;
            white-space: nowrap;
        }

        td {
            padding: 15px 13px;
            border-top: 1px solid #edf0ed;
            font-size: 12px;
            line-height: 1.6;
            vertical-align: top;
        }

        tbody tr:hover { background: #f9fcfa; }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.pending {
            background: #fff1df;
            color: #a66514;
        }

        .status.approved {
            background: #e5f6eb;
            color: #247548;
        }

        .status.rejected {
            background: #ffebeb;
            color: #b13b3b;
        }

        .status.cancelled {
            background: #edf1ee;
            color: #53645a;
        }

        .actions {
            min-width: 190px;
        }

        .actions form {
            margin-bottom: 9px;
        }

        .actions input {
            width: 100%;
            padding: 8px;
            margin-bottom: 7px;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 11px;
        }

        button {
            width: 100%;
            padding: 8px 10px;
            border: 0;
            border-radius: 7px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
        }

        .btn-approve {
            color: white;
            background: var(--green);
        }

        .btn-approve:hover { background: var(--green-dark); }

        .btn-reject {
            color: white;
            background: #bd4b4b;
        }

        .btn-reject:hover { background: #9e3535; }

        .empty-state {
            padding: 45px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-state strong {
            display: block;
            color: var(--text);
            margin: 10px 0 6px;
        }

        .footer {
            text-align: center;
            padding: 25px 10px;
            color: #929d96;
            font-size: 11px;
        }

        @media (max-width: 700px) {
            .topbar {
                padding: 15px;
                gap: 10px;
            }

            .container { padding: 22px 14px; }

            .page-heading h1 { font-size: 23px; }

            .filter-card {
                align-items: flex-start;
                flex-direction: column;
            }

            select { width: 100%; }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <div class="brand-icon">📅</div>
        <div>
            <div class="brand-name">Smart Campus</div>
            <div class="brand-sub">QUẢN LÝ ĐẶT PHÒNG THỰC HÀNH</div>
        </div>
    </div>

    <a class="back-link" href="index.php?action=dashboard">
        ← Quay lại Dashboard
    </a>
</header>

<main class="container">

    <section class="page-heading">
        <h1>Duyệt yêu cầu đặt phòng</h1>
        <p>
            Kiểm tra thông tin, xác nhận hoặc từ chối các yêu cầu sử dụng phòng thực hành.
        </p>
    </section>

    <?php if ($flash): ?>
        <div class="alert <?= approvalEscape($flash['type'] ?? 'success') ?>">
            <?= approvalEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <section class="filter-card">
        <form method="GET" action="index.php">
            <input type="hidden" name="action" value="booking_approvals">

            <label for="status">Lọc theo trạng thái:</label>

            <select name="status" id="status" onchange="this.form.submit()">
                <option value="pending" <?= ($status ?? '') === 'pending' ? 'selected' : '' ?>>
                    Chờ duyệt
                </option>

                <option value="approved" <?= ($status ?? '') === 'approved' ? 'selected' : '' ?>>
                    Đã duyệt
                </option>

                <option value="rejected" <?= ($status ?? '') === 'rejected' ? 'selected' : '' ?>>
                    Đã từ chối
                </option>

                <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>
                    Đã hủy
                </option>

                <option value="all" <?= ($status ?? '') === 'all' ? 'selected' : '' ?>>
                    Tất cả
                </option>
            </select>
        </form>
    </section>

    <section class="table-card">

        <div class="table-heading">
            <h2>Danh sách yêu cầu</h2>
            <p>
                Tổng số yêu cầu: <?= count($bookings) ?>
            </p>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mã yêu cầu</th>
                        <th>Người đặt</th>
                        <th>Phòng</th>
                        <th>Nội dung</th>
                        <th>Thời gian sử dụng</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (empty($bookings)): ?>

                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div style="font-size:35px;">📭</div>
                                <strong>Không có yêu cầu đặt phòng</strong>
                                <div>Không tìm thấy yêu cầu phù hợp với bộ lọc hiện tại.</div>
                            </div>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($bookings as $booking): ?>

                        <?php
                            $bookingStatus = $booking['status'] ?? '';
                            $statusLabel = $statusLabels[$bookingStatus] ?? $bookingStatus;
                        ?>

                        <tr>
                            <td>
                                <strong>#<?= (int)$booking['booking_id'] ?></strong>
                            </td>

                            <td>
                                <strong><?= approvalEscape($booking['requester_name']) ?></strong>
                                <br>
                                <?= approvalEscape($booking['requester_email']) ?>
                            </td>

                            <td>
                                <strong><?= approvalEscape($booking['room_code']) ?></strong>
                                <br>
                                <?= approvalEscape($booking['room_name']) ?>
                                <br>
                                <?= approvalEscape($booking['building_name'] ?? '') ?>
                            </td>

                            <td>
                                <strong><?= approvalEscape($booking['title']) ?></strong>
                                <br>
                                <?= nl2br(approvalEscape($booking['purpose'])) ?>
                            </td>

                            <td>
                                <strong>Bắt đầu:</strong>
                                <br>
                                <?= approvalEscape($booking['start_time']) ?>
                                <br><br>

                                <strong>Kết thúc:</strong>
                                <br>
                                <?= approvalEscape($booking['end_time']) ?>
                            </td>

                            <td>
                                <span class="status <?= approvalEscape($bookingStatus) ?>">
                                    <?= approvalEscape($statusLabel) ?>
                                </span>
                            </td>

                            <td class="actions">

                                <?php if ($bookingStatus === 'pending'): ?>

                                    <form
                                        method="POST"
                                        action="index.php?action=booking_approve"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn duyệt yêu cầu này?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking['booking_id'] ?>"
                                        >

                                        <input
                                            type="text"
                                            name="comment"
                                            placeholder="Ghi chú (không bắt buộc)"
                                        >

                                        <button type="submit" class="btn-approve">
                                            ✓ Duyệt yêu cầu
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="index.php?action=booking_reject"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn từ chối yêu cầu này?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking['booking_id'] ?>"
                                        >

                                        <input
                                            type="text"
                                            name="comment"
                                            placeholder="Lý do từ chối"
                                            required
                                        >

                                        <button type="submit" class="btn-reject">
                                            ✕ Từ chối
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <span style="color:#929d96;">
                                        Đã xử lý
                                    </span>

                                <?php endif; ?>

                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </section>

    <footer class="footer">
        © Smart Campus Management · Quản lý phòng thực hành và thiết bị
    </footer>

</main>

</body>
</html>