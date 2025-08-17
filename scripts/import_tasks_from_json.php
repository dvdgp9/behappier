<?php
/**
 * Script para importar tareas desde 3 archivos JSON a SQL
 * Genera data/tasks_import.sql con TRUNCATE + INSERT
 */

declare(strict_types=1);

function escapeString(string $str): string {
    return "'" . addslashes($str) . "'";
}

function truncateString(string $str, int $maxLength): string {
    return mb_strlen($str) > $maxLength ? mb_substr($str, 0, $maxLength) : $str;
}

function processJsonFile(string $filePath, int $duration): array {
    if (!file_exists($filePath)) {
        echo "Archivo no encontrado: $filePath\n";
        return [];
    }
    
    $content = file_get_contents($filePath);
    if ($content === false) {
        echo "Error leyendo archivo: $filePath\n";
        return [];
    }
    
    $data = json_decode($content, true);
    if (!is_array($data)) {
        echo "JSON inválido en: $filePath\n";
        return [];
    }
    
    $tasks = [];
    foreach ($data as $item) {
        if (!is_array($item)) continue;
        
        $title = truncateString(trim($item['title'] ?? ''), 120);
        $guidance = truncateString(trim($item['description'] ?? $item['guidance'] ?? ''), 255);
        $category = isset($item['category']) ? truncateString(trim($item['category']), 80) : null;
        $steps = null;
        
        // Si hay steps, convertir a JSON
        if (isset($item['steps']) && is_array($item['steps']) && !empty($item['steps'])) {
            $steps = json_encode($item['steps'], JSON_UNESCAPED_UNICODE);
        }
        
        if ($title === '' || $guidance === '') {
            echo "Saltando tarea sin título o descripción en $filePath\n";
            continue;
        }
        
        $tasks[] = [
            'duration' => $duration,
            'title' => $title,
            'guidance' => $guidance,
            'category' => $category,
            'steps' => $steps
        ];
    }
    
    return $tasks;
}

// Procesar los 3 archivos
$allTasks = [];
$allTasks = array_merge($allTasks, processJsonFile(__DIR__ . '/../data/tasks1.json', 1));
$allTasks = array_merge($allTasks, processJsonFile(__DIR__ . '/../data/tasks5.json', 5));
$allTasks = array_merge($allTasks, processJsonFile(__DIR__ . '/../data/tasks10.json', 10));

if (empty($allTasks)) {
    echo "No se encontraron tareas válidas para importar.\n";
    exit(1);
}

// Generar SQL
$sql = "-- Importación de tareas desde JSON\n";
$sql .= "-- Generado automáticamente el " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET NAMES utf8mb4;\n";
$sql .= "SET time_zone = '+00:00';\n\n";
$sql .= "-- Limpiar tabla existente\n";
$sql .= "TRUNCATE TABLE tasks;\n\n";
$sql .= "-- Insertar nuevas tareas\n";
$sql .= "INSERT INTO tasks (duration, title, guidance, category, steps, active) VALUES\n";

$values = [];
foreach ($allTasks as $task) {
    $duration = $task['duration'];
    $title = escapeString($task['title']);
    $guidance = escapeString($task['guidance']);
    $category = $task['category'] ? escapeString($task['category']) : 'NULL';
    $steps = $task['steps'] ? escapeString($task['steps']) : 'NULL';
    
    $values[] = "  ($duration, $title, $guidance, $category, $steps, 1)";
}

$sql .= implode(",\n", $values) . ";\n";

// Escribir archivo
$outputFile = __DIR__ . '/../data/tasks_import.sql';
if (file_put_contents($outputFile, $sql) === false) {
    echo "Error escribiendo archivo: $outputFile\n";
    exit(1);
}

echo "✓ Generado: $outputFile\n";
echo "✓ Total de tareas: " . count($allTasks) . "\n";
echo "  - 1 minuto: " . count(array_filter($allTasks, fn($t) => $t['duration'] === 1)) . "\n";
echo "  - 5 minutos: " . count(array_filter($allTasks, fn($t) => $t['duration'] === 5)) . "\n";
echo "  - 10 minutos: " . count(array_filter($allTasks, fn($t) => $t['duration'] === 10)) . "\n";
echo "\nEjecuta el archivo SQL en tu base de datos para importar las tareas.\n";
