<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Detectar paciente activo en la sesión de OpenEMR
$patient_id = isset($patient_id) ? $patient_id : ($_SESSION['pid'] ?? null);
$patient_name = isset($patient_name) ? $patient_name : '';

if ($patient_id && empty($patient_name)) {
    $patient_res = getPatientData($patient_id, "fname, lname");
    if ($patient_res) {
        $patient_name = $patient_res['fname'] . ' ' . $patient_res['lname'];
    }
}

$backgroundPatientCard = "#f6f9bc";

// ==========================================
// LÓGICA PARA "Patient Check-In / Reserve"
// ==========================================
$hasPreadmission = false;
$hasAdmission = false;
$checkInUrl = "assign_bed.php?patient_id=" . urlencode($patient_id) .
              "&patient_name=" . urlencode($patient_name) .
              "&bed_action=Assign";

if ($patient_id) {
    // Detectar si el paciente tiene una ADMISIÓN activa (prioritario sobre pre-admisión)
    $admissionQuery = "SELECT bp.id as beds_patients_id,
                              bp.current_bed_id,
                              bp.current_room_id,
                              bp.current_unit_id,
                              bp.facility_id,
                              b.bed_name,
                              r.room_name,
                              u.unit_name,
                              f.name as facility_name
                       FROM beds_patients bp
                       LEFT JOIN beds b ON bp.current_bed_id = b.id
                       LEFT JOIN rooms r ON bp.current_room_id = r.id
                       LEFT JOIN units u ON bp.current_unit_id = u.id
                       LEFT JOIN facility f ON bp.facility_id = f.id
                       WHERE bp.patient_id = ?
                       AND bp.status = 'admitted'
                       LIMIT 1";

    $admissionData = sqlQuery($admissionQuery, [$patient_id]);

    if ($admissionData && $admissionData['current_bed_id']) {
        // Tiene admisión con cama asignada
        $hasAdmission = true;
        $checkInUrl = "load_beds.php?room_id=" . urlencode($admissionData['current_room_id']) .
                      "&room_name=" . urlencode($admissionData['room_name']) .
                      "&unit_id=" . urlencode($admissionData['current_unit_id']) .
                      "&unit_name=" . urlencode($admissionData['unit_name']) .
                      "&facility_id=" . urlencode($admissionData['facility_id']) .
                      "&facility_name=" . urlencode($admissionData['facility_name']) .
                      "&bed_action=Assign" .
                      "&patient_id=" . urlencode($patient_id) .
                      "&patient_name=" . urlencode($patient_name) .
                      "&beds_patients_id=" . urlencode($admissionData['beds_patients_id']);
    } else {
        // Si no tiene admisión, verificar si tiene pre-admisión
        $preadmissionQuery = "SELECT bp.id as beds_patients_id,
                                     bp.current_bed_id,
                                     bp.current_room_id,
                                     bp.current_unit_id,
                                     bp.facility_id,
                                     b.bed_name,
                                     r.room_name,
                                     u.unit_name,
                                     f.name as facility_name
                              FROM beds_patients bp
                              LEFT JOIN beds b ON bp.current_bed_id = b.id
                              LEFT JOIN rooms r ON bp.current_room_id = r.id
                              LEFT JOIN units u ON bp.current_unit_id = u.id
                              LEFT JOIN facility f ON bp.facility_id = f.id
                              WHERE bp.patient_id = ?
                              AND bp.status = 'preadmitted'
                              LIMIT 1";

        $preadmissionData = sqlQuery($preadmissionQuery, [$patient_id]);

        if ($preadmissionData && $preadmissionData['current_bed_id']) {
            // Tiene pre-admisión con cama reservada
            $hasPreadmission = true;
            $checkInUrl = "load_beds.php?room_id=" . urlencode($preadmissionData['current_room_id']) .
                          "&room_name=" . urlencode($preadmissionData['room_name']) .
                          "&unit_id=" . urlencode($preadmissionData['current_unit_id']) .
                          "&unit_name=" . urlencode($preadmissionData['unit_name']) .
                          "&facility_id=" . urlencode($preadmissionData['facility_id']) .
                          "&facility_name=" . urlencode($preadmissionData['facility_name']) .
                          "&bed_action=Assign" .
                          "&patient_id=" . urlencode($patient_id) .
                          "&patient_name=" . urlencode($patient_name) .
                          "&beds_patients_id=" . urlencode($preadmissionData['beds_patients_id']);
        }
    }
}

// ==========================================
// LÓGICA PARA "Patient Check-Out / Relocate"
// ==========================================
$isAdmitted = false;
$managementUrl = "javascript:void(0);";

