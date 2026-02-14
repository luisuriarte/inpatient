<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');

try {
    $userId = $_SESSION['authUserID'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $alarmId = $_POST['alarm_id'] ?? '';
        $action = $_POST['action'] ?? 'silence'; // 'silence', 'activate', 'delete'
        
        // Extraer el ID del suministro y el número de alarma del ID de la alarma
        // El formato es: alarm_[supply_id]_[1|2]
        if (preg_match('/^alarm_(\d+)_(\d)$/', $alarmId, $matches)) {
            $supplyId = intval($matches[1]);
            $alarmNumber = intval($matches[2]);
            
            // Determinar qué campo actualizar
            $fieldToUpdate = ($alarmNumber == 1) ? 'alarm1_active' : 'alarm2_active';
            
            // Determinar el valor según la acción
            $value = 1; // Valor por defecto
            if ($action === 'delete') {
                $value = 0; // Eliminar (desaparece del gráfico)
            } 
            // Para 'silence' y 'activate', no cambiamos el valor en la base de datos
            // El estado visual de silencio se maneja solo en el frontend
            
            // Solo actualizamos en la base de datos si es para eliminar
            if ($action === 'delete') {
                $updateQuery = "UPDATE prescriptions_supply SET $fieldToUpdate = ? WHERE supply_id = ?";
                $result = sqlStatement($updateQuery, [$value, $supplyId]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Alarm status updated successfully',
                        'new_status' => $value,
                        'action_performed' => $action
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update alarm status']);
                }
            } else {
                // Para silenciar o activar, solo devolvemos éxito pero no cambiamos la base de datos
                echo json_encode([
                    'success' => true, 
                    'message' => 'Alarm visual status updated',
                    'new_status' => 'visual_only', // Indicar que no se cambió en la base de datos
                    'action_performed' => $action
                ]);
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