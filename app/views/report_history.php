<?php
$history = $history ?? [];
$reportInfo = $reportInfo ?? null;

$statusLabels = [
    'reported' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'resolved' => 'Đã hoàn thành',
    'cancelled' => 'Đã hủy'
];

function historyEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lịch sử bảo trì - Smart Campus</title>

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
            max-width: 1250px;
            padding: 32px;
            margin: auto;
        }

        .page-heading {
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
        }

        .report-card,
        .table-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .report-card {
            padding: 22px 24px;
        }

        .report-card h2 {
            margin: 0 0 18px;
            font-size: 16px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px 25px;
        }

        .info-label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 13px;
            font-weight: 650;
            overflow-wrap: anywhere;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: 700;
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
            min-width: 800px;
            border-collapse: collapse;
            text-align: left;
        }

        thead { background: #f5f8f6; }

        th {
            color: #65766b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 15px;
            white-space: nowrap;
        }

        td {
            padding: 16px 15px;
            border-top: 1px solid #edf0ed;
            font-size: 12px;
            line-height: 1.6;
            vertical-align: top;
        }

        tbody tr:hover { background: #f9fcfa; }

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

        .empty-icon {
            font-size: 35px;
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

            .container {
                padding: 22px 14px;
            }

            .page-heading h1 {
                font-size: 23px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
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

    <a class="back-link" href="index.php?action=report_list">
        ← Quay lại danh sách
    </a>
</header>

<main class="container">

    <section class="page-heading">
        <h1>Lịch sử xử lý phiếu báo hỏng</h1>
        <p>
            Theo dõi các thay đổi trạng thái và hoạt động bảo trì thiết bị.
        </p>
    </section>

    <?php if ($reportInfo): ?>
        <section class="report-card">
            <h2>Thông tin phiếu báo hỏng</h2>

            <div class="info-grid">

                <div>
                    <div class="info-label">Mã phiếu</div>
                    <div class="info-value">
                        #<?= (int)$reportInfo['report_id'] ?>
                    </div>
                </div>

                <div>
                    <div class="info-label">Trạng thái hiện tại</div>
                    <div class="info-value">
                        <?php
                            $currentStatus = $reportInfo['status'] ?? '';
                            $currentLabel = $statusLabels[$currentStatus] ?? $currentStatus;
                        ?>

                        <span class="status <?= historyEscape($currentStatus) ?>">
                            <?= historyEscape($currentLabel) ?>
                        </span>
                    </div>
                </div>

                <div>
                    <div class="info-label">Mã thiết bị</div>
                    <div class="info-value">
                        <?= historyEscape($reportInfo['equipment_code'] ?? '') ?>
                    </div>
                </div>

                <div>
                    <div class="info-label">Tên thiết bị</div>
                    <div class="info-value">
                        <?= historyEscape($reportInfo['equipment_name'] ?? '') ?>
                    </div>
                </div>

                <div>
                    <div class="info-label">Tiêu đề phiếu</div>
                    <div class="info-value">
                        <?= historyEscape($reportInfo['title'] ?? '') ?>
                    </div>
                </div>

            </div>
        </section>
    <?php endif; ?>

    <section class="table-card">

        <div class="table-heading">
            <h2>Chi tiết lịch sử</h2>
            <p>Các hoạt động được ghi nhận trong quá trình xử lý phiếu</p>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Mã lịch sử</th>
                        <th>Trạng thái cũ</th>
                        <th>Trạng thái mới</th>
                        <th>Người thực hiện</th>
                        <th>Ghi chú</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (empty($history)): ?>

                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-icon">📭</div>
                                    <strong>Chưa có lịch sử xử lý</strong>
                                    <div>Phiếu báo hỏng này chưa ghi nhận hoạt động nào.</div>
                                </div>
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($history as $item): ?>

                            <?php
                                $oldStatus = $item['old_status'] ?? '';
                                $newStatus = $item['new_status'] ?? '';

                                $oldLabel = $oldStatus === ''
                                    ? 'Chưa có'
                                    : ($statusLabels[$oldStatus] ?? $oldStatus);

                                $newLabel = $statusLabels[$newStatus] ?? $newStatus;
                            ?>

                            <tr>
                                <td>
                                    #<?= (int)$item['history_id'] ?>
                                </td>

                                <td>
                                    <?php if ($oldStatus === ''): ?>
                                        <?= historyEscape($oldLabel) ?>
                                    <?php else: ?>
                                        <span class="status <?= historyEscape($oldStatus) ?>">
                                            <?= historyEscape($oldLabel) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <span class="status <?= historyEscape($newStatus) ?>">
                                        <?= historyEscape($newLabel) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= historyEscape($item['user_name'] ?? 'Không rõ') ?>
                                </td>

                                <td>
                                    <?= nl2br(historyEscape($item['action_note'] ?? '')) ?>
                                </td>

                                <td>
                                    <?= historyEscape($item['changed_at'] ?? '') ?>
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