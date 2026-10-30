<?php

require_once __DIR__ . '/../models/RoomModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class RoomController
{
    private RoomModel $model;

    public function __construct()
    {
        $this->model = new RoomModel();
    }

    /**
     * Chỉ admin và manager được quản lý phòng.
     */
    private function authorizeManager(): void
    {
        Authorization::requireRole(['admin', 'manager']);
    }

    /**
     * Hiển thị danh sách phòng.
     */
    public function index(): void
    {
        $this->authorizeManager();

        $keyword = trim($_GET['keyword'] ?? '');

        if ($keyword !== '') {
            $rooms = $this->model->search($keyword);
        } else {
            $rooms = $this->model->getAll();
        }

        require __DIR__ . '/../views/room_list.php';
    }

    /**
     * Hiển thị form thêm hoặc sửa phòng.
     */
    public function form(): void
    {
        $this->authorizeManager();

        $id = (int)($_GET['id'] ?? 0);

        $room = $id > 0
            ? $this->model->getById($id)
            : null;

        $buildings = $this->model->getBuildings();
        $error = '';

        if ($id > 0 && $room === null) {
            $error = 'Không tìm thấy phòng cần sửa.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $buildingId = (int)($_POST['building_id'] ?? 0);

            $data = [
                'building_id' => $buildingId > 0 ? $buildingId : null,
                'room_code' => trim($_POST['room_code'] ?? ''),
                'room_name' => trim($_POST['room_name'] ?? ''),
                'capacity' => (int)($_POST['capacity'] ?? 0),
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];

            if ($id > 0 && $room === null) {
                $error = 'Không tìm thấy phòng cần sửa.';
            } elseif (
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
                    exit();

                } catch (PDOException $e) {
                    error_log('Save room error: ' . $e->getMessage());
                    $error = 'Không lưu được phòng. Hãy kiểm tra mã phòng hoặc tòa nhà.';
                }
            }

            $room = $data;
        }

        require __DIR__ . '/../views/room_form.php';
    }

    /**
     * Xóa phòng.
     */
    public function delete(): void
    {
        $this->authorizeManager();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Phương thức yêu cầu không hợp lệ.');
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Mã phòng không hợp lệ.'
            ];

            header('Location: index.php?action=rooms');
            exit();
        }

        try {
            $success = $this->model->delete($id);

            $_SESSION['flash'] = [
                'type' => $success ? 'success' : 'error',
                'message' => $success
                    ? 'Đã xóa phòng.'
                    : 'Không thể xóa phòng. Phòng có thể đang được sử dụng.'
            ];

        } catch (PDOException $e) {
            error_log('Delete room error: ' . $e->getMessage());

            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Không thể xóa phòng vì dữ liệu khác đang tham chiếu đến phòng này.'
            ];
        }

        header('Location: index.php?action=rooms');
        exit();
    }
}