
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử bảo trì</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 10px; }
        th { background: #e8f0fe; }
    </style>
</head>
<body>

<h2>Lịch sử xử lý phiếu báo hỏng</h2>

<p><a href="index.php?action=reports">Quay lại danh sách</a></p>

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
            <tr><td colspan="6">Phiếu chưa có lịch sử xử lý.</td></tr>
        <?php else: ?>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= (int)$item['history_id'] ?></td>
                    <td><?= htmlspecialchars($item['old_status'] ?? 'Chưa có') ?></td>
                    <td><?= htmlspecialchars($item['new_status']) ?></td>
                    <td><?= htmlspecialchars($item['user_name'] ?? 'Không rõ') ?></td>
                    <td><?= htmlspecialchars($item['action_note'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['changed_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
