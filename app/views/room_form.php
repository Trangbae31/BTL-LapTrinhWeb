<?php

function roomFormEscape($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$isEdit = !empty($_GET['id']) || !empty($room['room_id']);
$roomId = (int)($room['room_id'] ?? $_GET['id'] ?? 0);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $isEdit ? 'Sửa phòng' : 'Thêm phòng' ?></title>

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
            max-width: 650px;
            margin: 20px auto;
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(67, 53, 44, 0.08);
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
            flex-wrap: wrap;
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
        <?= $isEdit ? 'Cập nhật phòng' : 'Thêm phòng thực hành' ?>
    </h1>

    <p class="description">
        <?= $isEdit
            ? 'Chỉnh sửa thông tin phòng thực hành trong hệ thống.'
            : 'Nhập thông tin để thêm phòng thực hành mới.' ?>
    </p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= roomFormEscape($error) ?>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="index.php?action=room_form<?= $isEdit ? '&id=' . $roomId : '' ?>"
    >

        <label for="building_id">Tòa nhà</label>

        <select id="building_id" name="building_id">

            <option value="">-- Không chọn tòa nhà --</option>

            <?php foreach ($buildings as $building): ?>

                <option
                    value="<?= (int)$building['building_id'] ?>"
                    <?= (string)($room['building_id'] ?? '') ===
                        (string)$building['building_id'] ? 'selected' : '' ?>
                >
                    <?= roomFormEscape($building['building_name']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="room_code">Mã phòng</label>

        <input
            type="text"
            id="room_code"
            name="room_code"
            required
            maxlength="30"
            value="<?= roomFormEscape($room['room_code'] ?? '') ?>"
            placeholder="Ví dụ: PTH001"
        >

        <label for="room_name">Tên phòng</label>

        <input
            type="text"
            id="room_name"
            name="room_name"
            required
            maxlength="150"
            value="<?= roomFormEscape($room['room_name'] ?? '') ?>"
            placeholder="Nhập tên phòng"
        >

        <label for="capacity">Sức chứa</label>

        <input
            type="number"
            id="capacity"
            name="capacity"
            min="1"
            required
            value="<?= roomFormEscape($room['capacity'] ?? 30) ?>"
        >

        <label for="location">Vị trí</label>

        <input
            type="text"
            id="location"
            name="location"
            maxlength="255"
            value="<?= roomFormEscape($room['location'] ?? '') ?>"
            placeholder="Ví dụ: Tầng 2, khu A"
        >

        <label for="status">Trạng thái</label>

        <select id="status" name="status">

            <option
                value="active"
                <?= ($room['status'] ?? 'active') === 'active' ? 'selected' : '' ?>
            >
                Đang hoạt động
            </option>

            <option
                value="inactive"
                <?= ($room['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>
            >
                Ngừng hoạt động
            </option>

        </select>

        <label for="description">Mô tả</label>

        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Nhập mô tả về phòng..."
        ><?= roomFormEscape($room['description'] ?? '') ?></textarea>

        <div class="buttons">

            <button type="submit">
                <?= $isEdit ? 'Cập nhật phòng' : 'Lưu phòng' ?>
            </button>

            <a
                class="back-link"
                href="index.php?action=rooms"
            >
                Quay lại
            </a>

        </div>

    </form>

</div>

</body>
</html>