<?php
function roomEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý phòng thực hành</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body{font-family:Arial,sans-serif;background:#f6f1e8;color:#43352c;margin:0;padding:30px}
        .container{max-width:1100px;margin:auto;background:white;padding:25px;border-radius:12px}
        h1{color:#795548}a,.btn{display:inline-block;padding:9px 13px;background:#795548;color:white;text-decoration:none;border:0;border-radius:5px;cursor:pointer}
        .add{margin-bottom:15px}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #e7ddd2;text-align:left}th{background:#eee4d8}
        .actions{display:flex;gap:6px}.delete{background:#a94442}
    </style>
</head>
<body>
<div class="container">
    <h1>Quản lý phòng thực hành</h1>
    <p>Danh sách phòng trong hệ thống</p>
    <a class="add" href="index.php?action=room_form">+ Thêm phòng</a>
    <a href="index.php?action=dashboard">Về trang chính</a>
    <table>
        <thead>
        <tr>
            <th>Mã phòng</th><th>Tên phòng</th><th>Tòa nhà</th>
            <th>Sức chứa</th><th>Tầng</th><th>Trạng thái</th><th>Thao tác</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($rooms)): ?>
            <tr><td colspan="7">Chưa có dữ liệu phòng.</td></tr>
        <?php else: foreach ($rooms as $room): ?>
            <tr>
                <td><?= roomEscape($room['room_code']) ?></td>
                <td><?= roomEscape($room['room_name']) ?></td>
                <td><?= roomEscape($room['building_name']) ?></td>
                <td><?= roomEscape($room['capacity']) ?></td>
                <td><?= roomEscape($room['floor_number']) ?></td>
                <td><?= roomEscape($room['status']) ?></td>
                <td>
                    <div class="actions">
                        <a href="index.php?action=room_form&id=<?= (int)$room['room_id'] ?>">Sửa</a>
                        <form method="post" action="index.php?action=room_delete"
                              onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?')">
                            <input type="hidden" name="id" value="<?= (int)$room['room_id'] ?>">
                            <button class="btn delete" type="submit">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
