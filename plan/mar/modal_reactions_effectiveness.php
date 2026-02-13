<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Debug: Log parameters received
error_log("modal_reactions_effectiveness.php - GET params: " . print_r($_GET, true));

if (isset($_GET['supply_id']) && isset($_GET['schedule_id'])) {
    $supply_id = (int)$_GET['supply_id'];
    $schedule_id = (int)$_GET['schedule_id'];
    
    error_log("Processing supply_id: $supply_id, schedule_id: $schedule_id");
    
    // Obtener detalles actuales de la dosis
    $supply_details = sqlQuery("
        SELECT ps.*, p.fname, p.lname, pn.drug_id,
               d.name as drug_name
        FROM prescriptions_supply ps
        LEFT JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
        LEFT JOIN prescriptions pn ON sch.prescription_id = pn.id
        LEFT JOIN patient_data p ON sch.patient_id = p.pid
        LEFT JOIN drugs d ON pn.drug_id = d.drug_id
        WHERE ps.supply_id = ? AND ps.schedule_id = ?
    ", [$supply_id, $schedule_id]);
    
    // Obtener opciones de efectividad
    $effectiveness_options = sqlStatement("
        SELECT option_id, title 
        FROM list_options 
        WHERE list_id = 'drug_effectiveness' 
        ORDER BY seq, title
    ");
    
    // Obtener opciones de severidad
    $severity_options = sqlStatement("
        SELECT option_id, title 
        FROM list_options 
        WHERE list_id = 'severity_ccda' 
        ORDER BY seq, title
    ");
    
    // Obtener opciones de reacciones predefinidas
    $reaction_options = sqlStatement("
        SELECT option_id, title 
        FROM list_options 
        WHERE list_id = 'drug_reaction' 
        ORDER BY seq, title
    ");
    
    ob_start();
    ?>
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-12">
                <h5><?php echo xlt("Medication Effectiveness & Reactions"); ?></h5>
                <p class="text-muted mb-2">
                    <strong><?php echo xlt("Patient"); ?>:</strong> 
                    <?php echo text($supply_details['fname'] . ' ' . $supply_details['lname']); ?><br>
                    <strong><?php echo xlt("Medication"); ?>:</strong> 
                    <?php echo text($supply_details['drug_name']); ?>
                </p>
            </div>
        </div>

        <form id="reactionsEffectivenessForm" method="POST" action="save_reactions_effectiveness.php">
            <input type="hidden" name="supply_id" value="<?php echo attr($supply_id); ?>">
            <input type="hidden" name="schedule_id" value="<?php echo attr($schedule_id); ?>">
            
            <!-- Effectiveness Section -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><?php echo xlt("Effectiveness Evaluation"); ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="effectiveness_score" class="form-label"><?php echo xlt("Effectiveness Score"); ?></label>
                        <select class="form-select" id="effectiveness_score" name="effectiveness_score">
                            <option value=""><?php echo xlt("Select effectiveness..."); ?></option>
                            <?php while ($option = sqlFetchArray($effectiveness_options)) { ?>
                                <option value="<?php echo attr($option['option_id']); ?>" 
                                    <?php echo ($supply_details['effectiveness_score'] == $option['option_id']) ? 'selected' : ''; ?>>
                                    <?php echo xlt($option['title']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="effectiveness_notes" class="form-label"><?php echo xlt("Effectiveness Notes"); ?></label>
                        <textarea class="form-control" id="effectiveness_notes" name="effectiveness_notes" rows="3" 
                                  placeholder="<?php echo xlt("Enter observations about medication effectiveness..."); ?>"><?php echo text($supply_details['effectiveness_notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Reactions Section -->
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><?php echo xlt("Adverse Reactions"); ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="reaction_type" class="form-label"><?php echo xlt("Common Reaction Types"); ?></label>
                        <select class="form-select" id="reaction_type" name="reaction_type" onchange="addReactionTypeToDescription()">
                            <option value=""><?php echo xlt("Select common reaction..."); ?></option>
                            <?php while ($option = sqlFetchArray($reaction_options)) { ?>
                                <option value="<?php echo attr($option['title']); ?>">
                                    <?php echo xlt($option['title']); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <div class="form-text"><?php echo xlt("Select to add to description below"); ?></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reaction_description" class="form-label"><?php echo xlt("Reaction Description"); ?></label>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?php echo xlt("This field is automatically populated from common reactions selection above. For detailed descriptions, use the notes field below."); ?>
                        </div>
                        <textarea class="form-control" id="reaction_description" name="reaction_description" rows="2" 
                                  readonly placeholder="<?php echo xlt("Select reactions from the dropdown above"); ?>"><?php echo text($supply_details['reaction_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reaction_time" class="form-label"><?php echo xlt("Reaction Time"); ?></label>
                                <input type="datetime-local" class="form-control" id="reaction_time" name="reaction_time" 
                                       value="<?php echo !empty($supply_details['reaction_time']) ? date('Y-m-d\TH:i', strtotime($supply_details['reaction_time'])) : ''; ?>">
                                <div class="form-text"><?php echo xlt("When the reaction occurred"); ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reaction_severity" class="form-label"><?php echo xlt("Reaction Severity"); ?></label>
                                <select class="form-select" id="reaction_severity" name="reaction_severity">
                                    <option value=""><?php echo xlt("Select severity..."); ?></option>
                                    <?php while ($option = sqlFetchArray($severity_options)) { ?>
                                        <option value="<?php echo attr($option['option_id']); ?>" 
                                            <?php echo ($supply_details['reaction_severity'] == $option['option_id']) ? 'selected' : ''; ?>>
                                            <?php echo xlt($option['title']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reaction_notes" class="form-label"><?php echo xlt("Reaction Notes"); ?></label>
                        <textarea class="form-control" id="reaction_notes" name="reaction_notes" rows="3" 
                                  placeholder="<?php echo xlt("Additional notes about the reaction..."); ?>"><?php echo text($supply_details['reaction_notes'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> <?php echo xlt("Cancel"); ?>
                </button>
                <div>
                    <button type="reset" class="btn btn-outline-warning me-2">
                        <i class="fas fa-undo"></i> <?php echo xlt("Reset"); ?>
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo xlt("Save Evaluation"); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        $('#reactionsEffectivenessForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: 'save_reactions_effectiveness.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                beforeSend: function() {
                    // Mostrar loading
                    $('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php echo xlt("Saving..."); ?>');
                },
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        // Mostrar éxito
                        showAlert('success', response.message);
                        
                        // Cerrar modal después de un breve retraso
                        setTimeout(function() {
                            // Cerrar el modal de reacciones
                            $('#reactionsModal').modal('hide');
                            
                            // Forzar la eliminación del backdrop si queda alguno
                            setTimeout(function() {
                                $('.modal-backdrop').remove();
                                $('body').removeClass('modal-open');
                                $('body').css('overflow', 'auto');
                            }, 200);
                        }, 1500);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    showAlert('danger', 'Error saving evaluation. Please try again. (' + error + ')');
                },
                complete: function() {
                    // Restaurar botón
                    $('button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save"></i> <?php echo xlt("Save Evaluation"); ?>');
                }
            });
        });
        
        // Función para mostrar alertas dentro del modal
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Insertar alerta al principio del modal
            $('.container-fluid').prepend(alertHtml);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    });
    
    // Función para agregar tipo de reacción al campo de descripción
    function addReactionTypeToDescription() {
        const selectedType = $('#reaction_type').val();
        const descriptionField = $('#reaction_description');
        const currentText = descriptionField.val();
        
        if (selectedType) {
            if (currentText.trim() === '') {
                descriptionField.val(selectedType);
            } else {
                // Agregar con coma si no está ya incluido
                if (!currentText.toLowerCase().includes(selectedType.toLowerCase())) {
                    descriptionField.val(currentText + ', ' + selectedType);
                }
            }
        }
        
        // Resetear el select
        $('#reaction_type').val('');
    }
    </script>
    <?php
    echo ob_get_clean();
} else {
    echo "<p class='alert alert-danger'>" . xlt("Invalid parameters") . "</p>";
}
?>