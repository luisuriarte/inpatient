<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================
$bedsPatientsId = intval($_POST['beds_patients_id']);
$patientId = intval($_POST['patient_id']);

// Datos de ORIGEN
$fromBedId = intval($_POST['from_bed_id']);
$fromBedName = htmlspecialchars($_POST['from_bed_name']);
$fromRoomId = intval($_POST['from_room_id']);
$fromRoomName = htmlspecialchars($_POST['from_room_name'] ?? '');
$fromUnitId = intval($_POST['from_unit_id']);
$fromUnitName = htmlspecialchars($_POST['from_unit_name'] ?? '');
$fromFacilityId = intval($_POST['from_facility_id']);
$fromFacilityName = htmlspecialchars($_POST['from_facility_name'] ?? '');

// Datos de DESTINO
$toBedId = intval($_POST['to_bed_id']);
$toBedName = htmlspecialchars($_POST['to_bed_name']);
$toBedStatus = htmlspecialchars($_POST['to_bed_status']);
$toBedType = htmlspecialchars($_POST['to_bed_type']);
$toRoomId = intval($_POST['to_room_id']);
$toRoomName = htmlspecialchars($_POST['to_room_name']);
$toUnitId = intval($_POST['to_unit_id']);
$toUnitName = htmlspecialchars($_POST['to_unit_name']);
$toFacilityId = intval($_POST['to_facility_id']);
$toFacilityName = htmlspecialchars($_POST['to_facility_name']);

// Otros datos
$responsibleUserId = empty($_POST['responsible_user_id']) ? null : intval($_POST['responsible_user_id']);
$reason = text($_POST['reason']);
$notes = text($_POST['notes']);

// Usuario y timestamps
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$datetimeModif = date('Y-m-d H:i:s');
$backgroundPatientCard = htmlspecialchars($_POST['background_card'] ?? '');
$bedAction = htmlspecialchars($_POST['bed_action'] ?? 'Management');

// ==========================================
// OBTENER INFORMACIÓN DE LA CAMA ORIGEN
// ==========================================
$fromBedQuery = "SELECT bed_status, bed_type FROM beds WHERE id = ?";
$fromBedData = sqlQuery($fromBedQuery, [$fromBedId]);

if (!$fromBedData) {
    die("Error: No se pudo obtener información de la cama origen.");
}

// ==========================================
// INICIAR TRANSACCIÓN
// ==========================================
$database->StartTrans();

try {
    // ==========================================
    // 1. ACTUALIZAR BEDS_PATIENTS CON NUEVA UBICACIÓN
    // ==========================================
    $updateQuery = "UPDATE beds_patients 
                   SET current_bed_id = ?,
                       current_room_id = ?,
                       current_unit_id = ?,
                       responsible_user_id = ?,
                       user_modif = ?,
                       datetime_modif = ?
                   WHERE id = ?";
    
    sqlStatement($updateQuery, [
        $toBedId, $toRoomId, $toUnitId, $responsibleUserId,
        $userFullName, $datetimeModif, $bedsPatientsId
    ]);
    
    // ==========================================
    // 2. INSERTAR MOVIMIENTO EN BEDS_PATIENTS_TRACKER
    // ==========================================
    $trackerQuery = "INSERT INTO beds_patients_tracker (
        uuid, beds_patients_id, patient_id, movement_type, movement_date,
        responsible_user_id,
        bed_id_from, room_id_from, unit_id_from, facility_id_from, 
        bed_name_from, bed_status_from, bed_type_from, bed_condition_from,
        bed_id_to, room_id_to, unit_id_to, facility_id_to,
        bed_name_to, bed_status_to, bed_type_to, bed_condition_to,
        reason, notes, user_modif, datetime_modif
    ) VALUES (
        UUID(), ?, ?, 'relocation', ?, ?,
        ?, ?, ?, ?, ?, ?, ?, 'occupied',
        ?, ?, ?, ?, ?, ?, ?, 'occupied',
        ?, ?, ?, ?
    )";
    
    sqlStatement($trackerQuery, [
        $bedsPatientsId, $patientId, $datetimeModif, $responsibleUserId,
        $fromBedId, $fromRoomId, $fromUnitId, $fromFacilityId,
        $fromBedName, $fromBedData['bed_status'], $fromBedData['bed_type'],
        $toBedId, $toRoomId, $toUnitId, $toFacilityId,
        $toBedName, $toBedStatus, $toBedType,
        $reason, $notes, $userFullName, $datetimeModif
    ]);
    
    // ==========================================
    // 3. ACTUALIZAR ESTADO DE CAMAS EN BEDS_STATUS_LOG
    // ==========================================
    // Liberar cama anterior (normalmente va a limpieza)
    insertBedStatusLog($fromBedId, 'cleaning', $userId, null, 
                      'Bed freed after patient relocation');
    
    // Ocupar nueva cama
    insertBedStatusLog($toBedId, 'occupied', $userId, $bedsPatientsId, 
                      'Bed occupied after patient relocation - Reason: ' . $reason);
    
    // ==========================================
    // CONFIRMAR TRANSACCIÓN
    // ==========================================
    $database->CompleteTrans();
    
    // ==========================================
    // REDIRECCIÓN A LA NUEVA UBICACIÓN
    // ==========================================
    header("Location: load_beds.php?room_id=" . urlencode($toRoomId) . 
            "&room_name=" . urlencode($toRoomName) . 
            "&unit_id=" . urlencode($toUnitId) . 
            "&unit_name=" . urlencode($toUnitName) . 
            "&facility_id=" . urlencode($toFacilityId) . 
            "&facility_name=" . urlencode($toFacilityName) . 
            "&bed_action=" . urlencode($bedAction) .
            "&background_card=" . urlencode($backgroundPatientCard) .
            "&status=success&message=" . urlencode("Patient successfully relocated"));
    exit();
    
} catch (Exception $e) {
    $database->FailTrans();
    error_log("Error en save_bed_relocate: " . $e->getMessage());
    
    header("Location: load_beds.php?room_id=" . urlencode($fromRoomId) . 
            "&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

// End of file