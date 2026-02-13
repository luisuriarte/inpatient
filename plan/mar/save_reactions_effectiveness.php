<?php
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Debug: Log all POST data
error_log("save_reactions_effectiveness.php - POST data: " . print_r($_POST, true));

try {
    // Verificar que se recibieron los parámetros necesarios
    if (!isset($_POST['supply_id']) || !isset($_POST['schedule_id'])) {
        throw new Exception("Missing required parameters");
    }
    
    $supply_id = (int)$_POST['supply_id'];
    $schedule_id = (int)$_POST['schedule_id'];
    
    // Validar que el supply_id exista y pertenezca al schedule_id
    $supply_check = sqlQuery("
        SELECT supply_id FROM prescriptions_supply 
        WHERE supply_id = ? AND schedule_id = ?
    ", [$supply_id, $schedule_id]);
    
    if (!$supply_check) {
        throw new Exception("Invalid supply or schedule ID");
    }
    
    // Preparar los datos para actualizar
    $update_data = [];
    $update_params = [];
    
    // Effectiveness fields
    if (isset($_POST['effectiveness_score']) && $_POST['effectiveness_score'] !== '') {
        // Buscar el title correspondiente al option_id
        $effectiveness_title = sqlQuery("
            SELECT title FROM list_options 
            WHERE list_id = 'drug_effectiveness' AND option_id = ?
        ", [$_POST['effectiveness_score']]);
        
        $title_value = $effectiveness_title ? $effectiveness_title['title'] : $_POST['effectiveness_score'];
        $update_data[] = "effectiveness_score = ?";
        $update_params[] = $title_value;
        error_log("Adding effectiveness_score: " . $_POST['effectiveness_score'] . " -> " . $title_value);
    }
    
    if (isset($_POST['effectiveness_notes']) && $_POST['effectiveness_notes'] !== '') {
        $update_data[] = "effectiveness_notes = ?";
        $update_params[] = $_POST['effectiveness_notes'];
        error_log("Adding effectiveness_notes: " . $_POST['effectiveness_notes']);
    }
    
    // Reaction description - siempre establecer un valor por defecto
    if (isset($_POST['reaction_description']) && $_POST['reaction_description'] !== '') {
        $update_data[] = "reaction_description = ?";
        $update_params[] = $_POST['reaction_description'];
        error_log("Adding reaction_description: " . $_POST['reaction_description']);
    } else {
        // Si no hay reacciones, establecer "No Reaction" por defecto
        $update_data[] = "reaction_description = ?";
        $update_params[] = "No Reaction";
        error_log("Setting default reaction_description: No Reaction");
    }
    
    if (isset($_POST['reaction_time']) && $_POST['reaction_time'] !== '') {
        $update_data[] = "reaction_time = ?";
        $update_params[] = $_POST['reaction_time'];
        error_log("Adding reaction_time: " . $_POST['reaction_time']);
    }
    
    if (isset($_POST['reaction_severity']) && $_POST['reaction_severity'] !== '') {
        // Buscar el title correspondiente al option_id
        $severity_title = sqlQuery("
            SELECT title FROM list_options 
            WHERE list_id = 'severity_ccda' AND option_id = ?
        ", [$_POST['reaction_severity']]);
        
        $severity_value = $severity_title ? $severity_title['title'] : $_POST['reaction_severity'];
        $update_data[] = "reaction_severity = ?";
        $update_params[] = $severity_value;
        error_log("Adding reaction_severity: " . $_POST['reaction_severity'] . " -> " . $severity_value);
    }
    
    if (isset($_POST['reaction_notes']) && $_POST['reaction_notes'] !== '') {
        $update_data[] = "reaction_notes = ?";
        $update_params[] = $_POST['reaction_notes'];
        error_log("Adding reaction_notes: " . $_POST['reaction_notes']);
    }
    
    // Construir y ejecutar la consulta UPDATE
    if (!empty($update_data)) {
        $update_params[] = $supply_id; // Para el WHERE clause
        $update_sql = "UPDATE prescriptions_supply SET " . implode(', ', $update_data) . " WHERE supply_id = ?";
        
        error_log("SQL Query: $update_sql");
        error_log("Parameters: " . print_r($update_params, true));
        
        error_log("Attempting SQL update with sqlStatement()...");
        $result = sqlStatement($update_sql, $update_params);
        
        error_log("SQL Statement Result: " . print_r($result, true));
        
        // Try with sqlQuery as fallback
        if (!$result) {
            error_log("sqlStatement() failed, trying sqlQuery()...");
            $result = sqlQuery($update_sql, $update_params);
            error_log("SQL Query Result: " . print_r($result, true));
        }
        
        if ($result) {
            // Registrar en log de auditoría si está disponible
            $user_id = $_SESSION['authUserID'] ?? null;
            if ($user_id) {
                // Aquí podrías agregar logging si tienes una tabla de auditoría
                // audit_log('prescriptions_supply', $supply_id, 'UPDATE', 'Updated effectiveness/reactions data', $user_id);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Effectiveness and reactions data saved successfully'
            ]);
        } else {
            $sql_error = sqlStatementError();
            $error_msg = "Error updating database";
            if ($sql_error) {
                $error_msg .= " - SQL Error: " . $sql_error;
            }
            throw new Exception($error_msg);
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No changes to save'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>