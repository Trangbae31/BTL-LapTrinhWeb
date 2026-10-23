<?php

require_once __DIR__ . '/../../config/database.php';

class RoomModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getAll(): array
    {
        $sql = "SELECT r.*, b.building_name
                FROM rooms r
                JOIN buildings b ON r.building_id = b.building_id
                ORDER BY r.room_id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM rooms WHERE room_id = ?"
        );
        $stmt->execute([$id]);

        $room = $stmt->fetch();

        return $room ?: null;
    }

    public function getBuildings(): array
    {
        return $this->db
            ->query("SELECT * FROM buildings ORDER BY building_name")
            ->fetchAll();
    }

    public function create(array $data): bool
    {
        $sql = "INSERT INTO rooms
                (building_id, room_code, room_name, capacity,
                 floor_number, room_type, status, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['building_id'],
            $data['room_code'],
            $data['room_name'],
            $data['capacity'],
            $data['floor_number'] ?: null,
            $data['room_type'],
            $data['status'],
            $data['description']
        ]);
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE rooms SET
                    building_id = ?,
                    room_code = ?,
                    room_name = ?,
                    capacity = ?,
                    floor_number = ?,
                    room_type = ?,
                    status = ?,
                    description = ?
                WHERE room_id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['building_id'],
            $data['room_code'],
            $data['room_name'],
            $data['capacity'],
            $data['floor_number'] ?: null,
            $data['room_type'],
            $data['status'],
            $data['description'],
            $id
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM rooms WHERE room_id = ?"
        );

        return $stmt->execute([$id]);
    }
}