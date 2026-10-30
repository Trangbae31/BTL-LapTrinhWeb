<?php

require_once __DIR__ . '/../../config/database.php';

class EquipmentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Lấy toàn bộ thiết bị.
     */
    public function getAll(): array
    {
        $sql = "
            SELECT
                e.*,
                r.room_code,
                r.room_name,
                t.type_name
            FROM equipment e
            LEFT JOIN rooms r
                ON e.room_id = r.room_id
            LEFT JOIN equipment_types t
                ON e.equipment_type_id = t.equipment_type_id
            ORDER BY e.equipment_id DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm kiếm thiết bị.
     */
    public function search(string $keyword): array
    {
        $sql = "
            SELECT
                e.*,
                r.room_code,
                r.room_name,
                t.type_name
            FROM equipment e
            LEFT JOIN rooms r
                ON e.room_id = r.room_id
            LEFT JOIN equipment_types t
                ON e.equipment_type_id = t.equipment_type_id
            WHERE e.equipment_code LIKE ?
               OR e.equipment_name LIKE ?
               OR e.brand LIKE ?
               OR r.room_code LIKE ?
               OR r.room_name LIKE ?
               OR t.type_name LIKE ?
            ORDER BY e.equipment_id DESC
        ";

        $stmt = $this->db->prepare($sql);

        $keyword = '%' . $keyword . '%';

        $stmt->execute([
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thiết bị theo ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM equipment
            WHERE equipment_id = ?
        ");

        $stmt->execute([$id]);

        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);

        return $equipment ?: null;
    }

    /**
     * Lấy danh sách phòng đang hoạt động.
     */
    public function getRooms(): array
    {
        $sql = "
            SELECT
                room_id,
                room_code,
                room_name
            FROM rooms
            WHERE status = 'active'
            ORDER BY room_code
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách loại thiết bị.
     */
    public function getTypes(): array
    {
        $sql = "
            SELECT
                equipment_type_id,
                type_name
            FROM equipment_types
            ORDER BY type_name
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm thiết bị.
     */
    public function create(array $data): bool
    {
        $sql = "
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['room_id'],
            $data['equipment_type_id'],
            $data['equipment_code'],
            $data['equipment_name'],
            $data['brand'] ?: null,
            $data['purchase_date'] ?: null,
            $data['status'],
            $data['description'] ?: null
        ]);
    }

    /**
     * Cập nhật thiết bị.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "
            UPDATE equipment
            SET
                room_id = ?,
                equipment_type_id = ?,
                equipment_code = ?,
                equipment_name = ?,
                brand = ?,
                purchase_date = ?,
                status = ?,
                description = ?
            WHERE equipment_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['room_id'],
            $data['equipment_type_id'],
            $data['equipment_code'],
            $data['equipment_name'],
            $data['brand'] ?: null,
            $data['purchase_date'] ?: null,
            $data['status'],
            $data['description'] ?: null,
            $id
        ]);
    }

    /**
     * Xóa thiết bị.
     * CSDL sẽ từ chối nếu thiết bị đang được bảng khác tham chiếu.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM equipment
            WHERE equipment_id = ?
        ");

        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}