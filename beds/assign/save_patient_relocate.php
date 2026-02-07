<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
//    echo '<pre>';
//    print_r($_POST);
//    echo '</pre>';
//    exit; // Agregar esto para detener la ejecución temporalmente
//}

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $fromIdBedsPatients = intval($_POST['from_id_beds_patients']) ?? null;
    $bedsPatientsId = $_POST['beds_patients_id'] ?? null;  // ID de 
    $bedIdNew = intval($_POST['bed_id']) ?? null;  // Nueva cama (cama destino)
    $bedNameNew = htmlspecialchars($_POST['bed_name']) ?? null;
    $bedStatusNew = htmlspecialchars($_POST['bed_status']) ?? null;
    $bedTypeNew = htmlspecialchars($_POST['bed_type']) ?? null;
    $patientId = intval($_POST['patient_id']) ?? null;
    $responsibleUserId = intval($_POST['responsible_user_id_relocate']) ?? null;
    $relocateReason = htmlspecialchars($_POST['relocate_reason']) ?? null;

    $roomIdNew = intval($_POST['room_id']) ?? null;
    $roomNameNew = htmlspecialchars($_POST['room_name']) ?? null;
    $unitIdNew = intval($_POST['unit_id']) ?? null;
    $unitNameNew = htmlspecialchars($_POST['unit_name']) ?? null;
    $facilityIdNew = intval($_POST['facility_id']) ?? null;
    $facilityNameNew = htmlspecialchars($_POST['facility_name']) ?? null;
    $userId = $_SESSION['authUserID'];
    $userFullName = getUserFullName($userId);
    $notes = text($_POST['notes'] ?? '');
    $now = date('Y-m-d H:i:s');
    $bedAction = htmlspecialchars($_POST['bed_action']);
    $operation = htmlspecialchars($bedAction);
    $backgroundPatientCard = htmlspecialchars($_POST['background_card']);
    $cleaning = $_POST['cleaning'] ?? null;

    // Validar si todos los datos requeridos están presentes
    // bedsPatientsId puede ser null si la cama destino está vacía (INSERT)
    if ($responsibleUserId && $bedIdNew && $patientId) {
        // Iniciar la transacción
        $database->StartTrans();

        try {
            // Obtener los datos actuales de la cama vieja (origen)
            $oldBedQuery = "SELECT * FROM beds_patients WHERE id = ?";
            $oldBedData = sqlQuery($oldBedQuery, [$fromIdBedsPatients]);

            if ($oldBedData) {
                // 1. GESTIONAR CAMA NUEVA (DESTINO)
                $updateNewBedResult = false;

                if ($bedsPatientsId) {
                    // Actualizar registro existente en la cama destino (si estaba ocupada/sucia/reservada y tiene ID)
                    $updateNewBedQuery = "UPDATE beds_patients 
                                          SET patient_id = ?, assigned_date = ?, change_date = NULL,
                                              patient_care = ?, inpatient_physical_restrictions = ?, 
                                              inpatient_sensory_restrictions = ?, inpatient_cognitive_restrictions = ?, 
                                              inpatient_behavioral_restrictions = ?, inpatient_dietary_restrictions = ?, 
                                              inpatient_other_restrictions = ?, 
                                              `condition` = 'Occupied', operation = 'Relocation', 
                                              user_modif = ?, datetime_modif = ?, active = 1
                                          WHERE id = ?";

                    $updateNewBedResult = sqlStatement($updateNewBedQuery, [
                        $patientId,
                        $oldBedData['assigned_date'],
                        $oldBedData['patient_care'],
                        $oldBedData['inpatient_physical_restrictions'],
                        $oldBedData['inpatient_sensory_restrictions'],
                        $oldBedData['inpatient_cognitive_restrictions'],
                        $oldBedData['inpatient_behavioral_restrictions'],
                        $oldBedData['inpatient_dietary_restrictions'],
                        $oldBedData['inpatient_other_restrictions'],
                        $userFullName,
                        $now,
                        $bedsPatientsId // PK de la fila de la cama destino
                    ]);
                } else {
                    // Insertar nuevo registro para la cama destino vacía
                    $insertNewBedQuery = "INSERT INTO beds_patients (
                                              bed_id, room_id, unit_id, facility_id,
                                              patient_id, assigned_date, 
                                              patient_care, inpatient_physical_restrictions, 
                                              inpatient_sensory_restrictions, inpatient_cognitive_restrictions, 
                                              inpatient_behavioral_restrictions, inpatient_dietary_restrictions, 
                                              inpatient_other_restrictions, 
                                              `condition`, operation, 
                                              user_modif, datetime_modif, active, created_by, creation_datetime
                                          ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Occupied', 'Relocation', ?, ?, 1, ?, ?)";
                    
                    $updateNewBedResult = sqlStatement($insertNewBedQuery, [
                        $bedIdNew, $roomIdNew, $unitIdNew, $facilityIdNew,
                        $patientId,
                        $oldBedData['assigned_date'], // Mantener fecha de asignación original
                        $oldBedData['patient_care'],
                        $oldBedData['inpatient_physical_restrictions'],
                        $oldBedData['inpatient_sensory_restrictions'],
                        $oldBedData['inpatient_cognitive_restrictions'],
                        $oldBedData['inpatient_behavioral_restrictions'],
                        $oldBedData['inpatient_dietary_restrictions'],
                        $oldBedData['inpatient_other_restrictions'],
                        $userFullName,
                        $now,
                        $userId,
                        $now
                    ]);
                }

                if ($updateNewBedResult) {
                    // 2. ARCHIVAR LA CAMA ANTERIOR (ORIGEN)
                    // Actualizar active=0 y condition='Archival' para cerrar el historial
                    $archiveOldBedQuery = "UPDATE beds_patients 
                                            SET change_date = ?, 
                                                condition = 'Archival', 
                                                operation = 'Relocation', 
                                                user_modif = ?, 
                                                datetime_modif = ?, 
                                                active = 0 
                                            WHERE id = ?";

                    $archiveOldBedResult = sqlStatement($archiveOldBedQuery, [
                        $now,   // change_date (fecha de fin de estancia en esa cama)
                        $userFullName, 
                        $now,   
                        $fromIdBedsPatients // ID de la fila origen
                    ]);

                    if ($archiveOldBedResult) {
                        // 3. INSERTAR NUEVO REGISTRO 'CLEANING' PARA LA CAMA DE ORIGEN
                        // Se crea un nuevo registro activo para indicar que la cama ahora está sucia
                         $insertCleaningQuery = "INSERT INTO beds_patients (
                            bed_id, room_id, unit_id, facility_id,
                            bed_name, bed_status, bed_type,
                            `condition`, operation, 
                            user_modif, datetime_modif, active
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Cleaning', 'Relocation', ?, ?, 1)";

                        // Usamos los datos de oldBedData para mantener la consistencia de ubicación
                        $insertCleaningResult = sqlStatement($insertCleaningQuery, [
                            $oldBedData['bed_id'], 
                            $oldBedData['room_id'], 
                            $oldBedData['unit_id'], 
                            $oldBedData['facility_id'],
                            $oldBedData['bed_name'], 
                            $oldBedData['bed_status'], 
                            $oldBedData['bed_type'],
                            $userFullName, 
                            $now
                        ]);

                        if ($insertCleaningResult) {
                            // 4. INSERTAR EN EL TRACKER (Igual que antes)
                            $insertTrackerQuery = "INSERT INTO beds_patients_tracker (
                                                       patient_id, bed_id_from, room_id_from, bed_name_from, 
                                                       bed_status_from, bed_type_from, unit_id_from, facility_id_from, 
                                                       bed_id_to, bed_name_to, bed_status_to, bed_type_to, 
                                                       room_id_to, unit_id_to, facility_id_to, move_date, 
                                                       responsible_user_id, reason, notes, user_modif, datetime_modif
                                                   ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                            $insertTrackerResult = sqlStatement($insertTrackerQuery, [
                                $patientId,
                                $oldBedData['bed_id'],
                                $oldBedData['room_id'],
                                $oldBedData['bed_name'],
                                $oldBedData['bed_status'],
                                $oldBedData['bed_type'],
                                $oldBedData['unit_id'],
                                $oldBedData['facility_id'],
                                $bedIdNew,
                                $bedNameNew,
                                $bedStatusNew,
                                $bedTypeNew,
                                $roomIdNew,
                                $unitIdNew,
                                $facilityIdNew,
                                $now,
                                $responsibleUserId,
                                $relocateReason,
                                $notes,
                                $userFullName,
                                $now
                            ]);

                            if ($insertTrackerResult) {
                                // Confirmar la transacción
                                $database->CompleteTrans();

                                header("Location: load_beds.php?view=room" . 
                                       "&room_id=" . urlencode($roomIdNew) . 
                                       "&room_name=" . urlencode($roomNameNew) . 
                                       "&unit_id=" . urlencode($unitIdNew) . 
                                       "&unit_name=" . urlencode($unitNameNew) . 
                                       "&facility_id=" . urlencode($facilityIdNew) . 
                                       "&facility_name=" . urlencode($facilityNameNew) . 
                                       "&bed_action=" . urlencode($bedAction) . 
                                       "&background_card=" . urlencode($backgroundPatientCard));
                                exit();
                            } else {
                                echo "Error al registrar el movimiento en el tracker.";
                                $database->FailTrans();
                            }
                        } else {
                            echo "Error al crear el estado de limpieza para la cama anterior.";
                            $database->FailTrans();
                        }
                    } else {
                        echo "Error al archivar la cama anterior.";
                        $database->FailTrans();
                    }
                } else {
                    echo "Error al actualizar la cama de destino.";
                    $database->FailTrans();
                }
            } else {
                echo "No se pudo obtener la información de la cama anterior.";
            }
        } catch (Exception $e) {
            // Error en la transacción
            echo "Ocurrió un error: " . $e->getMessage();
            $database->FailTrans();
        }
    } else {
        echo "Datos incompletos. Verifique e intente nuevamente.<br>";
        if (!$responsibleUserId) echo "- Falta ID de Usuario Responsable.<br>";
        if (!$bedIdNew) echo "- Falta ID de Cama Destino.<br>";
        if (!$patientId) echo "- Falta ID de Paciente.<br>";
    }
}
?>
