<?php

function equipmentFormEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$isEdit = !empty($equipment['equipment_id']);

$statusOptions = [
    'available' => 'Sẵn sàng',
    'maintenance' => 'Đang bảo trì',
    'broken' => 'Bị hỏng',
    'retired' => 'Đã thanh lý'
];

$currentStatus = $equipment['status'] ?? 'available';

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= $isEdit ? 'Sửa thiết bị' : 'Thêm thiết bị' ?>
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f6f1e8;
            color: #43352c;
            padding: 25px;
            margin: 0;
        }

        .container {
            max-width: 700px;
            margin: 20px auto;
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        h1 {
            color: #795548;
            margin-top: 0;
        }

        .description {
            color: #796b60;
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 11px;
            margin-top: 6px;
            border: 1px solid #d5c5b6;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #795548;
        }

        textarea {
            resize: vertical;
        }

        .error {
            color: #a12e2a;
            background: #fbe8e7;
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        button,
        .back-link {
            display: inline-block;
            padding: 11px 18px;
            background: #795548;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        .back-link {
            background: #a1887f;
        }

        button:hover {
            background: #604237;
        }

        .back-link:hover {
            background: #89766e;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>
        <?= $isEdit ? 'Cập nhật thiết bị' : 'Thêm thiết bị mới' ?>
    </h1>

    <p class="description">
        <?= $isEdit
            ? 'Chỉnh sửa thông tin thiết bị trong hệ thống.'
            : 'Nhập thông tin để thêm thiết bị vào hệ thống.' ?>
    </p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= equipmentFormEscape($error) ?>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="index.php?action=equipment_form<?= $isEdit ? '&id=' . (int)$equipment['equipment_id'] : '' ?>"
    >

        <label for="equipment_code">Mã thiết bị</label>

        <input
            type="text"
            id="equipment_code"
            name="equipment_code"
            maxlength="50"
            required
            value="<?= equipmentFormEscape($equipment['equipment_code'] ?? '') ?>"
        >

        <label for="equipment_name">Tên thiết bị</label>

        <input
            type="text"
            id="equipment_name"
            name="equipment_name"
            maxlength="150"
            required
            value="<?= equipmentFormEscape($equipment['equipment_name'] ?? '') ?>"
        >

        <label for="equipment_type_id">Loại thiết bị</label>

        <select
            id="equipment_type_id"
            name="equipment_type_id"
            required
        >
            <option value="">-- Chọn loại thiết bị --</option>

            <?php foreach ($types as $type): ?>

                <option
                    value="<?= (int)$type['equipment_type_id'] ?>"
                    <?= (string)($equipment['equipment_type_id'] ?? '') ===
                        (string)$type['equipment_type_id'] ? 'selected' : '' ?>
                >
                    <?= equipmentFormEscape($type['type_name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="room_id">Phòng</label>

        <select
            id="room_id"
            name="room_id"
            required
        >
            <option value="">-- Chọn phòng --</option>

            <?php foreach ($rooms as $room): ?>

                <option
                    value="<?= (int)$room['room_id'] ?>"
                    <?= (string)($equipment['room_id'] ?? '') ===
                        (string)$room['room_id'] ? 'selected' : '' ?>
                >
                    <?= equipmentFormEscape(
                        $room['room_code'] . ' - ' . $room['room_name']
                    ) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="brand">Hãng sản xuất</label>

        <input
            type="text"
            id="brand"
            name="brand"
            maxlength="100"
            value="<?= equipmentFormEscape($equipment['brand'] ?? '') ?>"
        >

        <label for="purchase_date">Ngày mua</label>

        <input
            type="date"
            id="purchase_date"
            name="purchase_date"
            value="<?= equipmentFormEscape($equipment['purchase_date'] ?? '') ?>"
        >

        <label for="status">Trạng thái</label>

        <select id="status" name="status">

            <?php foreach ($statusOptions as $value => $label): ?>

                <option
                    value="<?= equipmentFormEscape($value) ?>"
                    <?= $currentStatus === $value ? 'selected' : '' ?>
                >
                    <?= equipmentFormEscape($label) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="description">Mô tả</label>

        <textarea
            id="description"
            name="description"
            rows="4"
        ><?= equipmentFormEscape($equipment['description'] ?? '') ?></textarea>

        <div class="buttons">

            <button type="submit">
                <?= $isEdit ? 'Cập nhật thiết bị' : 'Lưu thiết bị' ?>
            </button>

            <a
                class="back-link"
                href="index.php?action=equipment"
            >
                Quay lại
            </a>

        </div>

    </form>

</div>

</body>
</html>