<?php
// Ensure the user is authenticated and load the globals
require_once("../../functions.php");
require_once('../../../interface/globals.php');
require_once($GLOBALS['srcdir'] . '/patient.inc.php');
require_once($GLOBALS['srcdir'] . '/options.inc.php');

use OpenEMR\Core\Header;

// Iniciar la sesión si no está iniciada (normalmente OpenEMR lo hace, pero por seguridad)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si viene set_pid en la URL, actualizar la sesión
if (isset($_GET['set_pid']) && is_numeric($_GET['set_pid'])) {
    $_SESSION['pid'] = (int)$_GET['set_pid'];
    // Obtener el nombre del paciente
    $sql = "SELECT fname, lname FROM patient_data WHERE pid = ?";
    $result = sqlQuery($sql, [$_SESSION['pid']]);
    if ($result) {
        $_SESSION['patient_name'] = $result['fname'] . ' ' . $result['lname'];
    }
}

// Capturar valores del formulario
$selected_facility = $_POST['facility_id'] ?? '';
$selected_floor = $_POST['floor_id'] ?? '';
$selected_unit = $_POST['unit_id'] ?? '';
$selected_room = $_POST['room_id'] ?? '';

// Get the authenticated user information
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Obtener paciente de la sesión de OpenEMR
$patient_id = $_SESSION['pid'] ?? null;
$patient_name = $_SESSION['patient_name'] ?? '';
if ($patient_id && empty($patient_name)) {
    $patient_data = getPatientData($patient_id, "fname, lname");
    if ($patient_data) {
        $patient_name = $patient_data['fname'] . ' ' . $patient_data['lname'];
    }
}

// Consulta para obtener las opciones de Facility, Floor, Unit y Room
$facilities = sqlStatement("SELECT id, name FROM facility WHERE inactive = 0 ORDER BY name ASC");
$units = $selected_facility ? sqlStatement("SELECT id, unit_name FROM units WHERE facility_id = ? AND floor = ? AND active = 1", [$selected_facility, $selected_floor]) : [];
$floors = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'unit_floor' ORDER BY title ASC");
$rooms = $selected_unit ? sqlStatement("SELECT id, room_name FROM rooms WHERE unit_id = ?  AND active = 1", [$selected_unit]) : [];

// Modificar la consulta principal según los filtros
// Solo pacientes actualmente admitidos con dosis desde las últimas 24 horas hacia adelante
$filters = "WHERE bp.status = 'admitted' 
    AND ps.status != 'Cancelled'
    AND ps.schedule_datetime >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
if ($selected_facility) {
    $filters .= " AND bp.facility_id = $selected_facility";
}
if ($selected_floor) {
    $filters .= " AND u.floor = '$selected_floor'"; // Filtrar por piso
}
if ($selected_unit) {
    $filters .= " AND bp.current_unit_id = $selected_unit";
}
if ($selected_room) {
    $filters .= " AND bp.current_room_id = $selected_room";
}

$sql_query = "
SELECT ps.supply_id, ps.schedule_datetime, ps.alarm1_datetime, ps.alarm1_active, ps.alarm2_datetime, ps.alarm2_active, 
    ps.status, ps.dose_number, ps.max_dose, DATE_FORMAT(ps.schedule_datetime, '%h:%i %p') AS hs, supply_datetime,
    CONCAT(p.lname, ', ', p.fname, 
      IF(p.mname IS NOT NULL AND p.mname != '', CONCAT(' ', p.mname), '')
     ) AS patient_full_name, us.id,
    CONCAT(us.lname, ', ', us.fname, 
      IF(us.mname IS NOT NULL AND us.mname != '', CONCAT(' ', us.mname), '')
     ) AS user_full_name,
   bp.current_bed_id AS bed_id, bp.current_room_id AS room_id, bp.current_unit_id AS unit_id, bp.facility_id, bp.status AS inpatient,
   f.name AS facility_name, u.unit_name AS unit_name, r.room_name AS room_name, b.bed_name AS bed_name,
   ps.schedule_id, pn.id AS prescription_id
FROM prescriptions_supply ps
LEFT JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
LEFT JOIN prescriptions AS pn ON sch.prescription_id = pn.id
LEFT JOIN patient_data AS p ON sch.patient_id = p.pid
LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid
LEFT JOIN facility AS f ON f.id = bp.facility_id
LEFT JOIN units AS u ON u.id = bp.current_unit_id
LEFT JOIN rooms AS r ON r.id = bp.current_room_id
LEFT JOIN beds AS b ON b.id = bp.current_bed_id
LEFT JOIN users AS us ON us.id = supplied_by
$filters
ORDER BY ps.schedule_datetime;
";
$result = sqlStatement($sql_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Medical Administration Record (MAR)'); ?></title>

    <?php Header::setupHeader(['datetime-picker']); ?>
    
    <!-- Material Icons (no incluido en OpenEMR por defecto) -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Vis.js (no incluido en OpenEMR) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.js"></script>
    
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../../styles.css">
    
    <!-- Estilos para manejar z-index de modales -->
    <style>
        /* Modal principal custom */
        #marActionsModal {
            z-index: 99999 !important;
        }
        
        /* Modales Bootstrap en dynamicModalContainer deben estar por encima */
        #dynamicModalContainer .modal {
            z-index: 100000 !important;
        }
        #dynamicModalContainer .modal-backdrop {
            z-index: 99999 !important;
        }
        
        /* DateTimePicker debe estar por encima de todos los modales */
        .xdsoft_datetimepicker {
            z-index: 999999 !important;
        }
    </style>
    
    <script src="<?php echo $GLOBALS['webroot']; ?>/interface/main/left_nav.js"></script>
     
</head>

