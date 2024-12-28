<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

header('Content-Type: application/json');

$query = $_GET['query'] ?? '';

// SQL para buscar paciente, ubicación actual, fechas y estado de la cama
$sql = "
    SELECT 
        p.pid, 
        CONCAT(p.lname, ' ', COALESCE(p.fname, ''), ' ', p.mname) AS patient_full_name,
        r.room_name,
        u.unit_name,
        f.name AS facility_name,
        bp.bed_id,
        bp.assigned_date,
        bp.change_date,
        bp.condition,
        bp.active
    FROM patient_data AS p
    INNER JOIN beds_patients AS bp ON p.pid = bp.patient_id
    LEFT JOIN rooms AS r ON bp.room_id = r.id
    LEFT JOIN units AS u ON bp.unit_id = u.id
    LEFT JOIN facility AS f ON bp.facility_id = f.id
    WHERE LOWER(CONCAT(p.lname, ' ', COALESCE(p.fname, ''), ' ', p.mname)) LIKE LOWER(?)
";

$binds = ["%$query%"];
$result = sqlStatement($sql, $binds);
$patients = [];

while ($row = sqlFetchArray($result)) {
    $pid = $row['pid'];

    // Obtener datos adicionales del paciente
    $patientData = getPatientData($pid, "DOB, sex, pubpid");
    $bedPatientAge = getPatientAge(str_replace('-', '', $patientData['DOB']));
    $bedPatientSex = text($patientData['sex']);
    $bedPatientDNI = text($patientData['pubpid']);

    // Obtener datos de aseguradora
    $insuranceData = getInsuranceData($pid, "primary", "insd.*, ic.name as provider_name");
    $bedPatientInsuranceName = text($insuranceData['provider_name']);

    // Agregar los datos al array de resultados
    $patients[] = [
        'pid' => $row['pid'],
        'text' => $row['patient_full_name'],
        'room_name' => $row['room_name'],
        'unit_name' => $row['unit_name'],
        'facility_name' => $row['facility_name'],
        'DOB' => $patientData['DOB'],
        'age' => $bedPatientAge,
        'sex' => xlt($bedPatientSex),
        'pubpid' => $bedPatientDNI,
        'insurance' => $bedPatientInsuranceName,
        'assigned_date' => $row['assigned_date'] ? oeTimestampFormatDateTime(strtotime($row['assigned_date'])) : '',
        'change_date' => $row['change_date'] ? oeTimestampFormatDateTime(strtotime($row['change_date'])) : '',
        'condition' => $row['condition'],
        'active' => $row['active']
    ];
}

// Retornar resultados en formato JSON
echo json_encode(['results' => $patients]);

?>
