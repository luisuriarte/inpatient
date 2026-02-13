<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar si la solicitud es POST
//if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Imprimir todos los datos enviados para debug
//    echo '<pre>';
//    print_r($_POST);
//    echo '</pre>';
//    exit; // Agregar esto para detener la ejecución temporalmente
//}

$userId = $_SESSION['authUserID'];
$userName = $_SESSION['authUser'];
$patient_id = $_POST['patient_id'];  // ID del paciente

// Recolectar datos del formulario
$active = isset($_POST['active']) ? 1 : 0;
$provider_id = $_POST['provider_id'];
$drug = $_POST['drug'];
$dosage = $_POST['dosage'];
$quantity = $_POST['dosage'];
$size = $_POST['size'];
$unit = $_POST['unit'];
$form = $_POST['form'];
$route = $_POST['route'];
$note = $_POST['note'];
$uuid = generateUUID();
$uuid_lists = generateUUID();
$intravenous = isset($_POST['intravenous_switch']) ? 1 : 0;
$scheduled = isset($_POST['scheduled']) ? 1 : 0;
$notifications = isset($_POST['notifications']) ? 1 : 0;
$medications_list = isset($_POST['add_medications']) ? 1 : 0;
$unit_frequency = $_POST['unit_frequency'];
$time_frequency = $_POST['time_frequency'];
//$duration = $_POST['duration'];
$unit_duration = $_POST['duration'];
$time_duration = $_POST['time_duration'];
$alarm1_unit = $_POST['alarm1_unit'];
$alarm1_time = $_POST['alarm1_time'];
$alarm2_unit = $_POST['alarm2_unit'];
$alarm2_time = $_POST['alarm2_time'];
$vehicle = $_POST['vehicle'];
$catheter_type = $_POST['catheter_type'];
$infusion_rate = $_POST['infusion_rate'];
$iv_route = $_POST['iv_route'];
$total_volume = $_POST['total_volume'];
$concentration = $_POST['concentration'];
$concentration_units = $_POST['concentration_units'];
$iv_duration = $_POST['iv_duration'];
$iv_status = $_POST['iv_status'];
$start_date_str = $_POST['start_date'];
$start_date = new DateTime($start_date_str);

// Validar si end_date existe y no está vacío
if (!empty($_POST['end_date'])) {
    $end_date_str = $_POST['end_date'];
    $end_date = new DateTime($end_date_str);
} else {
    $end_date = null; // Establecer end_date como null si no se proporciona
}

// Formatear las fechas solo si se han creado correctamente
$start_date_formatted = $start_date->format('Y-m-d H:i:s');

if ($end_date) {
    $end_date_formatted = $end_date->format('Y-m-d H:i:s');
} else {
    $end_date_formatted = null; // O podrías dejarlo vacío si prefieres
}

// Inicia la transacción
$database->StartTrans();

try {

    // Insertar la orden en la tabla prescriptions
    $prescription_query = "INSERT INTO prescriptions 
                        (uuid, patient_id, provider_id, drug, dosage, quantity, size, unit, form, route, start_date, note, 
                            active, date_added, date_modified, created_by, updated_by, usage_category, usage_category_title)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, ?, 'inpatient', 'Inpatient')";

    $prescription_id = sqlInsert($prescription_query,
        array(
            $uuid, 
            $patient_id, 
            $provider_id, 
            $drug, 
            $dosage, 
            $quantity, 
            $size, 
            $unit, 
            $form, 
            $route,
            $start_date_formatted, 
            $note, 
            $active, 
            $userId,
            $userId
        )
    );

    // 2. Insertar el cronograma en la tabla prescriptions_schedule
    $schedule_query = "INSERT INTO prescriptions_schedule 
                    (prescription_id, patient_id, intravenous, scheduled, notifications, start_date, end_date, unit_frequency, 
                        time_frequency, unit_duration, time_duration, alarm1_unit, alarm1_time, alarm2_unit, alarm2_time, `status`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";

    $schedule_id = sqlInsert($schedule_query, 
                array(
                        $prescription_id, 
                        $patient_id, 
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
                        $alarm2_time
                    )
                );

    // Graba Intravenoso
    if ($intravenous == 1) {                    
        $insertQuery = "INSERT INTO prescriptions_intravenous (prescription_id, schedule_id, vehicle, catheter_type, infusion_rate, iv_route, total_volume, concentration, concentration_units, iv_duration, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $insertResult = sqlStatement($insertQuery, 
        [
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
            $iv_status
        ]
        );
    }
    
    // Formatea el la frecuencia de dosaje, para agregar a prescriptions
    $med_query = "
        SELECT p.drug, p.size, p.dosage, ps.unit_frequency, ps.time_frequency, ps.scheduled,
        (SELECT title FROM list_options WHERE option_id = p.unit AND list_id = 'drug_units') AS unit_title, 
        p.route 
        FROM prescriptions AS p
        INNER JOIN prescriptions_schedule AS ps ON ps.prescription_id = p.id
        WHERE ps.schedule_id = ?";
    $med_result = sqlStatement($med_query, array($schedule_id));

    $medications = [];
    while ($med_row = sqlFetchArray($med_result)) {
        // Construir la descripción del medicamento
        $medication_description = $med_row['dosage'] . ' ' . xlt('dose');

        // Verificar si la frecuencia está programada
        if ($med_row['scheduled'] == 1) {
            $medication_description .= ' ' . xlt('Every') . ' ' . $med_row['unit_frequency'] . ' ' . xlt($med_row['time_frequency']);
        }

        $medications[] = $medication_description;
    }

    // Combina todos los medicamentos en una cadena
    $medications_text = implode(', ', $medications);

    // Inserta el lists y lists_medication si esta marcado
    if ($medications_list == 1) {
        $medication_insert = "INSERT INTO lists 
            (uuid, date, type, subtype, title, begdate, enddate, comments, pid, user, modifydate)
            VALUES (?, NOW(), 'medication', 'diagnosis', ?, ?, ?, ?, ?, ?, NOW())";

        $medication_lists_id = sqlInsert($medication_insert, 
        array(
            $uuid_lists,
            $drug,
            $start_date_formatted,
            $end_date_formatted,
            $notes,
            $patient_id,
            $userName
        ));

        $insertListMed = "INSERT INTO lists_medication (list_id, drug_dosage_instructions, usage_category, usage_category_title, request_intent, request_intent_title)
        VALUES (?, ?, 'inpatient', 'Inpatient', 'order', 'Order')";

        $insertResult = sqlStatement($insertListMed, 
        [
            $medication_lists_id,
            $medications_text
        
        ]);
    }

    // Graba prescriptions_supply cuando es dosis unica.
    if ($scheduled == 0) {                    
        $insertQueryPs = "INSERT INTO prescriptions_supply (schedule_id, patient_id, schedule_datetime, dose_number, max_dose, status, active, created_by, creation_datetime)
        VALUES (?, ?, ?, 1, 1, 'Pending', 1, ?, NOW())";

        $insertResultPs = sqlStatement($insertQueryPs, 
        [
            $schedule_id,
            $patient_id,
            $start_date_formatted,
            $userId
        ]
        );
    }

    // Llenar la tabla prescriptions_supply con las administraciones programadas
    createPrescriptionsSupply($schedule_id);

    // Commit de la transacción si todo salió bien
    $database->CompleteTrans();

    // Redirigir a la página de inicio
    header("Location: mar.php");

} catch (Exception $e) {
    // Si hay algún error, se hace rollback
    $database->FailTrans();
    $database->CompleteTrans();
    echo "Error saving data: " . $e->getMessage();
}

?>