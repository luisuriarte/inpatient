<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

$roomId = $_GET['room_id'] ?? null;
$roomName = $_GET['room_name'] ?? null;
$unitId = $_GET['unit_id'] ?? null;
$unitName = $_GET['unit_name'] ?? null;
$facilityId = $_GET['facility_id'] ?? null;
$facilityName = $_GET['facility_name'] ?? null;
$bedAction = $_GET['bed_action'] ?? null;
$backgroundPatientCard = htmlspecialchars($_GET['background_card']) ?? null;
$patientIdRelocate = htmlspecialchars($_GET['patient_id_relocate']) ?? null;
$patientDniRelocate = htmlspecialchars($_GET['patient_dni_relocate']) ?? null;
$patientNameRelocate = htmlspecialchars($_GET['patient_name_relocate']) ?? null;
$patientAgeRelocate = htmlspecialchars($_GET['patient_age_relocate']) ?? null;
$patientSexRelocate = htmlspecialchars($_GET['patient_sex_relocate']) ?? null;
$insuranceNameRelocate = htmlspecialchars($_GET['insurance_name_relocate']) ?? null;
$fromIdBedsPatients = htmlspecialchars($_GET['from_id_beds_patients']) ?? null;
$fromBedId = htmlspecialchars($_GET['from_bed_id']) ?? null;
$fromRoomId = htmlspecialchars($_GET['from_room_id']) ?? null;
$fromUnitId = htmlspecialchars($_GET['from_unit_id']) ?? null;
$fromFacilityId = htmlspecialchars($_GET['from_facility_id']) ?? null;

switch ($bedAction) {
    case 'Assign':
        $bedActionTitle = xlt('Beds Assign');
        $title_patient_name = $patient_name ?? 'Unknown'; // Asegúrate de tener valores predeterminados si falta la información
        $title_patient_dni = $patient_dni ?? 'Unknown';
        $title_patient_age = $patient_age ?? 'Unknown';
        $title_patient_sex = $patient_sex ?? 'Unknown';
        $title_insurance_name = $insurance_name ?? 'Unknown';
        break;
    case 'Relocation':
        $bedActionTitle = xlt('Patient Relocate');
        $title_patient_name = $patientNameRelocate ?? 'Unknown';
        $title_patient_dni = $patientDniRelocate ?? 'Unknown';
        $title_patient_age = $patientAgeRelocate ?? 'Unknown';
        $title_patient_sex = $patientSexRelocate ?? 'Unknown';
        $title_insurance_name = $insuranceNameRelocate ?? 'Unknown';
        break;
    default:
        $bedActionTitle = xlt('Manage Bed Assignments');
}

// Obtener las camas del cuarto seleccionado
$bedPatients = getBedsPatientsData($roomId);

//echo '<pre>'; // Opcional: Formatea la salida para facilitar la lectura
//var_dump($beds);
//echo '</pre>';
//echo 'Bed Action: ' . $bedAction;
//?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../../styles.css">
    <style>
        .ui-autocomplete {
            z-index: 1060;  /* O un valor más alto si es necesario */
         
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($roomName); ?> - <?php echo $bedActionTitle; ?></title>

</head>
<body>
<?php
 


$bedPatientId = 0;

// Verifica si los parámetros GET están disponibles
if (isset($_GET['room_id'], $_GET['room_name'], $_GET['unit_id'], $_GET['unit_name'], $_GET['facility_id'], $_GET['facility_name'])) {
    // Asignar los valores de $_GET
    $roomId = $_GET['room_id'];
    $roomName = $_GET['room_name'];
    $unitId = $_GET['unit_id'];
    $unitName = $_GET['unit_name'];
    $facilityId = $_GET['facility_id'];
    $facilityName = $_GET['facility_name'];
    $bedAction = $_GET['bed_action'];
    $backgroundPatientCard = htmlspecialchars($_GET['background_card']);

    // Verifica si se ha enviado el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bedPatientId'])) {
        // Actualiza el ID del paciente de la cama con el valor enviado
        $bedPatientId = intval($_POST['bedPatientId']);
        $userId = $_SESSION['authUserID']; // Obtener el ID del usuario de la sesión
        $userFullName = getUserFullName($userId); // Asegúrate de que esta función exista y devuelva el nombre del usuario

        // Actualiza el campo `condition` en la tabla `beds_patients`
        $query = "UPDATE beds_patients 
                  SET `condition` = ?, user_modif = ?, datetime_modif = NOW() 
                  WHERE id = ?";
        $result = sqlStatement($query, ['Vacant', $userFullName, $bedPatientId]);

        // Verifica si la consulta fue exitosa
        if ($result) {
            // Redirige a `load_beds.php` con los parámetros necesarios
            header("Location: load_beds.php?room_id=" . urlencode($roomId) . 
                   "&room_name=" . urlencode($roomName) . 
                   "&unit_id=" . urlencode($unitId) . 
                   "&unit_name=" . urlencode($unitName) . 
                   "&facility_id=" . urlencode($facilityId) . 
                   "&facility_name=" . urlencode($facilityName) . 
                   "&bed_action=" . urlencode($bedAction) . 
                   "&background_card=" . urlencode($backgroundPatientCard));
            exit;
        } else {
            echo "Error al actualizar la información.";
        }
    }
}
?>

