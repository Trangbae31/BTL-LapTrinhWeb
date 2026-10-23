<?php

require_once __DIR__ . '/../../config/database.php';

class EquipmentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getAll(): array
    {
        $sql = "SELECT e.*,
                       r.room_name,
                       r.room_code,
                       t.type_name
                FROM equipment e
                LEFT JOIN rooms r ON e.room_id = r.room_id
                JOIN equipment_types t ON e.type_id = t.type_id
                ORDER BY e.equipment_id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM equipment WHERE equipment_id = ?"
        );
        $stmt->execute([$id]);

        $equipment = $stmt->fetch();

        return $equipment ?: null;
    }

    public function getRooms(): array
    {
        return $this->db
            ->query("SELECT room_id, room_code, room_name
                     FROM rooms ORDER BY room_code")
            ->fetchAll();
    }

    public function getTypes(): array
    {
        return $this->db
            ->query("SELECT * FROM equipment_types ORDER BY type_name")
            ->fetchAll();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO equipment
                (room_id, type_id, equipment_code, equipment_name,
                 brand, model, quantity, purchase_date, status, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['room_id'] ?: null,
            $data['type_id'],
            $data['equipment_code'],
            $data['equipment_name'],
            $data['brand'],
            $data['model'],
            $data['quantity'],
            $data['purchase_date'] ?: null,
            $data['status'],
            $data['description']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE equipment SET
                    room_id = ?,
                    type_id = ?,
                    equipment_code = ?,
                    equipment_name = ?,
                    brand = ?,
                    model = ?,
                    quantity = ?,
                    purchase_date = ?,
                    status = ?,
                    description = ?
                WHERE equipment_id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['room_id'] ?: null,
            $data['type_id'],
            $data['equipment_code'],
            $data['equipment_name'],
            $data['brand'],
            $data['model'],
            $data['quantity'],
            $data['purchase_date'] ?: null,
            $data['status'],
            $data['description'],
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM equipment WHERE equipment_id = ?"
        );

        return $stmt->execute([$id]);
    }
}