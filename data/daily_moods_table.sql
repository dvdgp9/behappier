-- Tabla para registro diario de estado anímico
-- Ejecutar después de tener la BD principal configurada

CREATE TABLE daily_moods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  date DATE NOT NULL,
  mood TINYINT NOT NULL CHECK (mood BETWEEN 1 AND 5),
  note TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Índices
  UNIQUE KEY unique_user_date (user_id, date),
  KEY idx_user_date (user_id, date DESC),
  
  -- Clave foránea
  CONSTRAINT fk_daily_moods_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
