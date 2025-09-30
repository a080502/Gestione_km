CREATE TABLE IF NOT EXISTS anomalie_flaggate (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_rifornimento INT NOT NULL,
    username VARCHAR(64) NOT NULL,
    targa_mezzo VARCHAR(32) NOT NULL,
    data_flag DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(255) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    INDEX (id_rifornimento),
    INDEX (targa_mezzo),
    INDEX (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
