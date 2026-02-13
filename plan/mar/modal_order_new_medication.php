<?php
$sql_query = "SELECT CONCAT(f.name, ' - ', u.unit_name, ' - ', r.room_name, ' - ', b.bed_name) AS patient_location 
	FROM beds_patients AS bp
	INNER JOIN facility AS f ON bp.facility_id = f.id
	INNER JOIN units AS u ON bp.current_unit_id = u.id
	INNER JOIN rooms AS r ON bp.current_room_id = r.id
	INNER JOIN beds AS b ON bp.current_bed_id = b.id
WHERE bp.patient_id = ? AND bp.status = 'admitted';";
$result = sqlStatement($sql_query, array($patient_id));
$row = sqlFetchArray($result);

?>
<!-- Modal for "Order New Medication" -->
<div class="modal fade" id="openMedicationModal<?= $patient_id ?>" tabindex="-1" aria-labelledby="openMedicationModalLabel<?= $patient_name ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="openMedicationForm<?= $patient_id ?>" method="POST" action="save_order_medication.php">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo xlt('Order New Medication'); ?></h5>
                    <p><strong><?php echo xlt('Patient'); ?>:</strong> <?php echo text($patient_name); ?></p>
                    <p><strong><?php echo xlt('Location'); ?>:</strong> 
                        <?php echo $row['patient_location']; ?>
                    </p>
                    <input type="hidden" name="patient_id" value="<?php echo attr($patient_id); ?>">
                </div>
                <div class="modal-body">
                        <div class="row">
                            <!-- Field: Active -->
                            <div class="col-md-6">
                                <div class="form-group d-flex align-items-center">
                                <div class="custom-slider-switch mr-3">
                                        <input type="checkbox" name="active" id="activeSwitch" value="1" autocomplete="off" checked>
                                        <title for="activeactiveSwitch"><?php echo xl('Active Green, Deactive Grey'); ?></title>
                                    </div>
                                    <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Active'); ?></label>
                                </div>
                            </div>
                            <!-- Field: Start Date/Time -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_date"><?php echo xlt('Start Date/Time'); ?></label>
                                    <input type="datetime-local" class="form-control" id="start_date" name="start_date" data-toggle="message" title="<?php echo xlt('Start Date & Time first Infusion'); ?>" autocomplete="off" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Provider -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="provider_id"><?php echo xlt('Provider'); ?></label>
                                    <select class="form-control" id="provider_id" name="provider_id" required>
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $provider_query = "SELECT id, fname, lname, mname FROM users WHERE authorized=1";
                                        $providers_result = sqlStatement($provider_query);
                                        while ($provider = sqlFetchArray($providers_result)) {
                                            echo '<option value="' . attr($provider['id']) . '">' . text($provider['fname'] . ' ' . $provider['lname']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Drug -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="drug"><?php echo xlt('Drug'); ?></label>
                                    <input type="text" class="form-control" id="drug" name="drug" required data-toggle="message" title="<?php echo xl('Put only the drug'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Field: Dosage -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dosage"><?php echo xlt('Dosage'); ?></label>
                                    <input type="text" class="form-control" id="dosage" name="dosage" required>
                                </div>
                            </div>
                            <!-- Field: Units -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="size"><?php echo xlt('Units'); ?></label>
                                    <input type="text" class="form-control" id="size" name="size" required>
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
                                        while ($unit = sqlFetchArray($units_result)) {
                                            echo '<option value="' . attr($unit['option_id']) . '">' . text($unit['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Drug Form -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="form"><?php echo xlt('Drug Form'); ?></label>
                                    <select class="form-control" id="form" name="form" required>
                                        <option value="" style="color: gray;"><?php echo xlt('-- Select One --'); ?></option>
                                        <?php
                                        $form_query = "SELECT option_id, title FROM list_options WHERE list_id = 'drug_form'";
                                        $form_result = sqlStatement($form_query);
                                        while ($form = sqlFetchArray($form_result)) {
                                            echo '<option value="' . attr($form['option_id']) . '">' . text($form['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
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
                                    while ($route = sqlFetchArray($route_result)) {
                                        echo '<option value="' . attr($route['option_id']) . '">'
                                            . text($route['title']) . '</option>';
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
                                <input type="checkbox" name="intravenous_switch" id="intravenousSwitch" value="1" autocomplete="off">
                            </div>
                            <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Intravenous'); ?></label>
                        </div>
                    </div>
                    <!-- Intravenous fields (only visible if Intravenous is Yes) -->
                    <div id="intravenousFields"  style="background-color: #f9e6e6; border-top: 1px solid #dee2e6;">
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
                                        while ($vehicle = sqlFetchArray($vehicle_result)) {
                                            echo '<option value="' . attr($vehicle['title']) . '">' . text($vehicle['title']) . '</option>';
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
                                        while ($catheter = sqlFetchArray($catheter_result)) {
                                            echo '<option value="' . attr($catheter['title']) . '">' . text($catheter['title']) . '</option>';
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
                                        while ($unit = sqlFetchArray($units_result)) {
                                            echo '<option value="' . attr($unit['title']) . '">' . text($unit['title']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- Field: Concentration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="concentration"><?php echo xlt('Concentration'); ?></label>
                                    <input type="number" class="form-control" id="concentration" name="concentration" step="0.01">
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
                                        while ($unit = sqlFetchArray($units_result)) {
                                            echo '<option value="' . attr($unit['title']) . '">' . text($unit['title']) . '</option>';
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
                                    <input type="number" class="form-control" id="infusion_rate" name="infusion_rate" step="0.01">
                                </div>
                            </div>

                            <!-- Field: Total Volume -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_volume"><?php echo xlt('Total Volume (ml)'); ?></label>
                                    <input type="number" class="form-control" id="total_volume" name="total_volume" step="0.01">
                                </div>
                            </div>

                            <!-- Field: Duration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="iv_duration"><?php echo xlt('Total Duration'); ?></label>
                                    <input type="number" class="form-control" id="iv_duration" name="iv_duration">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" class="form-control" name="iv_status" value="Active" id="iv_status">
                    </div>
                    <p> </p>
                    <!-- Field: Scheduled -->
                    <div class="form-group d-flex align-items-center">
                        <div class="custom-slider-switch ml-3">
                            <input type="checkbox" name="scheduled" id="scheduledSwitch" value="1" autocomplete="off">
                        </div>
                        <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Scheduled'); ?></label>
                    </div>

                    <!-- Repeat medication fields (only visible if One-time Medication is No) -->
                    <div id="repeatFields" style="background-color: #e0e1fb; border-top: 1px solid #dee2e6;">
                        <!-- Group: Unit Frequency and Time Frequency -->
                        <div class="form-group row">
                            <!-- Field: Unit Frequency -->
                            <div class="col-md-6">
                                <label for="unit_frequency"><?php echo xlt('Frequency'); ?></label>
                                <input type="number" class="form-control" id="unit_frequency" name="unit_frequency">
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
                                        echo '<option value="' . attr($time_unit['option_id']) . '">' . text($time_unit['title']) . '</option>';
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
                                <input type="number" class="form-control" id="duration" name="duration">
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
                                        echo '<option value="' . attr($unit_duration['option_id']) . '">' . text($unit_duration['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <!-- Field: Start Date/Time -->
                        <div class="col-md-6">
                           <div class="form-group">
                                <label for="end_date"><?php echo xlt('End Date/Time'); ?></label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                    <p> </p>
                    <div class="form-group d-flex align-items-center">
                        <div class="custom-slider-switch ls-3">
                            <input type="checkbox" name="notifications" id="notificationSwitch" value="1" autocomplete="off">
                        </div>
                        <label class="mb-0" style="font-weight: bold;"><?php echo xlt('Notifications'); ?></label>
                    </div>
                    <!-- Field: Alarms -->
                    <div id="notificationFields" style="background-color: #e0fbe1; padding: 15px; border-radius: 5px;">
                        <div class="row">
                            <div class="col">
                                <label for="alarm1_unit"><?php echo xlt('First Alarm (Minutes Offset)'); ?></label>
                                <input type="number" class="form-control" id="alarm1_unit" name="alarm1_unit">
                            </div>
                            <div class="col">
                                <label for="alarm1_time"><?php echo xlt('Unit of Time'); ?></label>
                                <select class="form-control" id="alarm1_time" name="alarm1_time">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    $time_unit_query = "SELECT option_id, title FROM list_options WHERE list_id='time_unit'";
                                    $time_unit_result = sqlStatement($time_unit_query);
                                    while ($time_unit = sqlFetchArray($time_unit_result)) {
                                        echo '<option value="' . attr($time_unit['option_id']) . '">' . text($time_unit['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col">
                                <label for="alarm2_unit"><?php echo xlt('Second Alarm (Minutes Offset)'); ?></label>
                                <input type="number" class="form-control" id="alarm2_unit" name="alarm2_unit">
                            </div>
                            <div class="col">
                                <label for="alarm2_time"><?php echo xlt('Unit of Time'); ?></label>
                                <select class="form-control" id="alarm2_time" name="alarm2_time">
                                    <option value=""><?php echo xlt('-- Select One --'); ?></option>
                                    <?php
                                    // Reutilizamos la misma consulta para ambos selects
                                    $time_unit_result = sqlStatement($time_unit_query);
                                    while ($time_unit = sqlFetchArray($time_unit_result)) {
                                        echo '<option value="' . attr($time_unit['option_id']) . '">' . text($time_unit['title']) . '</option>';
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
                        <textarea class="form-control" id="note" name="note"></textarea>
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
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-success"><?= xlt('Save') ?></button>
                    <button type="button" class="btn btn-secondary" id="closeModalButton"><?= xlt('Close') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script JavaScript para Validación, Autocompletado y verifica responsable functions.js -->
<script>
        $(document).ready(function() {
            $('#closeModalButton').click(function() {
                $('#newMedicationModal<?= $patient_id ?>').modal('hide');
            });
        });
    var searchUsersUrl = "<?php echo $GLOBALS['webroot']; ?>/inpatient/search_users.php";

    $(document).ready(function() {
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
    });

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

    $(document).ready(function() {
        $('#route').change(function() {
            // Obtener el `option_id` del atributo data-option-id
            var selectedOptionId = $(this).find(':selected').data('option-id');
            // Actualizar el campo oculto
            $('#route_option_id').val(selectedOptionId);
        });
    });

</script>