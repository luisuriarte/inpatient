<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");
// Verificar si la solicitud es POST
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
//    echo '<pre>';
//    print_r($_POST);
//    echo '</pre>';
//    exit; // Agregar esto para detener la ejecución temporalmente
//}
// Obtener datos del usuario de la sesión
$bedsPatientsId = $_POST['beds_patients_id'] ?? null;
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$bedId = intval($_POST['bed_id']);
$patientId = intval($_POST['patient_id']);
$notes = text($_POST['notes']);
$datetimeModif = date('Y-m-d H:i:s');
$condition = 'Reserved';
$now = date('Y-m-d H:i:s');
$responsibleUserId = intval($_POST['responsible_user_id_reserve']);
$roomId = intval($_POST['room_id']);
$roomName = htmlspecialchars($_POST['room_name']);
$unitId = intval($_POST['unit_id']);
$unitName = htmlspecialchars($_POST['unit_name']);
$facilityId = intval($_POST['facility_id']);
$facilityName = htmlspecialchars($_POST['facility_name']);
$bedAction = htmlspecialchars($_POST['bed_action']);
$operation = 'Reserve';
$bedAction = htmlspecialchars($_POST['bed_action']);
$backgroundPatientCard = htmlspecialchars($_POST['background_card']);

// Guardar los datos en la base de datos
// bedsPatientsId puede ser null si es una cama nueva sin historial
if ($responsibleUserId) {
    // Iniciar transacción
    $database->StartTrans();

    try {
        // 1. Desactivar el registro actual (Vacant) SOLO si existe
        if ($bedsPatientsId) {
            $updateQuery = "UPDATE beds_patients 
                           SET active = 0, 
                               operation = 'Archive (Pre-Reserve)', 
                               user_modif = ?, 
                               datetime_modif = ? 
                           WHERE id = ? AND active = 1";
            sqlStatement($updateQuery, [$userFullName, $datetimeModif, $bedsPatientsId]);
        }

        // 2. Insertar nuevo registro con estado Reserved
        $insertQuery = "INSERT INTO beds_patients (
                            bed_id, room_id, unit_id, facility_id, 
                            bed_name, bed_status, bed_type, 
                            patient_id, responsible_user_id, 
                            assigned_date, change_date, 
                            `condition`, operation, notes, 
                            user_modif, datetime_modif, active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";

        $insertResult = sqlStatement($insertQuery, [
            $bedId, $roomId, $unitId, $facilityId,
            $bedName, $bedStatus, $bedType,
            $patientId, $responsibleUserId,
            $now, $now,
            $condition, $operation, $notes,
            $userFullName, $datetimeModif
        ]);

        if ($insertResult) {
            $database->CompleteTrans();
            // Redirigir con la URL corregida
            header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
                   "&room_name=" . urlencode($roomName) . 
                   "&unit_id=" . urlencode($unitId) . 
                   "&unit_name=" . urlencode($unitName) . 
                   "&facility_id=" . urlencode($facilityId) . 
                   "&facility_name=" . urlencode($facilityName) . 
                   "&bed_action=" . urlencode($bedAction) . 
                   "&background_card=" . urlencode($backgroundPatientCard) .
                   "&patient_id=" . urlencode($patientId));
            exit();
        } else {
            throw new Exception("Error al insertar reserva.");
        }
    } catch (Exception $e) {
        $database->FailTrans();
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Datos incompletos.";
}
exit();
?>
