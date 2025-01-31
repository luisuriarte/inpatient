<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Inicializar variables para los mensajes de advertencia
$warningMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos existan y no estén vacíos
    if (isset($_POST['unit_name'], $_POST['number_of_rooms'], $_POST['obs'], $_POST['centro_id'], $_POST['centro_name'], $_POST['uuid'], $_POST['user_modif'], $_POST['operation'], $_POST['floor'])) {
        $unitName = trim($_POST['unit_name']);
        $numberOfRooms = intval($_POST['number_of_rooms']);
        $obs = trim($_POST['obs']);
        $centroId = intval($_POST['centro_id']);
        $centroName = trim($_POST['centro_name']);
        $uuid = trim($_POST['uuid']);
        $userModif = trim($_POST['user_modif']);
        $operation = trim($_POST['operation']);
        $floor = trim($_POST['floor']); // Nuevo campo Floor
        $active = isset($_POST['active']) ? 1 : 0;
        $datetimeModif = date('Y-m-d H:i:s');

        // Obtener el límite de unidades permitido desde la tabla `facility`
        $queryFacility = "SELECT facility_taxonomy FROM facility WHERE id = ?";
        $facilityResult = sqlStatement($queryFacility, [$centroId]);
        $facilityData = sqlFetchArray($facilityResult);
        $facilitynumberofunits = $facilityData['facility_taxonomy'];

        // Contar las unidades activas que no están marcadas como eliminadas
        $queryUnitsCount = "SELECT COUNT(*) as count FROM units WHERE facility_id = ? AND active = 1 AND operation != 'Delete'";
        $unitsCountResult = sqlStatement($queryUnitsCount, [$centroId]);
        $unitsCountData = sqlFetchArray($unitsCountResult);
        $currentUnitsCount = $unitsCountData['count'];

        // Validar si la cantidad de unidades excede el límite permitido
        if ($currentUnitsCount >= $facilitynumberofunits) {
            // Codificar el mensaje de advertencia para pasarlo en la URL
            $warningMessage = urlencode("No se puede agregar la unidad. El número máximo de unidades permitidas para este centro es " . $facilitynumberofunits . ".");
            header("Location: list_units.php?centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName) . "&warningMessage=" . $warningMessage . "&showWarning=true");
            exit;
        }

        // Validar datos
        if (empty($unitName) || empty($userModif) || empty($uuid) || empty($operation) || empty($floor)) {
            $warningMessage = "Todos los campos obligatorios deben estar completos.";
        } elseif (!is_int($numberOfRooms) || $numberOfRooms < 0) {
            $warningMessage = "El número de cuartos debe ser un número entero no negativo.";
        } else {
            // Insertar los datos en la tabla `units`
            $query = "INSERT INTO units (uuid, facility_id, unit_name, number_of_rooms, obs, active, operation, user_modif, datetime_modif, floor)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = sqlStatement($query, [$uuid, $centroId, $unitName, $numberOfRooms, $obs, $active, $operation, $userModif, $datetimeModif, $floor]);

            // Verificar si la inserción tuvo éxito
            if ($result) {
                // Redirigir al formulario list_units.php con el ID y nombre del centro
                header("Location: list_units.php?centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName));
                exit;
            } else {
                $warningMessage = "Error al insertar la Unidad.";
            }
        }
    }    
}