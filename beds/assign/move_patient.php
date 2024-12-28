<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos enviados desde la petición AJAX
$current_bed_id = isset($_POST['current_bed_id']) ? intval($_POST['current_bed_id']) : 0;
$new_bed_id = isset($_POST['new_bed_id']) ? intval($_POST['new_bed_id']) : 0;
$patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
$room_id_from = isset($_POST['room_id_from']) ? intval($_POST['room_id_from']) : 0;
$room_id_to = isset($_POST['room_id_to']) ? intval($_POST['room_id_to']) : 0;
$unit_id_from = isset($_POST['unit_id_from']) ? intval($_POST['unit_id_from']) : 0;
$unit_id_to = isset($_POST['unit_id_to']) ? intval($_POST['unit_id_to']) : 0;
$centro_id_from = isset($_POST['centro_id_from']) ? intval($_POST['centro_id_from']) : 0;
$centro_id_to = isset($_POST['centro_id_to']) ? intval($_POST['centro_id_to']) : 0;
$move_date = isset($_POST['move_date']) ? $_POST['move_date'] : date('Y-m-d H:i:s');
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$user_modif = $_SESSION['authUser']; // Usuario autenticado
$uuid = generateUUID(); // Generar UUID para el movimiento

// Verificar que los datos requeridos sean válidos
if ($current_bed_id <= 0 || $new_bed_id <= 0 || $patient_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos para el movimiento.']);
    exit;
}

// Registrar el movimiento en la tabla beds_patients_tracker
$trackerQuery = "INSERT INTO beds_patients_tracker (uuid, patient_id, bed_id_from, room_id_from, unit_id_from, facility_id_from, 
                   bed_id_to, room_id_to, unit_id_to, centro_id_to, move_date, reason, status, notes, user_modif, datetime_modif) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)";
$trackerResult = sqlStatement($trackerQuery, [
    $uuid, $patient_id, $current_bed_id, $room_id_from, $unit_id_from, $centro_id_from,
    $new_bed_id, $room_id_to, $unit_id_to, $centro_id_to, $move_date, $reason, $reason, $user_modif, $move_date
]);

// Actualizar la asignación en la tabla beds_patients
$updateQuery = "UPDATE beds_patients 
                SET bed_id = ?, room_id = ?, unit_id = ?, centro_id = ?, assigned_date = ?, notes = ?, user_modif = ?, datetime_modif = ? 
                WHERE patient_id = ? AND change_date IS NULL";
$updateResult = sqlStatement($updateQuery, [
    $new_bed_id, $room_id_to, $unit_id_to, $centro_id_to, $move_date, $reason, $user_modif, $move_date, $patient_id
]);

// Verificar si el movimiento y la actualización fueron exitosos
if ($trackerResult && $updateResult) {
    echo json_encode(['status' => 'success', 'message' => 'Movimiento registrado con éxito.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Error al registrar el movimiento.']);
}
?>
