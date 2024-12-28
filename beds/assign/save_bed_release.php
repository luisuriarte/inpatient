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
    $responsibleUserId = intval($_POST['responsible_user_id_release']) ?? null;

    $roomId = intval($_POST['room_id']) ?? null;
    $roomName = htmlspecialchars($_POST['room_name']) ?? null;
    $unitId = intval($_POST['unit_id']) ?? null;
    $unitName = htmlspecialchars($_POST['unit_name']) ?? null;
    $facilityId = intval($_POST['facility_id']) ?? null;
    $facilityName = htmlspecialchars($_POST['facility_name']) ?? null;
    $userId = $_SESSION['authUserID'];
    $userFullName = getUserFullName($userId);
    $notes = text($_POST['notes'] ?? '');
    $bedAction = htmlspecialchars($_POST['bed_action']);
    $operation = htmlspecialchars($bedAction);
    $cleaning = $_POST['cleaning'] ?? null;
    $backgroundPatientCard = htmlspecialchars($_POST['background_card']);

    // Validar si todos los datos requeridos están presentes
    if ($bedsPatientsId && $responsibleUserId && $bedId) {
        // Obtener la fecha y hora actual
        $now = date('Y-m-d H:i:s');

            // Actualizar el registro de beds_patients con la información de discharge
            $updateQuery = "UPDATE beds_patients 
                            SET responsible_user_id = ?, 
                                `condition` = 'Vacant', 
                                notes = ?, 
                                operation = 'Release', 
                                user_modif = ?, 
                                datetime_modif = ?, 
                                active = 1 
                            WHERE id = ?";

            // Parámetros para la consulta de actualización
            $updateResult = sqlStatement($updateQuery, [
                $responsibleUserId,  // ID de usuario responsable
                $notes,              // Notas de alta
                $userFullName,           // Usuario que modifica
                $now,      // Fecha y hora de modificación
                $bedsPatientsId      // ID de beds_patients
            ]);

                // Verificar si la inserción fue exitosa
                if ($updateResult) {
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
                echo "Datos incompletos. Verifique e intente nuevamente.";
            }
        }
}

?>
