<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Consulta para obtener las unidades activas del centro
$query = "SELECT * FROM facility WHERE inactive = 0 AND facility_taxonomy = '30' AND ";
$result = sqlStatement($query, [$centro_id]);

$units = [];

// Obtener los resultados y almacenarlos en un array
while ($row = sqlFetchArray($result)) {
    $units[] = [
        'id' => $row['id'],
        'unit_name' => $row['unit_name'],
        'number_of_units' => $row['number_of_units'],
        'obs' => $row['obs']
    ];
}

// Enviar los datos en formato JSON
echo json_encode($units);
?>