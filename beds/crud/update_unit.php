<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verificar que se ha recibido el ID de la unidad
    if (!isset($_POST['unit_id']) || empty($_POST['unit_id'])) {
        echo "ID de unidad no proporcionado.";
        exit();
    }
    // Obtener el centroId y centroName desde la sesión o el POST
    $centroId = $_POST['centroId'];
    $centroName = $_POST['centroName'];

    // Obtener los datos del formulario
    $unitId = intval($_POST['unit_id']);
    $unitName = $_POST['unit_name'];
    $numberOfRooms = $_POST['number_of_rooms'];
    $obs = $_POST['obs'];
    $active = isset($_POST['active']) ? 1 : 0;
    $operation = 'Edit';
    $userId = $_SESSION['authUserID'];
    $userFullName = getuserFullName($userId);
    $datetimeModif = date('Y-m-d H:i:s');
 
    // Preparar la consulta para actualizar la unidad
    $query = "UPDATE units 
              SET unit_name = ?, number_of_rooms = ?, obs = ?, active = ?, operation = ?, user_modif = ?, datetime_modif = ?
              WHERE id = ?";

    try {
        sqlStatement($query, array($unitName, $numberOfRooms, $obs, $active, $operation, $userFullName, $datetimeModif, $unitId));

        // Redirigir al formulario list_units.php con el ID y nombre del centro
        header("Location: list_units.php?centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
        exit;
     
    } catch (Exception $e) {
        // Mostrar el error si la consulta falla
        echo "Error al actualizar el cuarto: " . $e->getMessage();
        }
    
    } else {
    echo "Método de solicitud no permitido.";
    }
?>
    
