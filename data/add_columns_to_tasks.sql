-- Añadir columnas category y steps a tabla tasks existente
-- Ejecutar ANTES de la importación de tareas

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Añadir columna category
ALTER TABLE tasks ADD COLUMN category VARCHAR(80) NULL AFTER guidance;

-- Añadir columna steps para JSON de pasos
ALTER TABLE tasks ADD COLUMN steps TEXT NULL AFTER category;

-- Añadir índice en category para consultas eficientes
ALTER TABLE tasks ADD KEY idx_tasks_category (category);
