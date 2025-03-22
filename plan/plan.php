<?php

require_once("../functions.php");
require_once('../../interface/globals.php');

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// The patient ID and name should be already available
$patient_id = isset($patient_id) ? $patient_id : null;
$patient_name = isset($patient_name) ? $patient_name : '';
$title_patient_name = $patient_name;
$title_patient_dni = $patient_dni;
$title_patient_age = $patient_age;
$title_patient_sex = $patient_sex;
$title_insurance_name = $insurance_name;

// Verificar si se ha seleccionado un paciente
//if (empty($patient_id) || empty($patient_name)) {
//    echo "<script>
//        alert('" . xla('First you must choose a patient') . "');
//        window.location.href = '../../../interface/main/finder/dynamic_finder.php';
//    </script>";
//    exit;
//}
$backgroundPatientCard = "#f6f9bc";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewports" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Care Board'); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css"> <!-- Enlace al archivo CSS externo -->
    <style>
        :root {
            --background-color: <?php echo $backgroundPatientCard; ?>;
        }
    </style>
</head>
<body>

<div class="container mt-4">
    <?php
        // Validar que al menos una variable tenga un valor no vacío y sin espacios en blanco
    //    if (trim($patient_id) !== '' || trim($patient_name) !== '') {
            include '../patient_header.html';
    //    }
    ?>
    <!-- Iconos del Pizarrón -->
	<div class="icon-container">
		<!-- Botón para MAR -->
		<button class="btn btn-custom btn-primary-custom" onclick="window.location.href='mar/mar.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>'">
            <i class="fa-solid fa-bed-pulse fa-2x mb-2 fa-2x mb-2"></i>
			<p><?php echo xl('Medical Administration Recod (MAR)'); ?></p>
		</button>

		<!-- Botón para Care Plan -->
		<button class="btn btn-custom btn-secondary-custom" onclick="window.location.href='../../interface/main/main_screen.php?auth=login&site=<?php echo attr_url($_SESSION['site_id']); ?>'">
			<i class="fa-solid fa-kit-medical fa-2x mb-2"></i>
			<p><?php echo xl('Care Plan Overview'); ?></p>
		</button>

		<!-- Botón para Patient Assessment -->
		<button class="btn btn-custom btn-danger-custom" onclick="window.location.href='patient_assessment.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>'">
			<i class="fa-solid fa-heart-pulse fa-2x mb-2"></i>
			<p><?php echo xl('Patient Assessment'); ?></p>
		</button>

		<!-- Botón para Care Plan Report -->
		<button class="btn btn-custom btn-succes-custom" onclick="window.location.href='care_plan_report.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>'">
			<i class="fa-solid fa-id-card-clip fa-2x mb-2"></i>
			<p><?php echo xl('Care Plan Report'); ?></p>
		</button>
	</div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>

</body>
</html>
