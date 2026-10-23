CREATE TABLE IF NOT EXISTS buildings (
    building_id INT AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(100) NOT NULL,
    building_code VARCHAR(20) NOT NULL UNIQUE,
    address VARCHAR(255) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    building_id INT NOT NULL,
    room_code VARCHAR(30) NOT NULL UNIQUE,
    room_name VARCHAR(150) NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    floor_number INT NULL,
    room_type VARCHAR(50) NOT NULL DEFAULT 'Thuc hanh',
    status ENUM('available', 'maintenance', 'inactive')
        NOT NULL DEFAULT 'available',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rooms_buildings
        FOREIGN KEY (building_id)
        REFERENCES buildings(building_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_rooms_capacity CHECK (capacity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NULL,
    type_id INT NOT NULL,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    equipment_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) NULL,
    model VARCHAR(100) NULL,
    quantity INT NOT NULL DEFAULT 1,
    purchase_date DATE NULL,
    status ENUM('available', 'in_use', 'broken', 'maintenance', 'disposed')
        NOT NULL DEFAULT 'available',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_rooms
        FOREIGN KEY (room_id)
        REFERENCES rooms(room_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_equipment_types
        FOREIGN KEY (type_id)
        REFERENCES equipment_types(type_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_equipment_quantity CHECK (quantity >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS equipment_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    action_type ENUM('created', 'updated', 'moved', 'maintenance', 'disposed')
        NOT NULL,
    old_room_id INT NULL,
    new_room_id INT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_history_equipment
        FOREIGN KEY (equipment_id)
        REFERENCES equipment(equipment_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_history_old_room
        FOREIGN KEY (old_room_id)
        REFERENCES rooms(room_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_history_new_room
        FOREIGN KEY (new_room_id)
        REFERENCES rooms(room_id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Du lieu mau cho module cua Thao
INSERT IGNORE INTO buildings
    (building_name, building_code, address, description)
VALUES
    ('Toa nha A', 'A', NULL, 'Toa nha phong thuc hanh');

INSERT IGNORE INTO equipment_types (type_name, description)
VALUES
    ('May tinh', 'May tinh de ban va may tinh xach tay'),
    ('Thiet bi mang', 'Router, switch va thiet bi mang'),
    ('Thiet bi trinh chieu', 'May chieu va man hinh'),
    ('Thiet bi khac', 'Cac thiet bi phuc vu thuc hanh');
