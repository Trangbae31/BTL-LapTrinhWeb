<?php

require_once __DIR__ . '/../models/EquipmentModel.php';
require_once __DIR__ . '/../core/Authorization.php';

class EquipmentApiController
{
    private EquipmentModel $model;

    public function __construct()
    {
        $this->model = new EquipmentModel();
    }

    /**
     * API lấy danh sách và tìm kiếm thiết bị.
     */
    public function index(): void
    {
        Authorization::requireRole([
            'admin',
            'manager',
            'technician'
        ]);

        header('Content-Type: application/json; charset=utf-8');

        try {
            $keyword = trim($_GET['keyword'] ?? '');

            $equipmentList = $keyword !== ''
                ? $this->model->search($keyword)
                : $this->model->getAll();

            http_response_code(200);

            echo json_encode([
                'success' => true,
                'message' => 'Lấy danh sách thiết bị thành công.',
                'data' => $equipmentList
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            error_log('Equipment API error: ' . $e->getMessage());

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'Không thể lấy danh sách thiết bị.'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
