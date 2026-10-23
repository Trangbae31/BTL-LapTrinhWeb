<?php
function equipmentEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quản lý thiết bị</title>
<style>
body{font-family:Arial,sans-serif;background:#f6f1e8;color:#43352c;margin:0;padding:30px}
.container{max-width:1200px;margin:auto;background:white;padding:25px;border-radius:12px}
h1{color:#795548}a,button{display:inline-block;padding:9px 12px;background:#795548;color:white;text-decoration:none;border:0;border-radius:5px;cursor:pointer}
table{width:100%;border-collapse:collapse;margin-top:20px}th,td{padding:11px;border-bottom:1px solid #e7ddd2;text-align:left}th{background:#eee4d8}.actions{display:flex;gap:6px}.delete{background:#a94442}
</style>
</head>
<body>
<div class="container">
<h1>Quản lý thiết bị</h1>
<p>Theo dõi thiết bị theo phòng và loại thiết bị.</p>
<a href="index.php?action=equipment_form">+ Thêm thiết bị</a>
<a href="index.php?action=dashboard">Về trang chính</a>

<table>
<thead><tr><th>Mã thiết bị</th><th>Tên thiết bị</th><th>Loại</th><th>Phòng</th><th>Số lượng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
<tbody>
<?php if (empty($equipmentList)): ?>
<tr><td colspan="7">Chưa có dữ liệu thiết bị.</td></tr>
<?php else: foreach ($equipmentList as $item): ?>
<tr>
<td><?= equipmentEscape($item['equipment_code']) ?></td>
<td><?= equipmentEscape($item['equipment_name']) ?></td>
<td><?= equipmentEscape($item['type_name']) ?></td>
<td><?= equipmentEscape(($item['room_code'] ?? '') . ' ' . ($item['room_name'] ?? 'Chưa phân phòng')) ?></td>
<td><?= (int)$item['quantity'] ?></td>
<td><?= equipmentEscape($item['status']) ?></td>
<td><div class="actions">
<a href="index.php?action=equipment_form&id=<?= (int)$item['equipment_id'] ?>">Sửa</a>
<form method="post" action="index.php?action=equipment_delete" onsubmit="return confirm('Bạn có chắc muốn xóa thiết bị này?')">
<input type="hidden" name="id" value="<?= (int)$item['equipment_id'] ?>">
<button class="delete" type="submit">Xóa</button>
</form>
</div></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</body>
</html>
