<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si la solicitud es POST
//if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
//    echo json_encode(['success' => false, 'message' => xlt('Method not allowed')]);
//    exit;
//}

$userId = $_SESSION['authUserID'];
$userName = $_SESSION['authUser'];

// Obtener schedule_id (campo oculto que debe agregarse al formulario)
$schedule_id = $_POST['schedule_id'] ?? null;

if (!$schedule_id) {
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

// Obtener datos actuales para comparar cambios
$current_data_query = "
    SELECT ps.prescription_id, ps.patient_id, ps.scheduled, ps.intravenous
    FROM prescriptions_schedule ps
    WHERE ps.schedule_id = ?
";
$current_data = sqlQuery($current_data_query, [$schedule_id]);

if (!$current_data) {
    echo json_encode(['success' => false, 'message' => xlt('Schedule not found')]);
    exit;
}

$prescription_id = $current_data['prescription_id'];
$patient_id = $current_data['patient_id'];
$previous_scheduled = $current_data['scheduled'];
$previous_intravenous = $current_data['intravenous'];

// Inicia la transacción
$database->StartTrans();

try {
    // 1. Actualizar la tabla prescriptions
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
        $provider_id,
        $drug,
        $dosage,
        $dosage, // quantity igual a dosage
        $size,
        $unit,
        $form,
            $route,
        $note,
        $active,
        $userId,
        $prescription_id
    ]);

    // 2. Actualizar la tabla prescriptions_schedule
    $update_schedule_query = "
        UPDATE prescriptions_schedule 
        SET intravenous = ?,
            scheduled = ?,
            notifications = ?,
            start_date = ?,
            end_date = ?,
            unit_frequency = ?,
            time_frequency = ?,
            unit_duration = ?,
            time_duration = ?,
            alarm1_unit = ?,
            alarm1_time = ?,
            alarm2_unit = ?,
            alarm2_time = ?,
            modification_reason = ?,
            modifed_by = ?,
            modification_datetime = NOW(),
            `status` = 'Modified'
        WHERE schedule_id = ?
    ";
    
    sqlStatement($update_schedule_query, [
        $intravenous,
        $scheduled,
        $notifications,
        $start_date_formatted,
        $end_date_formatted,
        $unit_frequency,
        $time_frequency,
        $unit_duration,
        $time_duration,
        $alarm1_unit,
        $alarm1_time,
        $alarm2_unit,
        $alarm2_time,
        $modification_reason,
        $userId,
        $schedule_id
    ]);

    // 3. Manejar prescriptions_intravenous
    if ($intravenous == 1) {
        // Verificar si ya existe un registro intravenoso para este schedule
        $existing_iv = sqlQuery(
            "SELECT intravenous_id FROM prescriptions_intravenous WHERE schedule_id = ?",
            [$schedule_id]
        );
        
        if ($existing_iv) {
            // Actualizar registro existente
            $update_iv_query = "
                UPDATE prescriptions_intravenous 
                SET vehicle = ?,
                    catheter_type = ?,
                    infusion_rate = ?,
                    iv_route = ?,
                    total_volume = ?,
                    concentration = ?,
                    concentration_units = ?,
                    iv_duration = ?,
                    status = ?,
                    modify_datetime = NOW(),
                    user_modify = ?
                WHERE schedule_id = ?
            ";
            
            sqlStatement($update_iv_query, [
                $vehicle,
                $catheter_type,
                $infusion_rate,
                $iv_route,
                $total_volume,
                $concentration,
                $concentration_units,
                $iv_duration,
                $iv_status,
                $userId,
                $schedule_id
            ]);
        } else {
            // Insertar nuevo registro intravenoso
            $insert_iv_query = "
                INSERT INTO prescriptions_intravenous 
                (prescription_id, schedule_id, vehicle, catheter_type, infusion_rate, iv_route, 
                 total_volume, concentration, concentration_units, iv_duration, status, modify_datetime, user_modify)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ";
            
            sqlStatement($insert_iv_query, [
                $prescription_id,
                $schedule_id,
                $vehicle,
                $catheter_type,
                $infusion_rate,
                $iv_route,
                $total_volume,
                $concentration,
                $concentration_units,
                $iv_duration,
                $iv_status,
                $userId
            ]);
        }
    } else {
        // Si ya no es intravenoso, eliminar o desactivar el registro
        sqlStatement(
            "UPDATE prescriptions_intravenous SET status = 'Inactive', modify_datetime = NOW(), user_modify = ? WHERE schedule_id = ?",
            [$userId, $schedule_id]
        );
    }

    // 4. Manejar cambios en scheduled (programación de dosis)
    if ($previous_scheduled != $scheduled || ($scheduled == 1 && $previous_scheduled == 1)) {
        // Si cambió de no programado a programado, o si ya era programado y se modificó
        // Eliminar dosis pendientes futuras y regenerar
        
        // Marcar como canceladas las dosis pendientes futuras
        sqlStatement(
            "UPDATE prescriptions_supply 
             SET status = 'Cancelled', active = 0, modification_datetime = NOW() 
             WHERE schedule_id = ? AND status = 'Pending' AND schedule_datetime > NOW()",
            [$schedule_id]
        );
        
        if ($scheduled == 1) {
            // Regenerar dosis programadas - capturar cualquier salida
            ob_start();
            createPrescriptionsSupply($schedule_id);
            ob_end_clean();
        } else {
            // Si es dosis única, crear una sola dosis
            $existing_single = sqlQuery(
                "SELECT supply_id FROM prescriptions_supply WHERE schedule_id = ? AND dose_number = 1",
                [$schedule_id]
            );
            
            if (!$existing_single) {
                $insert_single_query = "
                    INSERT INTO prescriptions_supply 
                    (schedule_id, patient_id, schedule_datetime, dose_number, max_dose, status, active, created_by, creation_datetime)
                    VALUES (?, ?, ?, 1, 1, 'Pending', 1, ?, NOW())
                ";
                
                sqlStatement($insert_single_query, [
                    $schedule_id,
                    $patient_id,
                    $start_date_formatted,
                    $userId
                ]);
            }
        }
    }

    // 5. Actualizar alarms de las dosis pendientes existentes si cambiaron las alarmas
    if ($notifications == 1) {
        // Obtener todas las dosis pendientes
        $pending_doses = sqlStatement(
            "SELECT supply_id, schedule_datetime FROM prescriptions_supply WHERE schedule_id = ? AND status = 'Pending' AND active = 1",
            [$schedule_id]
        );
        
        while ($dose = sqlFetchArray($pending_doses)) {
            $supply_id = $dose['supply_id'];
            $dose_datetime = new DateTime($dose['schedule_datetime']);
            
            // Calcular nuevos tiempos de alarma
            $alarm1_datetime = null;
            $alarm2_datetime = null;
            
            if ($alarm1_unit && $alarm1_time) {
                $alarm1_offset = getTimeOffset($alarm1_unit, $alarm1_time);
                if ($alarm1_offset !== null) {
                    $alarm1_dt = clone $dose_datetime;
                    $alarm1_dt->modify("-{$alarm1_offset} minutes");
                    $alarm1_datetime = $alarm1_dt->format('Y-m-d H:i:s');
                }
            }
            
            if ($alarm2_unit && $alarm2_time) {
                $alarm2_offset = getTimeOffset($alarm2_unit, $alarm2_time);
                if ($alarm2_offset !== null) {
                    $alarm2_dt = clone $dose_datetime;
                    $alarm2_dt->modify("-{$alarm2_offset} minutes");
                    $alarm2_datetime = $alarm2_dt->format('Y-m-d H:i:s');
                }
            }
            
            // Actualizar alarms en prescriptions_supply
            sqlStatement(
                "UPDATE prescriptions_supply 
                 SET alarm1_datetime = ?, alarm1_active = ?, alarm2_datetime = ?, alarm2_active = ?
                 WHERE supply_id = ?",
                [
                    $alarm1_datetime,
                    $alarm1_datetime ? 1 : 0,
                    $alarm2_datetime,
                    $alarm2_datetime ? 1 : 0,
                    $supply_id
                ]
            );
        }
    }

    // 6. Manejar lists y lists_medication si está marcado
    if ($medications_list == 1) {
        // Verificar si ya existe en lists
        $existing_list = sqlQuery(
            "SELECT id FROM lists WHERE pid = ? AND type = 'medication' AND title = ? AND enddate IS NULL",
            [$patient_id, $drug]
        );
        
        // Formatear la descripción del medicamento
        $medication_description = $dosage . ' ' . xlt('dose');
        if ($scheduled == 1 && $unit_frequency && $time_frequency) {
            $medication_description .= ' ' . xlt('Every') . ' ' . $unit_frequency . ' ' . xlt($time_frequency);
        }
        
        if ($existing_list) {
            // Actualizar registro existente
            sqlStatement(
                "UPDATE lists 
                 SET begdate = ?, enddate = ?, comments = ?, user = ?, modifydate = NOW()
                 WHERE id = ?",
                [$start_date_formatted, $end_date_formatted, $note, $userName, $existing_list['id']]
            );
            
            // Actualizar lists_medication
            sqlStatement(
                "UPDATE lists_medication SET drug_dosage_instructions = ? WHERE list_id = ?",
                [$medication_description, $existing_list['id']]
            );
        } else {
            // Insertar nuevo registro
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
    
    echo json_encode(['success' => true, 'message' => xlt('Order updated successfully')]);

} catch (Exception $e) {
    // Rollback en caso de error
    $database->FailTrans();
    $database->CompleteTrans();
    
    error_log('Error updating order: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => xlt('Error updating order') . ': ' . $e->getMessage()]);
}

// Función auxiliar para calcular el offset en minutos según la unidad de tiempo
function getTimeOffset($value, $unit) {
    if (!$value || !$unit) return null;
    
    $multipliers = [
        'minutes' => 1,
        'hours' => 60,
        'days' => 1440,
        'weeks' => 10080,
        'months' => 43200 // Aproximado
    ];
    
    return $value * ($multipliers[$unit] ?? 1);
}
?>
