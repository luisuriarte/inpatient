<?php
require_once("../../functions.php");
require_once('../../../interface/globals.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$schedule_id = $_POST['schedule_id'] ?? null;
$suspension_reason = $_POST['suspension_reason'] ?? null;
$suspension_notes = $_POST['suspension_notes'] ?? '';

if (!$schedule_id || !$suspension_reason) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = $_SESSION['authUserID'] ?? null;
if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Obtener el título de la razón de suspensión
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
    $database->StartTrans();
    
    // 1. Actualizar prescriptions_schedule - desactivar y marcar como suspendido
    $update_schedule_query = "
        UPDATE prescriptions_schedule 
        SET active = 0,
            suspended_reason = ?,
            suspended_by = ?,
            suspension_datetime = ?
        WHERE schedule_id = ?
          AND active = 1
    ";
    
    sqlStatement($update_schedule_query, [
        $full_suspension_reason,
        $user_id,
        $current_datetime,
        $schedule_id
    ]);
    
    // 2. Suspender supplies pendientes futuros y desactivar alarmas
    $update_supply_query = "
        UPDATE prescriptions_supply 
        SET status = 'Suspended',
            modification_datetime = ?,
            modified_by = ?,
            alarm1_active = 0,
            alarm2_active = 0
        WHERE schedule_id = ?
          AND status = 'Pending'
          AND schedule_datetime >= NOW()
    ";
    
    $result = $database->Execute($update_supply_query, [
        $current_datetime,
        $user_id,
        $schedule_id
    ]);
    
    $affected_doses = $result ? $database->Affected_Rows() : 0;
    
    // Commit de la transacción
    $database->CompleteTrans();
    
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
    $database->FailTrans();
    $database->CompleteTrans();
    
    error_log('Error suspending order: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => xl('Error suspending order: ') . $e->getMessage()
    ]);
}
?>
