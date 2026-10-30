<?php
$isManager = $isManager ?? false;
$isTechnician = $isTechnician ?? false;
$isTeacher = $isTeacher ?? false;
$reports = $reports ?? [];
$technicians = $technicians ?? [];

$statusLabels = [
    'reported' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'resolved' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
];

function reportEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý báo hỏng - Smart Campus</title>

    <style>
        :root {
            --green: #176b4d;
            --green-dark: #104d39;
            --green-light: #e8f5ee;
            --bg: #f5f7f5;
            --text: #24372e;
            --muted: #78867e;
            --border: #e5ebe6;
            --white: #fff;
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
            gap: 15px;
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
            max-width: 1500px;
            padding: 32px;
            margin: auto;
        }

        .page-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin: 0;
        }

        .page-heading p {
            margin: 9px 0 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 8px;
            padding: 10px 13px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--green);
            color: white;
        }

        .btn-primary:hover { background: var(--green-dark); }

        .btn-edit {
            background: #eaf2ff;
            color: #285ca8;
        }

        .btn-edit:hover { background: #d8e7ff; }

        .btn-warning {
            background: #fff1df;
            color: #a66514;
        }

        .btn-warning:hover { background: #ffe6c5; }

        .btn-success {
            background: #e5f6eb;
            color: #247548;
        }

        .btn-success:hover { background: #d2efdc; }

        .summary-card {
            display: flex;
            align-items: center;
            gap: 17px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 21px 24px;
            margin-bottom: 24px;
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 13px;
            display: grid;
            place-items: center;
            background: #fff1df;
            font-size: 24px;
        }

        .summary-title {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .summary-number {
            font-size: 20px;
            font-weight: 750;
        }

        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
        }

        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 24px;
            border-bottom: 1px solid var(--border);
            gap: 15px;
        }

        .table-heading h2 {
            font-size: 17px;
            margin: 0;
        }

        .table-heading p {
            font-size: 12px;
            color: var(--muted);
            margin: 6px 0 0;
        }

        .table-scroll {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1200px;
            border-collapse: collapse;
            text-align: left;
        }

        thead { background: #f5f8f6; }

        th {
            color: #65766b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            font-weight: 700;
            padding: 15px 14px;
            white-space: nowrap;
        }

        td {
            padding: 16px 14px;
            border-top: 1px solid #edf0ed;
            font-size: 12px;
            line-height: 1.6;
            vertical-align: top;
        }

        tbody tr:hover { background: #f9fcfa; }

        .report-id {
            color: var(--green);
            font-weight: 750;
        }

        .equipment-name {
            font-weight: 650;
            min-width: 120px;
        }

        .description {
            max-width: 210px;
            min-width: 150px;
            color: #69776f;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 6px 9px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            background: #edf1ee;
            color: #53645a;
        }

        .status.reported {
            background: #fff1df;
            color: #a66514;
        }

        .status.processing {
            background: #eaf2ff;
            color: #3566b3;
        }

        .status.resolved {
            background: #e5f6eb;
            color: #247548;
        }

        .status.cancelled {
            background: #ffebeb;
            color: #b13b3b;
        }

        .history-link {
            color: var(--green);
            font-weight: 700;
            white-space: nowrap;
        }

        .history-link:hover { text-decoration: underline; }

        .action-cell {
            display: flex;
            flex-direction: column;
            gap: 9px;
            align-items: stretch;
            min-width: 190px;
        }

        .action-form {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 180px;
        }

        .action-form select,
        .action-form textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 9px;
            font-family: inherit;
            font-size: 12px;
            color: var(--text);
            background: white;
        }

        .action-form textarea {
            min-height: 58px;
            resize: vertical;
        }

        .action-form select:focus,
        .action-form textarea:focus {
            outline: 2px solid var(--green-light);
            border-color: var(--green);
        }

        .assigned-info {
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }

        .empty-state {
            padding: 55px 20px;
            text-align: center;
            color: var(--muted);
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .empty-state strong {
            display: block;
            color: var(--text);
            font-size: 15px;
            margin-bottom: 7px;
        }

        .footer {
            text-align: center;
            padding: 25px 10px;
            color: #929d96;
            font-size: 11px;
        }

        .role-note {
            margin: 0 0 18px;
            padding: 13px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: white;
            color: #65766b;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .topbar {
                height: auto;
                padding: 17px;
            }

            .brand-name { font-size: 15px; }

            .container { padding: 22px 14px; }

            .page-heading { flex-direction: column; }

            .page-heading h1 { font-size: 23px; }

            .table-heading { padding: 18px; }
        }
    </style>
</head>

<body>

<header class="topbar">
    <div class="brand">
        <div class="brand-icon">🛠️</div>
        <div>
            <div class="brand-name">Smart Campus</div>
            <div class="brand-sub">QUẢN LÝ BÁO HỎNG &amp; BẢO TRÌ</div>
        </div>
    </div>

    <a class="back-link" href="index.php?action=dashboard">
        ← Về trang chủ
    </a>
</header>

<main class="container">

    <section class="page-heading">
        <div>
            <h1>Quản lý báo hỏng</h1>
            <p>
                Theo dõi các sự cố thiết bị, trạng thái xử lý
                và lịch sử bảo trì trong phòng thực hành.
            </p>
        </div>

        <?php if ($isTeacher || (!$isManager && !$isTechnician)): ?>
            <a href="index.php?action=report_form" class="btn btn-primary">
                <span>＋</span> Tạo phiếu báo hỏng
            </a>
        <?php endif; ?>
    </section>

    <?php if ($isManager): ?>
        <div class="role-note">
            <strong>Quyền quản lý:</strong>
            Bạn có thể xem tất cả phiếu báo hỏng và phân công kỹ thuật viên xử lý.
        </div>
    <?php elseif ($isTechnician): ?>
        <div class="role-note">
            <strong>Quyền kỹ thuật:</strong>
            Bạn chỉ có thể cập nhật trạng thái và ghi chú xử lý đối với phiếu được phân công cho mình.
            Bạn không thể thay đổi người báo, thiết bị, tiêu đề hoặc mô tả sự cố.
        </div>
    <?php elseif ($isTeacher): ?>
        <div class="role-note">
            <strong>Quyền giảng viên:</strong>
            Bạn có thể tạo phiếu báo hỏng và theo dõi tình trạng xử lý.
        </div>
    <?php endif; ?>

    <section class="summary-card">
        <div class="summary-icon">📋</div>
        <div>
            <div class="summary-title">Tổng số phiếu đang hiển thị</div>
            <div class="summary-number">
                <?= count($reports) ?>
                <span style="font-size:12px;font-weight:400;color:#78867e">
                    phiếu báo hỏng
                </span>
            </div>
        </div>
    </section>

    <section class="table-card">
        <div class="table-heading">
            <div>
                <h2>Danh sách phiếu báo hỏng</h2>
                <p>Thông tin sự cố và lịch sử xử lý thiết bị</p>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Mã thiết bị</th>
                        <th>Tên thiết bị</th>
                        <th>Tiêu đề</th>
                        <th>Mô tả</th>
                        <th>Người báo</th>
                        <th>Người được giao</th>
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Lịch sử</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <strong>Chưa có phiếu báo hỏng</strong>
                                    <div>Hiện chưa có dữ liệu để hiển thị.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($reports as $report): ?>
                            <?php
                                $reportId = (int)($report['report_id'] ?? 0);
                                $status = (string)($report['status'] ?? '');
                                $statusLabel = $statusLabels[$status] ?? $status;
                                $assignedName = $report['technician_name'] ?? '';
                                $assignedId = (int)($report['assigned_to'] ?? 0);
                            ?>

                            <tr>
                                <td class="report-id">
                                    #<?= $reportId ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['equipment_code'] ?? '') ?>
                                </td>

                                <td class="equipment-name">
                                    <?= reportEscape($report['equipment_name'] ?? '') ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['title'] ?? '') ?>
                                </td>

                                <td class="description">
                                    <?= reportEscape($report['description'] ?? '') ?>
                                </td>

                                <td>
                                    <?= reportEscape($report['reporter_name'] ?? 'Không rõ') ?>
                                </td>

                                <td>
                                    <?php if ($assignedName !== ''): ?>
                                        <?= reportEscape($assignedName) ?>
                                    <?php else: ?>
                                        <span style="color:#a66514">Chưa phân công</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status <?= reportEscape($status) ?>">
                                        <?= reportEscape($statusLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= reportEscape($report['reported_at'] ?? '') ?>
                                </td>

                                <td>
                                    <a class="history-link"
                                       href="index.php?action=report_history&amp;id=<?= $reportId ?>">
                                        Xem lịch sử →
                                    </a>
                                </td>

                                <td>
                                    <div class="action-cell">

                                        <?php if ($isManager && !in_array($status, ['resolved', 'cancelled'], true)): ?>

                                            <form class="action-form"
                                                  method="POST"
                                                  action="index.php?action=report_assign">

                                                <input type="hidden"
                                                       name="report_id"
                                                       value="<?= $reportId ?>">

                                                <select name="technician_id" required>
                                                    <option value="">-- Chọn kỹ thuật viên --</option>

                                                    <?php foreach ($technicians as $technician): ?>
                                                        <option
                                                            value="<?= (int)$technician['user_id'] ?>"
                                                            <?= $assignedId === (int)$technician['user_id'] ? 'selected' : '' ?>>
                                                            <?= reportEscape($technician['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" class="btn btn-primary">
                                                    Phân công
                                                </button>
                                            </form>

                                        <?php elseif ($isTechnician && $assignedId === (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0) && !in_array($status, ['resolved', 'cancelled'], true)): ?>

                                            <form class="action-form"
                                                  method="POST"
                                                  action="index.php?action=report_update">

                                                <input type="hidden"
                                                       name="report_id"
                                                       value="<?= $reportId ?>">

                                                <?php if ($status === 'reported'): ?>
                                                    <button type="submit"
                                                            name="status"
                                                            value="processing"
                                                            class="btn btn-edit">
                                                        Bắt đầu xử lý
                                                    </button>
                                                <?php endif; ?>

                                                <textarea name="resolution_note"
                                                          placeholder="Ghi chú quá trình xử lý..."></textarea>

                                                <button type="submit"
                                                        name="status"
                                                        value="resolved"
                                                        class="btn btn-success">
                                                    Hoàn thành
                                                </button>
                                            </form>

                                        <?php elseif ($isTechnician && $assignedId !== (int)($_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? 0)): ?>

                                            <span class="assigned-info">
                                                Không thuộc phiếu được giao cho bạn.
                                            </span>

                                        <?php else: ?>

                                            <span class="assigned-info">
                                                Không có thao tác.
                                            </span>

                                        <?php endif; ?>

                                    </div>
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