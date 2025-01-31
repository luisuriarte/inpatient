<?php
require_once("../../functions.php");
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

    // Campos adicionales
    $sector = $_POST['sector'];
    $roomType = $_POST['room_type'];
    $isolationLevel = $_POST['isolation_level'];
    $status = $_POST['status'];

    // Equipamiento médico
    $oxigen = isset($_POST['oxigen']) ? 1 : 0;
    $suction = isset($_POST['suction']) ? 1 : 0;
    $cardiacMonitor = isset($_POST['cardiac_monitor']) ? 1 : 0;
    $ventilator = isset($_POST['ventilator']) ? 1 : 0;
    $infusionPump = isset($_POST['infusion_pump']) ? 1 : 0;
    $defibrillator = isset($_POST['defibrillator']) ? 1 : 0;
    $physiotherapy = isset($_POST['physiotherapy']) ? 1 : 0;
    $cribHeater = isset($_POST['crib_heater']) ? 1 : 0;
    $airPurifier = isset($_POST['air_purifier']) ? 1 : 0;

    // Comodidades
    $wifi = isset($_POST['wifi']) ? 1 : 0;
    $television = isset($_POST['television']) ? 1 : 0;
    $entertainmentSystem = isset($_POST['entertainment_system']) ? 1 : 0;
    $personalizedMenu = isset($_POST['personalized_menu']) ? 1 : 0;
    $companionSpace = isset($_POST['companion_space']) ? 1 : 0;
    $privateBathroom = isset($_POST['private_bathroom']) ? 1 : 0;
    $friendlyDecor = isset($_POST['friendly_decor']) ? 1 : 0;
    $lightMode = isset($_POST['light_mode']) ? 1 : 0;
    $thermostat = isset($_POST['thermostat']) ? 1 : 0;

    // Preparar la consulta para actualizar la habitación
    $query = "UPDATE rooms 
              SET room_name = ?, number_of_beds = ?, obs = ?, active = ?, operation = ?, user_modif = ?, datetime_modif = ?,
                  sector = ?, room_type = ?, isolation_level = ?, status = ?, oxigen = ?, suction = ?, cardiac_monitor = ?,
                  ventilator = ?, infusion_pump = ?, defibrillator = ?, physiotherapy = ?, crib_heater = ?, air_purifier = ?,
                  wifi = ?, television = ?, entertainment_system = ?, personalized_menu = ?, companion_space = ?,
                  private_bathroom = ?, friendly_decor = ?, light_mode = ?, thermostat = ?
              WHERE id = ?";

    // Parámetros para la consulta
    $params = [
        $roomName, $numberOfBeds, $obs, $active, $operation, $userFullName, $datetimeModif,
        $sector, $roomType, $isolationLevel, $status, $oxigen, $suction, $cardiacMonitor,
        $ventilator, $infusionPump, $defibrillator, $physiotherapy, $cribHeater, $airPurifier,
        $wifi, $television, $entertainmentSystem, $personalizedMenu, $companionSpace,
        $privateBathroom, $friendlyDecor, $lightMode, $thermostat, $roomId
    ];

    // Ejecutar la consulta con los datos
    try {
        sqlStatement($query, $params);

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