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
        pr.drug, pr.dosage, pr.size, pr.unit,
        form_opt.title AS form_title,
        route_opt.title AS route_title,
        ps.start_date, ps.end_date, ps.unit_frequency, ps.time_frequency, ps.unit_duration, ps.time_duration,
        COUNT(psu.supply_id) as total_doses,
        SUM(CASE WHEN psu.status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_doses,
        SUM(CASE WHEN psu.status = 'Pending' AND psu.schedule_datetime > NOW() THEN 1 ELSE 0 END) as future_doses
    FROM prescriptions_schedule ps
        LEFT JOIN prescriptions AS pr ON ps.prescription_id = pr.id
        LEFT JOIN patient_data AS p ON ps.patient_id = p.pid
        LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid AND bp.status = 'admitted'
        LEFT JOIN facility AS f ON f.id = bp.facility_id
        LEFT JOIN units AS u ON u.id = bp.current_unit_id
        LEFT JOIN rooms AS r ON r.id = bp.current_room_id
        LEFT JOIN beds AS b ON b.id = bp.current_bed_id
        LEFT JOIN prescriptions_supply AS psu ON psu.schedule_id = ps.schedule_id
        LEFT JOIN list_options AS form_opt ON form_opt.list_id = 'drug_form' AND form_opt.option_id = pr.form
        LEFT JOIN list_options AS route_opt ON route_opt.list_id = 'drug_route' AND route_opt.option_id = pr.route
    WHERE ps.schedule_id = ?
    GROUP BY ps.schedule_id;
";

$result = sqlQuery($query, [$schedule_id]);

if (!$result) {
    die(xlt('No data found for the provided schedule ID.'));
}

// Asignar resultados a variables
$patientFullName = $result['patient_full_name'];
$location = $result['Location'];
$drug = $result['drug'];
$dosage = $result['dosage'];
$size = $result['size'];
$unit = $result['unit'];
$form = $result['form_title'] ?? 'N/A';
$route = $result['route_title'] ?? 'N/A';
$startDate = $result['start_date'];
$endDate = $result['end_date'] ?? null;
$totalDoses = $result['total_doses'];
$confirmedDoses = $result['confirmed_doses'];
$futureDoses = $result['future_doses'];

// Obtener razones de suspensión desde list_options
$reasons_query = "SELECT option_id, title FROM list_options WHERE list_id = 'reason_discontinue_medication' AND activity = 1 ORDER BY seq, title";
$reasons_result = sqlStatement($reasons_query);
?>

<div class="modal fade" id="suspendScheduleModal" tabindex="-1" aria-labelledby="suspendScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="suspendScheduleForm" method="POST" action="save_order_suspend.php">
                <input type="hidden" name="schedule_id" value="<?php echo attr($schedule_id); ?>">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-ban"></i> <?php echo xlt('Suspend Medication Order'); ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php echo xlt('Close'); ?>"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Patient Information -->
                    <div class="alert alert-info">
                        <h6 class="mb-2"><strong><?php echo xlt('Patient Information'); ?></strong></h6>
                        <p class="mb-1"><strong><?php echo xlt('Patient'); ?>:</strong> <?php echo text($patientFullName); ?></p>
                        <p class="mb-1"><strong><?php echo xlt('Location'); ?>:</strong> <?php echo text($location); ?></p>
                    </div>

                    <!-- Medication Details -->
                    <div class="alert alert-warning">
                        <h6 class="mb-2"><strong><?php echo xlt('Medication Details'); ?></strong></h6>
                        <p class="mb-1"><strong><?php echo xlt('Drug'); ?>:</strong> <?php echo text($drug); ?></p>
                        <p class="mb-1">
                            <strong><?php echo xlt('Dosage'); ?>:</strong> 
                            <?php 
                            // Construir la dosificación con etiquetas claras
                            $dosage_parts = [];
                            if (!empty($dosage)) {
                                $dosage_parts[] = $dosage . ' ' . xlt('units');
                            }
                            if (!empty($size)) {
                                $dosage_parts[] = $size . ' ' . $unit;
                            }
                            echo text(implode(' - ', $dosage_parts));
                            ?>
                        </p>
                        <p class="mb-1"><strong><?php echo xlt('Form'); ?>:</strong> <?php echo text($form); ?></p>
                        <p class="mb-1"><strong><?php echo xlt('Route'); ?>:</strong> <?php echo text($route); ?></p>
                        <p class="mb-1">
                            <strong><?php echo xlt('Period'); ?>:</strong> 
                            <?php 
                            $start_formatted = date('Y-m-d H:i', strtotime($startDate));
                            $end_formatted = !empty($endDate) && $endDate != '0000-00-00 00:00:00' 
                                ? date('Y-m-d H:i', strtotime($endDate)) 
                                : xlt('Ongoing');
                            echo text($start_formatted . ' - ' . $end_formatted);
                            ?>
                        </p>
                    </div>

                    <!-- Dose Statistics -->
                    <div class="alert alert-secondary">
                        <h6 class="mb-2"><strong><?php echo xlt('Dose Statistics'); ?></strong></h6>
                        <div class="row">
                            <div class="col-md-4">
                                <p class="mb-0"><strong><?php echo xlt('Total Doses'); ?>:</strong> <?php echo text($totalDoses); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-0"><strong><?php echo xlt('Confirmed'); ?>:</strong> <?php echo text($confirmedDoses); ?></p>
                            </div>
                            <div class="col-md-4">
                                <p class="mb-0"><strong><?php echo xlt('Future Pending'); ?>:</strong> <?php echo text($futureDoses); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Suspension Scope -->
                    <div class="form-group mb-3">
                        <label for="suspension_scope" class="form-label"><strong><?php echo xlt('Suspension Scope'); ?>:</strong> <span class="text-danger">*</span></label>
                        <select class="form-select" id="suspension_scope" name="suspension_scope" required>
                            <option value=""><?php echo xlt('-- Select Scope --'); ?></option>
                            <option value="all_future"><?php echo xlt('All Future Doses'); ?> (<?php echo text($futureDoses); ?> <?php echo xlt('doses'); ?>)</option>
                            <option value="all_pending"><?php echo xlt('All Pending Doses (including past due)'); ?></option>
                            <option value="complete_order"><?php echo xlt('Complete Order (mark schedule as suspended)'); ?></option>
                        </select>
                        <small class="form-text text-muted">
                            <?php echo xlt('Select which doses should be suspended. Confirmed doses will not be affected.'); ?>
                        </small>
                    </div>

                    <!-- Suspension Reason -->
                    <div class="form-group mb-3">
                        <label for="suspension_reason" class="form-label"><strong><?php echo xlt('Suspension Reason'); ?>:</strong> <span class="text-danger">*</span></label>
                        <select class="form-select" id="suspension_reason" name="suspension_reason" required>
                            <option value=""><?php echo xlt('-- Select Reason --'); ?></option>
                            <?php
                            while ($reason = sqlFetchArray($reasons_result)) {
                                echo '<option value="' . attr($reason['option_id']) . '">' . text($reason['title']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Additional Notes -->
                    <div class="form-group mb-3">
                        <label for="suspension_notes" class="form-label"><strong><?php echo xlt('Additional Notes'); ?>:</strong></label>
                        <textarea class="form-control" id="suspension_notes" name="suspension_notes" rows="3" placeholder="<?php echo xlt('Provide additional details about the suspension...'); ?>"></textarea>
                    </div>

                    <!-- Confirmation Warning -->
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-triangle"></i> <strong><?php echo xlt('Warning'); ?></strong></h6>
                        <p class="mb-0"><?php echo xlt('This action will suspend the selected doses and cannot be easily reversed. The suspension will be recorded for audit purposes. Please confirm that you want to proceed.'); ?></p>
                    </div>

                    <!-- Confirmation Checkbox -->
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm_suspension" name="confirm_suspension" value="1" required>
                        <label class="form-check-label" for="confirm_suspension">
                            <strong><?php echo xlt('I confirm that I want to suspend this medication order'); ?></strong>
                        </label>
                    </div>
                </div>
                
                <div class="modal-footer justify-content-between" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> <?php echo xlt('Cancel'); ?>
                    </button>
                    <button type="submit" class="btn btn-danger" id="submitSuspendBtn" disabled>
                        <i class="fas fa-ban"></i> <?php echo xlt('Suspend Order'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Habilitar el botón de submit solo cuando se marque la confirmación
$(document).ready(function() {
    $('#confirm_suspension').on('change', function() {
        $('#submitSuspendBtn').prop('disabled', !this.checked);
    });

    // Handler para el submit del formulario via AJAX
    $('#suspendScheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validar que todos los campos requeridos estén completos
        if (!$('#suspension_scope').val() || !$('#suspension_reason').val() || !$('#confirm_suspension').is(':checked')) {
            alert('<?php echo xlt("Please complete all required fields and confirm the suspension."); ?>');
            return false;
        }
        
        var formData = $(this).serialize();
        
        // Confirmar una vez más
        if (!confirm('<?php echo xlt("Are you sure you want to suspend this medication order? This action will be recorded for audit purposes."); ?>')) {
            return false;
        }
        
        $.ajax({
            url: 'save_order_suspend.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    $('#suspendScheduleModal').modal('hide');
                    $('#marActionsModal').modal('hide');
                    // Recargar la página para ver los cambios
                    location.reload();
                } else {
                    alert('<?php echo xlt("Error"); ?>: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('<?php echo xlt("Error suspending the order. Please try again."); ?>');
            }
        });
    });
});
</script>
