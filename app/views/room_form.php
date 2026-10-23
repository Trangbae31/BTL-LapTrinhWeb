
<?php
function roomFormEscape($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
$isEdit = !empty($room['room_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isEdit ? 'Sửa phòng' : 'Thêm phòng' ?></title>
<style>
body{font-family:Arial,sans-serif;background:#f6f1e8;color:#43352c;padding:25px}
.container{max-width:650px;margin:auto;background:white;padding:25px;border-radius:12px}
h1{color:#795548}label{display:block;margin-top:13px}input,select,textarea{box-sizing:border-box;width:100%;padding:10px;margin-top:5px;border:1px solid #d5c5b6;border-radius:5px}
button,a{display:inline-block;margin-top:18px;padding:10px 15px;background:#795548;color:white;border:0;border-radius:5px;text-decoration:none;cursor:pointer}
.error{color:#b42318}
</style>
</head>
<body>
<div class="container">
<h1><?= $isEdit ? 'Cập nhật phòng' : 'Thêm phòng thực hành' ?></h1>

<?php if (!empty($error)): ?><p class="error"><?= roomFormEscape($error) ?></p><?php endif; ?>

<form method="post">
<label>Tòa nhà</label>
<select name="building_id" required>
<option value="">-- Chọn tòa nhà --</option>
<?php foreach ($buildings as $building): ?>
<option value="<?= (int)$building['building_id'] ?>"
<?= (string)($room['building_id'] ?? '') === (string)$building['building_id'] ? 'selected' : '' ?>>
<?= roomFormEscape($building['building_name']) ?>
</option>
<?php endforeach; ?>
</select>

<label>Mã phòng</label>
<input name="room_code" required maxlength="30" value="<?= roomFormEscape($room['room_code'] ?? '') ?>">

<label>Tên phòng</label>
<input name="room_name" required maxlength="150" value="<?= roomFormEscape($room['room_name'] ?? '') ?>">

<label>Sức chứa</label>
<input type="number" name="capacity" min="1" required value="<?= roomFormEscape($room['capacity'] ?? 30) ?>">

<label>Tầng</label>
<input type="number" name="floor_number" value="<?= roomFormEscape($room['floor_number'] ?? '') ?>">

<label>Loại phòng</label>
<input name="room_type" value="<?= roomFormEscape($room['room_type'] ?? 'Thuc hanh') ?>">

<label>Trạng thái</label>
<select name="status">
<?php foreach (['available'=>'Sẵn sàng','maintenance'=>'Đang bảo trì','inactive'=>'Ngừng hoạt động'] as $value=>$label): ?>
<option value="<?= $value ?>" <?= ($room['status'] ?? 'available') === $value ? 'selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
</select>

<label>Mô tả</label>
<textarea name="description" rows="3"><?= roomFormEscape($room['description'] ?? '') ?></textarea>

<button type="submit">Lưu phòng</button>
<a href="index.php?action=rooms">Quay lại</a>
</form>
</div>
</body>
</html>
