<?php
// Incluir funciones y variables globales
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar si se pasó el `schedule_id`
$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    die(xlt('No schedule ID provided.'));
}

// Obtener los detalles de la programación y la orden
$query = "
    SELECT 
        CONCAT(p.lname, ', ', p.fname, 
            IF(p.mname IS NOT NULL AND p.mname != '', CONCAT(' ', p.mname), '')
        ) AS patient_full_name,
        CONCAT(f.name, ' - ', u.unit_name, ' - ', r.room_name, ' - ', b.bed_name) AS Location,
        pr.`active`, ps.start_date, pr.provider_id, pr.drug, pr.dosage, pr.size, pr.unit, pr.form, pr.route, pr.note,
        ps.intravenous,
        iv.vehicle, iv.catheter_type, iv.infusion_rate, iv.iv_route, iv.concentration, iv.concentration_units, iv.total_volume, iv.iv_duration, iv.`status` AS iv_status,
        ps.status AS ps_status, ps.order_type, ps.unit_frequency, ps.time_frequency, ps.unit_duration, ps.time_duration,
        ps.alarm1_unit, ps.alarm1_time, ps.alarm2_unit, ps.alarm2_time, ps.end_date
    FROM prescriptions_schedule ps
        LEFT JOIN prescriptions AS pr ON ps.prescription_id = pr.id
        LEFT JOIN prescriptions_intravenous AS iv ON iv.schedule_id = ps.schedule_id
        LEFT JOIN patient_data AS p ON ps.patient_id = p.pid
        LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid
LEFT JOIN facility AS f ON f.id = bp.facility_id
        LEFT JOIN units AS u ON u.id = bp.current_unit_id
        LEFT JOIN rooms AS r ON r.id = bp.current_room_id
        LEFT JOIN beds AS b ON b.id = bp.current_bed_id
    WHERE ps.schedule_id = ? AND bp.status = 'admitted' AND ps.active = 1
    ORDER BY ps.start_date;
";

$result = sqlQuery($query, [$schedule_id]);

if (!$result) {
    die(xlt('No data found for the provided schedule ID.'));
}

// Asignar resultados a variables para el formulario
$patientFullName = $result['patient_full_name'];
$location = $result['Location'];
$active = $result['active'];
$startDate = $result['start_date'];
$providerId = $result['provider_id'];
$providerFullName = getUserFullName($providerId);
$drug = $result['drug'];
$dosage = $result['dosage'];
$size = $result['size'];
$unit = $result['unit'];
$form = $result['form'];
$route = $result['route'];
$note = $result['note'];
$orderType = $result['order_type'] ?? 'scheduled';
$unitFrequency = $result['unit_frequency'];
$timeFrequency = $result['time_frequency'];
$unitDuration = $result['unit_duration'];
$timeDuration = $result['time_duration'];
$alarm1Unit = $result['alarm1_unit'];
$alarm1Time = $result['alarm1_time'];
$alarm2Unit = $result['alarm2_unit'];
$alarm2Time = $result['alarm2_time'];
$endDate = $result['end_date'];
$intravenous = $result['intravenous'];
$ivVehicle = $result['vehicle'];
$ivCatheterType = $result['catheter_type'];
$ivInfusionRate = $result['infusion_rate'];
$ivRoute = $result['iv_route'];
$ivTotalVolume = $result['total_volume'];
$ivConcentration = $result['concentration'];
$ivConcentrationUnits = $result['concentration_units'];
$ivDuration = $result['iv_duration'];
$ivStatus = $result['iv_status'];
$psStatus = $result['ps_status'];
?>

