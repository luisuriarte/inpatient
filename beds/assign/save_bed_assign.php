<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    exit; // Agregar esto para detener la ejecución temporalmente
}
 // Obtener los datos del formulario
    $bedsPatientsId = $_POST['beds_patients_id'] ?? null;
    $bedId = intval($_POST['bed_id']) ?? null;
	$bedName = htmlspecialchars($_POST['bed_name']) ?? null;
	$bedStatus = htmlspecialchars($_POST['bed_status']) ?? null;
	$bedType = htmlspecialchars($_POST['bed_type']) ?? null;
    $patientId = intval($_POST['patient_id']) ?? null;
    $patientCareTitle = htmlspecialchars($_POST['patient_care_title']) ?? null;
    $physicalRestrictions = htmlspecialchars($_POST['physical_restrictions']) ?? null;
    $sensoryRestrictions = htmlspecialchars($_POST['sensory_restrictions']) ?? null;
    $cognitiveRestrictions = htmlspecialchars($_POST['cognitive_restrictions']) ?? null;
    $behavioralRestrictions = htmlspecialchars($_POST['behavioral_restrictions']) ?? null;
    $dietaryRestrictions = htmlspecialchars($_POST['dietary_restrictions']) ?? null;
    $otherRestrictions = htmlspecialchars($_POST['other_restrictions']) ?? null;
    $responsibleUserId = empty($_POST['responsible_user_id_assign']) ? null : intval($_POST['responsible_user_id_assign']);
    
    $roomId = intval($_POST['room_id']);
    $roomName = htmlspecialchars($_POST['room_name']);
    $unitId = intval($_POST['unit_id']);
    $unitName = htmlspecialchars($_POST['unit_name']);
    $facilityId = intval($_POST['facility_id']);
    $facilityName = htmlspecialchars($_POST['facility_name']);
    $userId = $_SESSION['authUserID'];
    $userFullName = getUserFullName($userId);
    $notes = text($_POST['notes']);
    $datetimeModif = date('Y-m-d H:i:s');
    $condition = 'Occupied';
    $assignedDate = date('Y-m-d H:i:s');
    $active = 1;
    $bedAction = htmlspecialchars($_POST['bed_action']);
    $operation = htmlspecialchars($bedAction);
    $backgroundPatientCard = htmlspecialchars($_POST['background_card']);


// Iniciar la transacción
$database->StartTrans();

    try {
        // Actualizar el registro de la cama
        $updateQuery = "UPDATE beds_patients SET `operation` = ?, user_modif = ?, datetime_modif = ?, active = 0 WHERE id = ? AND active = 1";
        $updateResult = sqlStatement($updateQuery, [$operation, $userFullName, $datetimeModif, $bedsPatientsId]);
    
        if (!$updateResult) {
            throw new Exception("Error al actualizar el registro de la cama.");
        }
    
        // Insertar el nuevo registro en beds_patients
        $insertQuery = "INSERT INTO beds_patients (bed_id, room_id, unit_id, facility_id, patient_id, responsible_user_id, assigned_date, `condition`, patient_care,
                inpatient_physical_restrictions, inpatient_sensory_restrictions, inpatient_cognitive_restrictions, inpatient_behavioral_restrictions,
                inpatient_dietary_restrictions, inpatient_other_restrictions, notes, `operation`, user_modif, datetime_modif, bed_name, bed_status, bed_type, active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      
      $insertResult = sqlStatement($insertQuery, [
                $bedId, $roomId, $unitId, $facilityId, $patientId, $responsibleUserId, $assignedDate, $condition, $patientCareTitle, $physicalRestrictions, $sensoryRestrictions,
                $cognitiveRestrictions, $behavioralRestrictions, $dietaryRestrictions, $otherRestrictions, $notes, $operation, $userFullName, $datetimeModif, $bedName, $bedStatus,
                $bedType, $active
      ]);
    
        if (!$insertResult) {
            throw new Exception("Error al insertar el nuevo registro en beds_patients.");
        }
    
        // Confirmar la transacción
        $database->CompleteTrans();
            header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
                    "&room_name=" . urlencode($roomName) . 
                    "&unit_id=" . urlencode($unitId) . 
                    "&unit_name=" . urlencode($unitName) . 
                    "&facility_id=" . urlencode($facilityId) . 
                    "&facility_name=" . urlencode($facilityName) . 
                    "&bed_action=" . urlencode($bedAction) . 
                    "&background_card=" . urlencode($backgroundPatientCard));
            exit();

    } catch (Exception $e) {
        // Si ocurre un error, revertir la transacción
        $database->FailTrans();
        header("Location: load_beds.php?status=error");
    }
    exit();
?>
