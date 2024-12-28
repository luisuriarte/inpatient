<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Inicializar variables para los mensajes de advertencia
$warningMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos existan y no estén vacíos
    if (isset($_POST['bed_name'], $_POST['bed_type'], $_POST['bed_status'], $_POST['obs'], $_POST['room_id'], $_POST['room_name'], $_POST['uuid'], $_POST['user_modif'], $_POST['operation'])) {
        $bedName = trim($_POST['bed_name']);
        $bedType = trim($_POST['bed_type']);
        $bedStatus = trim($_POST['bed_status']);
        $obs = trim($_POST['obs']);
        $centroId = intval($_POST['centro_id']);
        $centroName = trim($_POST['centro_name']);
        $unitId = intval($_POST['unit_id']);
        $unitName = trim($_POST['unit_name']);
        $roomId = intval($_POST['room_id']);
        $roomName = trim($_POST['room_name']);
        $uuid = trim($_POST['uuid']);
        $userModif = trim($_POST['user_modif']);
        $operation = trim($_POST['operation']);
        $active = isset($_POST['active']) ? 1 : 0;
        $datetimeModif = date('Y-m-d H:i:s');

        // Obtener el límite de camas permitido desde la tabla `rooms`
        $queryRoom = "SELECT number_of_beds FROM rooms WHERE id = ?";
        $roomResult = sqlStatement($queryRoom, [$roomId]);
        $roomData = sqlFetchArray($roomResult);
        $roomnumberofbeds = $roomData['number_of_beds'];

        // Contar las camas activas que no están marcadas como eliminadas
        $queryBedsCount = "SELECT COUNT(*) as count FROM beds WHERE room_id = ? AND active = 1 AND operation != 'Delete'";
        $bedsCountResult = sqlStatement($queryBedsCount, [$roomId]);
        $bedsCountData = sqlFetchArray($bedsCountResult);
        $currentBedsCount = $bedsCountData['count'];

        // Validar si la cantidad de camas excede el límite permitido
        if ($currentBedsCount >= $roomnumberofbeds) {
            // Codificar el mensaje de advertencia para pasarlo en la URL
            $warningMessage = urlencode("No se puede agregar la Cama. El número máximo de camas permitidas para este Cuarto es " . $roomnumberofbeds . ".");
            header("Location: list_beds.php?room_id=" . htmlspecialchars($roomId) . "&room_name=" . htmlspecialchars($roomName) . "&unit_id=" . htmlspecialchars($unitId) . "&unit_name=" . htmlspecialchars($unitName) . "&centro_id=" . htmlspecialchars($centroId) . "&centro_name=" . htmlspecialchars($centroName) . "&warningMessage=" . $warningMessage . "&showWarning=true");
            exit;
        }

        // Validar datos
        if (empty($bedName) || empty($bedType) || empty($bedStatus) || empty($userModif) || empty($uuid) || empty($operation)) {
            $warningMessage = "Campo obligatorio debe completarse.";
        } else {
            // Iniciar la transacción    
            $database->StartTrans();

            // Insertar los datos en la tabla `beds`
            $query = "INSERT INTO beds (uuid, room_id, unit_id, facility_id, bed_name, bed_type, bed_status, obs, active, operation, user_modif, datetime_modif)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $result = sqlStatement($query, [$uuid, $roomId, $unitId, $centroId, $bedName, $bedType, $bedStatus, $obs, $active, $operation, $userModif, $datetimeModif]);

            // Verificar si la inserción tuvo éxito
            if ($result) {
                // Obtener el ID de la nueva cama usando MAX(id)
                $bedIdQuery = sqlQuery("SELECT MAX(id) AS last_id FROM beds");
                $bedId = $bedIdQuery['last_id'];

                // Aquí falta la inserción de beds_patients, por lo tanto, agrego el código correspondiente
                $conditionQuery = "INSERT INTO beds_patients (bed_id, room_id, unit_id, facility_id, `condition`, user_modif, datetime_modif, bed_name, bed_status, bed_type, active)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
                $conditionParams = [$bedId, $roomId, $unitId, $centroId, 'Vacant', $userModif, $datetimeModif, $bedName, $bedStatus, $bedType, '1'];
                $conditionResult = sqlStatement($conditionQuery, $conditionParams);

                // Verificar el éxito de la inserción en bed_patients
                if ($conditionResult) {
                    $database->CompleteTrans(); // Confirmar la transacción
                    // Redirigir al formulario list_beds.php con el ID y nombre del centro
                    header("Location: list_beds.php?room_id=" . urlencode($roomId) . "&room_name=" . urlencode($roomName) . "&unit_id=" . urlencode($unitId) . "&unit_name=" . urlencode($unitName) . "&centro_id=" . urlencode($centroId) . "&centro_name=" . urlencode($centroName));
                    exit;
                } else {
                    $database->FailTrans(); // Fallar la transacción
                    $warningMessage = "Error al insertar los datos en bed_patients.";
                }
            } else {
                $database->FailTrans(); // Fallar la transacción
                $warningMessage = "Error al insertar la Cama.";
            }

            // Finalizar la transacción
            $database->CompleteTrans();
        }
    }
}

?>
