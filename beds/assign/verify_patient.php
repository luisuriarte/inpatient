<?php
// Incluir el archivo de funciones si es necesario
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si se ha seleccionado un paciente
$patientSelected = !empty($patient_id) && !empty($patient_name);

// Enviar respuesta en formato JSON
header('Content-Type: application/json');
echo json_encode(['patientSelected' => $patientSelected]);
?>
