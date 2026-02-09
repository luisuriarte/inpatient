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
$fromIdBedsPatients = $_GET['from_id_beds_patients'] ?? null;
$fromBedId = $_GET['from_bed_id'] ?? null;
$fromRoomId = $_GET['from_room_id'] ?? null;
$fromUnitId = $_GET['from_unit_id'] ?? null;
$fromFacilityId = $_GET['from_facility_id'] ?? null;

// Almacenar contexto en sesión
$context = [
    'facility_id' => $_GET['facility_id'] ?? ($_SESSION[$sessionKey]['context']['facility_id'] ?? null),
    'facility_name' => $_GET['facility_name'] ?? ($_SESSION[$sessionKey]['context']['facility_name'] ?? null),
    'unit_id' => $_GET['unit_id'] ?? ($_SESSION[$sessionKey]['context']['unit_id'] ?? null),
    'unit_name' => $_GET['unit_name'] ?? ($_SESSION[$sessionKey]['context']['unit_name'] ?? null),
    'background_card' => $_GET['background_card'] ?? ($_SESSION[$sessionKey]['context']['background_card'] ?? null),
];
$_SESSION[$sessionKey]['context'] = $context;

// Datos del paciente: Priorizar GET, luego sesión local, luego sesión global de OpenEMR
$patient_id = $_GET['patient_id'] ?? $_GET['patient_id_relocate'] ?? $_SESSION[$sessionKey]['patient_id'] ?? $_SESSION['pid'] ?? null;
$patient_name = $_GET['patient_name'] ?? $_GET['patient_name_relocate'] ?? $_SESSION[$sessionKey]['patient_name'] ?? $_SESSION['patient_name'] ?? null;

// Si tenemos ID pero no nombre, buscarlo
if ($patient_id && empty($patient_name)) {
    $patient_res = getPatientData($patient_id, "fname, lname, pubpid");
    if ($patient_res) {
        $patient_name = $patient_res['fname'] . ' ' . $patient_res['lname'];
        $pubpid = $patient_res['pubpid'];
    }
}

$patientData = [
    'id' => $patient_id,
    'name' => $patient_name ?? 'Unknown',
    'dni' => $_GET['patient_dni_relocate'] ?? ($_SESSION[$sessionKey]['patient_dni'] ?? 'Unknown'),
    'age' => $_GET['patient_age_relocate'] ?? ($_SESSION[$sessionKey]['patient_age'] ?? 'Unknown'),
    'sex' => $_GET['patient_sex_relocate'] ?? ($_SESSION[$sessionKey]['patient_sex'] ?? 'Unknown'),
    'insurance' => $_GET['insurance_name_relocate'] ?? ($_SESSION[$sessionKey]['insurance_name'] ?? 'Unknown'),
];

// Sincronizar con el subespacio de sesión para persistencia
if ($patientData['id'] && $patientData['id'] !== 'Unknown') {
    $_SESSION[$sessionKey]['patient_id'] = $patientData['id'];
    $_SESSION[$sessionKey]['patient_name'] = $patientData['name'];
    if (isset($pubpid)) $_SESSION[$sessionKey]['patient_pubpid'] = $pubpid;
    if ($patientData['dni'] !== 'Unknown') $_SESSION[$sessionKey]['patient_dni'] = $patientData['dni'];
    if ($patientData['age'] !== 'Unknown') $_SESSION[$sessionKey]['patient_age'] = $patientData['age'];
    if ($patientData['sex'] !== 'Unknown') $_SESSION[$sessionKey]['patient_sex'] = $patientData['sex'];
    if ($patientData['insurance'] !== 'Unknown') $_SESSION[$sessionKey]['insurance_name'] = $patientData['insurance'];
}

// Título según la acción
$bedActionTitle = ($bedAction === 'Relocation') ? xlt('Patient Relocate') : (($bedAction === 'Management') ? xlt('Inpatient Management Board') : xlt('Beds Assign'));

