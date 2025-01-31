<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener el ID de la unidad enviado desde la petición AJAX
$unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;

// Verificar que la unidad sea válida
if ($unit_id <= 0) {
    echo json_encode([]);
    exit;
}

// Consulta para obtener los cuartos activos de la unidad
$query = "SELECT * FROM rooms WHERE unit_id = ? AND active = 1 AND operation <> 'Delete'";
$result = sqlStatement($query, [$unit_id]);

$rooms = [];

// Obtener los resultados y almacenarlos en un array
while ($row = sqlFetchArray($result)) {
    $rooms[] = [
        'id' => $row['id'],
        'room_name' => $row['room_name'],
        'room_sector' => $row['room_sector'],
        'number_of_beds' => $row['number_of_beds'],
        'obs' => $row['obs']
    ];
}

// Enviar los datos en formato JSON
echo json_encode($rooms);
?>
