<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");
// Verificar si la solicitud es POST
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
//    echo '<pre>';
//    print_r($_POST);
//    echo '</pre>';
//    exit; // Agregar esto para detener la ejecución temporalmente
//}
// Obtener datos del usuario de la sesión
$bedsPatientsId = $_POST['beds_patients_id'] ?? null;
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);
$bedId = intval($_POST['bed_id']);
$patientId = intval($_POST['patient_id']);
$notes = text($_POST['notes']);
$datetimeModif = date('Y-m-d H:i:s');
$condition = 'Reserved';
$now = date('Y-m-d H:i:s');
$responsibleUserId = intval($_POST['responsible_user_id_reserve']);
$roomId = intval($_POST['room_id']);
$roomName = htmlspecialchars($_POST['room_name']);
$unitId = intval($_POST['unit_id']);
$unitName = htmlspecialchars($_POST['unit_name']);
$facilityId = intval($_POST['facility_id']);
$facilityName = htmlspecialchars($_POST['facility_name']);
$bedAction = htmlspecialchars($_POST['bed_action']);
$operation = 'Reserve';
$bedAction = htmlspecialchars($_POST['bed_action']);
$backgroundPatientCard = htmlspecialchars($_POST['background_card']);

// Guardar los datos en la base de datos
$query = "UPDATE beds_patients 
          SET patient_id = ?,
            responsible_user_id = ?, 
            assigned_date = ?,
            change_date = ?, 
            `condition` = ?, 
            `operation` = ?, 
            user_modif = ?, 
            datetime_modif = ?, 
            notes = ? 
          WHERE id = ? AND active = 1";

$result = sqlStatement($query, [$patientId, $responsibleUserId, $now, $now, $condition, $operation, $userFullName, $datetimeModif, $notes, $bedsPatientsId]);

// Redirigir según el resultado
if ($result) {
    // Redirigir con los parámetros de habitación, unidad y facility
    header("Location: load_beds.php?patient_id" . urlencode($patientId) .
            "&room_id=" . urlencode($roomId) . 
            "&room_name=" . urlencode($roomName) . 
            "&unit_id=" . urlencode($unitId) . 
            "&unit_name=" . urlencode($unitName) . 
            "&facility_id=" . urlencode($facilityId) . 
            "&facility_name=" . urlencode($facilityName) . 
            "&bed_action=" . urlencode($bedAction) . 
            "&background_card=" . urlencode($backgroundPatientCard));
    exit();
} else {
    header("Location: load_beds.php?status=error");
}
exit();
?>


