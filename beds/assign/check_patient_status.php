<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');

$patientId = $_GET['patient_id'] ?? null;

if (!$patientId) {
    echo json_encode(['status' => 'error', 'message' => xl('No patient ID provided')]);
    exit;
}

// Obtener el nombre del paciente desde patient_data (opcional, para incluirlo en la respuesta)
$queryName = "SELECT fname, lname FROM patient_data WHERE pid = ?";
$patientResult = sqlQuery($queryName, [$patientId]);
$patientName = $patientResult ? ($patientResult['fname'] . ' ' . $patientResult['lname']) : 'Unknown';

// Verificar si el paciente está internado
$query = "SELECT pd.pid 
          FROM beds_patients bp
          JOIN patient_data pd ON bp.patient_id = pd.pid
          WHERE bp.patient_id = ? 
          AND bp.condition = 'Occupied' 
          AND bp.active = 1";
$result = sqlQuery($query, [$patientId]);

$isAdmitted = !empty($result['pid']);
if ($isAdmitted) {
    $message = xl('The patient') . ' ' . htmlspecialchars($patientName) . ' ' . xl('is admitted, please choose another or Cancel');
    echo json_encode(['status' => 'admitted', 'message' => $message]);
} else {
    echo json_encode(['status' => 'available']);
}
exit;