<?php
require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si se recibió el ID de la unidad
if (isset($_POST['unit_id'])) {
    $centroId = $_POST['centro_id']; // Para redirección
    $centroName = $_POST['centro_name']; // Para redirección
    $unitId = intval($_POST['unit_id']);
    $unitName = $_POST['unit_name'];

    // Obtener ID y nombre del usuario de la sesión
    $userId = isset($_SESSION['authUserID']) ? $_SESSION['authUserID'] : null;
    if (!$userId) {
        die("Usuario no autenticado.");
    }

    $userFullName = getuserFullName($userId);
}

// Actualizar el registro en la base de datos utilizando sqlStatement()
$updateQuery = "UPDATE units SET active = 0, operation = 'Delete', user_modif = ?, datetime_modif = NOW() WHERE id = ?";

// Ejecutar la consulta pasando los parámetros correspondientes
$result = sqlStatement($updateQuery, [$userFullName, $unitId]);

// Verificar si la actualización fue exitosa
if ($result) {
    // Redirigir al formulario list_units.php con el ID y nombre del centro
    header("Location: list_units.php?centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
    exit;
} else {
    // Mostrar un mensaje de error si la consulta falla
    echo "Error al actualizar la unidad.";
}
?>