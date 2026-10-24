
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tạo phiếu báo hỏng</title>
</head>
<body>

<h2>Tạo phiếu báo hỏng thiết bị</h2>

<form action="index.php?action=report_store" method="POST">

    <p>
        <label>Thiết bị:</label><br>
        <select name="equipment_id" required>
            <option value="">-- Chọn thiết bị --</option>

            <?php foreach ($equipment as $item): ?>
                <option value="<?= (int)$item['equipment_id'] ?>">
                    <?= htmlspecialchars($item['equipment_code']) ?>
                    -
                    <?= htmlspecialchars($item['equipment_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <p>
        <label>Tiêu đề lỗi:</label><br>
        <input type="text" name="title" maxlength="200" required>
    </p>

    <p>
        <label>Mô tả lỗi:</label><br>
        <textarea name="description" rows="5" required></textarea>
    </p>

    <button type="submit">Gửi phiếu báo hỏng</button>
    <a href="index.php?action=reports">Quay lại</a>

</form>

</body>
</html>
