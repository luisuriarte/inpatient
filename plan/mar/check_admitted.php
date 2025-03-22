<?php
require_once("../../../interface/globals.php");

// Función para verificar si el paciente está internado
function isPatientAdmitted($pid) {
    $sql = "
        SELECT COUNT(*) as admitted
        FROM beds_patients bp
        WHERE bp.patient_id = ?
        AND bp.condition = 'occupied'
        AND bp.active = 1
    ";
    $result = sqlQuery($sql, [$pid]);
    return $result['admitted'] > 0;
}

$pid = $_POST['pid'] ?? null;
$response = ['isAdmitted' => false];

if ($pid) {
    $response['isAdmitted'] = isPatientAdmitted($pid);
}

header('Content-Type: application/json');
echo json_encode($response);
exit;