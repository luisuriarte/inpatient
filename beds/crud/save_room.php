<?php
require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Inicializar variables para los mensajes de advertencia
$warningMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos existan y no estén vacíos
    if (isset($_POST['room_name'], $_POST['number_of_beds'], $_POST['obs'], $_POST['unit_id'], $_POST['unit_name'], $_POST['uuid'], $_POST['user_modif'], $_POST['operation'], $_POST['centro_id'], $_POST['centro_name'])) {
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
        $active = isset($_POST['active']) ? 1 : 0;
        $datetimeModif = date('Y-m-d H:i:s');

        // Obtener el límite de cuartos permitido desde la tabla `units`
        $queryUnit = "SELECT number_of_rooms FROM units WHERE id = ?";
        $unitResult = sqlStatement($queryUnit, [$unitId]);
        $unitData = sqlFetchArray($unitResult);
        $unitNumberOfRooms = $unitData['number_of_rooms'] ?? 0;

        // Contar los cuartos activos que no están marcados como eliminadas
        $queryRoomsCount = "SELECT COUNT(*) as count FROM rooms WHERE unit_id = ? AND active = 1 AND operation != 'Delete'";
        $roomsCountResult = sqlStatement($queryRoomsCount, [$unitId]);
        $roomsCountData = sqlFetchArray($roomsCountResult);
        $currentRoomsCount = $roomsCountData['count'] ?? 0;

        // Validar si la cantidad de cuartos excede el límite permitido
        if ($currentRoomsCount >= $unitNumberOfRooms) {
            // Codificar el mensaje de advertencia para pasarlo en la URL
            $warningMessage = urlencode("No se puede agregar el Cuarto. El número máximo de cuartos permitidos para esta Unidad es " . $unitNumberOfRooms . ".");
            header("Location: list_rooms.php?unit_id=" . htmlspecialchars($unitId) . "&unit_name=" . htmlspecialchars($unitName) . "&centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName) . "&warningMessage=" . $warningMessage . "&showWarning=true");
            exit;
        }

        // Validar datos
        if (empty($roomName) || empty($numberOfBeds) || empty($userModif) || empty($uuid) || empty($operation)) {
            $warningMessage = "Todos los campos obligatorios deben completarse.";
        } else {
            // Insertar los datos en la tabla `rooms`
            $query = "INSERT INTO rooms (uuid, unit_id, facility_id, room_name, number_of_beds, obs, active, operation, user_modif, datetime_modif)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = sqlStatement($query, [$uuid, $unitId, $centroId, $roomName, $numberOfBeds, $obs, $active, $operation, $userModif, $datetimeModif]);

            // Verificar si la inserción tuvo éxito
            if ($result) {
                // Redirigir al formulario list_rooms.php con el ID y nombre del centro
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