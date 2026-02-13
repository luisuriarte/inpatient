<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ==========================================
    // OBTENER DATOS DEL FORMULARIO
    // ==========================================
    $bedsPatientsId = intval($_POST['beds_patients_id']);
    $patientId = intval($_POST['patient_id']);
    $bedId = intval($_POST['bed_id']);
    $bedName = htmlspecialchars($_POST['bed_name']);

    $dischargeDisposition = htmlspecialchars($_POST['discharge_disposition']);
    $responsibleUserId = empty($_POST['responsible_user_id_discharge']) ? null : intval($_POST['responsible_user_id_discharge']);
    $dischargeNotes = text($_POST['dischargeNotes']);
    $cleaningRequested = isset($_POST['cleaning']) ? true : false;

    // Ubicación (para tracker y redirección)
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

    // Parámetros de redirección
    $bedAction = htmlspecialchars($_POST['bed_action']);
    $backgroundPatientCard = htmlspecialchars($_POST['background_card']);

    // ==========================================
    // OBTENER DATOS ACTUALES DE LA INTERNACIÓN
    // ==========================================
    $admissionQuery = "SELECT current_bed_id, current_room_id, current_unit_id, facility_id, status
                       FROM beds_patients 
                       WHERE id = ? AND patient_id = ? AND status = 'admitted'";
    $admissionData = sqlQuery($admissionQuery, [$bedsPatientsId, $patientId]);

    if (!$admissionData) {
        error_log("Error: No se encontró una internación activa para patient_id=$patientId, beds_patients_id=$bedsPatientsId");
        header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
                "&status=error&message=" . urlencode("No active admission found"));
        exit();
    }

    // ==========================================
    // INICIAR TRANSACCIÓN
    // ==========================================
    $database->StartTrans();

    try {
        // ==========================================
        // 1. ACTUALIZAR BEDS_PATIENTS (ALTA)
        // ==========================================
        $updateQuery = "UPDATE beds_patients 
                       SET status = 'discharged',
                           discharge_date = ?,
                           discharge_user_id = ?,
                           discharge_disposition = ?,
                           current_bed_id = NULL,
                           current_room_id = NULL,
                           current_unit_id = NULL,
                           user_modif = ?,
                           datetime_modif = ?
                       WHERE id = ?";
        
        sqlStatement($updateQuery, [
            $datetimeModif, $userId, $dischargeDisposition,
            $userFullName, $datetimeModif, $bedsPatientsId
        ]);

        // ==========================================
        // 2. INSERTAR MOVIMIENTO EN BEDS_PATIENTS_TRACKER
        // ==========================================
        $trackerQuery = "INSERT INTO beds_patients_tracker (
            uuid, beds_patients_id, patient_id, movement_type, movement_date,
            responsible_user_id,
            bed_id_from, room_id_from, unit_id_from, facility_id_from, bed_condition_from,
            bed_id_to, room_id_to, unit_id_to, facility_id_to,
            reason, notes, user_modif, datetime_modif
        ) VALUES (
            UUID(), ?, ?, 'discharge', ?, ?,
            ?, ?, ?, ?, 'occupied',
            NULL, NULL, NULL, NULL,
            ?, ?, ?, ?
        )";
        
        sqlStatement($trackerQuery, [
            $bedsPatientsId, $patientId, $datetimeModif, $responsibleUserId,
            $admissionData['current_bed_id'], $admissionData['current_room_id'],
            $admissionData['current_unit_id'], $admissionData['facility_id'],
            $dischargeDisposition, $dischargeNotes, $userFullName, $datetimeModif
        ]);

        // ==========================================
        // 3. ACTUALIZAR BEDS_STATUS_LOG
        // ==========================================
        // Determinar nuevo estado de la cama según checkbox de limpieza
        $newBedCondition = $cleaningRequested ? 'cleaning' : 'vacant';
        $statusNotes = $cleaningRequested ? 
                       'Patient discharged - bed needs cleaning' : 
                       'Patient discharged - bed available';
        
        insertBedStatusLog($admissionData['current_bed_id'], $newBedCondition, $userId, null, $statusNotes);

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
                "&status=success&message=" . urlencode("Patient successfully discharged"));
        exit();

    } catch (Exception $e) {
        $database->FailTrans();
        error_log("Error en save_patient_discharge: " . $e->getMessage());

        header("Location: load_beds.php?room_id=" . urlencode($roomId ?? '') . 
                "&status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}
?>