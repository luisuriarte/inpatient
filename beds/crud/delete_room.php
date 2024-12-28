<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si se recibió el ID del Cuarto
if (isset($_POST['room_id'])) {
    $centroId = $_POST['centro_id']; // Para redirección
    $centroName = $_POST['centro_name']; // Para redirección
    $unitId = $_POST['unit_id']; // Para redirección
    $unitName = $_POST['unit_name']; // Para redirección
    $roomId = intval($_POST['room_id']);
    $roomName = $_POST['room_name'];

    // Obtener ID y nombre del usuario de la sesión
    $userId = isset($_SESSION['authUserID']) ? $_SESSION['authUserID'] : null;
    if (!$userId) {
        die("Usuario no autenticado.");
    }

    $userFullName = getuserFullName($userId);

// Actualizar el registro en la base de datos utilizando sqlStatement()
$updateQuery = "UPDATE rooms SET active = 0, operation = 'Delete', user_modif = ?, datetime_modif = NOW() WHERE id = ?";

// Ejecutar la consulta pasando los parámetros correspondientes
$result = sqlStatement($updateQuery, [$userFullName, $roomId]);

// Verificar si la actualización fue exitosa
if ($result) {
    // Redirigir al formulario list_rooms.php con el ID y nombre de la Unidad
    header("Location: list_rooms.php?unit_id=" . htmlspecialchars($unitId) . "&unit_name=" . htmlspecialchars($unitName) . "&centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
    exit;
} else {
    // Mostrar un mensaje de error si la consulta falla
    echo "Error al actualizar la unidad.";
}

?>