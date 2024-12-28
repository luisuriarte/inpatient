<?php
error_reporting(E_ALL);  // Muestra todos los errores
ini_set('display_errors', 1);  // Asegúrate de que los errores se muestren

header('Content-Type: application/json');  // Responde en formato JSON

require_once("../../../interface/globals.php");

// Leer y decodificar el JSON
$inputData = json_decode(file_get_contents('php://input'), true);

// Comprobar si la decodificación fue exitosa
if ($inputData === null) {
    echo json_encode(['success' => false, 'error' => 'Error al decodificar JSON']);
    exit;
}

$supplyId = isset($inputData['supply_id']) ? $inputData['supply_id'] : null;
$field = isset($inputData['field']) ? $inputData['field'] : null;

if (empty($supplyId) || empty($field)) {
    echo json_encode(['success' => false, 'error' => 'Faltan supply_id o field']);
    exit;
}

$validFields = ['alarm1_active', 'alarm2_active'];
if (!in_array($field, $validFields)) {
    echo json_encode(['success' => false, 'error' => 'Campo no válido']);
    exit;
}

// Aquí, se establece el valor 0 para desactivar la alarma
$sql = "UPDATE prescriptions_supply SET $field = 0 WHERE supply_id = ?";
$res = sqlStatement($sql, array($supplyId));

if ($res) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al ejecutar la consulta SQL']);
}
?>