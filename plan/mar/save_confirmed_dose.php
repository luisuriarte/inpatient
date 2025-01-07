<?php
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar los datos enviados por POST
$supply_id = $_POST['supply_id'] ?? null;
$infusion_datetime = $_POST['infusion_datetime'] ?? null;
$supplied_by = $_POST['supplied_by'] ?? null;
$dose_note = $_POST['dose_note'] ?? null;
$userId = $_SESSION['authUserID'];

// Validar que se proporcionaron los datos necesarios
if (!$supply_id || !$infusion_datetime || !$supplied_by) {
    die(xlt('Missing required information.'));
}

// Actualizar la dosis en la tabla `prescriptions_supply`
$sql = "
    UPDATE prescriptions_supply
    SET
        supply_datetime = ?,
        supplied_by = ?,
        status = 'Confirmed',
        confirmation_datetime = ?, 
        modified_by = ?,
        supply_notes = ?
    WHERE supply_id = ?
";
sqlStatement($sql, array($infusion_datetime, $supplied_by, $infusion_datetime, $userId, $dose_note, $supply_id));

// Confirmación de éxito
echo json_encode(['status' => 'success']);
?>
