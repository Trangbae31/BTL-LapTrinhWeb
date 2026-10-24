
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý báo hỏng</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #e8f0fe; }
        a, button { cursor: pointer; }
    </style>
</head>
<body>

<h2>Quản lý báo hỏng và bảo trì thiết bị</h2>

<p>
    <a href="index.php?action=dashboard">Về trang chủ</a> |
    <a href="index.php?action=report_form">+ Tạo phiếu báo hỏng</a>
</p>

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
        </tr>
    </thead>

    <tbody>
        <?php if (empty($reports)): ?>
            <tr>
                <td colspan="9">Chưa có phiếu báo hỏng.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= (int)$report['report_id'] ?></td>
                    <td><?= htmlspecialchars($report['equipment_code'] ?? '') ?></td>
                    <td><?= htmlspecialchars($report['equipment_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($report['title']) ?></td>
                    <td><?= htmlspecialchars($report['description']) ?></td>
                    <td><?= htmlspecialchars($report['reporter_name'] ?? 'Không rõ') ?></td>
                    <td><?= htmlspecialchars($report['status']) ?></td>
                    <td><?= htmlspecialchars($report['reported_at']) ?></td>
                    <td>
                        <a href="index.php?action=report_history&id=<?= (int)$report['report_id'] ?>">
                            Xem lịch sử
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
