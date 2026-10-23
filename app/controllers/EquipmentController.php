
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
        $equipmentList = $this->model->getAll();
        require __DIR__ . '/../views/equipment_list.php';
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $equipment = $id > 0 ? $this->model->getById($id) : null;
        $rooms = $this->model->getRooms();
        $types = $this->model->getTypes();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'room_id' => $_POST['room_id'] ?? '',
                'type_id' => (int)($_POST['type_id'] ?? 0),
                'equipment_code' => trim($_POST['equipment_code'] ?? ''),
                'equipment_name' => trim($_POST['equipment_name'] ?? ''),
                'brand' => trim($_POST['brand'] ?? ''),
                'model' => trim($_POST['model'] ?? ''),
                'quantity' => (int)($_POST['quantity'] ?? 1),
                'purchase_date' => $_POST['purchase_date'] ?? '',
                'status' => $_POST['status'] ?? 'available',
                'description' => trim($_POST['description'] ?? '')
            ];

            if ($data['type_id'] < 1 ||
                $data['equipment_code'] === '' ||
                $data['equipment_name'] === '' ||
                $data['quantity'] < 0) {
                $error = 'Vui lòng nhập đầy đủ thông tin hợp lệ.';
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
                    $error = 'Không lưu được thiết bị. Kiểm tra mã thiết bị và dữ liệu liên quan.';
                }
            }

            $equipment = $data;
        }

        require __DIR__ . '/../views/equipment_form.php';
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            try {
                $this->model->delete($id);
            } catch (PDOException $e) {
                // Không xóa nếu thiết bị đã có lịch sử liên quan.
            }
        }

        header('Location: index.php?action=equipment');
        exit;
    }
}
