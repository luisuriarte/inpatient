<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');

try {
    $userId = $_SESSION['authUserID'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $alarmId = $_POST['alarm_id'] ?? '';
        $status = $_POST['status'] ?? 'silenced'; // 'silenced', 'active'
        
        // Extraer el ID del suministro y el número de alarma del ID de la alarma
        // El formato es: alarm_[supply_id]_[1|2]
        if (preg_match('/^alarm_(\d+)_(\d)$/', $alarmId, $matches)) {
            $supplyId = intval($matches[1]);
            $alarmNumber = intval($matches[2]);
            
            // Determinar qué campo actualizar
            $fieldToUpdate = ($alarmNumber == 1) ? 'alarm1_active' : 'alarm2_active';
            $value = ($status === 'silenced') ? 0 : 1;
            
            // Actualizar el estado de la alarma en la base de datos
            $updateQuery = "UPDATE prescriptions_supply SET $fieldToUpdate = ? WHERE supply_id = ?";
            $result = sqlStatement($updateQuery, [$value, $supplyId]);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Alarm status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update alarm status']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid alarm ID format']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    error_log("Error updating alarm status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating alarm status: ' . $e->getMessage()]);
}
?>