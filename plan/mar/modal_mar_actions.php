<?php
// Asegurarse de que las funciones y globales estén disponibles
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar si se están solicitando detalles de la dosis mediante AJAX
if (isset($_GET['supply_id']) && isset($_GET['schedule_id'])) {
    $supply_id = $_GET['supply_id'];
    $schedule_id = $_GET['schedule_id'];

    $dose_details = getDoseDetails($supply_id);
    $dose_status = $dose_details['status'];
    $medications_text = getMedicationsDetails($schedule_id);

    if ($dose_details) {
        ob_start();
        ?>
        <div class="container mb-3">
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-1"><strong><?php echo xlt("Patient"); ?>:</strong> <?php echo text($dose_details['patient_name']); ?></p>
                    <p class="mb-1"><strong><?php echo xlt("Location"); ?>:</strong> <?php echo text($dose_details['facility_name'] . ", " . $dose_details['unit_name'] . ", " . $dose_details['room_name']); ?></p>
                    <p class="mb-1"><strong><?php echo xlt("Dose"); ?>:</strong> <?php echo xlt("#") . $dose_details['dose_number'] . "/" . $dose_details['max_dose']; ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($medications_text, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
            </div>
        </div>

        <!-- Botones de Acciones -->
        <div class="container mb-3">
        <div class="row">
            <div class="col">
                <?php if ($dose_status == 'Confirmed'): ?>
                    <!-- Si la dosis está confirmada, el botón cambia a "Confirmed Dose" y se deshabilita -->
                    <button class="btn btn-success btn-block" disabled>
                        <?php echo xlt("Confirmed Dose"); ?>
                    </button>
                <?php else: ?>
                    <!-- Si no está confirmada, el botón sigue igual y se puede hacer clic -->
                    <button class="btn btn-success btn-block" onclick="confirmDose(<?php echo attr($dose_details['supply_id']); ?>)">
                        <?php echo xlt("Confirm Dose"); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="col">
                <?php if ($dose_status == 'Confirmed'): ?>
                    <!-- Si la dosis está confirmada, el botón de "Adjust Schedule" se deshabilita -->
                    <button class="btn btn-primary btn-block" disabled>
                        <?php echo xlt("Adjust Schedule"); ?>
                    </button>
                <?php else: ?>
                    <!-- Si no está confirmada, el botón de "Adjust Schedule" sigue igual y se puede hacer clic -->
                    <button class="btn btn-primary btn-block" onclick="adjustSchedule(<?php echo attr($schedule_id); ?>)">
                        <?php echo xlt("Order Edit"); ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="col">
                <?php if ($dose_status == 'Confirmed'): ?>
                    <!-- Si la dosis está confirmada, el botón de "Suspend Schedule" se deshabilita -->
                    <button class="btn btn-danger btn-block" disabled>
                        <?php echo xlt("Suspend Schedule"); ?>
                    </button>
                <?php else: ?>
                    <!-- Si no está confirmada, el botón de "Suspend Schedule" sigue igual y se puede hacer clic -->
                    <button class="btn btn-danger btn-block" onclick="suspendSchedule(<?php echo attr($schedule_id); ?>)">
                        <?php echo xlt("Suspend Schedule"); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historial de Dosis Expandible -->
        <div class="container mb-3">
            <h6><?php echo xlt("Dose History"); ?></h6>
            <button class="btn btn-secondary btn-sm" onclick="toggleHistory()">
                <?php echo xlt("View History"); ?>
            </button>
            <div id="doseHistory" class="mt-2" style="display: none;">
                <!-- Historial cargado vía AJAX -->
            </div>
        </div>

        <!-- Evaluación de Alergia y Efectividad -->
        <div class="container">
            <h6><?php echo xlt("Allergy and Effectiveness Evaluation"); ?></h6>
            <button class="btn btn-info btn-block" onclick="evaluateAllergyEffectiveness(<?php echo attr($dose_details['supply_id']); ?>)">
                <?php echo xlt("Register Reaction or Effectiveness"); ?>
            </button>
        </div>
        <?php
        echo ob_get_clean(); // Devuelve solo el contenido capturado
    } else {
        echo "<p>" . xlt("No dose information available.") . "</p>";
    }
    die();
}

?>
<script>
    function toggleRepeatFields() {
        const switchElement = $('#scheduledSwitch');
        const repeatFields = $('#repeatFields');

        if (switchElement.is(':checked')) {
            repeatFields.show();
        } else {
            repeatFields.hide();
        }
    }

    // Delegación de eventos para el contenido dinámico
    $(document).on('change', '#scheduledSwitch', toggleRepeatFields);

    // Cuando el modal secundario se abre
    $('#modalOrderEdit').on('shown.bs.modal', function () {
        toggleRepeatFields(); // Inicializa el estado correcto
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
    function suspendSchedule(scheduleId) {
        $.ajax({
            url: 'modal_suspend_schedule.php',
            type: 'POST',
            data: { schedule_id: scheduleId },
            success: function(response) {
                alert('Schedule suspended successfully');
                $('#marActionsModal').modal('hide');
                location.reload();
            },
            error: function() {
                alert('Error suspending schedule');
            }
        });
    }

    function toggleHistory() {
        $('#doseHistory').toggle(); // Muestra/oculta historial
        if ($('#doseHistory').is(':visible')) {
            loadDoseHistory();
        }
    }

    function loadDoseHistory() {
        $.ajax({
            url: 'modal_dose_history.php',
            type: 'GET',
            data: { schedule_id: <?php echo json_encode($dose_details['schedule_id'] ?? ''); ?> },
            success: function(response) {
                $('#doseHistory').html(response);
            },
            error: function() {
                alert('Error loading dose history');
            }
        });
    }

    function evaluateAllergyEffectiveness(supplyId) {
        // Abrir formulario secundario en un modal para registrar alergia o evaluar efectividad
    }
</script>
