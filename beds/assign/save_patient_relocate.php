<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================
$bedsPatientsId = intval($_POST['beds_patients_id']);
$patientId = intval($_POST['patient_id']);

// Datos de ORIGEN (provenientes del formulario)
$fromIdBedsPatients = intval($_POST['from_id_beds_patients']);
// Nota: No usaremos $fromBedId, $fromRoomId, $fromUnitId, $fromFacilityId del formulario, 
// los obtendremos de la base de datos

// Datos de DESTINO (la cama a la que se va a reubicar)
$bedId = intval($_POST['bed_id']); // El ID de la cama destino
$toBedName = htmlspecialchars($_POST['bed_name']);
$toBedStatus = htmlspecialchars($_POST['bed_status']);
$toBedType = htmlspecialchars($_POST['bed_type']);
$toRoomId = intval($_POST['room_id']);
$toRoomName = htmlspecialchars($_POST['room_name']);
$toUnitId = intval($_POST['unit_id']);
$toUnitName = htmlspecialchars($_POST['unit_name']);
$toFacilityId = intval($_POST['facility_id']);
$toFacilityName = htmlspecialchars($_POST['facility_name']);

// Otros datos
$responsibleUserId = empty($_POST['responsible_user_id_relocate']) ? null : intval($_POST['responsible_user_id_relocate']);
$reason = text($_POST['relocate_reason']);
$notes = text($_POST['relocateNotes']);
$bedToCleaning = isset($_POST['bed_to_cleaning']) ? true : false;

// Usuario y timestamps
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$datetimeModif = date('Y-m-d H:i:s');
$backgroundPatientCard = htmlspecialchars($_POST['background_card'] ?? '');
$bedAction = htmlspecialchars($_POST['bed_action'] ?? 'Management');

// ==========================================
// OBTENER INFORMACIÓN DE LA CAMA ORIGEN
// ==========================================
// Obtenemos la información completa de la cama actual del paciente desde beds_patients
$currentBedQuery = "SELECT current_bed_id, current_room_id, current_unit_id, facility_id FROM beds_patients WHERE id = ?";
$currentBedData = sqlQuery($currentBedQuery, [$fromIdBedsPatients]);

if (!$currentBedData) {
    die("Error: No se pudo obtener información de la cama origen.");
}

$fromBedId = $currentBedData['current_bed_id'];
$fromRoomId = $currentBedData['current_room_id'];
$fromUnitId = $currentBedData['current_unit_id'];
$fromFacilityId = $currentBedData['facility_id'];

// Ahora obtenemos el nombre de la cama de origen
$fromBedQuery = "SELECT bed_name FROM beds WHERE id = ?";
$fromBedData = sqlQuery($fromBedQuery, [$fromBedId]);

if (!$fromBedData) {
    die("Error: No se pudo obtener el nombre de la cama origen.");
}

$fromBedName = $fromBedData['bed_name'];

// Para la actualización, usaremos el ID del registro de la cama de origen
$bedsPatientsId = $fromIdBedsPatients;

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
        $bedId, $toRoomId, $toUnitId, $responsibleUserId,
        $userFullName, $datetimeModif, $bedsPatientsId
    ]);
    
    // ==========================================
    // 2. INSERTAR MOVIMIENTO EN BEDS_PATIENTS_TRACKER
    // ==========================================
    $trackerQuery = "INSERT INTO beds_patients_tracker (
        uuid, beds_patients_id, patient_id, movement_type, movement_date,
        responsible_user_id,
        bed_id_from, room_id_from, unit_id_from, facility_id_from,
        bed_condition_from,
        bed_id_to, room_id_to, unit_id_to, facility_id_to,
        bed_condition_to,
        reason, notes, user_modif, datetime_modif
    ) VALUES (
        UUID(), ?, ?, 'relocation', ?, ?,
        ?, ?, ?, ?, 'occupied',
        ?, ?, ?, ?, 'occupied',
        ?, ?, ?, ?
    )";

    sqlStatement($trackerQuery, [
        $bedsPatientsId, $patientId, $datetimeModif, $responsibleUserId,
        $fromBedId, $fromRoomId, $fromUnitId, $fromFacilityId,
        $bedId, $toRoomId, $toUnitId, $toFacilityId,
        $reason, $notes, $userFullName, $datetimeModif
    ]);

    // ==========================================
    // 3. ACTUALIZAR ESTADO DE CAMAS EN BEDS_STATUS_LOG
    // ==========================================
    // Determinar el estado para la cama anterior según la elección del usuario
    $previousBedStatus = $bedToCleaning ? 'cleaning' : 'vacant';
    $previousBedNote = $bedToCleaning ? 
                      'Bed freed after patient relocation - Set to cleaning' : 
                      'Bed freed after patient relocation - Set to vacant';

    // Actualizar estado de la cama anterior
    insertBedStatusLog($fromBedId, $previousBedStatus, $userId, null, $previousBedNote);

    // Ocupar nueva cama
    insertBedStatusLog($bedId, 'occupied', $userId, $bedsPatientsId,
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