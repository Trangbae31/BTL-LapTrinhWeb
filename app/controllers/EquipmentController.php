
<?php
require_once __DIR__ . '/../models/EquipmentModel.php';

class EquipmentController
{
    private EquipmentModel $model;

    public function __construct()
    {
        $this->model = new EquipmentModel();
    }

    public function index(): void
    {
        $keyword = trim($_GET['keyword'] ?? '');
        $equipmentList = $keyword !== ''
            ? $this->model->search($keyword)
            : $this->model->getAll();

        require __DIR__ . '/../views/equipment_list.php';
    }

    public function form(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $equipment = $id > 0 ? $this->model->getById($id) : null;
        $rooms = $this->model->getRooms();
        $types = $this->model->getTypes();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'room_id' => (int) ($_POST['room_id'] ?? 0),
                'equipment_type_id' => (int) ($_POST['equipment_type_id'] ?? 0),
                'equipment_code' => trim($_POST['equipment_code'] ?? ''),
                'equipment_name' => trim($_POST['equipment_name'] ?? ''),
                'brand' => trim($_POST['brand'] ?? ''),
                'purchase_date' => trim($_POST['purchase_date'] ?? ''),
                'status' => $_POST['status'] ?? 'available',
                'description' => trim($_POST['description'] ?? '')
            ];

            $allowed = ['available', 'maintenance', 'broken', 'retired'];

            if (
                $data['room_id'] < 1 ||
                $data['equipment_type_id'] < 1 ||
                $data['equipment_code'] === '' ||
                $data['equipment_name'] === ''
            ) {
                $error = 'Vui lòng nhập đủ mã, tên, phòng và loại thiết bị.';
            } elseif (!in_array($data['status'], $allowed, true)) {
                $error = 'Trạng thái không hợp lệ.';
            } else {
                try {
                    if ($id > 0) {
                        $this->model->update($id, $data);
                    } else {
                        $this->model->create($data);
                    }

                    header('Location: index.php?action=equipment');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Không lưu được. Kiểm tra mã thiết bị và dữ liệu CSDL.';
                }
            }

            $equipment = $data;
        }

        require __DIR__ . '/../views/equipment_form.php';
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id > 0) {
                try {
                    $this->model->delete($id);
                } catch (PDOException $e) {
                    // Không xóa nếu thiết bị đang được dữ liệu khác tham chiếu.
                }
            }
        }

        header('Location: index.php?action=equipment');
        exit;
    }
}
