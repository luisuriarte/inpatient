<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");
require_once($GLOBALS['srcdir'] . '/formatting.inc.php');

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    die('Schedule ID is required');
}

// Consulta para obtener el historial completo de dosis
$history_query = "
    SELECT ps.supply_id, ps.schedule_datetime, ps.supply_datetime, ps.status, 
           ps.dose_number, ps.max_dose, ps.confirmation_datetime,
           ps.effectiveness_score, ps.effectiveness_notes, ps.reaction_description,
           ps.reaction_time, ps.reaction_notes, ps.reaction_severity,
           CONCAT(u.lname, ', ', u.fname, 
               IF(u.mname IS NOT NULL AND u.mname != '', CONCAT(' ', u.mname), '')
           ) AS supplied_by_name
    FROM prescriptions_supply ps
    LEFT JOIN users u ON ps.supplied_by = u.id
    WHERE ps.schedule_id = ? AND status != 'Cancelled'
    ORDER BY ps.schedule_datetime DESC
";

$result = sqlStatement($history_query, [$schedule_id]);

// Contar estadísticas en PHP
$total_doses = 0;
$confirmed_count = 0;
$pending_count = 0;
$effectiveness_count = 0;
$reactions_count = 0;

$result_count = sqlStatement($history_query, [$schedule_id]);
while ($count_row = sqlFetchArray($result_count)) {
    $total_doses++;
    
    if ($count_row['status'] === 'Confirmed') {
        $confirmed_count++;
    } elseif ($count_row['status'] === 'Pending') {
        $pending_count++;
    }
    
    if (!empty($count_row['effectiveness_score'])) {
        $effectiveness_count++;
    }
    
    // Contar reacciones solo si no es "No Reaction"
    if (!empty($count_row['reaction_description']) && 
        strtolower($count_row['reaction_description']) !== 'no reaction' && 
        strtolower($count_row['reaction_description']) !== 'nothing') {
        $reactions_count++;
    }
}
?>

<div class="modal-header">
    <h5 class="modal-title">
        <i class="fas fa-history"></i> <?php echo xlt('Dose Administration History'); ?>
    </h5>
    <button type="button" class="btn-close" onclick="closeMarModal()" aria-label="<?php echo xlt('Close'); ?>"></button>
</div>
<div class="modal-body">
    <?php if (sqlNumRows($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="doseHistoryTable">
                <thead class="table-dark">
                    <tr>
                        <th><?php echo xlt('Dose #'); ?></th>
                        <th><?php echo xlt('Scheduled'); ?></th>
                        <th><?php echo xlt('Administered'); ?></th>
                        <th><?php echo xlt('Status'); ?></th>
                        <th><?php echo xlt('Administered By'); ?></th>
                        <th><?php echo xlt('Effectiveness'); ?></th>
                        <th><?php echo xlt('Reactions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlFetchArray($result)): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($row['dose_number']); ?></span>
                            </td>
                            <td>
                                <small class="text-muted"><?php echo oeFormatDateTime($row['schedule_datetime']); ?></small>
                            </td>
                            <td>
                                <?php if ($row['supply_datetime']): ?>
                                    <small><?php echo oeFormatDateTime($row['supply_datetime']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_class = '';
                                switch ($row['status']) {
                                    case 'Confirmed':
                                        $status_class = 'bg-success';
                                        break;
                                    case 'Pending':
                                        $status_class = 'bg-warning';
                                        break;
                                    case 'Cancelled':
                                        $status_class = 'bg-danger';
                                        break;
                                    case 'Suspended':
                                        $status_class = 'bg-info';
                                        break;
                                    default:
                                        $status_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo xlt($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['supplied_by_name']): ?>
                                    <small><?php echo htmlspecialchars($row['supplied_by_name']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['effectiveness_score']): ?>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-1"><?php echo htmlspecialchars($row['effectiveness_score']); ?></span>
                                        <?php if ($row['effectiveness_notes']): ?>
                                            <i class="fas fa-comment text-info" title="<?php echo htmlspecialchars($row['effectiveness_notes']); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['reaction_description']): ?>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-warning text-dark me-1"><?php echo xlt($row['reaction_description']); ?></span>
                                        <?php if ($row['reaction_severity']): ?>
                                            <small class="text-muted">(<?php echo xlt($row['reaction_severity']); ?>)</small>
                                        <?php endif; ?>
                                        <?php if ($row['reaction_notes']): ?>
                                            <i class="fas fa-comment text-info" title="<?php echo htmlspecialchars($row['reaction_notes']); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Estadísticas resumidas -->
        <div class="row mt-3">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?php echo xlt('Total Doses'); ?></h6>
                        <h4 class="text-primary"><?php echo $total_doses; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?php echo xlt('Confirmed'); ?></h6>
                        <h4 class="text-success"><?php echo $confirmed_count; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?php echo xlt('Pending'); ?></h6>
                        <h4 class="text-warning"><?php echo $pending_count; ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="card-title"><?php echo xlt('With Reactions'); ?></h6>
                        <h4 class="text-danger"><?php echo $reactions_count; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> <?php echo xlt('No dose history found for this medication.'); ?>
        </div>
    <?php endif; ?>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeMarModal()">
        <i class="fas fa-times"></i> <?php echo xlt('Close'); ?>
    </button>
</div>
