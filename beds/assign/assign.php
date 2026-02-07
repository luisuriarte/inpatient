<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Detectar paciente activo en la sesión de OpenEMR
// El ID y nombre del paciente ya deberían estar disponibles por el core de OpenEMR
$patient_id = isset($patient_id) ? $patient_id : ($_SESSION['pid'] ?? null);
$patient_name = isset($patient_name) ? $patient_name : '';

if ($patient_id && empty($patient_name)) {
    $patient_res = getPatientData($patient_id, "fname, lname");
    if ($patient_res) {
        $patient_name = $patient_res['fname'] . ' ' . $patient_res['lname'];
    }
}

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
    <div class="icon-container d-flex flex-wrap justify-content-center gap-4">
        <!-- Botón para Ingreso / Reserva (Requiere Paciente) -->
        <a href="assign_bed.php?patient_id=<?php echo urlencode($patient_id); ?>&patient_name=<?php echo urlencode($patient_name); ?>&bed_action=Assign" 
           class="btn btn-custom btn-primary-custom" 
           id="assignEntryBtn"
           onclick="return handleEntryClick(event, true)">
            <i class="fas fa-user-plus fa-2x mb-2"></i>
            <p><?php echo xl('Patient Check-In / Reserve'); ?></p>
        </a>

        <!-- Botón para Gestión de Internación / Tablero (No requiere Paciente) -->
        <a href="assign_bed.php?bed_action=Management" 
           class="btn btn-custom btn-success-custom" 
           id="managementEntryBtn">
            <i class="fas fa-chalkboard-user fa-2x mb-2"></i>
            <p><?php echo xl('Inpatient Management Board'); ?></p>
        </a>

        <!-- Botón para Buscar -->
        <a href="patient_search.php?patient_id=<?php echo urlencode($patient_id); ?>&patient_name=<?php echo urlencode($patient_name); ?>&bed_action=Search" 
           class="btn btn-custom btn-danger-custom">
            <i class="fas fa-search fa-2x mb-2"></i>
            <p><?php echo xl('Search Patient'); ?></p>
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
function handleEntryClick(event, requiresPatient) {
    const patientId = "<?php echo $patient_id; ?>";
    
    if (requiresPatient && (!patientId || patientId === "")) {
        event.preventDefault();
        alert("<?php echo xlt('Please select a patient first to proceed with Inpatient Check-In.'); ?>\n<?php echo xlt('Open Patient Finder Tab'); ?>");
        
        // Intentar enfocar la pestaña de Patient Finder en OpenEMR
        const tabIds = ['finder', 'pat_finder'];
        let success = false;
        try {
            for (const id of tabIds) {
                if (typeof top.focusTab === 'function') {
                    top.focusTab(id);
                    success = true;
                } else if (top.maintab && typeof top.maintab.openTab === 'function') {
                    top.maintab.openTab(id);
                    success = true;
                }
                if (success) break;
            }
        } catch (e) {
            console.error("Error al intentar cambiar a la pestaña Finder:", e);
        }
        return false;
    }
    return true;
}
</script>
</body>
</html>