if ($patient_id) {
    // Detectar si el paciente tiene una ADMISIÓN activa (admitted)
    $admissionQuery = "SELECT bp.id as beds_patients_id,
                              bp.current_bed_id, 
                              bp.current_room_id, 
                              bp.current_unit_id, 
                              bp.facility_id,
                              b.bed_name,
                              r.room_name, 
                              u.unit_name, 
                              f.name as facility_name
                       FROM beds_patients bp
                       LEFT JOIN beds b ON bp.current_bed_id = b.id
                       LEFT JOIN rooms r ON bp.current_room_id = r.id
                       LEFT JOIN units u ON bp.current_unit_id = u.id
                       LEFT JOIN facility f ON bp.facility_id = f.id
                       WHERE bp.patient_id = ? 
                       AND bp.status = 'admitted'
                       LIMIT 1";
    
    $admissionData = sqlQuery($admissionQuery, [$patient_id]);
    
    if ($admissionData && $admissionData['current_bed_id']) {
        $isAdmitted = true;
        $managementUrl = "load_beds.php?room_id=" . urlencode($admissionData['current_room_id']) . 
                        "&room_name=" . urlencode($admissionData['room_name']) . 
                        "&unit_id=" . urlencode($admissionData['current_unit_id']) . 
                        "&unit_name=" . urlencode($admissionData['unit_name']) . 
                        "&facility_id=" . urlencode($admissionData['facility_id']) . 
                        "&facility_name=" . urlencode($admissionData['facility_name']) . 
                        "&bed_action=Management" . 
                        "&patient_id=" . urlencode($patient_id) .
                        "&patient_name=" . urlencode($patient_name) .
                        "&beds_patients_id=" . urlencode($admissionData['beds_patients_id']);
    }
}
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
    <link rel="stylesheet" href="../../styles.css">
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
        
        <!-- Botón 1: Patient Operations -->
        <a href="<?php echo $checkInUrl; ?>"
           class="btn btn-custom btn-primary-custom"
           id="patientOpsBtn"
           onclick="return handleCheckInClick(event)">
            <i class="fas fa-user-injured fa-2x mb-2"></i>
            <p><?php echo xl('Patient Operations'); ?></p>
        </a>

        <!-- Botón 2: Bed Operations -->
        <a href="assign_bed.php?bed_action=BedManagement"
           class="btn btn-custom btn-success-custom"
           id="bedOpsBtn">
            <i class="fas fa-bed fa-2x mb-2"></i>
            <p><?php echo xl('Bed Operations'); ?></p>
        </a>

        <!-- Botón 3: Search Patient -->
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
// ==========================================
// FUNCIÓN: Validar selección de paciente
// ==========================================
function validatePatientSelected() {
    const patientId = "<?php echo $patient_id ?? ''; ?>";
    
    if (!patientId || patientId === "") {
        alert("<?php echo xlt('Please select a patient first'); ?>\n<?php echo xlt('Open Patient Finder Tab'); ?>");
        
        // Intentar enfocar la pestaña de Patient Finder en OpenEMR
        const tabIds = ['finder', 'pat_finder'];
        try {
            for (const id of tabIds) {
                if (typeof top.focusTab === 'function') {
                    top.focusTab(id);
                    break;
                } else if (top.maintab && typeof top.maintab.openTab === 'function') {
                    top.maintab.openTab(id);
                    break;
                }
            }
        } catch (e) {
            console.error("Error al intentar cambiar a la pestaña Finder:", e);
        }
        return false;
    }
    return true;
}

// ==========================================
// FUNCIÓN: Manejar clic en Check-In / Reserve
// ==========================================
function handleCheckInClick(event) {
    // 1. Validar que haya paciente seleccionado
    if (!validatePatientSelected()) {
        event.preventDefault();
        return false;
    }

    // 2. Informar al usuario si tiene admisión o pre-admisión
    var hasAdmission = <?php echo $hasAdmission ? 'true' : 'false'; ?>;
    var hasPreadmission = <?php echo $hasPreadmission ? 'true' : 'false'; ?>;

    if (hasAdmission) {
        // El usuario será redirigido directamente a la cama asignada
        console.log("Patient has admission - redirecting to assigned bed");
    } else if (hasPreadmission) {
        // El usuario será redirigido directamente a la cama reservada
        console.log("Patient has preadmission - redirecting to reserved bed");
    } else {
        // El usuario navegará por Facility → Unit → Room
        console.log("Patient has no admission or preadmission - redirecting to facility selection");
    }

    return true;
}

// ==========================================
// FUNCIÓN: Manejar clic en Bed Operations
// ==========================================
function handleBedOpsClick(event) {
    // No se requiere validación específica para operaciones de cama
    return true;
}
</script>
</body>
</html>