// ==========================================
// MANEJO DE ACCIONES POST (MADE UP)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'made_up') {
    $bedId = intval($_POST['bed_id']);
    
    try {
        // Verificar que no haya paciente asignado a esta cama
        $checkQuery = "SELECT id FROM beds_patients 
                      WHERE current_bed_id = ? 
                      AND status IN ('preadmitted', 'admitted')
                      LIMIT 1";
        $hasPatient = sqlQuery($checkQuery, [$bedId]);
        
        if ($hasPatient) {
            throw new Exception("Cannot mark as vacant - bed is currently assigned to a patient");
        }
        
        // Marcar cama como vacant
        insertBedStatusLog($bedId, 'vacant', $userId, null, 'Bed cleaned and marked as vacant (Made Up)');
        
        // Mensaje de éxito
        $_SESSION['success_message'] = xlt('Bed successfully marked as vacant');
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    // Recargar la página para reflejar cambios
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Obtener datos del cuarto, unidad y camas
$query = "SELECT r.*, u.unit_name, lo.title AS floor_title, ls.title AS sector_title FROM rooms AS r 
          LEFT JOIN units AS u ON r.unit_id = u.id 
          LEFT JOIN list_options AS lo ON u.floor = lo.option_id AND lo.list_id = 'unit_floor'
          LEFT JOIN list_options AS ls ON r.sector = ls.option_id AND ls.list_id = 'room_sector'
          WHERE r.id = ?";
$result = sqlStatement($query, [$roomId]);
$room = sqlFetchArray($result);
$bedPatients = getBedsPatientsData($roomId);

$roomName = $room['room_name'] ?? 'Unknown';
$roomSector = $room['sector_title'] ?? $room['sector'] ?? 'Unknown';
$unitName = $room['unit_name'] ?? $context['unit_name'] ?? 'Unknown';
$unitId = $room['unit_id'] ?? $context['unit_id'] ?? null;
$unitFloor = $room['floor_title'] ?? $context['unit_floor'] ?? 'Unknown';
$facilityName = $context['facility_name'] ?? 'Unknown';
$facilityId = $context['facility_id'] ?? null;
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
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .ui-autocomplete {
            z-index: 1060;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($roomName); ?> - <?php echo $bedActionTitle; ?></title>
</head>
<body>

<!-- Mostrar mensajes de éxito/error -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

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

$additional_info = [
    "room_type" => ["label" => "Room Type", "value" => $room['room_type']],
    "isolation_level" => ["label" => "Isolation Level", "value" => $room['isolation_level']],
    "status" => ["label" => "Room Status", "value" => $room['status']]
];
?>

<div class="facility-info mb-4 text-center">
    <h4>
        <i class="fas fa-hospital-alt" style="color: #0d47a1;"></i> <?php echo htmlspecialchars($facilityName); ?> 
        <span class="mx-2">|</span>
        <i class="fas fa-layer-group" style="color: #00897b;"></i> <?php echo xl('Unit'); ?>: <?php echo htmlspecialchars($unitName); ?>
        <span class="mx-2">|</span>
        <i class="fas fa-stairs" style="color: #616161;"></i> <?php echo xl('Floor'); ?>: <?php echo htmlspecialchars($unitFloor); ?>
        <span class="mx-2">|</span>
        <i class="fas fa-door-open" style="color: #e65100;"></i> <?php echo xl('Room'); ?>: <?php echo htmlspecialchars($roomName); ?>
        <span class="mx-2">|</span>
        <i class="fas fa-map-marker-alt" style="color: #7b1fa2;"></i> <?php echo xl('Sector'); ?>: <?php echo htmlspecialchars($roomSector); ?>
    </h4>
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

                            <?php 
                                $conditions = array_column($bedPatient['conditions'], 'title'); 
                                $startText = in_array('Occupied', $conditions) ? xlt('Start') : (in_array('Reserved', $conditions) ? xlt('Since') : '');
                            ?>
                            <div class="assignment-info d-flex justify-content-between mt-2">
                                <span class="assign-detail fw-bold">
                                    <?= $startText ?>: <?= oeTimestampFormatDateTime(strtotime($bedPatient['assigned_date'])) ?>
                                </span>
                                <span class="duration-detail fw-bold">
                                    <?= htmlspecialchars($bedPatient['total_hours_days']) ?>
                                </span>
                            </div>

                            <?php if (!empty($bedPatient['bed_patient_care'])): ?>
                                <div class="patient-care mt-2">
                                    <div class="text-center">
                                        <img src="<?= htmlspecialchars($bedPatient['bed_patient_care']['care_icon']) ?>" class="care-icon mb-1" alt="Care Icon">
                                        <span class="fw-bold d-block"><?= htmlspecialchars(xl($bedPatient['bed_patient_care']['care_title'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?><br>

                        <!-- Botones según la condición de la cama -->
                        <?php $conditions = array_column($bedPatient['conditions'], 'key'); ?>

                        <?php if (in_array('vacant', $conditions)): ?>
                            <?php if ($bedAction === 'Relocation'): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#relocateBedPatientModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Select'); ?>
                                </button>
                            <?php elseif ($bedAction === 'Assign'): ?>
                                <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                                <button id="reserveBtn" type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                    <?php echo xlt('Reserve'); ?>
                                </button>
                            <?php endif; ?>

                        <?php elseif (in_array('reserved', $conditions)): ?>
                            <?php if ($bedAction === 'Relocation'): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#relocateBedPatientModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Select'); ?>
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reserveInfoModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Info'); ?>
                                </button>
                            <?php elseif ($bedAction === 'Assign'): ?>
                                <button id="assignBtn" type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                    <?php echo xlt('Assign'); ?>
                                </button>
                                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalBedRelease<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Release'); ?>
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reserveInfoModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Info'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#reserveInfoModal<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Info'); ?>
                                </button>
                            <?php endif; ?>

                        <?php elseif (in_array('cleaning', $conditions)): ?>
                            <?php if ($bedAction !== 'Relocation'): ?>
                                <div class="d-inline-block">
                                    <!-- Made Up disponible en Assign y Management -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="made_up">
                                        <input type="hidden" name="bed_id" value="<?php echo htmlspecialchars($bedPatient['bed_id']); ?>">
                                        <button type="submit" class="btn btn-success"><?php echo xlt('Made Up'); ?></button>
                                    </form>
                                    
                                    <?php if ($bedAction === 'Assign'): ?>
                                        <button type="button" class="btn btn-primary" onclick="checkPatientSelected('assign', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                            <?php echo xlt('Assign'); ?>
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="checkPatientSelected('reserve', '<?php echo htmlspecialchars($bedPatient['id']); ?>')">
                                            <?php echo xlt('Reserve'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                        <?php elseif (in_array('occupied', $conditions)): ?>
                            <?php if ($bedAction === 'Management'): ?>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalDischargePatient<?= $bedPatient['id'] ?>">
                                    <?php echo xlt('Discharge'); ?>
                                </button>
                                <a href="assign_bed.php?from_id_beds_patients=<?= urlencode(trim($bedPatient['bp_id'])) ?>&patient_id_relocate=<?= urlencode(trim($bedPatient['bed_patient_id'])) ?>&patient_name_relocate=<?= urlencode(trim($bedPatient['bed_patient_name'])) ?>&patient_dni_relocate=<?= urlencode(trim($bedPatient['bed_patient_dni'])) ?>&patient_age_relocate=<?= urlencode(trim($bedPatient['bed_patient_age'])) ?>&patient_sex_relocate=<?= urlencode(trim($bedPatient['bed_patient_sex'])) ?>&insurance_name_relocate=<?= urlencode(trim($bedPatient['bed_patient_insurance_name'])) ?>&from_bed_id=<?= urlencode(trim($bedPatient['id'])) ?>&from_room_id=<?= urlencode(trim($roomId)) ?>&from_unit_id=<?= urlencode(trim($unitId)) ?>&from_facility_id=<?= urlencode(trim($facilityId)) ?>&bed_action=Relocation" class="btn btn-light">
                                    <?php echo xlt('Relocate'); ?>
                                </a>
                            <?php endif; ?>
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

    <a href="assign_bed.php?view=rooms&facility_id=<?= htmlspecialchars($context['facility_id']) ?>&unit_id=<?= htmlspecialchars($context['unit_id']) ?>&bed_action=<?= htmlspecialchars($bedAction) ?>&from_id_beds_patients=<?= htmlspecialchars($fromIdBedsPatients ?? '') ?>&patient_id_relocate=<?= htmlspecialchars($patientData['id'] ?? '') ?>&patient_name_relocate=<?= htmlspecialchars($patientData['name'] ?? '') ?>" class="btn btn-secondary mt-3">
        <i class="fas fa-arrow-left"></i> <?= xlt('Back to Rooms'); ?>
    </a>
    
    <a href="assign.php" class="btn btn-primary mt-3 ms-2">
        <i class="fas fa-home"></i> <?= xlt('Principal Board'); ?>
    </a>
</div>

<script src="../../functions.js"></script>
<script>
function checkPatientSelected(actionType, bedId) {
    const patientId = "<?php echo htmlspecialchars($patientData['id'] ?? ''); ?>";
    const patientName = "<?php echo htmlspecialchars($patientData['name'] ?? ''); ?>";

    if (!patientId || !patientName || patientName === 'Unknown') {
        new bootstrap.Modal(document.getElementById('patientSelectionWarningModal')).show();
        return;
    }

    fetch(`check_patient_status.php?patient_id=${encodeURIComponent(patientId)}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'admitted') {
                alert(data.message);
                return;
            }
            if (data.status === 'available') {
                const modalId = (actionType === 'assign') ? `#assignBedPatientModal${bedId}` : `#reserveModal${bedId}`;
                const modalElement = document.querySelector(modalId);
                if (modalElement) {
                    new bootstrap.Modal(modalElement).show();
                } else {
                    alert("<?php echo xlt('Error: Modal not found for this bed.'); ?>");
                }
            } else {
                alert("<?php echo xlt('Error verifying patient status.'); ?>");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("<?php echo xlt('Error verifying patient status.'); ?>");
        });
}
</script>
</body>
</html>