<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verificar que se ha recibido el ID del cuarto
    if (!isset($_POST['room_id']) || empty($_POST['room_id'])) {
        echo "ID de cuarto no proporcionado.";
        exit();
    }
    // Obtener el centroId y centroName desde la sesión o el POST
    $centroId = $_POST['centro_id'];
    $centroName = $_POST['centro_name'];
    $unitId = $_POST['unit_id'];
    $unitName = $_POST['unit_name'];

    // Obtener los datos del formulario
    $roomId = intval($_POST['room_id']);
    $roomName = $_POST['room_name'];
    $numberOfBeds = $_POST['number_of_beds'];
    $obs = $_POST['obs'];
    $active = isset($_POST['active']) ? 1 : 0;
    $operation = 'Edit';
    $userId = $_SESSION['authUserID'];
    $userFullName = getuserFullName($userId);
    $datetimeModif = date('Y-m-d H:i:s');
 
    // Preparar la consulta para actualizar la unidad
    $query = "UPDATE rooms 
              SET room_name = ?, number_of_beds = ?, obs = ?, active = ?, operation = ?, user_modif = ?, datetime_modif = ?
              WHERE id = ?";

    // Ejecutar la consulta con los datos
    try {
    sqlStatement($query, array($roomName, $numberOfBeds, $obs, $active, $operation, $userFullName, $datetimeModif, $roomId));

    // Redirigir al formulario list_rooms.php con el ID y nombre de la unidad
    header("Location: list_rooms.php?unit_id=" . htmlspecialchars($unitId) . "&unit_name=" . htmlspecialchars($unitName) . "&centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
    exit;
     
    } catch (Exception $e) {
    // Mostrar el error si la consulta falla
    echo "Error al actualizar el cuarto: " . $e->getMessage();
    }

} else {
echo "Método de solicitud no permitido.";
}
?>
