
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
        }

        .btn-primary {
            background: var(--green);
            color: white;
        }

        .btn-primary:hover {
            background: var(--green-dark);
        }

        .btn-edit {
            background: #eaf2ff;
            color: #285ca8;
        }

        .btn-edit:hover {
            background: #d8e7ff;
        }

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
            gap: 7px;
            align-items: flex-start;
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

        <a href="index.php?action=report_form" class="btn btn-primary">
            <span>＋</span> Tạo phiếu báo hỏng
        </a>
    </section>

    <section class="summary-card">
        <div class="summary-icon">📋</div>
        <div>
            <div class="summary-title">Tổng số phiếu đang hiển thị</div>
            <div class="summary-number">
                <?= is_countable($reports ?? []) ? count($reports ?? []) : 0 ?>
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
                        <th>Trạng thái</th>
                        <th>Thời gian</th>
                        <th>Lịch sử</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="10">
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

                                $statusLabels = [
                                    'reported' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'resolved' => 'Đã hoàn thành',
                                    'cancelled' => 'Đã hủy'
                                ];

                                $statusLabel = $statusLabels[$status] ?? $status;
                            ?>

                            <tr>
                                <td class="report-id">
                                    #<?= $reportId ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string)($report['equipment_code'] ?? ''),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td class="equipment-name">
                                    <?= htmlspecialchars(
                                        (string)($report['equipment_name'] ?? ''),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string)($report['title'] ?? ''),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td class="description">
                                    <?= htmlspecialchars(
                                        (string)($report['description'] ?? ''),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string)($report['reporter_name'] ?? 'Không rõ'),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <span class="status <?= htmlspecialchars(
                                        $status, ENT_QUOTES, 'UTF-8'
                                    ) ?>">
                                        <?= htmlspecialchars(
                                            $statusLabel, ENT_QUOTES, 'UTF-8'
                                        ) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        (string)($report['reported_at'] ?? ''),
                                        ENT_QUOTES, 'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <a class="history-link"
                                       href="index.php?action=report_history&amp;id=<?= $reportId ?>">
                                        Xem lịch sử →
                                    </a>
                                </td>

                                <td>
                                    <div class="action-cell">
                                        <a class="btn btn-edit"
                                           href="index.php?action=report_edit&amp;id=<?= $reportId ?>">
                                            ✏️ Sửa
                                        </a>
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
