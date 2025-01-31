<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener el ID del centro enviado desde la petición AJAX
$centro_id = isset($_POST['centro_id']) ? intval($_POST['centro_id']) : 0;

// Verificar que el centro sea válido
if ($centro_id <= 0) {
    echo json_encode([]);
    exit;
}

// Consulta para obtener las unidades activas del centro
$query = "SELECT * FROM units WHERE facility_id = ? AND active = 1 AND operation <> 'Delete'";
$result = sqlStatement($query, [$centro_id]);

$units = [];

// Obtener los resultados y almacenarlos en un array
while ($row = sqlFetchArray($result)) {
    // Obtener la descripción del piso
    $floor_query = "SELECT title FROM list_options WHERE list_id = 'unit_floor' AND option_id = ?";
    $floor_result = sqlQuery($floor_query, [$row['unit_floor_id']]);
    $floor_title = $floor_result ? $floor_result['title'] : '';

    $units[] = [
        'id' => $row['id'],
        'unit_name' => $row['unit_name'],
        'unit_floor_id' => $row['unit_floor_id'],
        'unit_floor' => $floor_title, // Agregamos la descripción del piso
        'number_of_rooms' => $row['number_of_rooms'],
        'obs' => $row['obs']
    ];
}

// Enviar los datos en formato JSON
echo json_encode($units);
?>