<div class="container mt-4">

<?php
    if (!empty($patient_id) || !empty($patient_name)) {
        include '../../patient_header.html';
    }
?>
    <div class="facility-info mb-4">
        <h4><?php echo htmlspecialchars($facilityName) . ' - ' . xl('Unit') . ': ' . htmlspecialchars($unitId) . ' - ' . xl('Room') . ': ' . htmlspecialchars($roomName); ?></h4>
    </div>
    
    <?php if (!empty($bedPatients)): ?>
        <div class="row">
        <?php foreach ($bedPatients as $bedPatient): ?>
            <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card bed-card text-center" style="width: 18rem;">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-bed"></i>
                            <?php echo xl("Bed") . ' ' . htmlspecialchars($bedPatient['bed_name']); ?>
                        </h5>
                        <p class="card-text minimal-spacing">
                            <?php echo xl('Bed Type'); ?>: <strong><?php echo xlt(htmlspecialchars($bedPatient['bed_type'])); ?></strong>
                        </p>
                        <p class="card-text minimal-spacing">
                            <?php echo xl('Status'); ?>: 
                            <span style="color: <?php echo htmlspecialchars($bedPatient['status_color']); ?>; font-weight: bold;">
                                <?php echo xlt(htmlspecialchars($bedPatient['bed_status'])); ?>
                            </span>
                        </p>
                        <?php echo $bedPatient['id']; ?>
                        <div class="conditions">
                                <?php foreach ($bedPatient['conditions'] as $condition): ?>
                                    <p class="card-text minimal-spacing">
                                        <?php if ($condition['title'] === 'Occupied' || $condition['title'] === 'Reserved'): ?>
                                            <?php if ($bedPatient['bed_patient_sex'] === 'Male'): ?>
                                                <img src="../images/male_icon.svg" alt="Male Icon" class="me-2" style="width: 20px; height: 20px;">
                                            <?php elseif ($bedPatient['bed_patient_sex'] === 'Female'): ?>
                                                <img src="../images/female_icon.svg" alt="Female Icon" class="me-2" style="width: 20px; height: 20px;">
                                            <?php else: ?>
                                                <img src="../images/non_binary_icon.svg" alt="Non-Binary Icon" class="me-2" style="width: 20px; height: 20px;">
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <img src="<?php echo htmlspecialchars($condition['icon']); ?>" alt="<?php echo htmlspecialchars($condition['title']); ?>" style="color: <?php echo htmlspecialchars($condition['color']); ?>;">
                                        <br />
                                        <font color="<?php echo htmlspecialchars($condition['color']); ?>">
                                            <strong><?php echo xlt(htmlspecialchars($condition['title'])); ?></strong>
                                        </font>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <!-- Información del paciente ocupando la cama -->
                        <?php if (in_array('Occupied', array_column($bedPatient['conditions'], 'title')) || in_array('Reserved', array_column($bedPatient['conditions'], 'title'))): ?>
                            <div class="patient-info d-flex justify-content-between">
                                <span class="patient-detail fw-bold"><?= xl($bedPatient['bed_patient_sex']) ?></span>
                                <span class="patient-detail fw-bold"><?= htmlspecialchars($bedPatient['bed_patient_age']) ?> <?php echo xl('years'); ?></span>
                                <span class="patient-detail fw-bold"><?= htmlspecialchars($bedPatient['bed_patient_insurance_name']) ?></span>
                            </div>
                           <!-- Mostrar la fecha y hora de asignación y duración -->
						   <?php 
								$conditions = array_column($bedPatient['conditions'], 'title'); 
								$startText = in_array('Occupied', $conditions) ? xlt('Start') : (in_array('Reserved', $conditions) ? xlt('Since') : '');
							?>
                            <div class="assignment-info d-flex justify-content-between mt-2">
                                <span class="assign-detail fw-bold type="text">
                                <?= $startText ?>: <?= oeTimestampFormatDateTime(strtotime($bedPatient['assigned_date'])) ?>
                                </span>
                                <span class="duration-detail fw-bold">
                                    <?= htmlspecialchars($bedPatient['total_hours_days']) ?>
                                </span>
                            </div>
                            <!-- Mostrar el icono y título del cuidado del paciente -->
                            <?php if (!empty($bedPatient['bed_patient_care'])): ?>
                                <div class="patient-care mt-2">
                                    <!-- Imagen del ícono del cuidado -->
                                    <div class="text-center">
                                        <img src="<?= htmlspecialchars($bedPatient['bed_patient_care']['care_icon']) ?>" class="care-icon mb-1" alt="Care Icon">
                                        <!-- Título del cuidado debajo del ícono -->
                                        <span class="fw-bold d-block"><?= htmlspecialchars(xl($bedPatient['bed_patient_care']['care_title'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?></br>
                        <!-- Botones según la condición de la cama -->
                        <?php $conditions = array_column($bedPatient['conditions'], 'title'); ?>

                        <?php if (in_array('Vacant', $conditions)): ?>
                            <?php if ($bedAction === 'Relocation'): ?>
                                <!-- Cambiar Assign por Select y abrir relocate modal -->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#relocateBedPatientModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Select'); ?>
                                </button>
                            <?php else: ?>
                                <!-- Botón Assign normal si no es Relocate -->
                                <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign')">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                                <!-- Botón Reserve solo si no es Relocate -->
                                <button id="reserveBtn" type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve')">
                                    <?php echo xlt('Reserve'); ?>
                                </button>
                            <?php endif; ?>
                        <?php elseif (in_array('Reserved', $conditions)): ?>
                            <?php if ($bedAction === 'Relocation'): ?>
                                <!-- Cambiar Assign por Select y abrir relocate modal -->
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#relocateBedPatientModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Select'); ?>
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reserveInfoModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Info'); ?>
                                </button>
                            <?php else: ?>
                                <!-- Botón Assign normal si no es Relocate -->
                                <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign')">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                                <!-- Botón Release -->
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalBedRelease<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Release'); ?>
                                </button>
                                <!-- Botón Information -->
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reserveInfoModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Info'); ?>
                                </button>
                            <?php endif; ?>

                        <?php elseif (in_array('Cleaning', $conditions)): ?>
                            <?php if ($bedAction !== 'Relocation'): ?>
                                <div class="d-inline-block">
                                    <!-- Formulario para cambiar la condición de la cama -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="bedPatientId" id="bedPatientId" value="<?php echo htmlspecialchars($bedPatient['id']); ?>">
                                        <button type="submit" class="btn btn-success"><?php echo xlt('Made Up'); ?></button>
                                    </form>
                                    <!-- Botón Reserve solo si no es Relocate -->
                                    <button id="reserveBtn" type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve')">
                                        <?php echo xlt('Reserve'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>

                        <?php elseif (in_array('Occupied', $conditions)): ?>
                            <?php if ($bedAction !== 'Relocation'): ?>
                                <!-- Formulario para dar de alta al paciente solo si no es Relocate -->
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalDischargePatient<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Discharge'); ?>
                                </button>
                            <?php endif; ?>
                            <!-- Botón Relocate, oculto si es Relocation -->
                            <?php if ($bedAction !== 'Relocation'): ?>
                                <a href="assign_bed.php?from_id_beds_patients=<?= trim(htmlspecialchars($bedPatient['id'])) ?>
                                                    &patient_id_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_id'])) ?>
                                                    &patient_name_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_name'])) ?>
                                                    &patient_dni_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_dni'])) ?>
                                                    &patient_age_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_age'])) ?>
                                                    &patient_sex_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_sex'])) ?>
                                                    &insurance_name_relocate=<?= trim(htmlspecialchars($bedPatient['bed_patient_insurance_name'])) ?>
                                                    &from_bed_id=<?= trim(htmlspecialchars($bedPatient['bed_id'])) ?>
                                                    &from_room_id=<?= trim(htmlspecialchars($bedPatient['room_id'])) ?>
                                                    &from_unit_id=<?= trim(htmlspecialchars($bedPatient['unit_id'])) ?>
                                                    &from_facility_id=<?= trim(htmlspecialchars($bedPatient['facility_id'])) ?>
                                                    &bed_action=Relocation" 
                                class="btn btn-light">
                                    <?php echo xlt('Relocate'); ?>
                                </a>

                            <?php endif; ?>
                            <!-- Botón Information, siempre debe mostrarse -->
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#patientInfoModal<?= $bedPatient['id'] ?>">
                                <?php echo xlt('Info'); ?>
                            </button>
                        <?php endif; ?>
                        <!-- Incluyendo los modales -->
                        <?php include 'modal_bed_reserve.php'; ?>
                        <?php include 'modal_patient_info.php'; ?>
                        <?php include 'modal_reserved_info.php'; ?>
                        <?php include 'modal_bed_assign.php'; ?>
                        <?php include 'modal_patient_relocate.php'; ?>
                        <?php include 'modal_patient_discharge.php'; ?>
                        <?php include 'modal_responsible_alert.php'; ?>
                        <?php include 'modal_bed_release.php'; ?>
                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <!-- Modal para advertencia de paciente no seleccionado -->
        <div class="modal fade" id="patientSelectionWarningModal" tabindex="-1" aria-labelledby="patientSelectionWarningLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="patientSelectionWarningLabel"><?php echo xlt('Warning'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php echo xlt('It is necessary to select a patient to Assign or Reserve a bed'); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <p><?php echo xl('No beds available in this room.'); ?></p>
    <?php endif; ?>

    <!-- Enlace para volver a la vista de cuartos -->
    <a href="assign_bed.php?view=rooms&facility_id=<?= trim(htmlspecialchars($facilityId)); ?>
                &facility_name=<?= trim(htmlspecialchars($facilityName)); ?>
                &unit_id=<?= trim(htmlspecialchars($unitId)); ?>
                &unit_name=<?= trim(htmlspecialchars($unitName)); ?>
                &from_id_beds_patients=<?= trim(htmlspecialchars($fromIdBedsPatients)); ?>
                &patient_id_relocate=<?= trim(htmlspecialchars($patientIdRelocate)); ?>
                &patient_name_relocate=<?= trim(htmlspecialchars($patientNameRelocate)); ?>
                &patient_dni_relocate=<?= trim(htmlspecialchars($patientDniRelocate)); ?>
                &patient_age_relocate=<?= trim(htmlspecialchars($patientAgeRelocate)); ?>
                &patient_sex_relocate=<?= trim(htmlspecialchars($patientSexRelocate)); ?>
                &insurance_name_relocate=<?= trim(htmlspecialchars($insuranceNameRelocate)); ?>
                &bed_action=<?= trim(htmlspecialchars($bedAction)); ?>
                &background_card=<?= trim(htmlspecialchars($backgroundPatientCard)); ?>"
        class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> <?= xl('Back to Rooms'); ?>
    </a>
<script src="../../functions.js"></script>
<script>
function checkPatientSelected(actionType) {
    var patientId = "<?php echo isset($patient_id) ? $patient_id : ''; ?>";
    var patientName = "<?php echo isset($patient_name) ? $patient_name : ''; ?>";
    
    if (!patientId || !patientName) {
        // Mostrar el modal si no hay un paciente seleccionado
        var warningModal = new bootstrap.Modal(document.getElementById('patientSelectionWarningModal'));
        warningModal.show();
    } else {
        // Simular clic en los botones respectivos para abrir los modales
        if (actionType === 'assign') {
            document.getElementById('assignBtn').setAttribute('data-bs-toggle', 'modal');
            document.getElementById('assignBtn').setAttribute('data-bs-target', '#assignBedPatientModal<?= $bedPatient['id'] ?>');
            document.getElementById('assignBtn').click();
        } else if (actionType === 'reserve') {
            document.getElementById('reserveBtn').setAttribute('data-bs-toggle', 'modal');
            document.getElementById('reserveBtn').setAttribute('data-bs-target', '#reserveModal<?= $bedPatient['id'] ?>');
            document.getElementById('reserveBtn').click();
        }
    }
}
</script>
</body>
</html>