<body>
    <!-- Formulario de filtros -->
    <form method="POST" action="" class="mb-3">
        <div class="row g-2">
            <!-- Dropdown Facility -->
            <div class="col-auto">
                <label for="facility_id" class="form-label mb-1"><?php echo xlt('Select Facility'); ?></label>
                <select name="facility_id" id="facility_id" class="form-select form-select-sm" style="width: 200px;" onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Facilities'); ?></option>
                    <?php while ($facility = sqlFetchArray($facilities)) { ?>
                        <option value="<?php echo $facility['id']; ?>" 
                            <?php echo ($facility['id'] == $selected_facility) ? 'selected' : ''; ?>>
                            <?php echo text($facility['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Dropdown Floor -->
            <div class="col-auto">
                <label for="floor_id" class="form-label mb-1"><?php echo xlt('Select Floor'); ?></label>
                <select name="floor_id" id="floor_id" class="form-select form-select-sm" style="width: 150px;" <?php echo !$selected_facility ? 'disabled' : ''; ?> onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Floors'); ?></option>
                    <?php if ($selected_facility) {
                        while ($floor = sqlFetchArray($floors)) { ?>
                            <option value="<?php echo $floor['option_id']; ?>" 
                                <?php echo ($floor['option_id'] == $selected_floor) ? 'selected' : ''; ?>>
                                <?php echo text($floor['title']); ?>
                            </option>
                    <?php }
                    } ?>
                </select>
            </div>
            
            <!-- Dropdown Unit -->
            <div class="col-auto">
                <label for="unit_id" class="form-label mb-1"><?php echo xlt('Select Unit'); ?></label>
                <select name="unit_id" id="unit_id" class="form-select form-select-sm" style="width: 150px;" <?php echo !$selected_facility || !$selected_floor ? 'disabled' : ''; ?> onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Units'); ?></option>
                    <?php if ($selected_facility && $selected_floor) {
                        while ($unit = sqlFetchArray($units)) { ?>
                            <option value="<?php echo $unit['id']; ?>" 
                                <?php echo ($unit['id'] == $selected_unit) ? 'selected' : ''; ?>>
                                <?php echo text($unit['unit_name']); ?>
                            </option>
                    <?php }
                    } ?>
                </select>
            </div>

            <!-- Dropdown Room -->
            <div class="col-auto">
                <label for="room_id" class="form-label mb-1"><?php echo xlt('Select Room'); ?></label>
                <select name="room_id" id="room_id" class="form-select form-select-sm" style="width: 150px;" <?php echo !$selected_unit ? 'disabled' : ''; ?> onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Rooms'); ?></option>
                    <?php if ($selected_unit) {
                        while ($room = sqlFetchArray($rooms)) { ?>
                            <option value="<?php echo $room['id']; ?>" 
                                <?php echo ($room['id'] == $selected_room) ? 'selected' : ''; ?>>
                                <?php echo text($room['room_name']); ?>
                            </option>
                    <?php }
                    } ?>
                </select>
            </div>
        </div>
            <!-- Agregar esto después del formulario de filtros -->
        <div id="alarmStatus" style="position: fixed; top: 10px; left: 10px; z-index: 1000; background: rgba(0,0,0,0.7); color: white; padding: 10px 20px; border-radius: 8px; display: none;">
            <i class="fas fa-bell"></i> 
            <span id="activeAlarmCount">0</span> <?php echo xlt('active alarms'); ?>
        </div>
    </form>

    <h1><?php echo xl('Medical Administration Record MAR') ; ?></h1>
        <div id="visualization"></div>
        <div class="d-flex justify-content-between mt-3">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='../plan.php';">
                <i class="fas fa-home"></i> <?php echo xlt('Back to Plan'); ?>
            </button>
            <button type="button" class="btn btn-primary" id="orderMedicationButton">
                <?php echo xlt('Order New Medication'); ?>
            </button>
        </div>
        <!-- Patient Search Modal -->
        <div class="modal fade" id="inpatientSearchModal" tabindex="-1" aria-labelledby="inpatientSearchModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="inpatientSearchModalLabel">
                            <i class="material-icons">search</i> <?php echo xlt('Search Admitted Patients'); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo xlt('Close'); ?>"></button>
                    </div>
                    <div class="modal-body">
                        <form id="inpatientSearchForm" class="mb-3">
                            <div class="input-group">
                                <input type="text" id="searchQuery" name="searchQuery" class="form-control" placeholder="<?php echo xlt('Search by name, ID, room, unit, sector, or floor...'); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">search</i> <?php echo xlt('Search'); ?>
                                </button>
                            </div>
                        </form>
                        <div id="searchResults" class="table-responsive"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="material-icons">close</i> <?php echo xlt('Close'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <button type="button" class="btn btn-warning" onclick="silenceAllAlarms()">
        <i class="fas fa-volume-mute"></i> <?php echo xlt('Silence All Alarms'); ?>
    </button>

    <script>
        
        var now = new Date(); // Obtener la fecha y hora actual
        var start = new Date(now.getTime() - 1000 * 60 * 30); // Media hora antes de la hora actual
        var end = new Date(now.getTime() + 1000 * 60 * 60 * 24); // 24 horas después de la hora actual

        // Inicializar arrays para items (medicamentos y alarmas) y groups (pacientes)
        var items = new vis.DataSet();
        var groups = new vis.DataSet();
        //var items = [];
        //var groups = [];
        var existingGroups = {}; // Para almacenar los grupos existentes
        var pendingAlarms = []; // Para almacenar alarmas que necesitan activarse
        var activeAlarms = {};
        var alarmCheckInterval = null;
        
        console.log('Creating timeline...');
        console.log('Items:', items.length, 'Groups:', groups.length);
        // Obtener el contenedor del timeline
        var container = document.getElementById('visualization');

        // SOLUCIÓN SIN ARCHIVOS - Generar tonos con Web Audio API
        var activeAudioContexts = {}; // Para controlar múltiples alarmas

        items.clear();
        groups.clear();

// ==========================================
// FUNCIONES DE ALARMA (CONSOLIDADAS)
// ==========================================

// Reproducir sonido con Web Audio API
function playAlarmSound(alarmType) {
    console.log('[playAlarmSound] Starting:', alarmType);
    
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const config = {
            'alarm1': { frequencies: [800, 1000], duration: 0.3 },
            'alarm2': { frequencies: [1000, 1200], duration: 0.2 }
        };
        
        const { frequencies, duration } = config[alarmType];
        let currentFreqIndex = 0;
        let isPlaying = true;
        
        function createBeep() {
            if (!isPlaying) return;
            
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.type = 'sine';
            oscillator.frequency.value = frequencies[currentFreqIndex];
            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.3, audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + duration);
            
            currentFreqIndex = (currentFreqIndex + 1) % frequencies.length;
            setTimeout(createBeep, duration * 1000 + 100);
        }
        
        createBeep();
        console.log('[playAlarmSound] ✓ Playing');
        
        return {
            pause: function() {
                isPlaying = false;
                console.log('[playAlarmSound] Stopped');
            },
            currentTime: 0
        };
        
    } catch (error) {
        console.error('[playAlarmSound] ✗ Error:', error);
        return { pause: function() {} };
    }
}

// Activar alarma
function triggerAlarmForItem(itemId, alarmType) {
    console.log('[triggerAlarm] Item:', itemId, 'Type:', alarmType);
    
    if (activeAlarms[itemId]) {
        console.log('[triggerAlarm] Already active');
        return;
    }
    
    var item = items.get(itemId);
    if (!item) {
        console.error('[triggerAlarm] Item not found!');
        return;
    }
    
    const alarmSound = playAlarmSound(alarmType);
    const originalClassName = item.className || '';
    
    // Asegurar que la clase blinking se añada correctamente
    items.update({
        id: itemId,
        className: originalClassName + ' blinking'
    });
    
    activeAlarms[itemId] = {
        sound: alarmSound,
        originalClassName: originalClassName,
        timeout: setTimeout(() => stopAlarm(itemId), 120000)
    };
    
    updateAlarmCounter();
    console.log('[triggerAlarm] ✓ Activated');
}

// Detener alarma (sin silenciar permanentemente)
function stopAlarm(itemId) {
    if (!activeAlarms[itemId]) return;
    
    const alarm = activeAlarms[itemId];
    
    if (alarm.sound) alarm.sound.pause();
    if (alarm.timeout) clearTimeout(alarm.timeout);
    
    var item = items.get(itemId);
    if (item) {
        items.update({
            id: itemId,
            className: alarm.originalClassName
        });
    }
    
    delete activeAlarms[itemId];
    updateAlarmCounter();
}

// Silenciar alarma (cambia a estado gris sin sonido)
function silenceAlarm(itemId) {
    console.log('[silenceAlarm] Silencing:', itemId);

    const alarm = activeAlarms[itemId];
    if (alarm) {
        if (alarm.sound) alarm.sound.pause();
        if (alarm.timeout) clearTimeout(alarm.timeout);
        delete activeAlarms[itemId];
    }

    var item = items.get(itemId);
    if (!item) return;

    // Solo cambiar si es una alarma
    if (!item.className.includes('alarm1') && !item.className.includes('alarm2')) return;

    // Actualizar el estado en la base de datos
    fetch('update_alarm_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `alarm_id=${encodeURIComponent(itemId)}&action=silence`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Determinar clase silenciada (alarm1-silenced o alarm2-silenced)
            const baseClass = item.className.includes('alarm1') ? 'alarm1' : 'alarm2';
            const silencedClass = baseClass + '-silenced';
            
            // Actualizar visualmente solo si la actualización en DB fue exitosa
            items.update({
                id: itemId,
                className: silencedClass,
                title: item.title + ' - <?php echo xlt("SILENCED"); ?>'
            });

            updateAlarmCounter();
            showNotification('<?php echo xlt("Alarm silenced"); ?>', 'success');
        } else {
            showNotification('<?php echo xlt("Error silencing alarm"); ?>: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error updating alarm status:', error);
        showNotification('<?php echo xlt("Error connecting to server"); ?>', 'error');
    });
}

// Silenciar todas las alarmas activas
function silenceAllAlarms() {
    const alarmIds = Object.keys(activeAlarms);

    if (alarmIds.length === 0) {
        // Si no hay alarmas activas en memoria, buscar todas las alarmas activas en la interfaz
        var allItems = items.get({
            filter: function(item) {
                return item.className &&
                       (item.className.includes('alarm1') || item.className.includes('alarm2')) &&
                       !item.className.includes('silenced');
            }
        });
        
        allItems.forEach(function(item) {
            silenceAlarm(item.id);
        });
        
        if (allItems.length === 0) {
            showNotification('<?php echo xlt("No active alarms"); ?>', 'info');
        } else {
            showNotification(`<?php echo xlt("Silenced"); ?> ${allItems.length} <?php echo xlt("alarms"); ?>`, 'success');
        }
    } else {
        // Clonar los IDs para evitar problemas al eliminar mientras iteramos
        [...alarmIds].forEach(silenceAlarm);
        showNotification(`<?php echo xlt("Silenced"); ?> ${alarmIds.length} <?php echo xlt("alarms"); ?>`, 'success');
    }
}

// Verificar alarmas que deberían estar sonando (según el tiempo actual)
function checkPendingAlarms() {
    const now = new Date().getTime();
    
    var allItems = items.get({
        filter: function(item) {
            // Filtrar solo items de alarma que no estén silenciados ya
            return item.className && 
                   (item.className.includes('alarm1') || item.className.includes('alarm2')) &&
                   !item.className.includes('silenced');
        }
    });
    
    allItems.forEach(function(item) {
        const alarmTime = new Date(item.start).getTime();
        const timeDiff = now - alarmTime;
        
        // Si ha pasado el tiempo de la alarma y es reciente (menos de 2 mins)
        if (timeDiff > 0 && timeDiff < 120000 && !activeAlarms[item.id]) {
            const alarmType = item.className.includes('alarm1') ? 'alarm1' : 'alarm2';
            console.log('[checkPending] Auto-triggering:', item.id);
            triggerAlarmForItem(item.id, alarmType);
        }
    });
}

// Actualizar el contador visual de alarmas activas
function updateAlarmCounter() {
    const count = Object.keys(activeAlarms).length;
    const statusDiv = document.getElementById('alarmStatus');
    const countSpan = document.getElementById('activeAlarmCount');
    
    if (count > 0) {
        statusDiv.style.display = 'block';
        countSpan.textContent = count;
    } else {
        statusDiv.style.display = 'none';
    }
}

// Notificaciones tipo Toast (simplificado)
function showNotification(message, type = 'info') {
    const colors = {
        'success': '#4caf50',
        'warning': '#ff9800',
        'error': '#f44336',
        'info': '#2196f3'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed; bottom: 20px; right: 20px;
        background-color: ${colors[type]}; color: white;
        padding: 15px 25px; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 10000; font-size: 16px;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s';
        setTimeout(() => notification.remove(), 500);
    }, 3000);
}

