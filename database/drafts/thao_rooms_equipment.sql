USE ql_phong_thuc_hanh;

-- 1. BUILDINGS: TOA NHA
CREATE TABLE IF NOT EXISTS buildings (
    building_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(100) NOT NULL,
    address VARCHAR(255),
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ROOMS: PHONG THUC HANH
CREATE TABLE IF NOT EXISTS rooms (
    room_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id INT UNSIGNED,
    room_code VARCHAR(30) NOT NULL UNIQUE,
    room_name VARCHAR(100) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    location VARCHAR(255),
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rooms_building
        FOREIGN KEY (building_id)
        REFERENCES buildings(building_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. EQUIPMENT_TYPES: LOAI THIET BI
CREATE TABLE IF NOT EXISTS equipment_types (
    equipment_type_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. EQUIPMENT: THIET BI
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    equipment_type_id INT UNSIGNED NOT NULL,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    equipment_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    purchase_date DATE,
    status ENUM(
        'available',
        'maintenance',
        'broken',
        'retired'
    ) NOT NULL DEFAULT 'available',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_room
        FOREIGN KEY (room_id)
        REFERENCES rooms(room_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_type
        FOREIGN KEY (equipment_type_id)
        REFERENCES equipment_types(equipment_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_equipment_room (room_id),
    INDEX idx_equipment_type (equipment_type_id),
    INDEX idx_equipment_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. EQUIPMENT_HISTORY: LICH SU THAY DOI THIET BI
CREATE TABLE IF NOT EXISTS equipment_history (
    history_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    old_status ENUM(
        'available', 'maintenance', 'broken', 'retired'
    ),
    new_status ENUM(
        'available', 'maintenance', 'broken', 'retired'
    ) NOT NULL,
    change_note TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_history_equipment
        FOREIGN KEY (equipment_id)
        REFERENCES equipment(equipment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipment_history_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_equipment_history_equipment (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DU LIEU MAU CHO MODULE CUA THAO
-- =====================================================

INSERT IGNORE INTO buildings
    (building_name, address, description, status)
VALUES
    ('Toa nha A', NULL, 'Toa nha co cac phong thuc hanh', 'active');

INSERT IGNORE INTO equipment_types (type_name, description)
VALUES
    ('May tinh', 'May tinh de ban va may tinh xach tay'),
    ('Thiet bi mang', 'Router, switch va thiet bi mang'),
    ('Thiet bi trinh chieu', 'May chieu va man hinh'),
    ('Thiet bi khac', 'Cac thiet bi phuc vu thuc hanh');