<div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
<form id="editScheduleForm" method="POST" action="save_order_edit.php">
                <input type="hidden" name="schedule_id" value="<?php echo attr($schedule_id); ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo xlt('Edit Medication Order'); ?></h5>
                    <p><strong><?php echo xlt('Patient'); ?>:</strong> <?php echo text($patientFullName); ?></p>
                    <p><strong><?php echo xlt('Location'); ?>:</strong> <?php echo $location; ?>
                    </p>
                </div>
                <div class="modal-body">
                        <div class="row">
                            <!-- Field: Active -->
                            <div class="col-md-6">
                                <div class="form-group d-flex align-items-center">
                                <div class="custom-slider-switch mr-3">
                                        <input type="checkbox" name="active" id="activeSwitch" value="<?php echo attr($active); ?>" autocomplete="off" checked>
                                        <title for="activeactiveSwitch"><?php echo xl('Active Green, Deactive Grey'); ?></title>
                                    </div>
                                    <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Active'); ?></label>
                                </div>
                            </div>
                            <!-- Field: Start Date/Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date"><?php echo xlt('Start Date/Time'); ?></label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo attr($startDate); ?>" data-toggle="message" title="<?php echo xlt('Start Date & Time first Infusion'); ?>" autocomplete="off" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Provider -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="provider"><?php echo xlt("Provider"); ?>:</label>
                                    <select class="form-control" id="provider_id" name="provider_id" required>
                                        <option value=""><?php echo xlt("-- Select Provider --"); ?></option>
                                        <?php
                                        $provider_query = "SELECT id, fname, lname, mname FROM users WHERE authorized = 1";
                                        $provider_result = sqlStatement($provider_query);
                                            while ($provider = sqlFetchArray($provider_result)) {
                                            $providerName = $provider['lname'] . ", " . $provider['fname'];
                                            if (!empty($provider['mname'])) {
                                                $providerName .= " " . $provider['mname'];
                                            }
                                            $selected = ($provider['id'] == $providerId) ? "selected" : "";
                                            echo '<option value="' . attr($provider['id']) . '" ' . $selected . '>' . text($providerName) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                            </div>
                            <!-- Field: Drug -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="drug"><?php echo xlt("Put only the drug"); ?>:</label>
                                    <input type="text" class="form-control" id="drug" name="drug" value="<?php echo attr($drug); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Dosage -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dosage"><?php echo xlt("Dosage"); ?>:</label>
                                    <input type="text" class="form-control" id="dosage" name="dosage" value="<?php echo attr($dosage); ?>" required>
                                </div>
                            </div>
                            <!-- Field: Units -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="size"><?php echo xlt("Units"); ?>:</label>
                                    <input type="text" class="form-control" id="size" name="size" value="<?php echo attr($size); ?>" required>
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Volume/Weight -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit"><?php echo xlt('Volume/Weight'); ?></label>
                                    <select class="form-control" id="unit" name="unit" required>
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $units_query = "SELECT option_id, title FROM list_options WHERE list_id = 'drug_units'";
                                        $units_result = sqlStatement($units_query);
                                        while ($unit_option = sqlFetchArray($units_result)) {
                                            $selected = ($unit_option['option_id'] == $unit) ? 'selected' : '';
                                            echo '<option value="' . attr($unit_option['option_id']) . '" ' . $selected . '>' . text($unit_option['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Drug Form -->
                            <div class="col-md-6">
                                <label for="form"><?php echo xlt("Form"); ?>:</label>
                                <select class="form-control" id="form" name="form" required>
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $form_query = "SELECT option_id, title FROM list_options WHERE list_id = 'drug_form'";
                                    $form_result = sqlStatement($form_query);
                                    while ($form_option = sqlFetchArray($form_result)) {
                                        $selected = ($form_option['option_id'] == $form) ? 'selected' : '';
                                        echo '<option value="' . attr($form_option['option_id']) . '" ' . $selected . '>' . text($form_option['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Field: Route -->
                            <div class="col-md-6">
                                <div class="form-group">
                                <label for="route"><?php echo xlt('Route'); ?></label>
                                <select class="form-control" id="route" name="route" required>
                                    <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $route_query = "SELECT title, option_id FROM list_options WHERE list_id = 'drug_route'";
                                    $route_result = sqlStatement($route_query);
                                    while ($route_option = sqlFetchArray($route_result)) {
                                        $selected = ($route_option['option_id'] == $route) ? 'selected' : '';
                                        echo '<option value="' . attr($route_option['option_id']) . '" ' . $selected . '>' . text($route_option['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                                </div>
                            </div>
                        </div>
                        <p> </p>
                    <div class="row">
                        <!-- Field: Intravenous Switch -->
                        <div class="form-group d-flex align-items-center">
                            <div class="custom-slider-switch mr-3">
                                 <input type="checkbox" name="intravenous_switch" id="intravenousSwitchEdit" value="1" autocomplete="off" <?php echo ($intravenous == 1) ? 'checked' : ''; ?>>
                            </div>
                            <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Intravenous'); ?></label>
                        </div>
                    </div>
    <!-- Intravenous fields (only visible if Intravenous is Yes) -->
                    <div id="intravenousFieldsEdit" style="background-color: #f9e6e6; border-top: 1px solid #dee2e6; <?php echo ($intravenous != 1) ? 'display: none;' : ''; ?>">
                        <div class="row">
                            <!-- Field: Vehicle -->
                            <div class="col-md-6">
                                <div class="form-group">
                                <label for="vehicle"><?php echo xlt('Vehicle'); ?></label>
                                    <select class="form-control" id="vehicle" name="vehicle">
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $vehicle_query = "SELECT title FROM list_options WHERE list_id = 'intravenous_vehicle'";
                                        $vehicle_result = sqlStatement($vehicle_query);
                                        while ($vehicle_option = sqlFetchArray($vehicle_result)) {
                                            // Compara el valor de `$ivVehicle` con el `title` de `list_options`
                                            $selected = ($vehicle_option['title'] == $ivVehicle) ? 'selected' : '';
                                            echo '<option value="' . attr($vehicle_option['title']) . '" ' . $selected . '>' . text($vehicle_option['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Catheter Type -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="catheter_type"><?php echo xlt('Catheter Type'); ?></label>
                                    <select class="form-control" id="catheter_type" name="catheter_type">
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $catheter_query = "SELECT title FROM list_options WHERE list_id = 'catheter_type'";
                                        $catheter_result = sqlStatement($catheter_query);
                                        while ($catheter_option = sqlFetchArray($catheter_result)) {
                                            $selected = ($catheter_option['title'] == $ivCatheterType) ? 'selected' : '';
                                            echo '<option value="' . attr($catheter_option['title']) . '" ' . $selected . '>' . text($catheter_option['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: IV Route -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="iv_route"><?php echo xlt('IV Route'); ?></label>
                                    <select class="form-control" id="iv_route" name="iv_route">
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $units_query = "SELECT title FROM list_options WHERE list_id = 'intravenous_route'";
                                        $units_result = sqlStatement($units_query);
                                        while ($unit_option = sqlFetchArray($units_result)) {
                                            $selected = ($unit_option['title'] == $ivRoute) ? 'selected' : '';
                                            echo '<option value="' . attr($unit_option['title']) . '" ' . $selected . '>' . text($unit_option['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Concentration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="concentration"><?php echo xlt('Concentration'); ?></label>
                                    <input type="number" class="form-control" id="concentration" name="concentration" value="<?php echo attr($ivConcentration); ?>" step="0.01">
                                </div>
                            </div>

                            <!-- Field: Concentration Units -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="concentration_units"><?php echo xlt('Concentration Units'); ?></label>
                                    <select class="form-control" id="concentration_units" name="concentration_units">
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $units_query = "SELECT title FROM list_options WHERE list_id = 'proc_unit'";
                                        $units_result = sqlStatement($units_query);
                                        while ($unit_option = sqlFetchArray($units_result)) {
                                            $selected = ($unit_option['title'] == $ivConcentrationUnits) ? 'selected' : '';
                                            echo '<option value="' . attr($unit_option['title']) . '" ' . $selected . '>' . text($unit_option['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Infusion Rate -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="infusion_rate"><?php echo xlt('Infusion Rate (ml/h)'); ?></label>
                                    <input type="number" class="form-control" id="infusion_rate" name="infusion_rate" value="<?php echo attr($ivInfusionRate); ?>" step="0.01">
                                </div>
                            </div>

                            <!-- Field: Total Volume -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_volume"><?php echo xlt('Total Volume (ml)'); ?></label>
                                    <input type="number" class="form-control" id="total_volume" name="total_volume" value="<?php echo attr($ivTotalVolume); ?>" step="0.01">
                                </div>
                            </div>

                            <!-- Field: Duration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="iv_duration"><?php echo xlt('Total Duration'); ?></label>
                                    <input type="number" class="form-control" id="iv_duration" name="iv_duration" value="<?php echo attr($ivDuration); ?>">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="iv_status" value="<?php echo attr($ivStatus); ?>" id="iv_status">
                    </div>
                    <p> </p>

                    <!-- Field: Order Type -->
                    <div class="form-group">
                        <label for="order_type"><strong><?php echo xlt('Order Type'); ?></strong></label>
                        <select class="form-control" id="order_type" name="order_type" required>
                            <option value="scheduled" <?php echo ($orderType == 'scheduled') ? 'selected' : ''; ?>>
                                <?php echo xlt('Scheduled'); ?>
                            </option>
                            <option value="unique" <?php echo ($orderType == 'unique') ? 'selected' : ''; ?>>
                                <?php echo xlt('One Time'); ?>
                            </option>
                            <option value="prn" <?php echo ($orderType == 'prn') ? 'selected' : ''; ?>>
                                <?php echo xlt('PRN (As Needed)'); ?>
                            </option>
                            <option value="stat" <?php echo ($orderType == 'stat') ? 'selected' : ''; ?>>
                                <?php echo xlt('STAT (Immediate)'); ?>
                            </option>
                        </select>
                    </div>


                    <!-- Repeat medication fields (only visible if One-time Medication is No) -->
                    <div id="repeatFieldsEdit" style="background-color: #e0e1fb; border-top: 1px solid #dee2e6; <?php echo ($orderType !== 'scheduled') ? 'display: none;' : ''; ?>
                        <!-- Group: Unit Frequency and Time Frequency -->
                        <div class="form-group row">
                            <!-- Field: Unit Frequency -->
                            <div class="col-md-6">
                                <label for="unit_frequency"><?php echo xlt('Frequency'); ?></label>
                                <input type="number" class="form-control" id="unit_frequency" name="unit_frequency" value="<?php echo attr($unitFrequency); ?>">
                            </div>

                            <!-- Field: Time Frequency -->
                            <div class="col-md-6">
                                <label for="time_frequency"><?php echo xlt('Time unit'); ?></label>
                                <select class="form-control" id="time_frequency" name="time_frequency">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $time_unit_query = "SELECT option_id, title FROM list_options WHERE list_id='time_unit'";
                                    $time_unit_result = sqlStatement($time_unit_query);
                                    while ($time_unit = sqlFetchArray($time_unit_result)) {
                                        $selected = ($time_unit['option_id'] == $timeFrequency) ? 'selected' : '';
                                        echo '<option value="' . attr($time_unit['option_id']) . '" ' . $selected . '>' . text($time_unit['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Group: Duration and Unit Duration -->
                        <div class="form-group row">
<!-- Field: Duration -->
                            <div class="col-md-6">
                                <label for="duration"><?php echo xlt('Duration'); ?></label>
                                <input type="number" class="form-control" id="duration" name="duration" value="<?php echo attr($unitDuration); ?>">
                            </div>

<!-- Field: Unit Duration -->
                            <div class="col-md-6">
                                <label for="time_duration"><?php echo xlt('Unit of Time'); ?></label>
                                <select class="form-control" id="time_duration" name="time_duration">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $duration_unit_query = "SELECT option_id, title FROM list_options WHERE list_id='time_unit'";
                                    $duration_unit_result = sqlStatement($duration_unit_query);
                                    while ($unit_duration = sqlFetchArray($duration_unit_result)) {
                                        $selected = ($unit_duration['option_id'] == $timeDuration) ? 'selected' : '';
                                        echo '<option value="' . attr($unit_duration['option_id']) . '" ' . $selected . '>' . text($unit_duration['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
<!-- Field: Start Date/Time -->
                        <div class="col-md-6">
                           <div class="form-group">
                                <label for="end_date"><?php echo xlt('End Date/Time'); ?></label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo attr($endDate); ?>">
                            </div>
                        </div>
                    </div>
                    <p> </p>
                    <div class="form-group d-flex align-items-center">
                        <div class="custom-slider-switch ls-3">
                            <input type="checkbox" name="notifications" id="notificationSwitchEdit" value="1" autocomplete="off" <?php echo ($notifications == 1) ? 'checked' : ''; ?>>
                        </div>
                        <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Notifications'); ?></label>
                    </div>
                    <!-- Field: Alarms -->
                    <div id="notificationFieldsEdit" style="background-color: #e0fbe1; padding: 15px; border-radius: 5px; <?php echo ($notifications != 1) ? 'display: none;' : ''; ?>">
                        <div class="row">
                            <div class="col">
<label for="alarm1_unit"><?php echo xlt('First Alarm (Minutes Offset)'); ?></label>
                                <input type="number" class="form-control" id="alarm1_unit" name="alarm1_unit" value="<?php echo attr($alarm1Unit); ?>">
                            </div>
                            <div class="col">
<label for="alarm1_time"><?php echo xlt('Unit of Time'); ?></label>
                                <select class="form-control" id="alarm1_time" name="alarm1_time">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $time_unit_query = "SELECT option_id, title FROM list_options WHERE list_id='time_unit'";
                                    $time_unit_result = sqlStatement($time_unit_query);
                                    while ($time_unit = sqlFetchArray($time_unit_result)) {
                                        $selected = ($time_unit['option_id'] == $alarm1Time) ? 'selected' : '';
                                        echo '<option value="' . attr($time_unit['option_id']) . '" ' . $selected . '>' . text($time_unit['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
<label for="alarm2_unit"><?php echo xlt('Second Alarm (Minutes Offset)'); ?></label>
                                <input type="number" class="form-control" id="alarm2_unit" name="alarm2_unit" value="<?php echo attr($alarm2Unit); ?>">
                            </div>
                            <div class="col">
<label for="alarm2_time"><?php echo xlt('Unit of Time'); ?></label>
                                <select class="form-control" id="alarm2_time" name="alarm2_time">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    // Reutilizamos la misma consulta para ambos selects
                                    $time_unit_result = sqlStatement($time_unit_query);
                                    while ($time_unit = sqlFetchArray($time_unit_result)) {
                                        $selected = ($time_unit['option_id'] == $alarm2Time) ? 'selected' : '';
                                        echo '<option value="' . attr($time_unit['option_id']) . '" ' . $selected . '>' . text($time_unit['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <p> </p>
                    <!-- Field: Notes -->
                    <div class="form-group">
<label for="note"><?php echo xlt('Notes'); ?></label>
                        <textarea class="form-control" id="note" name="note"><?php echo text($note); ?></textarea>
                    </div>
                    <p> </p>
                    <!-- Field: Medications List -->
                    <div class="form-group d-flex align-items-center">
                        <div class="custom-slider-switch mr-3">
                            <input type="checkbox" name="add_medications" id="medicationsSwitch" value="1" autocomplete="off">
                        </div>
                        <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Add to Medication List'); ?></label>
                    </div>
                </div>
                <div class="modal-footer justify-content-between" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= xlt('Close') ?></button>
                    <button type="submit" class="btn btn-success"><?= xlt('Save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Inicializar visibilidad de campos según los switches (se ejecuta inmediatamente al cargar)
(function() {
    console.log('Initializing edit modal switches...');
    
    // Función para alternar visibilidad de campos intravenosos
    function toggleIntravenousFieldsEdit() {
        var isChecked = $('#intravenousSwitchEdit').is(':checked');
        console.log('Intravenous switch:', isChecked);
        if (isChecked) {
            $('#intravenousFieldsEdit').show();
        } else {
            $('#intravenousFieldsEdit').hide();
        }
    }

    // Función para alternar visibilidad de campos de repetición
    function toggleOrderTypeFieldsEdit() {
        var type = $('#order_type').val();

        if (type === 'scheduled') {
            $('#repeatFieldsEdit').show();
        } else {
            $('#repeatFieldsEdit').hide();
        }

        if (type === 'prn') {
            $('#repeatFieldsEdit').hide();
        }

        if (type === 'unique' || type === 'stat') {
            $('#repeatFieldsEdit').hide();
        }
    }

    // Inicializar
    toggleOrderTypeFieldsEdit();

    $('#order_type').on('change', function() {
        toggleOrderTypeFieldsEdit();
    });


    // Función para alternar visibilidad de campos de notificaciones
    function toggleNotificationFieldsEdit() {
        var isChecked = $('#notificationSwitchEdit').is(':checked');
        console.log('Notification switch:', isChecked);
        if (isChecked) {
            $('#notificationFieldsEdit').show();
        } else {
            $('#notificationFieldsEdit').hide();
        }
    }

    // Inicializar visibilidad de campos según los switches
    toggleIntravenousFieldsEdit();
    toggleScheduledFieldsEdit();
    toggleNotificationFieldsEdit();

    // Escuchar cambios en los switches
    $('#intravenousSwitchEdit').on('change', function() {
        console.log('Intravenous changed');
        toggleIntravenousFieldsEdit();
    });

    $('#scheduledSwitchEdit').on('change', function() {
        console.log('Scheduled changed');
        toggleScheduledFieldsEdit();
    });

    $('#notificationSwitchEdit').on('change', function() {
        console.log('Notification changed');
        toggleNotificationFieldsEdit();
    });
    
    // Handler para el botón de cerrar
    $('#closeModalButton').on('click', function() {
        $('#editScheduleModal').modal('hide');
    });
    
    // Handler para el submit del formulario via AJAX
    $('#editScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'save_order_edit.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#editScheduleModal').modal('hide');
                    // Recargar la página para ver los cambios
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error al guardar los cambios. Por favor, intente nuevamente.');
            }
        });
    });
    
    console.log('Edit modal switches initialized');
})();
</script>
