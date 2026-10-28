<?php

require_once __DIR__ . '/../../config/database.php';

class RoomModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // Lay danh sach phong va ten toa nha
    public function getAll(): array
    {
        $sql = "SELECT r.*, b.building_name
                FROM rooms r
                LEFT JOIN buildings b
                    ON r.building_id = b.building_id
                ORDER BY r.room_id DESC";

        return $this->db->query($sql)->fetchAll();
    }

    // Lay thong tin mot phong theo ID
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM rooms WHERE room_id = ?"
        );

        $stmt->execute([$id]);
        $room = $stmt->fetch();

        return $room ?: null;
    }

    // Lay danh sach toa nha cho form
    public function getBuildings(): array
    {
        $sql = "SELECT building_id, building_name
                FROM buildings
                ORDER BY building_name";

        return $this->db->query($sql)->fetchAll();
    }

    // Them phong moi
    public function create(array $data): bool
    {
        $sql = "INSERT INTO rooms
                (building_id, room_code, room_name, capacity,
                 location, description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['building_id'],
            $data['room_code'],
            $data['room_name'],
            $data['capacity'],
            $data['location'],
            $data['description'],
            $data['status']
        ]);
    }

    // Cap nhat thong tin phong
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE rooms SET
                    building_id = ?,
                    room_code = ?,
                    room_name = ?,
                    capacity = ?,
                    location = ?,
                    description = ?,
                    status = ?
                WHERE room_id = ?";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['building_id'],
            $data['room_code'],
            $data['room_name'],
            $data['capacity'],
            $data['location'],
            $data['description'],
            $data['status'],
            $id
        ]);
    }

    // Xoa phong
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM rooms WHERE room_id = ?"
        );

        return $stmt->execute([$id]);
    }

    // Tim kiem phong theo ma, ten phong hoac vi tri
    public function search(string $keyword): array
    {
        $sql = "SELECT r.*, b.building_name
                FROM rooms r
                LEFT JOIN buildings b
                    ON r.building_id = b.building_id
                WHERE r.room_code LIKE ?
                   OR r.room_name LIKE ?
                   OR r.location LIKE ?
                   OR b.building_name LIKE ?
                ORDER BY r.room_id DESC";

        $stmt = $this->db->prepare($sql);
        $pattern = '%' . $keyword . '%';

        $stmt->execute([
            $pattern,
            $pattern,
            $pattern,
            $pattern
        ]);

        return $stmt->fetchAll();
    }
}
