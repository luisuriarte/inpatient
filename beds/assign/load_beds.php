<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Iniciar sesión y usar un subespacio
session_start();
$sessionKey = 'bed_management';

// Obtener usuario autenticado
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Recoger parámetros GET esenciales
$roomId = $_GET['room_id'] ?? null;
$bedAction = $_GET['bed_action'] ?? 'Assign';

// Almacenar contexto en sesión
$context = [
    'facility_id' => $_GET['facility_id'] ?? ($_SESSION[$sessionKey]['context']['facility_id'] ?? null),
    'facility_name' => $_GET['facility_name'] ?? ($_SESSION[$sessionKey]['context']['facility_name'] ?? null),
    'unit_id' => $_GET['unit_id'] ?? ($_SESSION[$sessionKey]['context']['unit_id'] ?? null),
    'unit_name' => $_GET['unit_name'] ?? ($_SESSION[$sessionKey]['context']['unit_name'] ?? null),
    'background_card' => $_GET['background_card'] ?? ($_SESSION[$sessionKey]['context']['background_card'] ?? null),
];
$_SESSION[$sessionKey]['context'] = $context;

// Datos del paciente desde GET o sesión
$patientData = [
    'id' => $_GET['patient_id_relocate'] ?? ($_SESSION[$sessionKey]['patient_id'] ?? null),
    'name' => $_GET['patient_name_relocate'] ?? ($_SESSION[$sessionKey]['patient_name'] ?? 'Unknown'),
    'dni' => $_GET['patient_dni_relocate'] ?? ($_SESSION[$sessionKey]['patient_dni'] ?? 'Unknown'),
    'age' => $_GET['patient_age_relocate'] ?? ($_SESSION[$sessionKey]['patient_age'] ?? 'Unknown'),
    'sex' => $_GET['patient_sex_relocate'] ?? ($_SESSION[$sessionKey]['patient_sex'] ?? 'Unknown'),
    'insurance' => $_GET['insurance_name_relocate'] ?? ($_SESSION[$sessionKey]['insurance_name'] ?? 'Unknown'),
];

// Guardar datos del paciente en sesión si vienen de Relocation o Assign
if ($_GET['patient_id_relocate'] || $bedAction === 'Relocation') {
    $_SESSION[$sessionKey]['patient_id'] = $patientData['id'];
    $_SESSION[$sessionKey]['patient_name'] = $patientData['name'];
    $_SESSION[$sessionKey]['patient_dni'] = $patientData['dni'];
    $_SESSION[$sessionKey]['patient_age'] = $patientData['age'];
    $_SESSION[$sessionKey]['patient_sex'] = $patientData['sex'];
    $_SESSION[$sessionKey]['insurance_name'] = $patientData['insurance'];
}

// Título según la acción
$bedActionTitle = ($bedAction === 'Relocation') ? xlt('Patient Relocate') : xlt('Beds Assign');

// Obtener datos del cuarto y camas
$query = "SELECT * FROM rooms WHERE id = ?";
$result = sqlStatement($query, [$roomId]);
$room = sqlFetchArray($result);
$bedPatients = getBedsPatientsData($roomId);

?>

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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- Material Icons -->
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

<!-- Encabezado del paciente si existe -->
<?php if ($patientData['id']): ?>
    <div class="patient-header">
        <?php include 'patient_header.html'; ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.patient-header h2').innerText = 
                    'Patient: <?php echo htmlspecialchars($patientData['name']); ?> (ID: <?php echo htmlspecialchars($patientData['id']); ?>)';
                document.querySelector('.patient-header p').innerText = 
                    'User: <?php echo htmlspecialchars($userFullName); ?>';
            });
        </script>
    </div>
<?php endif; ?>

<?php
// Lista de características con sus iconos y clases de color
$features = [
    "oxigen" => ["name" => "Oxygen", "icon" => "air", "class" => "icon-oxygen"],
    "suction" => ["name" => "Suction", "icon" => "plumbing", "class" => "icon-plumbing"],
    "cardiac_monitor" => ["name" => "Cardiac Monitor", "icon" => "monitor_heart", "class" => "icon-monitor"],
    "ventilator" => ["name" => "Ventilator", "icon" => "heat_pump", "class" => "icon-fan"],
    "infusion_pump" => ["name" => "Infusion Pump", "icon" => "medication", "class" => "icon-medication"],
    "defibrillator" => ["name" => "Defibrillator", "icon" => "flash_on", "class" => "icon-flash"],
    "crib_heater" => ["name" => "Crib Heater", "icon" => "crib", "class" => "icon-crib"],
    "air_purifier" => ["name" => "Air Purifier", "icon" => "sync_alt", "class" => "icon-sync"],
    "physiotherapy" => ["name" => "Physiotherapy", "icon" => "fitness_center", "class" => "icon-fitness"],
    "wifi" => ["name" => "WiFi", "icon" => "wifi", "class" => "icon-wifi"],
    "television" => ["name" => "Television", "icon" => "tv", "class" => "icon-tv"],
    "entertainment_system" => ["name" => "Entertainment System", "icon" => "play_circle", "class" => "icon-play"],
    "personalized_menu" => ["name" => "Personalized Menu", "icon" => "restaurant_menu", "class" => "icon-menu"],
    "companion_space" => ["name" => "Companion Space", "icon" => "chair", "class" => "icon-chair"],
    "private_bathroom" => ["name" => "Private Bathroom", "icon" => "bathroom", "class" => "icon-bathroom"],
    "friendly_decor" => ["name" => "Friendly Decor", "icon" => "sentiment_very_satisfied", "class" => "icon-smile"],
    "light_mode" => ["name" => "Lighting Mode", "icon" => "light_mode", "class" => "icon-light"],
    "thermostat" => ["name" => "Thermostat", "icon" => "thermostat", "class" => "icon-thermostat"]
];

