<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

$userId = $_SESSION['authUserID'];
$userName = $_SESSION['authUser'];

// Obtener schedule_id del schedule actual a modificar
$current_schedule_id = $_POST['schedule_id'] ?? null;

if (!$current_schedule_id) {
    echo json_encode(['success' => false, 'message' => xlt('No schedule ID provided')]);
    exit;
}

// Recolectar datos del formulario
$active = isset($_POST['active']) ? 1 : 0;
$provider_id = $_POST['provider_id'] ?? '';
$drug = $_POST['drug'] ?? '';
$dosage = $_POST['dosage'] ?? '';
$size = $_POST['size'] ?? '';
$unit = $_POST['unit'] ?? '';
$form = $_POST['form'] ?? '';
$route = $_POST['route'] ?? '';
$note = $_POST['note'] ?? '';
$intravenous = isset($_POST['intravenous_switch']) ? 1 : 0;
$scheduled = isset($_POST['scheduled']) ? 1 : 0;
$notifications = isset($_POST['notifications']) ? 1 : 0;
$medications_list = isset($_POST['add_medications']) ? 1 : 0;
$unit_frequency = $_POST['unit_frequency'] ?? null;
$time_frequency = $_POST['time_frequency'] ?? null;
$unit_duration = $_POST['duration'] ?? null;
$time_duration = $_POST['time_duration'] ?? null;
$alarm1_unit = $_POST['alarm1_unit'] ?? null;
$alarm1_time = $_POST['alarm1_time'] ?? null;
$alarm2_unit = $_POST['alarm2_unit'] ?? null;
$alarm2_time = $_POST['alarm2_time'] ?? null;
$vehicle = $_POST['vehicle'] ?? null;
$catheter_type = $_POST['catheter_type'] ?? null;
$infusion_rate = $_POST['infusion_rate'] ?? null;
$iv_route = $_POST['iv_route'] ?? null;
$total_volume = $_POST['total_volume'] ?? null;
$concentration = $_POST['concentration'] ?? null;
$concentration_units = $_POST['concentration_units'] ?? null;
$iv_duration = $_POST['iv_duration'] ?? null;
$iv_status = $_POST['iv_status'] ?? 'Active';
$modification_reason = $_POST['modification_reason'] ?? 'Order edited';

// Validar y formatear fechas
$start_date_str = $_POST['start_date'] ?? '';
if (empty($start_date_str)) {
    echo json_encode(['success' => false, 'message' => xlt('Start date is required')]);
    exit;
}
$start_date = new DateTime($start_date_str);
$start_date_formatted = $start_date->format('Y-m-d H:i:s');

$end_date_formatted = null;
if (!empty($_POST['end_date'])) {
    $end_date = new DateTime($_POST['end_date']);
    $end_date_formatted = $end_date->format('Y-m-d H:i:s');
}

// Obtener datos del schedule actual para versionado
$current_schedule_query = "
    SELECT ps.*, p.id as prescription_id, p.patient_id
    FROM prescriptions_schedule ps
    JOIN prescriptions p ON ps.prescription_id = p.id
    WHERE ps.schedule_id = ? AND ps.active = 1
";
$current_schedule = sqlQuery($current_schedule_query, [$current_schedule_id]);

if (!$current_schedule) {
    echo json_encode(['success' => false, 'message' => xlt('Active schedule not found')]);
    exit;
}

$prescription_id = $current_schedule['prescription_id'];
$patient_id = $current_schedule['patient_id'];
$previous_version = $current_schedule['version'] ?? 1;
$root_schedule_id = $current_schedule['root_schedule_id'] ?? $current_schedule_id;

// Inicia la transacción
$database->StartTrans();