// Reactivar una alarma silenciada
function reactivateAlarm(itemId) {
    console.log('[reactivateAlarm] Reactivating:', itemId);
    
    var item = items.get(itemId);
    if (!item) {
        console.error('[reactivateAlarm] Item not found!');
        return;
    }
    
    // Verificar si está silenciada (clase contiene -silenced)
    if (!item.className.includes('-silenced')) {
        console.log('[reactivateAlarm] Not silenced');
        return;
    }
    
    // Determinar tipo de alarma original (alarm1 o alarm2)
    const alarmType = item.className.includes('alarm1') ? 'alarm1' : 'alarm2';
    
    // Restaurar clase original (quitar -silenced y volver a poner la base)
    // Pero triggerAlarmForItem volverá a poner 'blinking'
    items.update({
        id: itemId,
        className: alarmType,
        title: item.title.replace(' - <?php echo xlt("SILENCED"); ?>', '')
    });
    
    // Reactivar alarma (sonido y parpadeo)
    triggerAlarmForItem(itemId, alarmType);
    
    showNotification('<?php echo xlt("Alarm reactivated"); ?>', 'warning');
}

        <?php
        //$event_id = 1; // Identificador único para cada evento
        $addedGroups = array(); // Rastrea los grupos ya añadidos
        
        echo "console.log('Starting to process schedules...');\n";
        while ($row = sqlFetchArray($result)) {
            echo "console.log('Processing schedule_id: " . $row['schedule_id'] . "');\n";
            $prescription_id = $row['prescription_id'];
            $patient_full_name = $row['patient_full_name'];
            $user_full_name = $row['user_full_name'];
            $patient_location = $row['facility_name'] . ', ' . $row['unit_name'] . ', ' . $row['room_name'] . ', ' . $row['bed_name'];
            $schedule_datetime = $row['schedule_datetime'];
            $formatted_hour = date('h:i A', strtotime($schedule_datetime));
            $supply_id = $row['supply_id'];
            $supply_datetime = $row['supply_datetime'];
            $formatted_hour_supply = date('h:i A', strtotime($supply_datetime));
            $alarm1_datetime = $row['alarm1_datetime'];
            $alarm1_active = $row['alarm1_active']; // Verificar el estado de la alarma 1
            $alarm2_datetime = $row['alarm2_datetime'];
            $alarm2_active = $row['alarm2_active']; // Verificar el estado de la alarma 2
            $schedule_id = $row['schedule_id'];
            $dose_number = $row['dose_number'];
            $max_dose = $row['max_dose'];
            $intravenous = $row['intravenous'];
            $dose_status = $row['status'];
            $unique_group_id = "group_" . $row['schedule_id']; // Crear un identificador único para el grupo basado en el paciente y su ubicación

            $medications_text = getMedicationsDetails($schedule_id);

            // Formatear las fechas correctamente en formato ISO
            $schedule_datetime_js = !empty($schedule_datetime) ? date('Y-m-d\TH:i:s', strtotime($schedule_datetime)) : '';
            $alarm1_datetime_js = !empty($alarm1_datetime) ? date('Y-m-d\TH:i:s', strtotime($alarm1_datetime)) : '';
            $alarm2_datetime_js = !empty($alarm2_datetime) ? date('Y-m-d\TH:i:s', strtotime($alarm2_datetime)) : '';
            
            // Obtener timestamp actual para comparar alarmas
            $current_time_unix = time();
             
            // Verificar si la función wrapText no ha sido definida antes de declararla
            if (!function_exists('wrapText')) {
                function wrapText($text, $maxLength = 65) {
                    $lines = [];
                    $current_line = '';

                    $words = explode(" ", $text);
                    foreach ($words as $word) {
                        // Si agregar la palabra excede el largo máximo, guarda la línea actual y empieza una nueva
                        if (strlen($current_line . " " . $word) > $maxLength) {
                            $lines[] = trim($current_line); // Añadir línea y limpiar espacios
                            $current_line = $word;
                        } else {
                            // Agregar la palabra a la línea actual
                            $current_line .= " " . $word;
                        }
                    }
                    // Añadir la última línea
                    if (!empty($current_line)) {
                        $lines[] = trim($current_line);
                    }
                    
                    return implode("<br>", $lines); // Unir las líneas con un salto de línea HTML
                }
            }
            // Agregar el grupo solo si no existe
            if (!isset($existingGroups[$unique_group_id])) {
                // Preparar el contenido base del grupo
                $group_content = "<strong>" . addslashes($patient_full_name) . "</strong>" . ' - ' 
               . addslashes($patient_location) . ' - ' 
               . addslashes($medications_text);

                // Acortar el contenido del grupo a líneas de X caracteres
                $group_content_wrapped = wrapText($group_content, 65);
                // Agregar el grupo con el contenido adecuado
                echo "groups.add({id: '$unique_group_id', content: '$group_content_wrapped'});\n";
                // Marcar este grupo como existente
                $existingGroups[$unique_group_id] = true;
            }

            // Agregar el evento principal para cada `supply_id`
            if ($schedule_datetime_js && $supply_id && $schedule_id) {
                $event_id = 'main_event_' . $supply_id . '_' . $schedule_id;

                // Ajustar el título y el color dependiendo del estado de la dosis
                if ($dose_status === 'Confirmed') {
                    // Formatear la hora a AM/PM
                    $title = addslashes(xlt('Dose') . "# {$dose_number}/{$max_dose} - {$dose_status} " . xlt('by') . " $user_full_name " . xlt('at') . " $formatted_hour_supply");
                    $color = 'background-color: blue; color: white;'; // Color azul si está confirmado
                    $className = 'supply';
                } elseif ($dose_status === 'Suspended') {
                    $title = addslashes(xlt('Dose') . "# {$dose_number}/{$max_dose} - SUSPENDED");
                    $color = ''; // El color viene del CSS
                    $className = 'supplySuspended';
                    $itemType = "type: 'point',";
                } else {
                    $title = addslashes($formatted_hour . ' ' . xlt('Dose') . "# {$dose_number}/{$max_dose} - {$dose_status} - {$medications_text}");
                    $color = ''; // Sin estilo adicional para otros estados
                    $className = 'supply';
                    $itemType = '';
                }

                echo "items.add({
                    id: '$event_id',
                    content: '',
                    start: '$schedule_datetime_js',
                    group: 'group_$schedule_id',
                    title: '$title',
                    data: {doseStatus: '{$dose_status}'},
                    style: '$color',
                    className: '$className',
                    $itemType
                });\n";
            }

            // Solo agregar las alarmas si $dose_status no es 'Confirmed' ni 'Suspended'
            if ($dose_status !== 'Confirmed' && $dose_status !== 'Suspended') {
                echo "console.log('Processing alarms for dose $supply_id, status: $dose_status');\n";
                
                // Procesar alarma 1 si está activa
                if ($alarm1_datetime_js && $alarm1_active == 1) {
                    $alarm1_formatted = $alarm1_datetime ? oeTimestampFormatDateTime(strtotime($alarm1_datetime)) : '';
                    $event_alarm1_id = 'alarm_' . $supply_id . '_1';
                    
                    // Agregar al timeline
                    echo "items.add({
                        id: '$event_alarm1_id',
                        content: '<i class=\"fas fa-bell\"></i>', 
                        start: '$alarm1_datetime_js', 
                        group: 'group_$schedule_id', 
                        className: 'alarm1', 
                        title: '" . xlt('Alarm 1') . ": $alarm1_formatted', 
                    });\n";
                    
                    // Verificar si debe activarse inmediatamente
                    if (strtotime($alarm1_datetime) <= $current_time_unix) {
                        echo "pendingAlarms.push({id: '$event_alarm1_id', type: 'alarm1'});\n";
                    }
                }

                // Procesar alarma 2 si está activa
                if ($alarm2_datetime_js && $alarm2_active == 1) {
                    $alarm2_formatted = $alarm2_datetime ? oeTimestampFormatDateTime(strtotime($alarm2_datetime)) : '';
                    $event_alarm2_id = 'alarm_' . $supply_id . '_2';
                    
                    // Agregar al timeline
                    echo "items.add({
                        id: '$event_alarm2_id',
                        content: '<i class=\"fas fa-bell\"></i><i class=\"fas fa-bell\"></i>', 
                        start: '$alarm2_datetime_js', 
                        group: 'group_$schedule_id', 
                        className: 'alarm2', 
                        title: '" . xlt('Alarm 2') . ": $alarm2_formatted', 
                    });\n";
                    
                    // Verificar si debe activarse inmediatamente
                    if (strtotime($alarm2_datetime) <= $current_time_unix) {
                        echo "pendingAlarms.push({id: '$event_alarm2_id', type: 'alarm2'});\n";
                    }
                }
            }
        } // Cerrar el while

        ?>

        // Actualizar opciones del timeline para mostrar tooltip personalizado
        var options = {
            start: start,
            end: end,
            showCurrentTime: true,
            zoomable: true,
            horizontalScroll: true,
            zoomMin: 1000 * 60 * 30,
            zoomMax: 1000 * 60 * 60 * 24 * 7,
            selectable: true,  // Habilitar selección de items
            multiselect: false, // Solo seleccionar un item a la vez
            timeAxis: {
                scale: 'minute',
                step: 60
            },
            tooltip: {
                followMouse: true,
                overflowMethod: 'cap'
            },
            template: function(item) {
                // Agregar instrucción para alarmas
                if (item.id && item.id.startsWith('alarm_')) {
                    return item.title + '<br><small><i><?php echo xlt("Click to silence"); ?></i></small>';
                }
                return item.title;
            }
        };

        var timeline = new vis.Timeline(container, items, groups, options);

        // Procesar alarmas pendientes DESPUÉS de que el timeline esté listo
        timeline.on('changed', function() {
            // Solo ejecutar una vez
            if (timeline._initialized) return;
            timeline._initialized = true;
            
            console.log('Timeline initialized, processing pending alarms:', pendingAlarms);
            
            setTimeout(function() {
                pendingAlarms.forEach(function(alarm) {
                    console.log('Activating pending alarm:', alarm);
                    triggerAlarmForItem(alarm.id, alarm.type);
                });
                
                // Iniciar verificación periódica de alarmas cada 30 segundos
                alarmCheckInterval = setInterval(checkPendingAlarms, 30000);
            }, 1000);
        });

        // Limpiar alarmas al cerrar/recargar página
        window.addEventListener('beforeunload', function() {
            Object.keys(activeAlarms).forEach(stopAlarm);
            if (alarmCheckInterval) {
                clearInterval(alarmCheckInterval);
            }
        });

        // Ajustar la escala al hacer zoom
        timeline.on('rangechange', function (properties) {
            var range = properties.end - properties.start;

            if (range <= 1000 * 60 * 480) { // Si el rango es menor o igual a 1 hora
                timeline.setOptions({
                    timeAxis: {
                        scale: 'minute',
                        step: 30 // Escala a 30 minutos
                    }
                });
            } else {
                timeline.setOptions({
                    timeAxis: {
                        scale: 'minute',
                        step: 60 // Escala a 60 minutos (por defecto)
                    }
                });
            }
        });
        
        // ==========================================
        // FUNCIÓN PRINCIPAL: ABRIR MODAL DE DOSIS (MEJORADA)
        // ==========================================

        function openMarActionsModal(supplyId, scheduleId) {
            console.log('[openMarActionsModal] Opening for supply_id:', supplyId, 'schedule_id:', scheduleId);

            // PASO 1: Limpiar TODO residual de Bootstrap
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');
            
            // PASO 2: Obtener el elemento del modal
            const modalEl = document.getElementById('marActionsModal');
            const modalBody = document.getElementById('marActionsModalBody');
            if (!modalEl || !modalBody) {
                console.error('[openMarActionsModal] Modal elements not found!');
                return;
            }
            
            // PASO 3: Cargar spinner
            modalBody.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3"><?php echo xlt('Loading dose information...'); ?></p>
                </div>
            `;
            
            // PASO 4: Mostrar el modal usando display block
            modalEl.style.display = 'block';
            
            // Debug: Verificar estado del modal
            console.log('[openMarActionsModal] Modal display:', modalEl.style.display);
            console.log('[openMarActionsModal] Modal computed display:', window.getComputedStyle(modalEl).display);
            console.log('[openMarActionsModal] Modal rect:', modalEl.getBoundingClientRect());
            console.log('[openMarActionsModal] Modal parent:', modalEl.parentElement);
            console.log('[openMarActionsModal] Modal z-index:', window.getComputedStyle(modalEl).zIndex);
            
            console.log('[openMarActionsModal] Modal shown, loading content...');
            
            // PASO 5: Cargar contenido vía AJAX
            $.ajax({
                url: '<?php echo $GLOBALS['webroot']; ?>/inpatient/plan/mar/modal_mar_actions.php',
                method: 'GET',
                data: {
                    supply_id: supplyId,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    console.log('[openMarActionsModal] Content loaded successfully');
                    modalBody.innerHTML = response;
                },
                error: function(xhr, status, error) {
                    console.error('[openMarActionsModal] AJAX error:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong><?php echo xlt('Error loading data'); ?>:</strong> ${error}
                        </div>
                    `;
                }
            });
        }

        // ==========================================
        // CERRAR MODAL PRINCIPAL (MEJORADO)
        // ==========================================

        function closeMarModal() {
            const modalEl = document.getElementById('marActionsModal');
            if (modalEl) {
                modalEl.style.display = 'none';
            }
            
            // Limpiar cualquier backdrop residual
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
        }

        // ==========================================
        // FUNCIÓN: GUARDAR EVALUACIÓN DE REACCIONES
        // ==========================================
        function saveReactionsEvaluation() {
            const form = document.getElementById('reactionsEffectivenessForm');
            if (!form) {
                console.error('[saveReactionsEvaluation] Form not found');
                return;
            }
            
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            
            console.log('[saveReactionsEvaluation] Saving with data:', data);
            
            const btnSave = document.getElementById('btnSaveEvaluation');
            if (btnSave) {
                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php echo xlt("Saving..."); ?>';
            }
            
            $.ajax({
                url: 'save_reactions_effectiveness.php',
                type: 'POST',
                data: data,
                dataType: 'json',
                success: function(response) {
                    console.log('[saveReactionsEvaluation] Response:', response);
                    if (response.success) {
                        alert(response.message);
                        closeMarModal();
                        location.reload();
                    } else {
                        alert('<?php echo xlt("Error"); ?>: ' + response.message);
                        if (btnSave) {
                            btnSave.disabled = false;
                            btnSave.innerHTML = '<i class="fas fa-save"></i> <?php echo xlt("Save Evaluation"); ?>';
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[saveReactionsEvaluation] AJAX Error:', error);
                    console.error('[saveReactionsEvaluation] Response:', xhr.responseText);
                    alert('<?php echo xlt("Error saving evaluation"); ?>: ' + error);
                    if (btnSave) {
                        btnSave.disabled = false;
                        btnSave.innerHTML = '<i class="fas fa-save"></i> <?php echo xlt("Save Evaluation"); ?>';
                    }
                }
            });
        }

        // ==========================================
        // FUNCIÓN: CONFIRMAR DOSIS
        // ==========================================
        function confirmDose(supplyId) {
            console.log('[confirmDose] Opening confirmation for supply_id:', supplyId);
            
            const modalBody = document.getElementById('marActionsModalBody');
            
            // Cargar formulario de confirmación en el mismo modal
            $.ajax({
                url: 'modal_confirm_dose.php',
                type: 'POST',
                data: { supply_id: supplyId },
                success: function(response) {
                    console.log('[confirmDose] Form loaded');
                    modalBody.innerHTML = response;
                },
                error: function(xhr, status, error) {
                    console.error('[confirmDose] Error:', error);
                    alert('<?php echo xlt("Error loading confirmation form"); ?>');
                }
            });
        }

        // ==========================================
        // FUNCIÓN: GUARDAR DOSIS CONFIRMADA (Global)
        // ==========================================
        function saveConfirmedDose(supplyId) {
            console.log('[saveConfirmedDose] Starting for supply_id:', supplyId);
            
            const infusionDatetimeEl = document.getElementById('infusion_datetime');
            const suppliedByEl = document.getElementById('supplied_by');
            const doseNoteEl = document.getElementById('dose_note');
            
            if (!infusionDatetimeEl || !suppliedByEl) {
                alert('<?php echo xlt("Form elements not found"); ?>');
                return;
            }
            
            const infusionDatetime = infusionDatetimeEl.value;
            const suppliedBy = suppliedByEl.value;
            const doseNote = doseNoteEl ? doseNoteEl.value : '';

            if (!suppliedBy) {
                alert('<?php echo xlt("Please select a user."); ?>');
                return;
            }
            
            if (!infusionDatetime) {
                alert('<?php echo xlt("Please select date and time."); ?>');
                return;
            }

            $.ajax({
                url: 'save_confirmed_dose.php',
                type: 'POST',
                data: {
                    supply_id: supplyId,
                    infusion_datetime: infusionDatetime,
                    supplied_by: suppliedBy,
                    dose_note: doseNote
                },
                success: function(response) {
                    console.log('[saveConfirmedDose] Success:', response);
                    alert('<?php echo xlt("Dose confirmed successfully"); ?>');
                    closeMarModal();
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.error('[saveConfirmedDose] Error:', status, error);
                    alert('<?php echo xlt("Error confirming dose"); ?>: ' + error);
                }
            });
        }

        // Ajustar schedule (modal secundario)
        function adjustSchedule(scheduleId) {
            console.log('[adjustSchedule] Opening for schedule_id:', scheduleId);
            
            // Cerrar modal principal
            closeMarModal();
            
            // Limpiar contenedor dinámico
            $('#dynamicModalContainer').empty();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
            
            $.ajax({
                url: 'modal_order_edit.php',
                type: 'GET',
                data: { schedule_id: scheduleId },
                success: function(response) {
                    $('#dynamicModalContainer').html(response);
                    
                    const editModalEl = document.getElementById('editScheduleModal');
                    if (editModalEl) {
                        const editModal = new bootstrap.Modal(editModalEl, {
                            backdrop: 'static',
                            keyboard: true
                        });
                        
                        editModalEl.addEventListener('hidden.bs.modal', function() {
                            $('#dynamicModalContainer').empty();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('overflow', '');
                        }, { once: true });
                        
                        // Agregar event listeners para botones de cerrar
                        editModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                editModal.hide();
                            });
                        });
                        
                        editModal.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[adjustSchedule] Error:', error);
                    alert('<?php echo xlt("Error loading edit modal"); ?>');
                }
            });
        }

        // Suspender schedule (modal secundario)
        function suspendSchedule(scheduleId) {
            console.log('[suspendSchedule] Opening for schedule_id:', scheduleId);
            
            // Cerrar modal principal
            closeMarModal();
            
            // Limpiar contenedor dinámico
            $('#dynamicModalContainer').empty();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '');
            
            $.ajax({
                url: 'modal_order_suspend.php',
                type: 'GET',
                data: { schedule_id: scheduleId },
                success: function(response) {
                    $('#dynamicModalContainer').html(response);
                    
                    const suspendModalEl = document.getElementById('suspendScheduleModal');
                    if (suspendModalEl) {
                        const suspendModal = new bootstrap.Modal(suspendModalEl, {
                            backdrop: 'static',
                            keyboard: true
                        });
                        
                        suspendModalEl.addEventListener('hidden.bs.modal', function() {
                            $('#dynamicModalContainer').empty();
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('overflow', '');
                        }, { once: true });
                        
                        // Agregar event listeners para botones de cerrar
                        suspendModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                suspendModal.hide();
                            });
                        });
                        
                        suspendModal.show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[suspendSchedule] Error:', error);
                    alert('<?php echo xlt("Error loading suspend modal"); ?>');
                }
            });
        }
        // ==========================================
        // FUNCIÓN: VER HISTORIAL DE DOSIS
        // ==========================================
        function viewDoseHistory(scheduleId) {
            console.log('[viewDoseHistory] Opening for schedule_id:', scheduleId);
            
            const modalBody = document.getElementById('marActionsModalBody');
            
            // Cargar historial de dosis en el mismo modal
            $.ajax({
                url: 'modal_dose_history.php',
                method: 'GET',
                data: { schedule_id: scheduleId },
                success: function(response) {
                    console.log('[viewDoseHistory] History loaded');
                    modalBody.innerHTML = response;
                },
                error: function(xhr, status, error) {
                    console.error('[viewDoseHistory] Error:', error);
                    alert('<?php echo xlt("Error loading dose history"); ?>');
                }
            });
        }

        // ==========================================
        // FUNCIÓN: REGISTRAR REACCIONES Y EFECTIVIDAD
        // ==========================================
        function registerReactions(supplyId, scheduleId) {
            console.log('[registerReactions] Opening for supply_id:', supplyId, 'schedule_id:', scheduleId);
            
            const modalBody = document.getElementById('marActionsModalBody');
            
            // Cargar formulario de reacciones en el mismo modal
            $.ajax({
                url: 'modal_reactions_effectiveness.php',
                method: 'GET',
                data: {
                    supply_id: supplyId,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    console.log('[registerReactions] Form loaded');
                    modalBody.innerHTML = response;
                },
                error: function(xhr, status, error) {
                    console.error('[registerReactions] Error:', error);
                    alert('<?php echo xlt("Error loading reactions form"); ?>');
                }
            });
        }

        // ==========================================
        // EVENT HANDLER DEL TIMELINE (MEJORADO)
        // ==========================================

        timeline.on('select', function (properties) {
            console.log('[timeline select] Event triggered:', properties);
            
            var itemId = properties.items[0];
            
            if (!itemId) {
                console.log('No item selected');
                return;
            }
            
            console.log('Selected item:', itemId);
            
            // Deseleccionar inmediatamente para permitir re-clicks
            timeline.setSelection([]);
            
            // CASO 1: Click en ALARMA
            if (itemId.startsWith('alarm_')) {
                // Verificar si la alarma está silenciada (tiene la clase silenced)
                var item = items.get(itemId);
                if (item && (item.className.includes('alarm1-silenced') || item.className.includes('alarm2-silenced'))) {
                    // Si la alarma está silenciada, mostrar opciones
                    handleSilencedAlarmClick(itemId);
                } else {
                    // Si la alarma está activa, silenciarla
                    console.log('Alarm selected, silencing...');
                    silenceAlarm(itemId);
                }
            } 
            // CASO 2: Click en DOSIS (main_event)
            else if (itemId.startsWith('main_event_')) {
                var parts = itemId.split('_');
                var supplyId = parts[2];
                var scheduleId = parts[3];

                if (supplyId && scheduleId) {
                    console.log('Opening modal for dose:', supplyId, scheduleId);
                    
                    // Pequeño delay para que Vis.js libere el foco completamente
                    setTimeout(function() {
                        openMarActionsModal(supplyId, scheduleId);
                    }, 100);
                } else {
                    console.error("Error: supplyId o scheduleId no están definidos.");
                }
            }
            else {
                console.log('Unknown item type:', itemId);
            }
        });

        $(document).ready(function () {
        // Cuando se seleccione una Facility
        $('#facilityFilter').change(function () {
            var facilityId = $(this).val();
            $('#unitFilter').html('<option value=""><?php echo xlt("Loading..."); ?></option>').prop('disabled', true);
            $('#roomFilter').html('<option value=""><?php echo xlt("Select Room"); ?></option>').prop('disabled', true);

            if (facilityId) {
                $.ajax({
                    url: 'get_units.php',
                    type: 'POST',
                    data: { facility_id: facilityId },
                    success: function (response) {
                        $('#unitFilter').html(response).prop('disabled', false);
                    }
                });
            }
        });

        // Cuando se seleccione una Unit
        $('#unitFilter').change(function () {
            var unitId = $(this).val();
            $('#roomFilter').html('<option value=""><?php echo xlt("Loading..."); ?></option>').prop('disabled', true);

            if (unitId) {
                $.ajax({
                    url: 'get_rooms.php',
                    type: 'POST',
                    data: { unit_id: unitId },
                    success: function (response) {
                        $('#roomFilter').html(response).prop('disabled', false);
                    }
                });
                }
            });
        });

        $(document).ready(function() {
            $('#closeModalButton').click(function() {
                    $('#editScheduleModal').modal('hide');
                });
            // Inicializar visibilidad de campos de repetición y intravenosos según los switches
            toggleRepeatFields();  
            toggleIntravenousFields();
            toggleNotificationFields();   

            // Escuchar cambios en los switches y alternar la visibilidad de los campos
            $('#scheduledSwitch').on('change', function() {
                toggleRepeatFields();  
            });

            $('#intravenousSwitch').on('change', function() {
                toggleIntravenousFields();  
            });

            $('#notificationSwitch').on('change', function() {
                toggleNotificationFields();  
            });

            $('#route').change(function() {
                // Obtener el `option_id` del atributo data-option-id
                var selectedOptionId = $(this).find(':selected').data('option-id');
                // Actualizar el campo oculto
                $('#route_option_id').val(selectedOptionId);
            });
        });
        
        var searchUsersUrl = "<?php echo $GLOBALS['webroot']; ?>/inpatient/search_users.php";

        // Función para alternar la visibilidad de los campos de repetición
        function toggleRepeatFields() {
            if ($('#scheduledSwitch').is(':checked')) {
                $('#repeatFields').show();  
            } else {
                $('#repeatFields').hide();  
            }
        }

        // Función para alternar la visibilidad de los campos intravenosos
        function toggleIntravenousFields() {
            if ($('#intravenousSwitch').is(':checked')) {
                $('#intravenousFields').show();  
            } else {
                $('#intravenousFields').hide();  
            }
        }
        // Función para alternar la visibilidad de los campos alarmas
        function toggleNotificationFields() {
            if ($('#notificationSwitch').is(':checked')) {
                $('#notificationFields').show();  
            } else {
                $('#notificationFields').hide();  
            }
        }
 
        // Validación para Order New Medication con AJAX
        function validatePatientSelection(callback) {
            const pid = <?php echo json_encode($GLOBALS['pid'] ?? null); ?>;
            if (!pid) {
                alert(<?php echo xlj('Please select an admitted patient'); ?>);
                $('#inpatientSearchModal').modal('show');
                callback(false);
            } else {
                $.ajax({
                    url: 'check_admitted.php',
                    method: 'POST',
                    data: { pid: pid },
                    dataType: 'json',
                    success: function(response) {
                        if (response.isAdmitted) {
                            callback(true);
                        } else {
                            alert("The patient <?php echo htmlspecialchars($GLOBALS['patient_name'] ?? ''); ?>, is not admitted. Please admit them or select another.");
                            $('#inpatientSearchModal').modal('show');
                            callback(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al verificar paciente:', error);
                        alert('Error al verificar el estado del paciente');
                        callback(false);
                    }
                });
            }
        }

        // Evitar doble clic
        let isProcessing = false;
        $('#orderMedicationButton').on('click', function() {
            if (isProcessing) return;
            isProcessing = true;

            validatePatientSelection(function(isValid) {
                if (isValid) {
                    const pid = <?php echo json_encode($GLOBALS['pid'] ?? ''); ?>;
                    console.log("Abrir modal de nueva medicación para PID:", pid);
                    $('#openMedicationModal' + pid).modal('show');
                }
                isProcessing = false;
            });
        });

        $('#inpatientSearchForm').on('submit', function(event) {
            event.preventDefault();
            const searchQuery = $('#searchQuery').val();

            $.ajax({
                url: 'search_inpatients.php',
                method: 'POST',
                data: { searchQuery: searchQuery },
                success: function(response) {
                    $('#searchResults').html(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    $('#searchResults').html('<div class="alert alert-danger">Error al realizar la búsqueda.</div>');
                }
            });
        });

        $(document).on('closeInpatientSearchModal', function() {
            $('#inpatientSearchModal').modal('hide');
            console.log("Modal cerrado vía evento desde mar.php");
            window.location.reload();
        });

        // Inicializar datetime picker para elementos dinámicos (como en care_plan)
        $(document).on('mouseover', '.datepicker', function () {
            const $this = $(this);
            // Evitar inicialización múltiple
            if ($this.data('xdsoft_datetimepicker')) {
                return;
            }
            
            $this.datetimepicker({
                timepicker: true,
                format: 'Y-m-d H:i',
                step: 15,
                yearStart: 1900,
                scrollInput: false,
                scrollMonth: false
            });
        });

    // Función para manejar el clic en alarmas silenciadas
    function handleSilencedAlarmClick(itemId) {
        // Obtener información del elemento
        var item = items.get(itemId);
        if (!item) return;

        // Determinar si es alarma 1 o 2
        const isAlarm1 = item.className.includes('alarm1-silenced');
        const alarmType = isAlarm1 ? 'alarm1' : 'alarm2';
        const alarmNumber = isAlarm1 ? 1 : 2;

        // Mostrar diálogo de confirmación con traducción
        const alarmTitle = item.title.replace(' - <?php echo xlt("SILENCED"); ?>', '');
        const message = `<?php echo xlt("What would you like to do with this alarm?"); ?>\n\n<?php echo xlt("Type"); ?>: ${alarmType.toUpperCase()}\n<?php echo xlt("Title"); ?>: ${alarmTitle}\n\n<?php echo xlt("OK to REACTIVATE, Cancel to DELETE"); ?>`;
        
        const action = confirm(message) ? 'reactivate' : 'delete';

        // Realizar la acción correspondiente
        fetch('update_alarm_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `alarm_id=${encodeURIComponent(itemId)}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (action === 'reactivate') {
                    // Reactivar: cambiar a clase activa
                    const activeClass = isAlarm1 ? 'alarm1' : 'alarm2';
                    items.update({
                        id: itemId,
                        className: activeClass,
                        title: item.title.replace(' - <?php echo xlt("SILENCED"); ?>', '')
                    });
                    showNotification('<?php echo xlt("Alarm reactivated"); ?>', 'success');
                } else if (action === 'delete') {
                    // Eliminar: remover del timeline
                    items.remove(itemId);
                    showNotification('<?php echo xlt("Alarm removed"); ?>', 'info');
                }
            } else {
                showNotification('<?php echo xlt("Error updating alarm"); ?>: ' + data.message, 'error');
            }
            updateAlarmCounter();
        })
        .catch(error => {
            console.error('Error updating alarm status:', error);
            showNotification('<?php echo xlt("Error connecting to server"); ?>', 'error');
        });
    }
    </script>

    <!-- Modal principal de dosis - Custom implementation to avoid Bootstrap conflicts -->
    <div id="marActionsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 99999; overflow-y: auto;">
        <div style="position: relative; margin: 50px auto; max-width: 800px; width: 90%; background: white; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5);">
            <div style="padding: 15px 20px; background: #0d6efd; color: white; border-top-left-radius: 8px; border-top-right-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0; font-size: 1.25rem;">
                    <i class="fas fa-pills"></i> <?php echo xlt("Dose Administration"); ?>
                </h5>
                <button type="button" onclick="closeMarModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0; width: 30px; height: 30px; line-height: 30px;">&times;</button>
            </div>
            <div id="marActionsModalBody" style="padding: 20px; background: white; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                <!-- Contenido cargado vía AJAX -->
            </div>
        </div>
    </div>

    <!-- Contenedor para modales dinámicos (edit, suspend, etc.) -->
    <div id="dynamicModalContainer"></div>

    <?php include 'modal_order_new_medication.php'; ?>
</body>
</html>