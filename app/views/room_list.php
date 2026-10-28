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

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f1e8;
            color: #43352c;
            margin: 0;
            padding: 30px;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(67, 53, 44, .08);
            overflow-x: auto;
        }
        h1 { color: #795548; }
        a, .btn {
            display: inline-block;
            padding: 9px 13px;
            background: #795548;
            color: white;
            text-decoration: none;
            border: 0;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin: 20px 0;
        }
        .search-form {
            display: flex;
            gap: 8px;
            flex: 1;
            min-width: 260px;
        }
        .search-form input {
            flex: 1;
            min-width: 0;
            padding: 10px;
            border: 1px solid #d5c5b6;
            border-radius: 5px;
        }
        .clear { background: #a1887f; }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e7ddd2;
            text-align: left;
        }
        th { background: #eee4d8; }
        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .actions form { margin: 0; }
        .delete { background: #a94442; }
        .status-active { color: #287a45; font-weight: bold; }
        .status-inactive { color: #a94442; font-weight: bold; }
        .empty { text-align: center; padding: 20px; }
    </style>
</head>

<body>
<div class="container">
    <h1>Quản lý phòng thực hành</h1>
    <p>Danh sách phòng trong hệ thống</p>

    <div class="toolbar">
        <form class="search-form" method="get" action="index.php">
            <input type="hidden" name="action" value="rooms">

            <input
                type="text"
                name="keyword"
                placeholder="Tìm theo mã phòng, tên phòng, vị trí hoặc tòa nhà..."
                value="<?= roomEscape($_GET['keyword'] ?? '') ?>"
            >

            <button class="btn" type="submit">Tìm kiếm</button>

            <?php if (!empty($_GET['keyword'])): ?>
                <a class="clear" href="index.php?action=rooms">Xóa tìm kiếm</a>
            <?php endif; ?>
        </form>

        <a href="index.php?action=room_form">+ Thêm phòng</a>
        <a href="index.php?action=dashboard">Về trang chính</a>
    </div>

    <table>
        <thead>
        <tr>
            <th>Mã phòng</th>
            <th>Tên phòng</th>
            <th>Tòa nhà</th>
            <th>Sức chứa</th>
            <th>Vị trí</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
        </thead>

        <tbody>
        <?php if (empty($rooms)): ?>
            <tr>
                <td colspan="7" class="empty">
                    Không tìm thấy phòng phù hợp.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?= roomEscape($room['room_code'] ?? '') ?></td>
                    <td><?= roomEscape($room['room_name'] ?? '') ?></td>
                    <td>
                        <?= roomEscape($room['building_name'] ?? 'Chưa phân tòa nhà') ?>
                    </td>
                    <td><?= roomEscape($room['capacity'] ?? '') ?></td>
                    <td><?= roomEscape($room['location'] ?? '') ?></td>
                    <td>
                        <?php if (($room['status'] ?? '') === 'active'): ?>
                            <span class="status-active">Đang hoạt động</span>
                        <?php else: ?>
                            <span class="status-inactive">Ngừng hoạt động</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="index.php?action=room_form&id=<?= (int)$room['room_id'] ?>">
                                Sửa
                            </a>

                            <form
                                method="post"
                                action="index.php?action=room_delete"
                                onsubmit="return confirm('Bạn có chắc muốn xóa phòng này?')"
                            >
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$room['room_id'] ?>"
                                >
                                <button class="btn delete" type="submit">Xóa</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