try {
    // 1. Actualizar la tabla prescriptions (datos del medicamento)
    $update_prescription_query = "
        UPDATE prescriptions 
        SET provider_id = ?,
            drug = ?,
            dosage = ?,
            quantity = ?,
            size = ?,
            unit = ?,
            form = ?,
            route = ?,
            note = ?,
            active = ?,
            date_modified = NOW(),
            updated_by = ?,
            usage_category = 'inpatient',
            usage_category_title = 'Inpatient'
        WHERE id = ?
    ";
    
    sqlStatement($update_prescription_query, [
        $provider_id, $drug, $dosage, $dosage, $size, $unit, $form,
        $route, $note, $active, $userId, $prescription_id
    ]);

    // 2. Desactivar el schedule actual y marcarlo como modificado
    $deactivate_schedule_query = "
        UPDATE prescriptions_schedule 
        SET active = 0,
            status = 'Modified',
            modification_reason = ?,
            modifed_by = ?,
            modification_datetime = NOW()
        WHERE schedule_id = ?
    ";
    
    sqlStatement($deactivate_schedule_query, [
        $modification_reason, $userId, $current_schedule_id
    ]);

    // 3. Insertar nuevo schedule con versión incrementada
    $new_version = $previous_version + 1;
    
    $insert_schedule_query = "
        INSERT INTO prescriptions_schedule 
        (prescription_id, patient_id, intravenous, scheduled, notifications, 
         start_date, end_date, unit_frequency, time_frequency, unit_duration, time_duration,
         alarm1_unit, alarm1_time, alarm2_unit, alarm2_time, status,
         version, previous_schedule_id, root_schedule_id, active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, 1)
    ";
    
    $new_schedule_id = sqlInsert($insert_schedule_query, [
        $prescription_id, $patient_id, $intravenous, $scheduled, $notifications,
        $start_date_formatted, $end_date_formatted, $unit_frequency, $time_frequency,
        $unit_duration, $time_duration, $alarm1_unit, $alarm1_time, $alarm2_unit, $alarm2_time,
        $new_version, $current_schedule_id, $root_schedule_id
    ]);

    // 4. Manejar prescriptions_intravenous para el nuevo schedule
    if ($intravenous == 1) {
        $insert_iv_query = "
            INSERT INTO prescriptions_intravenous 
            (prescription_id, schedule_id, vehicle, catheter_type, infusion_rate, iv_route, 
             total_volume, concentration, concentration_units, iv_duration, status, modify_datetime, user_modify)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ";
        
        sqlStatement($insert_iv_query, [
            $prescription_id, $new_schedule_id, $vehicle, $catheter_type, $infusion_rate,
            $iv_route, $total_volume, $concentration, $concentration_units, $iv_duration,
            $iv_status, $userId
        ]);
    }

    // 5. Cancelar supplies pendientes del schedule anterior
    $cancel_supplies_query = "
        UPDATE prescriptions_supply 
        SET status = 'Cancelled',
            modified_by = ?,
            modification_datetime = NOW(),
            active = 0
        WHERE schedule_id = ? 
        AND status = 'Pending'
    ";
    
    sqlStatement($cancel_supplies_query, [$userId, $current_schedule_id]);

    // 6. Generar nuevos supplies para el nuevo schedule
    if ($scheduled == 0) {
        // Dosis única
        $insert_single_query = "
            INSERT INTO prescriptions_supply 
            (schedule_id, patient_id, schedule_datetime, dose_number, max_dose, status, active, created_by, creation_datetime)
            VALUES (?, ?, ?, 1, 1, 'Pending', 1, ?, NOW())
        ";
        
        sqlStatement($insert_single_query, [
            $new_schedule_id, $patient_id, $start_date_formatted, $userId
        ]);
    } else {
        // Generar supplies programados usando la función existente
        createPrescriptionsSupply($new_schedule_id);
    }

    // 7. Manejar lists y lists_medication
    if ($medications_list == 1) {
        $medication_description = $dosage . ' ' . xlt('dose');
        if ($scheduled == 1 && $unit_frequency && $time_frequency) {
            $medication_description .= ' ' . xlt('Every') . ' ' . $unit_frequency . ' ' . xlt($time_frequency);
        }
        
        $existing_list = sqlQuery(
            "SELECT id FROM lists WHERE pid = ? AND type = 'medication' AND title = ? AND enddate IS NULL",
            [$patient_id, $drug]
        );
        
        if ($existing_list) {
            sqlStatement(
                "UPDATE lists SET begdate = ?, enddate = ?, comments = ?, user = ?, modifydate = NOW() WHERE id = ?",
                [$start_date_formatted, $end_date_formatted, $note, $userName, $existing_list['id']]
            );
            sqlStatement(
                "UPDATE lists_medication SET drug_dosage_instructions = ? WHERE list_id = ?",
                [$medication_description, $existing_list['id']]
            );
        } else {
            $uuid_lists = generateUUID();
            $list_id = sqlInsert(
                "INSERT INTO lists (uuid, date, type, subtype, title, begdate, enddate, comments, pid, user, modifydate)
                 VALUES (?, NOW(), 'medication', 'diagnosis', ?, ?, ?, ?, ?, ?, NOW())",
                [$uuid_lists, $drug, $start_date_formatted, $end_date_formatted, $note, $patient_id, $userName]
            );
            
            sqlStatement(
                "INSERT INTO lists_medication (list_id, drug_dosage_instructions, usage_category, usage_category_title, request_intent, request_intent_title)
                 VALUES (?, ?, 'inpatient', 'Inpatient', 'order', 'Order')",
                [$list_id, $medication_description]
            );
        }
    }

    // Commit de la transacción
    $database->CompleteTrans();
    
    echo json_encode([
        'success' => true, 
        'message' => xlt('Order updated successfully'),
        'new_schedule_id' => $new_schedule_id,
        'version' => $new_version
    ]);

} catch (Exception $e) {
    $database->FailTrans();
    $database->CompleteTrans();
    
    error_log('Error updating order: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => xlt('Error updating order') . ': ' . $e->getMessage()]);
}
?>
