<?php
require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Inicializar variables para los mensajes de advertencia
$warningMessage = '';

// Verificar si la solicitud es POST
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
//    echo '<pre>';
//    print_r($_POST);
//    echo '</pre>';
//    exit; // Agregar esto para detener la ejecución temporalmente
//}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        isset(
            $_POST['room_name'], $_POST['number_of_beds'], $_POST['unit_id'], $_POST['unit_name'], 
            $_POST['uuid'], $_POST['user_modif'], $_POST['operation'], $_POST['centro_id'], $_POST['centro_name'], 
        )
    ) {
        $roomName = trim($_POST['room_name']);
        $numberOfBeds = intval($_POST['number_of_beds']);
        $obs = trim($_POST['obs']);
        $centroId = intval($_POST['centro_id']);
        $centroName = trim($_POST['centro_name']);
        $unitId = intval($_POST['unit_id']);
        $unitName = trim($_POST['unit_name']);
        $uuid = trim($_POST['uuid']);
        $userModif = trim($_POST['user_modif']);
        $operation = trim($_POST['operation']);
        $sector = trim($_POST['sector']);
        $roomType = trim($_POST['room_type']);
        $isolationLevel = trim($_POST['isolation_level']);
        $status = trim($_POST['status']);
        $currentCapacity = intval($_POST['current_capacity']);
        $active = isset($_POST['active']) ? 1 : 0;
        $datetimeModif = date('Y-m-d H:i:s');

        // Características del cuarto (checkboxes, valores 0 o 1)
        $oxigen = isset($_POST['oxigen']) ? 1 : 0;
        $suction = isset($_POST['suction']) ? 1 : 0;
        $cardiacMonitor = isset($_POST['cardiac_monitor']) ? 1 : 0;
        $ventilator = isset($_POST['ventilator']) ? 1 : 0;
        $infusionPump = isset($_POST['infusion_pump']) ? 1 : 0;
        $defibrillator = isset($_POST['defibrillator']) ? 1 : 0;
        $cribHeater = isset($_POST['crib_heater']) ? 1 : 0;
        $airPurifier = isset($_POST['air_purifier']) ? 1 : 0;
        $physiotherapy = isset($_POST['physiotherapy']) ? 1 : 0;
        $wifi = isset($_POST['wifi']) ? 1 : 0;
        $television = isset($_POST['television']) ? 1 : 0;
        $entertainmentSystem = isset($_POST['entertainment_system']) ? 1 : 0;
        $personalizedMenu = isset($_POST['personalized_menu']) ? 1 : 0;
        $companionSpace = isset($_POST['companion_space']) ? 1 : 0;
        $privateBathroom = isset($_POST['private_bathroom']) ? 1 : 0;
        $friendlyDecor = isset($_POST['friendly_decor']) ? 1 : 0;
        $lightMode = isset($_POST['light_mode']) ? 1 : 0;
        $thermostat = isset($_POST['thermostat']) ? 1 : 0;

        // Validar datos
        if (empty($roomName) || empty($numberOfBeds) || empty($userModif) || empty($uuid) || empty($operation)) {
            $warningMessage = "Todos los campos obligatorios deben completarse.";
        } else {
            // Insertar los datos en la tabla `rooms`
            $query = "INSERT INTO rooms 
                (uuid, unit_id, facility_id, room_name, number_of_beds, sector, room_type, 
                isolation_level, status, current_capacity, oxigen, suction, cardiac_monitor, 
                ventilator, infusion_pump, defibrillator, crib_heater, air_purifier, physiotherapy, 
                wifi, television, entertainment_system, personalized_menu, companion_space, 
                private_bathroom, friendly_decor, light_mode, thermostat, obs, active, operation,
                user_modif, datetime_modif) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $result = sqlStatement($query, [
                $uuid, $unitId, $centroId, $roomName, $numberOfBeds, $sector, $roomType, 
                $isolationLevel, $status, $currentCapacity, $oxigen, $suction, $cardiacMonitor, 
                $ventilator, $infusionPump, $defibrillator, $cribHeater, $airPurifier, $physiotherapy, 
                $wifi, $television, $entertainmentSystem, $personalizedMenu, $companionSpace, 
                $privateBathroom, $friendlyDecor, $lightMode, $thermostat, $obs, $active, $operation, 
                $userModif, $datetimeModif
            ]);

            if ($result) {
                header("Location: list_rooms.php?unit_id=" . htmlspecialchars($unitId) . "&unit_name=" . htmlspecialchars($unitName) . "&centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
                exit;
            } else {
                $warningMessage = "Error al insertar el Cuarto.";
            }
        }
    } else {
        $warningMessage = "Campos requeridos faltantes.";
    }
}

