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
    
    // Obtener reacciones de TODAS las dosis del schedule (historial completo)
    $all_reactions_query = "
        SELECT ps.supply_id, ps.dose_number, ps.effectiveness_score, ps.effectiveness_notes, 
               ps.reaction_description, ps.reaction_time, ps.reaction_severity, ps.reaction_notes
        FROM prescriptions_supply ps
        WHERE ps.schedule_id = ?
          AND (ps.effectiveness_score IS NOT NULL OR ps.effectiveness_notes IS NOT NULL 
               OR ps.reaction_description IS NOT NULL OR ps.reaction_severity IS NOT NULL)
        ORDER BY ps.dose_number ASC
    ";
    $all_reactions = sqlStatement($all_reactions_query, [$schedule_id]);
    $reactions_history = [];
    while ($reaction_row = sqlFetchArray($all_reactions)) {
        $reactions_history[] = $reaction_row;
    }
    
    // Debug: Log whether dose details were found
    if ($dose_details) {
        error_log("modal_mar_actions.php: Found dose details for supply_id=$supply_id");
        error_log("modal_mar_actions.php: Found " . count($reactions_history) . " doses with reactions in schedule");
        
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
            // Contar cuántas dosis tienen evaluaciones
            $doses_with_evaluations = count($reactions_history);
            
            // Verificar si la dosis actual tiene evaluaciones
            $current_has_effectiveness = !empty($dose_details['effectiveness_score']) || !empty($dose_details['effectiveness_notes']);
            $current_has_reactions = (!empty($dose_details['reaction_description']) && 
                             strtolower($dose_details['reaction_description']) !== 'no reaction' && 
                             strtolower($dose_details['reaction_description']) !== 'nothing') 
                             || !empty($dose_details['reaction_severity']);
            
            // Mostrar mensaje resumen si hay evaluaciones en el historial
            if ($doses_with_evaluations > 0) {
                echo '<div class="alert alert-info">';
                echo '<i class="fas fa-info-circle"></i> ';
                echo xlt("Effectiveness and reactions have been registered for") . ' ' . $doses_with_evaluations . ' ';
                echo ($doses_with_evaluations === 1) ? xlt("dose") : xlt("doses");
                echo '.<br><small class="text-muted">';
                echo xlt("Press View History to see details");
                echo '</small></div>';
            }
            
            // Mostrar evaluaciones de la dosis actual si no están en el historial
            if (($current_has_effectiveness || $current_has_reactions) && $doses_with_evaluations === 0) {
                echo '<div class="alert alert-info">';
                if ($current_has_effectiveness) {
                    echo '<strong>' . xlt("Effectiveness") . ':</strong> ' . text($dose_details['effectiveness_score'] ?? 'N/A');
                    if (!empty($dose_details['effectiveness_notes'])) {
                        echo '<br><small>' . text($dose_details['effectiveness_notes']) . '</small>';
                    }
                }
                if ($current_has_effectiveness && $current_has_reactions) {
                    echo '<hr>';
                }
                if ($current_has_reactions) {
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
            
            // Mostrar botón para registrar evaluación en la dosis actual
            ?>
            <button class="btn btn-info btn-sm" onclick="registerReactions(<?php echo attr($supply_id); ?>, <?php echo attr($schedule_id); ?>)">
                <i class="fas fa-plus-circle"></i> <?php echo xlt("Register Evaluation for this Dose"); ?>
            </button>
            <div id="reactionsForm" class="mt-2" style="display: none;">
                <!-- Formulario de reacciones cargado vía AJAX -->
            </div>
        </div>
        </div>
        <div class="d-flex justify-content-start mt-3 pt-3 border-top">
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
