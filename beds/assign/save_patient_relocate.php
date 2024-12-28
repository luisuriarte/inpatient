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
    if ($bedsPatientsId && $responsibleUserId && $bedIdNew && $patientId) {
        // Iniciar la transacción
        $database->StartTrans();

        try {
            // Obtener los datos actuales de la cama vieja (origen)
            $oldBedQuery = "SELECT * FROM beds_patients WHERE id = ?";
            $oldBedData = sqlQuery($oldBedQuery, [$fromIdBedsPatients]);

            if ($oldBedData) {
                // Actualizar la cama destino con la información del paciente
                $updateNewBedQuery = "UPDATE beds_patients 
                                      SET patient_id = ?, assigned_date = ?, change_date = NULL,
                                          patient_care = ?, inpatient_physical_restrictions = ?, 
                                          inpatient_sensory_restrictions = ?, inpatient_cognitive_restrictions = ?, 
                                          inpatient_behavioral_restrictions = ?, inpatient_dietary_restrictions = ?, 
                                          inpatient_other_restrictions = ?, 
                                          `condition` = 'Occupied', operation = 'Relocation', 
                                          user_modif = ?, datetime_modif = ?, active = 1
                                      WHERE bed_id = ?";

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
                    $bedIdNew
                ]);

                if ($updateNewBedResult) {
                    // Actualizar la cama de origen para vaciar los datos del paciente
                    $updateOldBedQuery = "UPDATE beds_patients 
                                            SET patient_id = NULL, responsible_user_id = NULL, 
                                                assigned_date = NULL, change_date = ?, 
                                                discharge_disposition = NULL, 
                                                `condition` = 'Cleaning', 
                                                patient_care = NULL, inpatient_physical_restrictions = NULL, 
                                                inpatient_sensory_restrictions = NULL, inpatient_cognitive_restrictions = NULL, 
                                                inpatient_behavioral_restrictions = NULL, inpatient_dietary_restrictions = NULL, 
                                                inpatient_other_restrictions = NULL, notes = NULL, 
                                                operation = 'Relocation', user_modif = ?, 
                                                datetime_modif = ?, active = 1 
                                            WHERE id = ?";

                        $updateOldBedResult = sqlStatement($updateOldBedQuery, [
                        $now,   // change_date
                        $userFullName, // user_modif
                        $now,   // datetime_modif
                        $fromIdBedsPatients // Ahora usamos el id correcto
                        ]);

                    if ($updateOldBedResult) {
                        // Insertar en el tracker de transferencia
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

                            // Redirigir a la página deseada
                            //header("Location: assign.php");
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
                        echo "Error al actualizar la cama anterior.";
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
        echo "Datos incompletos. Verifique e intente nuevamente.";
    }
}
?>
