
<?php

require_once __DIR__ . '/../models/RoomModel.php';

class RoomController
{
    private RoomModel $model;

    public function __construct()
    {
        $this->model = new RoomModel();
    }

    // Danh sach va tim kiem phong
    public function index(): void
    {
        $keyword = trim($_GET['keyword'] ?? '');

        if ($keyword !== '') {
            $rooms = $this->model->search($keyword);
        } else {
            $rooms = $this->model->getAll();
        }

        require __DIR__ . '/../views/room_list.php';
    }

    // Them hoac sua phong
    public function form(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $room = $id > 0 ? $this->model->getById($id) : null;
        $buildings = $this->model->getBuildings();
        $error = '';

        if ($id > 0 && $room === null) {
            $error = 'Không tìm thấy phòng cần sửa.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $buildingId = (int) ($_POST['building_id'] ?? 0);

            $data = [
                'building_id' => $buildingId > 0 ? $buildingId : null,
                'room_code' => trim($_POST['room_code'] ?? ''),
                'room_name' => trim($_POST['room_name'] ?? ''),
                'capacity' => (int) ($_POST['capacity'] ?? 1),
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];

            if (
                $data['room_code'] === '' ||
                $data['room_name'] === '' ||
                $data['capacity'] < 1
            ) {
                $error = 'Vui lòng nhập mã phòng, tên phòng và sức chứa hợp lệ.';
            } elseif (
                !in_array($data['status'], ['active', 'inactive'], true)
            ) {
                $error = 'Trạng thái phòng không hợp lệ.';
            } else {
                try {
                    if ($id > 0) {
                        $this->model->update($id, $data);
                    } else {
                        $this->model->create($data);
                    }

                    header('Location: index.php?action=rooms');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Không lưu được phòng. Hãy kiểm tra mã phòng hoặc tòa nhà.';
                }
            }

            $room = $data;
        }

        require __DIR__ . '/../views/room_form.php';
    }

    // Xoa phong
    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $this->model->delete($id);
            } catch (PDOException $e) {
                // Khong xoa neu phong dang duoc du lieu khac tham chieu.
            }
        }

        header('Location: index.php?action=rooms');
        exit;
    }
}
