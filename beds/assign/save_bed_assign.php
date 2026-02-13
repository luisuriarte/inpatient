<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// ==========================================
// OBTENER DATOS DEL FORMULARIO
// ==========================================
$bedsPatientsId = $_POST['beds_patients_id'] ?? null;
$bedId = intval($_POST['bed_id']) ?? null;
$bedName = htmlspecialchars($_POST['bed_name']) ?? null;
$bedStatus = htmlspecialchars($_POST['bed_status']) ?? null;
$bedType = htmlspecialchars($_POST['bed_type']) ?? null;
$patientId = intval($_POST['patient_id']) ?? null;
$patientCareTitle = htmlspecialchars($_POST['patient_care_title']) ?? null;

// Restricciones del paciente
$physicalRestrictions = htmlspecialchars($_POST['physical_restrictions']) ?? null;
$sensoryRestrictions = htmlspecialchars($_POST['sensory_restrictions']) ?? null;
$cognitiveRestrictions = htmlspecialchars($_POST['cognitive_restrictions']) ?? null;
$behavioralRestrictions = htmlspecialchars($_POST['behavioral_restrictions']) ?? null;
$dietaryRestrictions = htmlspecialchars($_POST['dietary_restrictions']) ?? null;
$otherRestrictions = htmlspecialchars($_POST['other_restrictions']) ?? null;

// Ubicación y responsable
$responsibleUserId = empty($_POST['responsible_user_id_assign']) ? null : intval($_POST['responsible_user_id_assign']);
$roomId = intval($_POST['room_id']);
$roomName = htmlspecialchars($_POST['room_name']);
$unitId = intval($_POST['unit_id']);
$unitName = htmlspecialchars($_POST['unit_name']);
$facilityId = intval($_POST['facility_id']);
$facilityName = htmlspecialchars($_POST['facility_name']);

// Usuario y timestamps
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$notes = text($_POST['notes']);
$datetimeModif = date('Y-m-d H:i:s');

// Parámetros de acción
$bedAction = htmlspecialchars($_POST['bed_action']);
$backgroundPatientCard = htmlspecialchars($_POST['background_card']);
$admissionType = 'check_in'; // Siempre es check_in desde este modal

// ==========================================
// INICIAR TRANSACCIÓN
// ==========================================
$database->StartTrans();

try {
    $newBedsPatientsId = null;
    $movementType = '';
    
    // ==========================================
    // VERIFICAR SI EXISTE INTERNACIÓN ACTIVA
    // ==========================================
    $existingAdmissionQuery = "SELECT id, status, current_bed_id, admission_type
                                FROM beds_patients
                                WHERE patient_id = ?
                                AND status IN ('preadmitted', 'admitted')
                                ORDER BY admission_date DESC
                                LIMIT 1";
    $existingAdmission = sqlQuery($existingAdmissionQuery, [$patientId]);
    
    if ($existingAdmission) {
        // ==========================================
        // CASO 1: CONVERTIR PRE-ADMISIÓN A ADMISIÓN
        // ==========================================
        if ($existingAdmission['status'] === 'preadmitted') {
            $newBedsPatientsId = $existingAdmission['id'];
            $oldBedId = $existingAdmission['current_bed_id'];

            // Actualizar el registro existente a admitted
            $updateQuery = "UPDATE beds_patients
                           SET status = 'admitted',
                               admission_type = 'check_in',
                               admission_date = ?,
                               current_bed_id = ?,
                               current_room_id = ?,
                               current_unit_id = ?,
                               responsible_user_id = ?,
                               patient_care = ?,
                               inpatient_physical_restrictions = ?,
                               inpatient_sensory_restrictions = ?,
                               inpatient_cognitive_restrictions = ?,
                               inpatient_behavioral_restrictions = ?,
                               inpatient_dietary_restrictions = ?,
                               inpatient_other_restrictions = ?,
                               notes = ?,
                               user_modif = ?,
                               datetime_modif = ?
                           WHERE id = ?";

            sqlStatement($updateQuery, [
                $datetimeModif, // Nueva fecha de admisión
                $bedId, $roomId, $unitId, $responsibleUserId,
                $patientCareTitle, $physicalRestrictions, $sensoryRestrictions,
                $cognitiveRestrictions, $behavioralRestrictions, $dietaryRestrictions,
                $otherRestrictions, $notes, $userFullName, $datetimeModif,
                $newBedsPatientsId
            ]);

            $movementType = 'admission';

            // Si cambió de cama respecto a la pre-admisión, liberar la anterior
            if ($oldBedId && $oldBedId != $bedId) {
                insertBedStatusLog($oldBedId, 'vacant', $userId, null,
                                  'Bed released - patient moved from preadmission to different bed');
            }
            
        } else {
            // Ya está admitted - no debería pasar
            throw new Exception("El paciente ya tiene una internación activa (admitted).");
        }
        
    } else {
        // ==========================================
        // CASO 2: NUEVA ADMISIÓN (PRIMERA VEZ)
        // ==========================================
        $insertQuery = "INSERT INTO beds_patients (
            uuid, patient_id, facility_id, admission_type, admission_date, admission_user_id,
            status, current_bed_id, current_room_id, current_unit_id, responsible_user_id,
            patient_care, inpatient_physical_restrictions, inpatient_sensory_restrictions,
            inpatient_cognitive_restrictions, inpatient_behavioral_restrictions,
            inpatient_dietary_restrictions, inpatient_other_restrictions,
            notes, user_modif, datetime_modif
        ) VALUES (UUID(), ?, ?, ?, ?, ?, 'admitted', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $insertResult = sqlStatement($insertQuery, [
            $patientId, $facilityId, $admissionType, $datetimeModif, $userId,
            $bedId, $roomId, $unitId, $responsibleUserId,
            $patientCareTitle, $physicalRestrictions, $sensoryRestrictions,
            $cognitiveRestrictions, $behavioralRestrictions, $dietaryRestrictions,
            $otherRestrictions, $notes, $userFullName, $datetimeModif
        ]);
        
        if (!$insertResult) {
            throw new Exception("Error al insertar el nuevo registro en beds_patients.");
        }
        
        // Obtener el ID recién insertado
        $newBedsPatientsId = $database->Insert_ID();
        $movementType = 'admission';
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
        UUID(), ?, ?, ?, ?, ?, 
        NULL, NULL, NULL, NULL,
        ?, ?, ?, ?,
        'occupied', 'Patient admission', ?, ?, ?
    )";
    
    $trackerResult = sqlStatement($trackerQuery, [
        $newBedsPatientsId, $patientId, $movementType, $datetimeModif,
        $responsibleUserId,
        $bedId, $roomId, $unitId, $facilityId,
        $notes, $userFullName, $datetimeModif
    ]);
    
    if (!$trackerResult) {
        throw new Exception("Error al insertar en beds_patients_tracker.");
    }
    
    // ==========================================
    // 3. INSERTAR EN BEDS_STATUS_LOG
    // ==========================================
    insertBedStatusLog($bedId, 'occupied', $userId, $newBedsPatientsId, 'Patient admission');
    
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
            "&status=success&message=" . urlencode("Patient successfully admitted"));
    exit();
    
} catch (Exception $e) {
    // ==========================================
    // MANEJO DE ERRORES
    // ==========================================
    $database->FailTrans();
    error_log("Error en save_bed_assign: " . $e->getMessage());
    
    header("Location: load_beds.php?room_id=" . urlencode($roomId ?? '') . 
            "&status=error&message=" . urlencode($e->getMessage()));
    exit();
}

// End of file