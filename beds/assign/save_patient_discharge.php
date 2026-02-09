<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bedsPatientsId = $_POST['beds_patients_id'] ?? null;
    $bedId = intval($_POST['bed_id']);
    $patientId = intval($_POST['patient_id']);
    $responsibleUserId = intval($_POST['responsible_user_id_discharge']);
    
    // Datos de ubicación para la redirección
    $roomId = intval($_POST['room_id']);
    $unitId = intval($_POST['unit_id']);
    $facilityId = intval($_POST['facility_id']);
    
    $userId = $_SESSION['authUserID'];
    $userFullName = getUserFullName($userId);
    $now = date('Y-m-d H:i:s');
    $notes = text($_POST['notes'] ?? '');
    $cleaning = $_POST['cleaning'] ?? null;

    if ($bedsPatientsId && $patientId) {
        $database->StartTrans();

        try {
            // 1. OBTENER DATOS DE LA CAMA ANTES DEL ALTA (Para el Tracker)
            $oldDataQuery = "SELECT bed_name, bed_status, bed_type FROM beds_patients WHERE id = ?";
            $oldData = sqlQuery($oldDataQuery, [$bedsPatientsId]);

            // 2. ACTUALIZAR EL REGISTRO ACTUAL (Cerrar internación del paciente)
            // Usamos 'Archived' o 'Historical' para indicar que esta estancia terminó
            $updateQuery = "UPDATE beds_patients 
                            SET responsible_user_id = ?, 
                                change_date = ?,
                                discharge_disposition = ?, 
                                `condition` = 'Archived', 
                                notes = ?, 
                                operation = 'Discharge', 
                                user_modif = ?, 
                                datetime_modif = ?, 
                                active = 0 
                            WHERE id = ?";
            
            sqlStatement($updateQuery, [
                $responsibleUserId, $now, $_POST['discharge_disposition'], 
                $notes, $userFullName, $now, $bedsPatientsId
            ]);

            // 3. INSERTAR EN EL TRACKER (Auditoría de Salida)
            $insertTrackerQuery = "INSERT INTO beds_patients_tracker (
                patient_id, bed_id_from, room_id_from, bed_name_from, unit_id_from, facility_id_from,
                bed_id_to, move_date, responsible_user_id, reason, notes, user_modif, datetime_modif
            ) VALUES (?, ?, ?, ?, ?, ?, NULL, ?, ?, 'Discharge', ?, ?, ?)";

            sqlStatement($insertTrackerQuery, [
                $patientId, $bedId, $roomId, $oldData['bed_name'], $unitId, $facilityId,
                $now, $responsibleUserId, $notes, $userFullName, $now
            ]);

            // 4. PREPARAR LA CAMA PARA EL SIGUIENTE (Nuevo registro vacío)
            $newCondition = $cleaning ? 'Cleaning' : 'Vacant';
            $insertNewBedQuery = "INSERT INTO beds_patients (
                bed_id, room_id, unit_id, facility_id, 
                bed_name, bed_status, bed_type, 
                `condition`, operation, user_modif, 
                datetime_modif, active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Primed', ?, ?, 1)";

            sqlStatement($insertNewBedQuery, [
                $bedId, $roomId, $unitId, $facilityId,
                $_POST['bed_name'], $_POST['bed_status'], $_POST['bed_type'],
                $newCondition, $userFullName, $now
            ]);

            $database->CompleteTrans();

            // Redirección Exitosa
            header("Location: load_beds.php?room_id=" . urlencode($roomId) . "&status=discharged");
            exit();

        } catch (Exception $e) {
            $database->FailTrans();
            error_log("Error en Discharge: " . $e->getMessage());
            header("Location: load_beds.php?status=error&msg=" . urlencode($e->getMessage()));
            exit();
        }
    }
}