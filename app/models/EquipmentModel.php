<?php

require_once __DIR__ . '/../../config/database.php';

class EquipmentModel
{
    private PDO $db;

    private const STATUSES = [
        'available',
        'maintenance',
        'broken',
        'retired'
    ];

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getAll(): array
    {
        $sql = "
            SELECT e.*, r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t
                ON t.equipment_type_id = e.equipment_type_id
            ORDER BY e.equipment_id DESC
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function search(string $keyword): array
    {
        $like = '%' . trim($keyword) . '%';

        $stmt = $this->db->prepare("
            SELECT e.*, r.room_code, r.room_name, t.type_name
            FROM equipment e
            LEFT JOIN rooms r ON r.room_id = e.room_id
            LEFT JOIN equipment_types t
                ON t.equipment_type_id = e.equipment_type_id
            WHERE e.equipment_code LIKE :code
               OR e.equipment_name LIKE :name
               OR e.brand LIKE :brand
               OR r.room_code LIKE :room_code
               OR r.room_name LIKE :room_name
               OR t.type_name LIKE :type_name
            ORDER BY e.equipment_id DESC
        ");

        $stmt->execute([
            'code' => $like,
            'name' => $like,
            'brand' => $like,
            'room_code' => $like,
            'room_name' => $like,
            'type_name' => $like
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM equipment
            WHERE equipment_id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRooms(): array
    {
        return $this->db->query("
            SELECT room_id, room_code, room_name
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_code
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTypes(): array
    {
        return $this->db->query("
            SELECT equipment_type_id, type_name
            FROM equipment_types
            ORDER BY type_name
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    private function lockActiveRoom(int $roomId): bool
    {
        $stmt = $this->db->prepare("
            SELECT room_id
            FROM rooms
            WHERE room_id = :room_id
              AND status = 'active'
            FOR UPDATE
        ");
        $stmt->execute(['room_id' => $roomId]);

        return $stmt->fetchColumn() !== false;
    }

    private function typeExists(int $typeId): bool
    {
        $stmt = $this->db->prepare("
            SELECT equipment_type_id
            FROM equipment_types
            WHERE equipment_type_id = :type_id
        ");
        $stmt->execute(['type_id' => $typeId]);

        return $stmt->fetchColumn() !== false;
    }

    private function hasOpenReport(int $equipmentId): bool
    {
        $stmt = $this->db->prepare("
            SELECT report_id
            FROM maintenance_reports
            WHERE equipment_id = :equipment_id
              AND status IN ('reported', 'processing')
            LIMIT 1
        ");
        $stmt->execute(['equipment_id' => $equipmentId]);

        return $stmt->fetchColumn() !== false;
    }

    private function recordStatusHistory(
        int $equipmentId,
        int $userId,
        ?string $oldStatus,
        string $newStatus,
        string $note
    ): void {
        $stmt = $this->db->prepare("
            INSERT INTO equipment_history (
                equipment_id,
                user_id,
                old_status,
                new_status,
                change_note
            )
            VALUES (
                :equipment_id,
                :user_id,
                :old_status,
                :new_status,
                :change_note
            )
        ");

        $stmt->execute([
            'equipment_id' => $equipmentId,
            'user_id' => $userId > 0 ? $userId : null,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'change_note' => $note
        ]);
    }

    private function validateData(array $data): bool
    {
        return (int)($data['room_id'] ?? 0) > 0
            && (int)($data['equipment_type_id'] ?? 0) > 0
            && trim((string)($data['equipment_code'] ?? '')) !== ''
            && mb_strlen(trim((string)$data['equipment_code']), 'UTF-8') <= 50
            && trim((string)($data['equipment_name'] ?? '')) !== ''
            && mb_strlen(trim((string)$data['equipment_name']), 'UTF-8') <= 150
            && mb_strlen(trim((string)($data['brand'] ?? '')), 'UTF-8') <= 100
            && in_array((string)($data['status'] ?? ''), self::STATUSES, true);
    }

    public function create(array $data, int $userId): bool
    {
        if (!$this->validateData($data) || $userId <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            if (
                !$this->lockActiveRoom((int)$data['room_id'])
                || !$this->typeExists((int)$data['equipment_type_id'])
            ) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO equipment (
                    room_id,
                    equipment_type_id,
                    equipment_code,
                    equipment_name,
                    brand,
                    purchase_date,
                    status,
                    description
                )
                VALUES (
                    :room_id,
                    :type_id,
                    :code,
                    :name,
                    :brand,
                    :purchase_date,
                    :status,
                    :description
                )
            ");

            $stmt->execute([
                'room_id' => (int)$data['room_id'],
                'type_id' => (int)$data['equipment_type_id'],
                'code' => trim($data['equipment_code']),
                'name' => trim($data['equipment_name']),
                'brand' => trim((string)($data['brand'] ?? '')) ?: null,
                'purchase_date' => ($data['purchase_date'] ?? '') ?: null,
                'status' => $data['status'],
                'description' => trim((string)($data['description'] ?? '')) ?: null
            ]);

            $equipmentId = (int)$this->db->lastInsertId();

            $this->recordStatusHistory(
                $equipmentId,
                $userId,
                null,
                $data['status'],
                'Tạo thiết bị mới.'
            );

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment create error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $data, int $userId): bool
    {
        if (
            $id <= 0
            || $userId <= 0
            || !$this->validateData($data)
        ) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $lock = $this->db->prepare("
                SELECT status
                FROM equipment
                WHERE equipment_id = :id
                FOR UPDATE
            ");
            $lock->execute(['id' => $id]);
            $current = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$current) {
                $this->db->rollBack();
                return false;
            }

            if (
                !$this->lockActiveRoom((int)$data['room_id'])
                || !$this->typeExists((int)$data['equipment_type_id'])
            ) {
                $this->db->rollBack();
                return false;
            }

            // Không được đánh dấu sẵn sàng hoặc thanh lý khi còn phiếu bảo trì mở.
            if (
                in_array($data['status'], ['available', 'retired'], true)
                && $this->hasOpenReport($id)
            ) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE equipment
                SET room_id = :room_id,
                    equipment_type_id = :type_id,
                    equipment_code = :code,
                    equipment_name = :name,
                    brand = :brand,
                    purchase_date = :purchase_date,
                    status = :status,
                    description = :description
                WHERE equipment_id = :id
            ");

            $stmt->execute([
                'room_id' => (int)$data['room_id'],
                'type_id' => (int)$data['equipment_type_id'],
                'code' => trim($data['equipment_code']),
                'name' => trim($data['equipment_name']),
                'brand' => trim((string)($data['brand'] ?? '')) ?: null,
                'purchase_date' => ($data['purchase_date'] ?? '') ?: null,
                'status' => $data['status'],
                'description' => trim((string)($data['description'] ?? '')) ?: null,
                'id' => $id
            ]);

            if ($current['status'] !== $data['status']) {
                $this->recordStatusHistory(
                    $id,
                    $userId,
                    $current['status'],
                    $data['status'],
                    'Cập nhật trạng thái thiết bị.'
                );
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Không xóa cứng vì thiết bị có thể đang được lịch sử/phiếu bảo trì tham chiếu.
     * Chuyển trạng thái sang retired để giữ toàn bộ dữ liệu truy vết.
     */
    public function delete(int $id, int $userId): bool
    {
        if ($id <= 0 || $userId <= 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                SELECT status
                FROM equipment
                WHERE equipment_id = :id
                FOR UPDATE
            ");
            $stmt->execute(['id' => $id]);
            $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$equipment || $equipment['status'] === 'retired') {
                $this->db->rollBack();
                return false;
            }

            if ($this->hasOpenReport($id)) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare("
                UPDATE equipment
                SET status = 'retired'
                WHERE equipment_id = :id
            ");
            $update->execute(['id' => $id]);

            $this->recordStatusHistory(
                $id,
                $userId,
                $equipment['status'],
                'retired',
                'Ngừng sử dụng thiết bị từ chức năng quản lý thiết bị.'
            );

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Equipment retire error: ' . $e->getMessage());
            throw $e;
        }
    }
}
