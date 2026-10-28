
<?php
function equipmentFormEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$isEdit = !empty($equipment['equipment_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isEdit ? 'Sửa thiết bị' : 'Thêm thiết bị' ?></title>
<style>
body{font-family:Arial,sans-serif;background:#f6f1e8;color:#43352c;padding:25px}
.container{max-width:700px;margin:auto;background:white;padding:25px;border-radius:12px}
h1{color:#795548}
label{display:block;margin-top:12px}
input,select,textarea{box-sizing:border-box;width:100%;padding:10px;margin-top:5px;border:1px solid #d5c5b6;border-radius:5px}
button,a{display:inline-block;margin-top:18px;padding:10px 15px;background:#795548;color:white;border:0;border-radius:5px;text-decoration:none;cursor:pointer}
.error{color:#b42318}
</style>
</head>
<body>
<div class="container">
<h1><?= $isEdit ? 'Cập nhật thiết bị' : 'Thêm thiết bị' ?></h1>

<?php if (!empty($error)): ?>
<p class="error"><?= equipmentFormEscape($error) ?></p>
<?php endif; ?>

<form method="post">
<label>Mã thiết bị</label>
<input name="equipment_code" required maxlength="50"
value="<?= equipmentFormEscape($equipment['equipment_code'] ?? '') ?>">

<label>Tên thiết bị</label>
<input name="equipment_name" required maxlength="150"
value="<?= equipmentFormEscape($equipment['equipment_name'] ?? '') ?>">

<label>Loại thiết bị</label>
<select name="equipment_type_id" required>
<option value="">-- Chọn loại thiết bị --</option>
<?php foreach ($types as $type): ?>
<option value="<?= (int)$type['equipment_type_id'] ?>"
<?= (string)($equipment['equipment_type_id'] ?? '') === (string)$type['equipment_type_id'] ? 'selected' : '' ?>>
<?= equipmentFormEscape($type['type_name']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Phòng</label>
<select name="room_id" required>
<option value="">-- Chọn phòng --</option>
<?php foreach ($rooms as $room): ?>
<option value="<?= (int)$room['room_id'] ?>"
<?= (string)($equipment['room_id'] ?? '') === (string)$room['room_id'] ? 'selected' : '' ?>>
<?= equipmentFormEscape($room['room_code'] . ' - ' . $room['room_name']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Hãng sản xuất</label>
<input name="brand" maxlength="100"
value="<?= equipmentFormEscape($equipment['brand'] ?? '') ?>">

<label>Ngày mua</label>
<input type="date" name="purchase_date"
value="<?= equipmentFormEscape($equipment['purchase_date'] ?? '') ?>">

<label>Trạng thái</label>
<select name="status">
<?php foreach ([
    'available' => 'Sẵn sàng',
    'maintenance' => 'Đang bảo trì',
    'broken' => 'Bị hỏng',
    'retired' => 'Đã thanh lý'
] as $value => $label): ?>
<option value="<?= $value ?>"
<?= ($equipment['status'] ?? 'available') === $value ? 'selected' : '' ?>>
<?= $label ?>
</option>
<?php endforeach; ?>
</select>

<label>Mô tả</label>
<textarea name="description" rows="3"><?= equipmentFormEscape($equipment['description'] ?? '') ?></textarea>

<button type="submit">Lưu thiết bị</button>
<a href="index.php?action=equipment">Quay lại</a>
</form>
</div>
</body>
</html>
