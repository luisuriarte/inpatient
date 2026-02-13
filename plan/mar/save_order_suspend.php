<?php
// Incluir funciones y variables globales
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Obtener datos del formulario
$schedule_id = $_POST['schedule_id'] ?? null;
$suspension_scope = $_POST['suspension_scope'] ?? null;
$suspension_reason = $_POST['suspension_reason'] ?? null;
$suspension_notes = $_POST['suspension_notes'] ?? '';
$confirm_suspension = $_POST['confirm_suspension'] ?? 0;

// Validar datos requeridos
if (!$schedule_id || !$suspension_scope || !$suspension_reason || !$confirm_suspension) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Obtener información del usuario actual
$user_id = $_SESSION['authUserID'] ?? null;
if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Obtener el título de la razón de suspensión para el registro
$reason_query = "SELECT title FROM list_options WHERE list_id = 'reason_discontinue_medication' AND option_id = ?";
$reason_result = sqlQuery($reason_query, [$suspension_reason]);
$reason_text = $reason_result['title'] ?? $suspension_reason;

// Construir el texto completo de la razón
$full_suspension_reason = $reason_text;
if (!empty($suspension_notes)) {
    $full_suspension_reason .= ': ' . $suspension_notes;
}

$current_datetime = date('Y-m-d H:i:s');

try {
    // Iniciar transacción
    sqlBeginTrans();
    
    $affected_doses = 0;
    
    // Determinar qué dosis suspender según el alcance
    switch ($suspension_scope) {
        case 'all_future':
            // Suspender solo dosis futuras pendientes
            $update_query = "
                UPDATE prescriptions_supply 
                SET status = 'Suspended',
                    modified_by = ?,
                    modification_datetime = ?
                WHERE schedule_id = ? 
                AND status = 'Pending' 
                AND schedule_datetime > NOW()
            ";
            $result = sqlStatement($update_query, [$user_id, $current_datetime, $schedule_id]);
            $affected_doses = sqlAffectedRows();
            break;
            
        case 'all_pending':
            // Suspender todas las dosis pendientes (incluyendo vencidas)
            $update_query = "
                UPDATE prescriptions_supply 
                SET status = 'Suspended',
                    modified_by = ?,
                    modification_datetime = ?
                WHERE schedule_id = ? 
                AND status = 'Pending'
            ";
            $result = sqlStatement($update_query, [$user_id, $current_datetime, $schedule_id]);
            $affected_doses = sqlAffectedRows();
            break;
            
        case 'complete_order':
            // Suspender todas las dosis pendientes Y marcar el schedule como suspendido
            $update_supply_query = "
                UPDATE prescriptions_supply 
                SET status = 'Suspended',
                    modified_by = ?,
                    modification_datetime = ?
                WHERE schedule_id = ? 
                AND status = 'Pending'
            ";
            sqlStatement($update_supply_query, [$user_id, $current_datetime, $schedule_id]);
            $affected_doses = sqlAffectedRows();
            
            // Actualizar el schedule
            $update_schedule_query = "
                UPDATE prescriptions_schedule 
                SET active = 0,
                    suspended_reason = ?,
                    suspended_by = ?,
                    suspension_datetime = ?
                WHERE schedule_id = ?
            ";
            sqlStatement($update_schedule_query, [
                $full_suspension_reason,
                $user_id,
                $current_datetime,
                $schedule_id
            ]);
            break;
            
        default:
            throw new Exception('Invalid suspension scope');
    }
    
    // Registrar en el log de auditoría (si existe una tabla de logs)
    // Esto es opcional pero recomendado para trazabilidad
    $log_query = "
        INSERT INTO prescriptions_audit_log 
        (schedule_id, action_type, action_by, action_datetime, action_details)
        VALUES (?, 'SUSPEND', ?, ?, ?)
    ";
    
    // Verificar si la tabla de auditoría existe
    $table_check = sqlQuery("SHOW TABLES LIKE 'prescriptions_audit_log'");
    if ($table_check) {
        $log_details = json_encode([
            'scope' => $suspension_scope,
            'reason' => $suspension_reason,
            'reason_text' => $reason_text,
            'notes' => $suspension_notes,
            'affected_doses' => $affected_doses
        ]);
        
        sqlStatement($log_query, [
            $schedule_id,
            $user_id,
            $current_datetime,
            $log_details
        ]);
    }
    
    // Commit de la transacción
    sqlCommitTrans();
    
    // Preparar mensaje de éxito
    $success_message = sprintf(
        xl('Order suspended successfully. %d dose(s) affected.'),
        $affected_doses
    );
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $success_message,
        'affected_doses' => $affected_doses
    ]);
    
} catch (Exception $e) {
    // Rollback en caso de error
    sqlRollbackTrans();
    
    error_log('Error suspending order: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => xl('Error suspending order: ') . $e->getMessage()
    ]);
}
?>
