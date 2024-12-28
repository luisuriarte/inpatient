<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verificar que se ha recibido el ID del cuarto
    if (!isset($_POST['bed_id']) || empty($_POST['bed_id'])) {
        echo "ID de cuarto no proporcionado.";
        exit();
    }

    // Obtener el centroId y centroName desde la sesión o el POST
    $centroId = $_POST['centro_id'];
    $centroName = $_POST['centro_name'];
    $unitId = $_POST['unit_id'];
    $unitName = $_POST['unit_name'];
    $roomId = $_POST['room_id'];
    $roomName = $_POST['room_name'];

    // Obtener los datos del formulario
    $bedId = intval($_POST['bed_id']);
    $bedName = $_POST['bed_name'];
    $bedType = $_POST['bed_type'];
    $bedStatus = $_POST['bed_status'];
    $obs = $_POST['obs'];
    $active = isset($_POST['active']) ? 1 : 0;
    $operation = 'Edit';
    $userId = $_SESSION['authUserID'];
    $userFullName = getuserFullName($userId);
    $datetimeModif = date('Y-m-d H:i:s');

// Preparar la consulta para actualizar la cama en la tabla beds
$query = "UPDATE beds 
          SET bed_name = ?, bed_type = ?, bed_status = ?, obs = ?, active = ?, operation = ?, user_modif = ?, datetime_modif = ?
          WHERE id = ?";

try {
    // Iniciar la transacción
    $database->StartTrans();

    // Ejecutar la consulta para actualizar la cama en beds
    sqlStatement($query, [$bedName, $bedType, $bedStatus, $obs, $active, $operation, $userFullName, $datetimeModif, $bedId]);

    // Buscar el registro más reciente en beds_patients con el bed_id correspondiente
    $querySelect = "SELECT id FROM beds_patients WHERE bed_id = ? ORDER BY datetime_modif DESC LIMIT 1";
    $recentBedPatient = sqlQuery($querySelect, [$bedId]);

    // Verificar si se encontró un registro reciente asociado al bed_id
    if ($recentBedPatient) {
        $bedPatientId = $recentBedPatient['id'];

        // Preparar la consulta para actualizar el registro más reciente en beds_patients
        $queryUpdate = "UPDATE beds_patients 
                        SET user_modif = ?, datetime_modif = ?, bed_name = ?, bed_type = ?, bed_status = ?, operation = ? 
                        WHERE id = ? AND bed_id = ?"; // Se asegura que el registro pertenece a la cama actual
        
        // Ejecutar la actualización con la verificación de bed_id
        sqlStatement($queryUpdate, [$userFullName, $datetimeModif, $bedName, $bedType, $bedStatus, 'Bed Edit', $bedPatientId, $bedId]);
    }

    // Confirmar la transacción si todo es exitoso
    $database->CompleteTrans();

    // Redirigir al formulario list_beds.php con los parámetros de la URL
    header("Location: list_beds.php?room_id=" . urlencode($roomId) . "&room_name=" . urlencode($roomName) . "&unit_id=" . urlencode($unitId) . "&unit_name=" . urlencode($unitName) . "&centro_id=" . urlencode($centroId) . "&centro_name=" . urlencode($centroName));
    exit;

} catch (Exception $e) {
    // Fallar la transacción en caso de error
    $database->FailTrans();
    // Mostrar el error si la consulta falla
    echo "Error al actualizar la cama: " . $e->getMessage();
}

} else {
    echo "Método de solicitud no permitido.";
}

?>
