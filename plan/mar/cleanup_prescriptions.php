<?php
// larry :: hack add for command line version
$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
$_SERVER['SERVER_NAME'] = 'localhost';
$backpic = "";

// for cron
if ($argc > 1 && empty($_SESSION['site_id']) && empty($_GET['site'])) {
    $c = stripos($argv[1], 'site=');
    if ($c === false) {
        echo xlt("Missing Site Id using default") . "\n";
        $argv[1] = "site=default";
    }
    $args = explode('=', $argv[1]);
    $_GET['site'] = isset($args[1]) ? $args[1] : 'default';
}
if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';

    $ignoreAuth = true;
}
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Conectar con la base de datos y obtener los eventos que deben desactivarse
$currentDate = date('Y-m-d H:i:s');

// Buscar todos los `schedule_id` cuyo último evento ya pasó más de 24 horas
$sql_query = "
    SELECT ps.schedule_id, ps.supply_id, sch.prescription_id
    FROM prescriptions_supply ps
    INNER JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
    WHERE (ps.status = 'Confirmed' OR ps.status = 'Suspended')
      AND ps.schedule_datetime <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
      AND ps.dose_number = ps.max_dose";

$result = sqlStatement($sql_query);

while ($row = sqlFetchArray($result)) {
    $schedule_id = $row['schedule_id'];
    $supply_id = $row['supply_id'];
    $prescription_id = $row['prescription_id'];

    // Desactivar el supply relacionado
    sqlStatement(
        "UPDATE prescriptions_supply 
         SET status = 'Ended', 
         modification_datetime = ?,
         active = 0
         WHERE schedule_id = ?", 
        [$currentDate, $schedule_id]
    );

    // Desactivar el schedule relacionado
    sqlStatement(
        "UPDATE prescriptions_schedule 
         SET modification_datetime = ?,
         `status` = 'Ended' 
         WHERE schedule_id = ?", 
        [$currentDate, $schedule_id]
    );

    // Desactivar la prescripción relacionada
    sqlStatement(
        "UPDATE prescriptions 
         SET active = 0, 
             note = 'Prescription ended: all doses administered', 
             date_modified = ?, 
             updated_by = 1 
         WHERE id = ?", 
        [$currentDate, $prescription_id]
    );
}

// Retornar un mensaje para verificar ejecución
echo "Cleanup completed at: " . $currentDate;
?>
