<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

$backgroundPatientCard = "#f6f9bc";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Main Board'); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <style>
        :root {
            --background-color: <?php echo $backgroundPatientCard; ?>;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <!-- Iconos del Pizarrón -->
    <div class="icon-container">
        <!-- Botón para Asignar Cama -->
        <a href="assign_bed.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>&bed_action=Assign" class="btn btn-custom btn-primary-custom">
            <i class="fas fa-bed fa-2x mb-2"></i>
            <p><?php echo xl('Rooms Board'); ?></p>
        </a>

        <!-- Botón para Mover Paciente -->
        <!--
        <a href="assign_bed.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>&bed_action=Relocation" class="btn btn-custom btn-secondary-custom">
            <i class="fas fa-arrows-alt fa-2x mb-2"></i>
            <p><?php echo xl('Patient Relocation'); ?></p>
        </a>
        -->
        <!-- Botón para Buscar -->
        <a href="patient_search.php?patient_id=<?php echo $patient_id; ?>&patient_name=<?php echo urlencode($patient_name); ?>&bed_action=Search" class="btn btn-custom btn-danger-custom">
            <i class="fas fa-search fa-2x mb-2"></i>
            <p><?php echo xl('Search Patient'); ?></p>
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
