<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
    $bedsPatientsId = $_POST['beds_patients_id'] ?? null;
    $bedId = intval($_POST['bed_id']) ?? null;
    $bedName = htmlspecialchars($_POST['bed_name']) ?? null;
    $bedStatus = htmlspecialchars($_POST['bed_status']) ?? null;
    $bedType = htmlspecialchars($_POST['bed_type']) ?? null;
    $patientId = intval($_POST['patient_id']) ?? null;
    $responsibleUserId = intval($_POST['responsible_user_id_discharge']) ?? null;

    $roomId = intval($_POST['room_id']) ?? null;
    $roomName = htmlspecialchars($_POST['room_name']) ?? null;
    $unitId = intval($_POST['unit_id']) ?? null;
    $unitName = htmlspecialchars($_POST['unit_name']) ?? null;
    $facilityId = intval($_POST['facility_id']) ?? null;
    $facilityName = htmlspecialchars($_POST['facility_name']) ?? null;
    $userId = $_SESSION['authUserID'];
    $userFullName = getUserFullName($userId);
    $notes = text($_POST['notes'] ?? '');
    $dischargeDate = date('Y-m-d H:i:s');
    $dischargeDisposition = htmlspecialchars($_POST['discharge_disposition']) ?? null;
    $bedAction = htmlspecialchars($_POST['bed_action']);
    $operation = htmlspecialchars($bedAction);
    $cleaning = $_POST['cleaning'] ?? null;
    $backgroundPatientCard = htmlspecialchars($_POST['background_card']);

    // Validar si todos los datos requeridos están presentes
    if ($bedsPatientsId && $responsibleUserId && $bedId) {
        // Obtener la fecha y hora actual
        $now = date('Y-m-d H:i:s');

        // Iniciar la transacción
        $database->StartTrans();

        try {
            // Actualizar el registro de beds_patients con la información de discharge
            $updateQuery = "UPDATE beds_patients 
                            SET responsible_user_id = ?, 
                                change_date = ?,
                                discharge_disposition = ?, 
                                `condition` = 'Archival', 
                                notes = ?, 
                                operation = 'Discharge', 
                                user_modif = ?, 
                                datetime_modif = ?, 
                                active = 0 
                            WHERE id = ?";

            // Parámetros para la consulta de actualización
            $updateResult = sqlStatement($updateQuery, [
                $responsibleUserId,  // ID de usuario responsable
                $dischargeDate,      // Fecha de alta
                $dischargeDisposition,
                $notes,              // Notas de alta
                $userFullName,           // Usuario que modifica
                $dischargeDate,      // Fecha y hora de modificación
                $bedsPatientsId      // ID de beds_patients
            ]);

            // Verificar si la actualización fue exitosa
            if ($updateResult) {
                // Nueva condición basada en el checkbox de limpieza
                $newCondition = $cleaning ? 'Cleaning' : 'Vacant';

                // Consulta de inserción con escapado de la columna `condition`
                $insertQuery = "INSERT INTO beds_patients (
                                    bed_id, room_id, unit_id, facility_id, 
                                    bed_name, bed_status, bed_type, 
                                    `condition`, operation, user_modif, 
                                    datetime_modif, active
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Primed', ?, ?, 1)";

                // Parámetros para la consulta de inserción
                $insertResult = sqlStatement($insertQuery, [
                    $bedId,
                    $roomId,
                    $unitId,
                    $facilityId,
                    $bedName,
                    $bedStatus,
                    $bedType,
                    $newCondition,
                    $userFullName,
                    $now
                ]);

                // Verificar si la inserción fue exitosa
                if ($insertResult) {
                    // Confirmar la transacción
                    $database->CompleteTrans();

                    // Redirigir a la página deseada
                    header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
                           "&room_name=" . urlencode($roomName) . 
                           "&unit_id=" . urlencode($unitId) . 
                           "&unit_name=" . urlencode($unitName) . 
                           "&facility_id=" . urlencode($facilityId) . 
                           "&facility_name=" . urlencode($facilityName) . 
                           "&bed_action=" . urlencode($bedAction) . 
                           "&background_card=" . urlencode($backgroundPatientCard));
                    exit();
                } else {
                    echo "Error al preparar la cama.";
                    $database->FailTrans();
                }
            } else {
                echo "Error al actualizar la información de discharge.";
                $database->FailTrans();
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
