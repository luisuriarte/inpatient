<?php
// Asegurarse de que las funciones y globales estén disponibles
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar si se están solicitando detalles de la dosis mediante AJAX
if (isset($_GET['supply_id']) && isset($_GET['schedule_id'])) {
    $supply_id = $_GET['supply_id'];
    $schedule_id = $_GET['schedule_id'];

    // Debug: Log the received parameters
    error_log("modal_mar_actions.php: Received supply_id=$supply_id, schedule_id=$schedule_id");

    $dose_details = getDoseDetails($supply_id);
    
    // Debug: Log whether dose details were found
    if ($dose_details) {
        error_log("modal_mar_actions.php: Found dose details for supply_id=$supply_id");
        $dose_status = $dose_details['status'] ?? null;
        $medications_text = getMedicationsDetails($schedule_id);
        
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
            <button class="btn btn-secondary btn-sm" onclick="viewDoseHistory(<?php echo attr($schedule_id); ?>)">
                <?php echo xlt("View History"); ?>
            </button>
            <div id="doseHistory" class="mt-2" style="display: none;">
                <!-- Historial cargado vía AJAX -->
            </div>
        </div>

        <!-- Evaluación de Alergia y Efectividad -->
        <div class="container mt-3">
            <h6><?php echo xlt("Allergy and Effectiveness Evaluation"); ?></h6>
            <?php
            // Mostrar estado actual si existe
            $has_effectiveness = !empty($dose_details['effectiveness_score']) || !empty($dose_details['effectiveness_notes']);
            $has_reactions = (!empty($dose_details['reaction_description']) && 
                             strtolower($dose_details['reaction_description']) !== 'no reaction' && 
                             strtolower($dose_details['reaction_description']) !== 'nothing') 
                             || !empty($dose_details['reaction_severity']);
            
            if ($has_effectiveness || $has_reactions) {
                echo '<div class="alert alert-info">';
                if ($has_effectiveness) {
                    echo '<strong>' . xlt("Effectiveness") . ':</strong> ' . text($dose_details['effectiveness_score'] ?? 'N/A');
                    if (!empty($dose_details['effectiveness_notes'])) {
                        echo '<br><small>' . text($dose_details['effectiveness_notes']) . '</small>';
                    }
                }
                if ($has_effectiveness && $has_reactions) {
                    echo '<hr>';
                }
                if ($has_reactions) {
                    echo '<strong>' . xlt("Reactions") . ':</strong> ' . text($dose_details['reaction_description'] ?? 'N/A');
                    if (!empty($dose_details['reaction_severity'])) {
                        echo ' (' . text($dose_details['reaction_severity']) . ')';
                    }
                    if (!empty($dose_details['reaction_time'])) {
                        echo '<br><small>' . xlt("Time") . ': ' . text(date('Y-m-d H:i', strtotime($dose_details['reaction_time']))) . '</small>';
                    }
                }
                echo '</div>';
            }
            ?>
            <button class="btn btn-info btn-sm" onclick="registerReactions(<?php echo attr($supply_id); ?>, <?php echo attr($schedule_id); ?>)">
                <i class="fas fa-edit"></i> <?php echo xlt($has_effectiveness || $has_reactions ? "Edit Evaluation" : "Register Reaction or Effectiveness"); ?>
            </button>
            <div id="reactionsForm" class="mt-2" style="display: none;">
                <!-- Formulario de reacciones cargado vía AJAX -->
            </div>
        </div>
        </div>
        <div class="d-flex justify-content-end mt-3 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" onclick="if(typeof closeMarModal === 'function') { closeMarModal(); } else { document.getElementById('marActionsModal').style.display='none'; }">
                    <?php echo xlt("Close"); ?>
                </button>
        </div>
        <?php
        echo ob_get_clean(); // Devuelve solo el contenido capturado
    } else {
        echo "<p>" . xlt("No dose information available.") . "</p>";
    }
    die();
} else {
    // Parameters not provided - this might be the issue
    error_log("modal_mar_actions.php: Missing required parameters - supply_id: " . ($_GET['supply_id'] ?? 'NULL') . ", schedule_id: " . ($_GET['schedule_id'] ?? 'NULL'));
    echo "<p>" . xlt("Required parameters not provided.") . "</p>";
    die();
}

?>
