<?php
/**
 * Importador de ejercicios de 1 minuto desde CSV (separado por ;)
 *
 * Lee data/Ejercicios 1 minuto nuevo.csv y genera data/tasks_1min_import.sql
 * con: DELETE de las tareas de 1' + INSERTs de los nuevos ejercicios.
 *
 * CSV esperado (cabeceras):
 *   - Nombre ejercicio
 *   - Pasos            (JSON array en texto o lista; intentaremos normalizar)
 *   - Duración         (ignoramos y forzamos duration=1)
 *
 * Notas:
 * - Convierte a UTF-8 por si el CSV viene en Windows-1252/ISO-8859-1.
 * - guidance se genera a partir de los pasos (primeros 1-2 items unidos),
 *   truncado a 255 chars. Si no hay pasos, se usa el título.
 */

declare(strict_types=1);

$input = __DIR__ . '/../data/Ejercicios 1 minuto nuevo.csv';
$output = __DIR__ . '/../data/tasks_1min_import.sql';

if (!file_exists($input)) {
    fwrite(STDERR, "No se encuentra el CSV: $input\n");
    exit(1);
}

function mb_to_utf8(string $s): string {
    // Detecta/convierte a UTF-8 de forma defensiva
    $enc = mb_detect_encoding($s, ['UTF-8','ISO-8859-1','Windows-1252'], true);
    if ($enc && $enc !== 'UTF-8') {
        $s = mb_convert_encoding($s, 'UTF-8', $enc);
    }
    // Normaliza saltos
    return str_replace(["\r\n","\r"], "\n", $s);
}

function escape_sql(string $str): string {
    // Escapado para valores literales en MySQL
    $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
    return "'" . str_replace(
        ["'", "\\", "\0", "\n", "\r", "\x1a"],
        ["''", "\\\\", "\\0", "\\n", "\\r", "\\Z"],
        $str
    ) . "'";
}

function truncate(string $s, int $len): string {
    return (mb_strlen($s) > $len) ? mb_substr($s, 0, $len) : $s;
}

function parse_steps_field(?string $raw): ?array {
    if ($raw === null) return null;
    $raw = trim($raw);
    if ($raw === '') return null;
    // Normaliza comillas dobles duplicadas en CSV
    // "[""texto"", ""otro""]" -> "[\"texto\", \"otro\"]"
    $candidate = $raw;
    // Si huele a JSON array [ ... ]
    if ($candidate[0] === '[' && substr($candidate, -1) === ']') {
        // Intenta decodificar tal cual
        $json = json_decode($candidate, true);
        if (is_array($json)) return $json;
        // Reemplaza comillas dobles duplicadas por simples comillas dobles
        $candidate2 = preg_replace('/""/', '"', $candidate);
        $json2 = json_decode($candidate2, true);
        if (is_array($json2)) return $json2;
    }
    // Como fallback, separa por punto y coma dentro del campo o por punto.
    // Pero es preferible devolverlo como una única instrucción si no está claro.
    return [$raw];
}

$fh = fopen($input, 'r');
if ($fh === false) {
    fwrite(STDERR, "No se pudo abrir el CSV para lectura.\n");
    exit(1);
}

// Forzar delimitador ; y enclosure "
$delimiter = ';';
$enclosure = '"';
$escape = '\\';

$headers = null;
$rows = [];
while (($row = fgetcsv($fh, 0, $delimiter, $enclosure, $escape)) !== false) {
    // Convierte cada celda a UTF-8
    foreach ($row as &$cell) { $cell = mb_to_utf8((string)$cell); }
    unset($cell);

    if ($headers === null) {
        $headers = $row;
        continue;
    }
    // Evitar filas vacías
    $nonEmpty = array_filter($row, fn($v) => trim((string)$v) !== '');
    if (!$nonEmpty) continue;

    // Mapear columnas principales por índice
    // 0: Nombre ejercicio, 1: Pasos, 2: Duración, resto ignorado
    $title = truncate(trim((string)($row[0] ?? '')), 120);
    $stepsRaw = isset($row[1]) ? trim((string)$row[1]) : null;
    $steps = parse_steps_field($stepsRaw);

    if ($title === '') continue; // saltar inválidos

    // guidance: une primeros 1-2 pasos en una sola línea
    $guidance = $title; // fallback
    if (is_array($steps) && count($steps) > 0) {
        $gparts = array_slice($steps, 0, 2);
        $gparts = array_map(fn($s) => trim((string)$s), $gparts);
        $gparts = array_filter($gparts, fn($s) => $s !== '');
        if ($gparts) {
            $guidance = implode(' ', $gparts);
        }
    }
    $guidance = truncate($guidance, 255);

    $rows[] = [
        'duration' => 1,
        'title' => $title,
        'guidance' => $guidance,
        'category' => null,
        'steps' => $steps,
        'active' => 1,
    ];
}
fclose($fh);

if (!$rows) {
    fwrite(STDERR, "No se encontraron filas válidas en el CSV.\n");
    exit(1);
}

// Construir SQL
$sql = "-- Importación de tareas de 1 minuto desde CSV\n";
$sql .= "-- Generado automáticamente el " . date('Y-m-d H:i:s') . "\n\n";
$sql .= "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
$sql .= "SET CHARACTER SET utf8mb4;\n";
$sql .= "SET time_zone = '+00:00';\n\n";
$sql .= "START TRANSACTION;\n\n";
$sql .= "DELETE FROM tasks WHERE duration = 1;\n\n";

$sql .= "INSERT INTO tasks (duration, title, guidance, category, steps, active) VALUES\n";

$values = [];
foreach ($rows as $r) {
    $duration = (int)$r['duration'];
    $title = escape_sql($r['title']);
    $guidance = escape_sql($r['guidance']);
    $category = 'NULL';
    if (!empty($r['category'])) { $category = escape_sql((string)$r['category']); }

    $stepsJson = 'NULL';
    if (is_array($r['steps']) && !empty($r['steps'])) {
        // Limpiar pasos: eliminar vacíos y truncar cada item a algo razonable
        $clean = [];
        foreach ($r['steps'] as $s) {
            $s = trim((string)$s);
            if ($s === '') continue;
            $clean[] = truncate($s, 240);
        }
        if ($clean) {
            $stepsJson = escape_sql(json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    $values[] = sprintf("  (%d, %s, %s, %s, %s, 1)", $duration, $title, $guidance, $category, $stepsJson);
}

$sql .= implode(",\n", $values) . ";\n\n";
$sql .= "COMMIT;\n";

if (file_put_contents($output, $sql) === false) {
    fwrite(STDERR, "Error escribiendo archivo: $output\n");
    exit(1);
}

echo "✓ Generado: $output\n";
printf("✓ Total de nuevas tareas 1': %d\n", count($rows));

