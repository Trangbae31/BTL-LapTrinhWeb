
<?php

require_once __DIR__ . '/../models/RoomModel.php';

class RoomController
{
    private RoomModel $model;

    public function __construct()
    {
        $this->model = new RoomModel();
    }

    public function index(): void
    {
        $rooms = $this->model->getAll();
        require __DIR__ . '/../views/room_list.php';
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $room = $id > 0 ? $this->model->getById($id) : null;
        $buildings = $this->model->getBuildings();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'room_code' => trim($_POST['room_code'] ?? ''),
                'room_name' => trim($_POST['room_name'] ?? ''),
                'capacity' => (int)($_POST['capacity'] ?? 1),
                'floor_number' => trim($_POST['floor_number'] ?? ''),
                'room_type' => trim($_POST['room_type'] ?? 'Thuc hanh'),
                'status' => $_POST['status'] ?? 'available',
                'description' => trim($_POST['description'] ?? '')
            ];

            if ($data['building_id'] < 1 ||
                $data['room_code'] === '' ||
                $data['room_name'] === '' ||
                $data['capacity'] < 1) {
                $error = 'Vui lòng nhập đầy đủ thông tin hợp lệ.';
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
                    $error = 'Không lưu được phòng. Hãy kiểm tra mã phòng có bị trùng không.';
                }
            }

            $room = $data;
        }

        require __DIR__ . '/../views/room_form.php';
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $this->model->delete($id);
            } catch (PDOException $e) {
                // Không xóa nếu phòng đang được thiết bị hoặc dữ liệu khác tham chiếu.
            }
        }

        header('Location: index.php?action=rooms');
        exit;
    }
}
