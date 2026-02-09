<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================
$bedsPatientsId = $_POST['beds_patients_id'] ?? null;
$bedId = intval($_POST['bed_id']);
$patientId = intval($_POST['patient_id']);
$responsibleUserId = empty($_POST['responsible_user_id_reserve']) ? null : intval($_POST['responsible_user_id_reserve']);
$notes = text($_POST['notes']);

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

// Obtener datos de la cama
$bedQuery = "SELECT bed_name, bed_type, bed_status FROM beds WHERE id = ?";
$bedData = sqlQuery($bedQuery, [$bedId]);

// ==========================================
// INICIAR TRANSACCIÓN
// ==========================================
$database->StartTrans();

try {
    $newBedsPatientsId = null;
    
    // ==========================================
    // VERIFICAR SI YA EXISTE PRE-ADMISIÓN
    // ==========================================
    $existingAdmissionQuery = "SELECT id, status, current_bed_id
                                FROM beds_patients 
                                WHERE patient_id = ? 
                                AND status IN ('preadmitted', 'admitted')
                                LIMIT 1";
    $existingAdmission = sqlQuery($existingAdmissionQuery, [$patientId]);
    
    if ($existingAdmission) {
        if ($existingAdmission['status'] === 'admitted') {
            throw new Exception("El paciente ya tiene una internación activa.");
        }
        
        // Actualizar pre-admisión existente (cambio de cama reservada)
        $newBedsPatientsId = $existingAdmission['id'];
        $oldBedId = $existingAdmission['current_bed_id'];
        
        $updateQuery = "UPDATE beds_patients 
                       SET current_bed_id = ?,
                           current_room_id = ?,
                           current_unit_id = ?,
                           responsible_user_id = ?,
                           notes = ?,
                           user_modif = ?,
                           datetime_modif = ?
                       WHERE id = ?";
        
        sqlStatement($updateQuery, [
            $bedId, $roomId, $unitId, $responsibleUserId,
            $notes, $userFullName, $datetimeModif, $newBedsPatientsId
        ]);
        
        // Liberar cama anterior si cambió
        if ($oldBedId && $oldBedId != $bedId) {
            insertBedStatusLog($oldBedId, 'vacant', $userId, null, 
                              'Reservation moved to different bed');
        }
        
    } else {
        // ==========================================
        // NUEVA PRE-ADMISIÓN
        // ==========================================
        $insertQuery = "INSERT INTO beds_patients (
            uuid, patient_id, facility_id, admission_type, admission_date, admission_user_id,
            status, current_bed_id, current_room_id, current_unit_id, responsible_user_id,
            notes, user_modif, datetime_modif
        ) VALUES (UUID(), ?, ?, 'preadmission', ?, ?, 'preadmitted', ?, ?, ?, ?, ?, ?, ?)";
        
        $insertResult = sqlStatement($insertQuery, [
            $patientId, $facilityId, $datetimeModif, $userId,
            $bedId, $roomId, $unitId, $responsibleUserId,
            $notes, $userFullName, $datetimeModif
        ]);
        
        if (!$insertResult) {
            throw new Exception("Error al insertar la reserva en beds_patients.");
        }
        
        $newBedsPatientsId = $database->Insert_ID();
    }
    
    // ==========================================
    // 2. INSERTAR EN BEDS_PATIENTS_TRACKER
    // ==========================================
    $trackerQuery = "INSERT INTO beds_patients_tracker (
        uuid, beds_patients_id, patient_id, movement_type, movement_date,
        responsible_user_id,
        bed_id_from, room_id_from, unit_id_from, facility_id_from,
        bed_id_to, room_id_to, unit_id_to, facility_id_to,
        bed_condition_to, reason, notes, user_modif, datetime_modif
    ) VALUES (
        UUID(), ?, ?, 'bed_reservation', ?, ?,
        NULL, NULL, NULL, NULL,
        ?, ?, ?, ?,
        'reserved', 'Bed reservation/preadmission', ?, ?, ?
    )";
    
    $trackerResult = sqlStatement($trackerQuery, [
        $newBedsPatientsId, $patientId, $datetimeModif, $responsibleUserId,
        $bedId, $roomId, $unitId, $facilityId,
        $notes, $userFullName, $datetimeModif
    ]);
    
    if (!$trackerResult) {
        throw new Exception("Error al insertar en beds_patients_tracker.");
    }
    
    // ==========================================
    // 3. INSERTAR EN BEDS_STATUS_LOG
    // ==========================================
    insertBedStatusLog($bedId, 'reserved', $userId, $newBedsPatientsId, 
                      'Bed reserved for patient preadmission');
    
    // ==========================================
    // CONFIRMAR TRANSACCIÓN
    // ==========================================
    $database->CompleteTrans();
    
    // ==========================================
    // REDIRECCIÓN EXITOSA
    // ==========================================
    header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
            "&room_name=" . urlencode($roomName) . 
            "&unit_id=" . urlencode($unitId) . 
            "&unit_name=" . urlencode($unitName) . 
            "&facility_id=" . urlencode($facilityId) . 
            "&facility_name=" . urlencode($facilityName) . 
            "&bed_action=" . urlencode($bedAction) . 
            "&background_card=" . urlencode($backgroundPatientCard) .
            "&status=success&message=" . urlencode("Bed successfully reserved"));
    exit();
    
} catch (Exception $e) {
    $database->FailTrans();
    error_log("Error en save_bed_reserve: " . $e->getMessage());
    
    header("Location: load_beds.php?room_id=" . urlencode($roomId ?? '') . 
            "&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

// End of file