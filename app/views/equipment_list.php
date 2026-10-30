<?php

function equipmentEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusLabels = [
    'available' => 'Sẵn sàng',
    'maintenance' => 'Đang bảo trì',
    'broken' => 'Bị hỏng',
    'retired' => 'Đã thanh lý'
];

$canManage = $canManage ?? false;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý thiết bị</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f6f1e8;
    color: #43352c;
    margin: 0;
    padding: 30px;
}

.container {
    max-width: 1200px;
    margin: auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    overflow-x: auto;
}

h1 {
    color: #795548;
}

a, button {
    display: inline-block;
    padding: 9px 12px;
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
    margin: 18px 0;
}

.search-form {
    display: flex;
    gap: 8px;
    flex: 1;
    min-width: 280px;
}

.search-form input {
    flex: 1;
    min-width: 0;
    padding: 10px;
    border: 1px solid #d5c5b6;
    border-radius: 5px;
}

.clear {
    background: #a1887f;
}

table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 11px;
    border-bottom: 1px solid #e7ddd2;
    text-align: left;
}

th {
    background: #eee4d8;
}

.actions {
    display: flex;
    gap: 6px;
    align-items: center;
}

.actions form {
    margin: 0;
}

.delete {
    background: #a94442;
}

.empty {
    text-align: center;
    padding: 20px;
}

.flash {
    padding: 12px 15px;
    margin: 15px 0;
    border-radius: 6px;
}

.flash.success {
    background: #e5f3e8;
    color: #28643a;
}

.flash.error {
    background: #fbe8e7;
    color: #a12e2a;
}

.note {
    color: #796b60;
    font-size: 14px;
}
</style>
</head>

<body>

<div class="container">

    <h1>Quản lý thiết bị</h1>

    <p>Theo dõi thiết bị theo phòng và loại thiết bị.</p>

    <?php if ($flash): ?>
        <div class="flash <?= equipmentEscape($flash['type'] ?? 'success') ?>">
            <?= equipmentEscape($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="toolbar">

        <form class="search-form" method="get" action="index.php">

            <input type="hidden" name="action" value="equipment">

            <input
                type="text"
                name="keyword"
                placeholder="Tìm mã, tên, hãng, phòng hoặc loại thiết bị..."
                value="<?= equipmentEscape($_GET['keyword'] ?? '') ?>"
            >

            <button type="submit">Tìm kiếm</button>

            <?php if (!empty($_GET['keyword'])): ?>
                <a class="clear" href="index.php?action=equipment">
                    Xóa tìm kiếm
                </a>
            <?php endif; ?>

        </form>

        <?php if ($canManage): ?>
            <a href="index.php?action=equipment_form">
                + Thêm thiết bị
            </a>
        <?php endif; ?>

        <a href="index.php?action=dashboard">
            Về trang chính
        </a>

    </div>

    <?php if (!$canManage): ?>
        <p class="note">
            Bạn đang ở chế độ xem danh sách thiết bị.
        </p>
    <?php endif; ?>

    <table>

        <thead>
            <tr>
                <th>Mã thiết bị</th>
                <th>Tên thiết bị</th>
                <th>Loại</th>
                <th>Phòng</th>
                <th>Hãng sản xuất</th>
                <th>Ngày mua</th>
                <th>Trạng thái</th>

                <?php if ($canManage): ?>
                    <th>Thao tác</th>
                <?php endif; ?>
            </tr>
        </thead>

        <tbody>

            <?php if (empty($equipmentList)): ?>

                <tr>
                    <td colspan="<?= $canManage ? 8 : 7 ?>" class="empty">
                        Không tìm thấy thiết bị phù hợp.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($equipmentList as $item): ?>

                    <tr>

                        <td>
                            <?= equipmentEscape($item['equipment_code'] ?? '') ?>
                        </td>

                        <td>
                            <?= equipmentEscape($item['equipment_name'] ?? '') ?>
                        </td>

                        <td>
                            <?= equipmentEscape($item['type_name'] ?? 'Chưa phân loại') ?>
                        </td>

                        <td>
                            <?= equipmentEscape(
                                ($item['room_code'] ?? '') .
                                ' - ' .
                                ($item['room_name'] ?? 'Chưa phân phòng')
                            ) ?>
                        </td>

                        <td>
                            <?= equipmentEscape($item['brand'] ?? '') ?>
                        </td>

                        <td>
                            <?= equipmentEscape($item['purchase_date'] ?? '') ?>
                        </td>

                        <td>
                            <?= equipmentEscape(
                                $statusLabels[$item['status'] ?? '']
                                ?? ($item['status'] ?? '')
                            ) ?>
                        </td>

                        <?php if ($canManage): ?>

                            <td>

                                <div class="actions">

                                    <a href="index.php?action=equipment_form&id=<?= (int)$item['equipment_id'] ?>">
                                        Sửa
                                    </a>

                                    <form
                                        method="post"
                                        action="index.php?action=equipment_delete"
                                        onsubmit="return confirm('Bạn có chắc muốn xóa thiết bị này?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$item['equipment_id'] ?>"
                                        >

                                        <button class="delete" type="submit">
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

        </tbody>

    </table>

</div>

</body>
</html>