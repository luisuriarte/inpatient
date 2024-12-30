<?php
// Ensure the user is authenticated and load the globals
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Get the authenticated user information
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// The patient ID and name should be already available
$patient_id = isset($patient_id) ? $patient_id : null;
$patient_name = isset($patient_name) ? $patient_name : '';

// Ensure a patient is selected
if (!$patient_id) {
    die(xlt('No patient selected.'));
}

// Obtener los valores de los filtros de la solicitud POST o inicializarlos vacíos
$selected_facility = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : '';
$selected_unit = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : '';
$selected_room = isset($_POST['room_id']) ? intval($_POST['room_id']) : '';

// Consulta para obtener las opciones de Facility, Unit y Room
$facilities = sqlStatement("SELECT id, name FROM facility WHERE inactive = 0 ORDER BY name ASC");
$units = $selected_facility ? sqlStatement("SELECT id, unit_name FROM units WHERE facility_id = ? AND active = 1", [$selected_facility]) : [];
$rooms = $selected_unit ? sqlStatement("SELECT id, room_name FROM rooms WHERE unit_id = ?  AND active = 1", [$selected_unit]) : [];

// Modificar la consulta principal según los filtros
$filters = "WHERE bp.`active` = 1 AND ps.active = 1";
if ($selected_facility) {
    $filters .= " AND bp.facility_id = $selected_facility";
}
if ($selected_unit) {
    $filters .= " AND bp.unit_id = $selected_unit";
}
if ($selected_room) {
    $filters .= " AND bp.room_id = $selected_room";
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
   bp.bed_id, bp.room_id, bp.unit_id, bp.facility_id, bp.`active` AS inpatient,
   f.name AS facility_name, u.unit_name AS unit_name, r.room_name AS room_name, b.bed_name AS bed_name,
   ps.schedule_id, pn.id AS prescription_id
FROM prescriptions_supply ps
LEFT JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
LEFT JOIN prescriptions AS pn ON sch.prescription_id = pn.id
LEFT JOIN patient_data AS p ON sch.patient_id = p.pid
LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid
LEFT JOIN facility AS f ON f.id = bp.facility_id
LEFT JOIN units AS u ON u.id = bp.unit_id
LEFT JOIN rooms AS r ON r.id = bp.room_id
LEFT JOIN beds AS b ON b.id = bp.bed_id
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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.css">
    <!-- Cargar JavaScript: jQuery primero, luego Bootstrap, luego Vis.js -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vis/4.21.0/vis.min.js"></script>
    <!-- Incluir moment.js -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/es.js"></script> -->

    <style>

    </style>

</head>

<body>
    <!-- Formulario de filtros -->
    <form method="POST" action="" class="row mb-3 d-flex justify-content-center">
        <div class="form-row">
            <!-- Dropdown Facility -->
            <div class="col-md-4">
                <label for="facility_id" class="form-label"><?php echo xlt('Select Facility'); ?></label>
                <select name="facility_id" id="facility_id" class="form-select" onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Facilities'); ?></option>
                    <?php while ($facility = sqlFetchArray($facilities)) { ?>
                        <option value="<?php echo $facility['id']; ?>" 
                            <?php echo ($facility['id'] == $selected_facility) ? 'selected' : ''; ?>>
                            <?php echo text($facility['name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <!-- Dropdown Unit -->
            <div class="col-md-4">
                <label for="unit_id" class="form-label"><?php echo xlt('Select Unit'); ?></label>
                <select name="unit_id" id="unit_id" class="form-select" <?php echo !$selected_facility ? 'disabled' : ''; ?> onchange="this.form.submit()">
                    <option value=""><?php echo xlt('All Units'); ?></option>
                    <?php if ($selected_facility) {
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
            <div class="col-md-4">
                <label for="room_id" class="form-label"><?php echo xlt('Select Room'); ?></label>
                <select name="room_id" id="room_id" class="form-select" <?php echo !$selected_unit ? 'disabled' : ''; ?> onchange="this.form.submit()">
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
    </form>
    <h1><?php echo xl('Medical Administration Record MAR'); ?></h1>

    <!-- Aquí se incluirá el timeline -->
    <div id="visualization"></div>
    <!-- Botones inferiores -->
    <div class="text-right">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='../plan.php';">
            <i class="fas fa-home"></i> <?php echo xlt('Back to Plan'); ?>
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#openMedicationModal<?= $patient_id ?>">
            <?php echo xlt('Order New Medication'); ?>
        </button>
    </div>
    
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
        items.clear();

        <?php
        //$event_id = 1; // Identificador único para cada evento
        $addedGroups = array(); // Rastrea los grupos ya añadidos
        
        while ($row = sqlFetchArray($result)) {
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
                } else {
                    $title = addslashes($formatted_hour . ' ' . xlt('Dose') . "# {$dose_number}/{$max_dose} - {$dose_status} - {$medications_text}");
                    $color = ''; // Sin estilo adicional para otros estados
                }

                echo "items.add({
                    id: '$event_id',
                    content: '',
                    start: '$schedule_datetime_js',
                    group: 'group_$schedule_id',
                    title: '$title',
                    data: {doseStatus: '{$dose_status}'},
                    style: '$color',
                    className: 'supply',
                });\n";
            }

            // Solo agregar las alarmas si $dose_status no es 'Confirmed'
            if ($dose_status !== 'Confirmed') {
                // Procesar alarma 1 si está activa
                if ($alarm1_datetime_js && $alarm1_active == 1) {
                    $alarm1_formatted = $alarm1_datetime ? oeTimestampFormatDateTime(strtotime($alarm1_datetime)) : '';
                    $event_alarm1_id = 'alarm_' . $supply_id . '_1';
            
                    // Verificar si la alarma ya debería haber sonado
                    if (strtotime($alarm1_datetime) <= $current_time_unix) {
                        echo "triggerAlarm('$event_alarm1_id', 'alarm1');\n";
                    }
            
                    // Agregar al timeline
                    echo "items.add({
                        id: '$event_alarm1_id',
                        content: '', 
                        start: '$alarm1_datetime_js', 
                        group: 'group_$schedule_id', 
                        className: 'alarm1', 
                        title: '" . xlt('Alarm 1') . " - $event_alarm1_id: $alarm1_formatted', 
                    });\n";
                }
            
                // Procesar alarma 2 si está activa
                if ($alarm2_datetime_js && $alarm2_active == 1) {
                    $alarm2_formatted = $alarm2_datetime ? oeTimestampFormatDateTime(strtotime($alarm2_datetime)) : '';
                    $event_alarm2_id = 'alarm_' . $supply_id . '_2';
            
                    // Verificar si la alarma ya debería haber sonado
                    if (strtotime($alarm2_datetime) <= $current_time_unix) {
                        echo "triggerAlarm('$event_alarm2_id', 'alarm2');\n";
                    }
            
                    // Agregar al timeline
                    echo "items.add({
                        id: '$event_alarm2_id',
                        content: '', 
                        start: '$alarm2_datetime_js', 
                        group: 'group_$schedule_id', 
                        className: 'alarm2', 
                        title: '" . xlt('Alarm 2') . " - $event_alarm2_id: $alarm2_formatted', 
                    });\n";
                }
            }
        }

        ?>
        // Crear la instancia del timeline
        var container = document.getElementById('visualization');
        var options = {
            start: start,
            end: end,
            showCurrentTime: true,
            zoomable: true,
            horizontalScroll: true,
            zoomMin: 1000 * 60 * 30,
            zoomMax: 1000 * 60 * 60 * 24 * 7,
            timeAxis: {
                scale: 'minute',
                step: 60
            },
            tooltip: {
                followMouse: true, // Si deseas que el tooltip siga al ratón
                overflowMethod: 'cap' // Método de manejo de desbordamiento
            }
        };

        var timeline = new vis.Timeline(container, items, groups, options);

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
        // Función para abrir el modal en Bootstrap 5
        function openMarActionsModal(supplyId, scheduleId) {
            // Aquí cargas dinámicamente el contenido del modal (ya lo estás haciendo con AJAX)
            $.ajax({
                url: 'modal_mar_actions.php',
                method: 'GET',
                data: {
                    action: 'get_dose_details',
                    supply_id: supplyId,
                    schedule_id: scheduleId
                },
                success: function(response) {
                    // Mostrar la respuesta en el modal
                    $('#marActionsModal .modal-body').html(response);  // Aquí llenas el contenido dinámicamente
                    // Crear una instancia del modal y mostrarlo
                    var myModal = new bootstrap.Modal(document.getElementById('marActionsModal'));
                    myModal.show();  // Mostrar el modal
                },
                error: function(xhr, status, error) {
                    console.log("Error AJAX:", error);
                }
            });
        }

        $(document).ready(function() {
            timeline.on('click', function (properties) {
                var itemId = properties.item;
                if (itemId && itemId.startsWith('main_event_')) {
                    var parts = itemId.split('_');
                    var supplyId = parts[2];
                    var scheduleId = parts[3];
                    // Llamar a la función solo si ambos IDs existen
                    if (supplyId && scheduleId) {
                        openMarActionsModal(supplyId, scheduleId);
                    } else {
                        console.error("Error: supplyId o scheduleId no están definidos.");
                    }
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            // Función para reproducir sonido
            function playAlarmSound(alarmType) {
                const soundMap = {
                    'alarm1': '../sounds/alarm1.mp3',
                    'alarm2': '../sounds/alarm2.mp3'
                };

                const sound = new Audio(soundMap[alarmType]);
                sound.loop = true; // Hacer que el sonido se repita
                sound.play();

                // Retornar el objeto Audio para controlarlo después
                return sound;
            }

            // Función para manejar el parpadeo y sonido de la alarma
            function triggerAlarm(eventId, alarmType) {
                const element = document.getElementById(eventId);

                if (!element) {
                    console.error(`Elemento con ID ${eventId} no encontrado.`);
                    return;
                }

                // Añadir clase de parpadeo
                element.classList.add('blinking');

                // Reproducir sonido
                const alarmSound = playAlarmSound(alarmType);

                // Detener parpadeo y sonido después de 2 minutos
                setTimeout(() => {
                    element.classList.remove('blinking'); // Quitar parpadeo
                    alarmSound.pause(); // Detener sonido
                    alarmSound.currentTime = 0; // Reiniciar sonido
                }, 120000); // 2 minutos
            }
        
        });

        function confirmDose(supplyId) {
            $.ajax({
                url: 'modal_confirm_dose.php',
                type: 'POST',
                data: { supply_id: supplyId },
                success: function(response) {
                    // Cargar el contenido en el modal principal de mar.php
                    $('#marActionsModal .modal-body').html(response);
                },
                error: function() {
                    alert('Error loading confirmation modal.');
                }
            });
        }

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

        function adjustSchedule(scheduleId) {
            $.ajax({
                url: 'modal_order_edit.php',
                type: 'GET',
                data: { schedule_id: scheduleId },
                success: function(response) {
                    // Insertar el contenido dinámico en el modal
                    $('#dynamicModalContainer').html(response);
                    var editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                    editModal.show();
                },
                error: function() {
                    alert('Error loading the edit schedule modal.');
                }
            });
        }

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

    </script>

    <?php include 'modal_order_new_medication.php'; ?>
    <!-- <script src="../../functions.js"></script> -->
     

<!-- Modal de dosis (marActionsModal) -->
<div class="modal fade" id="marActionsModal" tabindex="-1" aria-labelledby="marActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo xlt("Dose Administration for Patient"); ?></h5>
            </div>
            <div class="modal-body">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <?php echo xlt("Close"); ?>
                </button>
            </div>
        </div>
    </div>
</div>
<div id="dynamicModalContainer"></div>
</body>
</html>
