<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================
$bedsPatientsId = intval($_POST['beds_patients_id']);
$patientId = intval($_POST['patient_id']);
$bedId = intval($_POST['bed_id']);
$bedName = htmlspecialchars($_POST['bed_name']);
$responsibleUserId = empty($_POST['responsible_user_id_release']) ? null : intval($_POST['responsible_user_id_release']);
$releaseNotes = text($_POST['releaseNotes']);

// Ubicación
$roomId = intval($_POST['room_id']);
$roomName = htmlspecialchars($_POST['room_name']);
$unitId = intval($_POST['unit_id']);
$unitName = htmlspecialchars($_POST['unit_name']);
$facilityId = intval($_POST['facility_id']);
$facilityName = htmlspecialchars($_POST['facility_name']);

// Usuario y timestamps
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$datetimeModif = date('Y-m-d H:i:s');

// Parámetros de acción
$bedAction = htmlspecialchars($_POST['bed_action']);
$backgroundPatientCard = htmlspecialchars($_POST['background_card']);

// ==========================================
// OBTENER DATOS DE LA RESERVA
// ==========================================
$reservationQuery = "SELECT bp.current_bed_id, b.bed_name, b.bed_status, b.bed_type,
                            bp.current_room_id, bp.current_unit_id, bp.facility_id
                     FROM beds_patients bp
                     LEFT JOIN beds b ON bp.current_bed_id = b.id
                     WHERE bp.id = ? AND bp.status = 'preadmitted'";
$reservationData = sqlQuery($reservationQuery, [$bedsPatientsId]);

if (!$reservationData) {
    die("Error: Reservation not found or patient is not in preadmitted status.");
}

// ==========================================
// INICIAR TRANSACCIÓN
// ==========================================
$database->StartTrans();

try {
    // ==========================================
    // 1. CANCELAR LA PRE-ADMISIÓN
    // ==========================================
    $updateQuery = "UPDATE beds_patients 
                   SET status = 'discharged',
                       discharge_date = ?,
                       discharge_user_id = ?,
                       discharge_disposition = 'preadmission_cancelled',
                       current_bed_id = NULL,
                       current_room_id = NULL,
                       current_unit_id = NULL,
                       user_modif = ?,
                       datetime_modif = ?
                   WHERE id = ?";
    
    sqlStatement($updateQuery, [
        $datetimeModif, $userId, $userFullName, $datetimeModif, $bedsPatientsId
    ]);
    
    // ==========================================
    // 2. INSERTAR MOVIMIENTO EN BEDS_PATIENTS_TRACKER
    // ==========================================
    $trackerQuery = "INSERT INTO beds_patients_tracker (
        uuid, beds_patients_id, patient_id, movement_type, movement_date,
        responsible_user_id,
        bed_id_from, room_id_from, unit_id_from, facility_id_from,
        bed_id_to, room_id_to, unit_id_to, facility_id_to,
        bed_condition_from, reason, notes, user_modif, datetime_modif
    ) VALUES (
        UUID(), ?, ?, 'bed_release', ?, ?,
        ?, ?, ?, ?,
        NULL, NULL, NULL, NULL,
        'reserved', 'Bed reservation cancelled', ?, ?, ?
    )";
    
    sqlStatement($trackerQuery, [
        $bedsPatientsId, $patientId, $datetimeModif, $responsibleUserId,
        $reservationData['current_bed_id'], $reservationData['current_room_id'],
        $reservationData['current_unit_id'], $reservationData['facility_id'],
        $releaseNotes, $userFullName, $datetimeModif
    ]);
    
    // ==========================================
    // 3. LIBERAR CAMA EN BEDS_STATUS_LOG
    // ==========================================
    insertBedStatusLog($reservationData['current_bed_id'], 'vacant', $userId, null, 
                      'Bed reservation cancelled - bed released');
    
    // ==========================================
    // CONFIRMAR TRANSACCIÓN
    // ==========================================
    $database->CompleteTrans();
    
    // ==========================================
    // REDIRECCIÓN
    // ==========================================
    header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
            "&room_name=" . urlencode($roomName) . 
            "&unit_id=" . urlencode($unitId) . 
            "&unit_name=" . urlencode($unitName) . 
            "&facility_id=" . urlencode($facilityId) . 
            "&facility_name=" . urlencode($facilityName) . 
            "&bed_action=" . urlencode($bedAction) . 
            "&background_card=" . urlencode($backgroundPatientCard) .
            "&status=success&message=" . urlencode("Reservation successfully cancelled"));
    exit();
    
} catch (Exception $e) {
    $database->FailTrans();
    error_log("Error en save_bed_release: " . $e->getMessage());
    
    header("Location: load_beds.php?room_id=" . urlencode($roomId ?? '') . 
            "&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

?>