// Información adicional (label arriba, texto abajo)
$additional_info = [
    "sector" => ["label" => "Sector", "value" => $room['sector']],
    "room_type" => ["label" => "Room Type", "value" => $room['room_type']],
    "isolation_level" => ["label" => "Isolation Level", "value" => $room['isolation_level']],
    "status" => ["label" => "Room Status", "value" => $room['status']]
];

?>
<div class="facility-info mb-4 text-center">
    <h4><?php echo htmlspecialchars($facilityName) . ' - ' . xl('Unit') . ': ' . htmlspecialchars($unitId)  . ' - ' . xl('Floor') . ': ' . htmlspecialchars($unitFloor)  . ' - ' . xl('Room') . ': ' . htmlspecialchars($roomName) . ' - ' . xl('Sector') . ': ' . htmlspecialchars($roomSector); ?></h4>
</div>

<div class="bed-banner">
        <div class="bed-banner-info">
            <?php foreach ($additional_info as $info): ?>
                <div class="bed-banner-info-item">
                    <div class="bed-banner-info-label"><?php echo xlt($info['label']); ?></div>
                    <div class="bed-banner-info-value"><?php echo htmlspecialchars($info['value']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="bed-banner-features">
            <?php foreach ($features as $key => $feature): 
                $isActive = !empty($room[$key]);
                $colorClass = $isActive ? $feature['class'] : "text-muted";
                $textClass = $isActive ? "font-weight-bold" : "text-muted";
                $featureName = xlt($feature['name']);
                $words = explode(" ", $featureName);
            ?>
                <div class="bed-banner-feature-item">
                    <span class="material-icons <?php echo $colorClass; ?>"><?php echo $feature['icon']; ?></span>
                    <div class="bed-banner-feature-label <?php echo $textClass; ?>">
                        <?php if (count($words) > 1): ?>
                            <span><?php echo $words[0]; ?></span><span><?php echo $words[1]; ?></span>
                        <?php else: ?>
                            <span><?php echo $featureName; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

<div class="container mt-4">
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
                                <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                                <!-- Botón Reserve solo si no es Relocate -->
                                <button id="reserveBtn" type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
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
                                    <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
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
                                    <!-- Botón Assign -->
                                    <button type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                        <?php echo xlt('Assign'); ?>
                                    </button>
                                    <!-- Botón Reserve -->
                                    <button type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
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
    <a href="assign_bed.php?view=rooms&facility_id=<?= htmlspecialchars($context['facility_id']) ?>&unit_id=<?= htmlspecialchars($context['unit_id']) ?>" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> <?= xlt('Back to Rooms'); ?>
    </a>
</div>
<script src="../../functions.js"></script>
<script>
    function checkPatientSelected(actionType, bedId) {
    const patientId = "<?php echo htmlspecialchars($patientData['id'] ?? ''); ?>";
    const patientName = "<?php echo htmlspecialchars($patientData['name'] ?? ''); ?>";

    console.log('actionType:', actionType, 'bedId:', bedId);

    if (!patientId || !patientName || patientName === 'Unknown') {
        new bootstrap.Modal(document.getElementById('patientSelectionWarningModal')).show();
        return;
    }

    fetch(`check_patient_status.php?patient_id=${encodeURIComponent(patientId)}`)
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.status === 'admitted') {
                alert(data.message);
                return; // Asegura que no continúe
            }
            if (data.status === 'available') {
                const modalId = (actionType === 'assign') ? `#assignBedPatientModal${bedId}` : `#reserveModal${bedId}`;
                console.log('Intentando abrir modal:', modalId);
                const modalElement = document.querySelector(modalId);
                if (modalElement) {
                    console.log('Modal encontrado:', modalElement);
                    new bootstrap.Modal(modalElement).show();
                } else {
                    console.error('Modal no encontrado:', modalId);
                    alert("<?php echo xlt('Error: Modal not found for this bed. Contact support.'); ?>");
                }
            } else {
                console.error('Error en la respuesta del servidor:', data.message);
                alert("<?php echo xlt('Error verifying patient status. Please try again.'); ?>");
            }
        })
        .catch(error => {
            console.error('Error en la solicitud AJAX:', error);
            alert("<?php echo xlt('Error verifying patient status. Please try again.'); ?>");
        });
    }
</script>
</body>
</html>