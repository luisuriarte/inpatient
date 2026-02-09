<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');
$pid = intval($_GET['pid']);

$sql = "SELECT move_date, reason, bed_name_from AS bed_from, bed_name_to AS bed_to, 
               user_modif AS user, notes 
        FROM beds_patients_tracker 
        WHERE patient_id = ? 
        ORDER BY move_date DESC";

$result = sqlStatement($sql, [$pid]);
$history = [];

while ($row = sqlFetchArray($result)) {
    $row['move_date'] = oeTimestampFormatDateTime(strtotime($row['move_date']));
    $history[] = $row;
}

echo json_encode($history);