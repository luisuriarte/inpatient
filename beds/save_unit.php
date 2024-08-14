<?php

// Incluir archivos necesarios
require_once 'functions.php';
require_once 'connection.php';

// Verificar si se recibió la solicitud POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
	$action = $_POST['action'];
    $unitName = $_POST['name'];
    $numberOfRooms = $_POST['number_of_rooms'];
    $obs = $_POST['obs'];
    $active = isset($_POST['active']) ? 0 : 1;

    // Obtener el centroId desde la sesión o el POST
    $centroId = $_POST['centro_id'];  // Asegúrate de que `centro_id` esté siendo enviado desde `add_unit.php`

    // Generar un UUID único
    //$uuid = generateUUID(); // Usando la función de UUID desde functions.php
	$uuid = $_POST['uuid'];

    // Obtener la información del usuario actual desde la sesión
    $userModif = $_POST['user_modif']; 
    $datetimeModif = date('Y-m-d H:i:s');

    // Insertar los datos en la tabla `units`
    $query = "INSERT INTO units (uuid, facility_id, name, number_of_rooms, obs, active, action, user_modif, datetime_modif)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->execute([$uuid, $centroId, $unitName, $numberOfRooms, $obs, $active, $action, $userModif, $datetimeModif]);

    // Redirigir al formulario list_units.php con el ID y nombre del facility
    header("Location: list_units.php?centro_id=$centroId&centro_name=" . urlencode($_POST['centro_name']));
    exit;
} else {
    // Si no se recibe una solicitud POST, redirigir de vuelta al formulario
    header('Location: add_unit.php');
    exit;
}
?>
