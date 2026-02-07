<?php

require_once(__DIR__ . "/../interface/globals.php");

function getCentros($showInactive) {
    $query = "SELECT * FROM facility WHERE 1";
    if (!$showInactive) {
        $query .= " AND inactive = 0";
    }
    
    $result = sqlStatement($query);
    
    $centros = [];
    while ($row = sqlFetchArray($result)) {
        $centros[] = $row;
    }
    
    return $centros;
}

function generateUUID() {
    // Generar 16 bytes (128 bits) aleatorios
    $data = random_bytes(16);

    // Establecer la versión a 0100 (UUIDv4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

    // Establecer los dos bits más significativos del byte 8 a 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Formatear los bytes en la forma de un UUID estándar
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function getUserFullName($userId) {
    $queryUser = "SELECT lname, mname, fname FROM users WHERE id = ?";
    
    $userResult = sqlStatement($queryUser, [$userId]);
    $userData = sqlFetchArray($userResult);
    
    $userFullName = $userData['lname'] . ', ' . $userData['fname'] . ' ' . $userData['mname'];
    return $userFullName;
}

/**
 * Obtiene alergias, medicamentos y problemas médicos de un paciente.
 *
 * @param int $bedPatienId El ID del paciente.
 * @return array Lista de condiciones del paciente.
 */
function getPatientConditions($bedPatientId) {
    // Consulta SQL para obtener las condiciones del paciente
    $query = "
        SELECT 
            l.type, 
            l.title,
            CASE WHEN l.reaction IN ('nothing', 'unassigned') THEN '' ELSE COALESCE(lo1.title, '') END AS reaction, 
            CASE WHEN l.severity_al IN ('nothing', 'unassigned') THEN '' ELSE COALESCE(lo2.title, '') END AS severity, 
            COALESCE(lm.drug_dosage_instructions, '') AS drug_dosage_instructions, 
            COALESCE(lm.usage_category_title, '') AS usar_en,
            COALESCE(lm.request_intent_title, '') AS tipo_solicitud,
            l.comments
        FROM lists AS l
        LEFT JOIN list_options AS lo1 ON lo1.option_id = l.reaction AND lo1.list_id = 'reaction'
        LEFT JOIN list_options AS lo2 ON lo2.option_id = l.severity_al AND lo2.list_id = 'severity_ccda'
        LEFT JOIN lists_medication AS lm ON lm.list_id = l.id
        LEFT JOIN list_options AS lo3 ON lo3.option_id = lm.usage_category AND lo3.list_id = 'medication-usage-category'
        WHERE l.pid = ?;  
    ";

    // Ejecutar la consulta y obtener el conjunto de resultados
    $resultSet = sqlStatement($query, [$bedPatientId]);
    
    // Convertir el conjunto de resultados en un array
    $conditions = [];
    while (!$resultSet->EOF) {
        $conditions[] = $resultSet->fields; // Añade la fila actual al array
        $resultSet->MoveNext(); // Mueve al siguiente registro
    }
    
    return $conditions; // Retorna el array de condiciones
}

$result = getPatientData($pid, "fname,lname,mname,pid,pubpid,DOB");
$patient_id = text($result['pid']);
$patient_name = text($result['lname']) . ", " . text($result['fname']) . " " . text($result['mname']);
$patient_dni = text($result['pubpid']);

$patient_data = getPatientData($pid);
$patient_age = getPatientAge(str_replace('-', '', $patient_data['DOB']));
$patient_sex = text($patient_data['sex']);

$insurance_data = getInsuranceData($pid, $type = "primary", $given = "insd.*, DATE_FORMAT(subscriber_DOB,'%m/%d/%Y') as subscriber_DOB, ic.name as provider_name");
$insurance_name = text($insurance_data['provider_name']);

// Función para obtener los centros disponibles
function getFacilitiesData() {
    $sql = "SELECT id, name FROM facility WHERE inactive = 0 AND pos_code = '30' AND facility_taxonomy > 1 ORDER BY name ASC";
    $result = sqlStatement($sql);
    $centers = [];

    while ($row = sqlFetchArray($result)) {
        $centers[] = $row;
    }

    return $centers;
}

// Función para obtener los datos de las facilities con camas
function getFacilitiesWithBedsData() {
    $facilitiesQuery = "SELECT id, name FROM facility WHERE inactive = 0";
    $facilitiesResult = sqlStatement($facilitiesQuery);
    $facilities = [];

    // Obtener las condiciones de las camas y sus iconos y colores
    $bedConditionsQuery = "SELECT lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'bed_condition'";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        // Separar icono y color usando el carácter | (pipe)
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['title']] = ['icon' => $icon, 'color' => $color];
    }

    while ($facility = sqlFetchArray($facilitiesResult)) {
        // Contar las camas activas de la facility
        $bedsQuery = "SELECT id FROM beds WHERE facility_id = ? AND active = 1 AND operation <> 'Delete'";
        $bedsResult = sqlStatement($bedsQuery, [$facility['id']]);
        $totalBeds = sqlNumRows($bedsResult);

        // Contar las camas por condición
        $bedsConditions = [];
        foreach ($conditions as $title => $data) {
            $conditionQuery = "SELECT COUNT(*) AS count FROM beds_patients WHERE facility_id = ? AND `condition` = ? AND active = 1";
            $conditionResult = sqlStatement($conditionQuery, [$facility['id'], $title]);
            $count = sqlFetchArray($conditionResult)['count'];
            if ($count > 0) {
                $bedsConditions[] = [
                    'title' => xl($title),
                    'icon' => $data['icon'],
                    'color' => $data['color'],
                    'count' => $count
                ];
            }
        }

        $facilities[] = [
            'id' => $facility['id'],
            'name' => $facility['name'],
            'total_beds' => $totalBeds,
            'bed_conditions' => $bedsConditions
        ];
    }

    return $facilities;
}

// Función para obtener los datos de las unidades con camas de una facility
function getUnitsWithBedsData($facilityId) {
    $unitsQuery = "SELECT u.id, u.unit_name, lo.title AS floor_title FROM units AS u 
                   LEFT JOIN list_options AS lo ON u.floor = lo.option_id AND lo.list_id = 'unit_floor'
                   WHERE u.facility_id = ? AND u.active = 1";
    $unitsResult = sqlStatement($unitsQuery, [$facilityId]);
    $units = [];

    // Obtener las condiciones de las camas y sus iconos y colores
    $bedConditionsQuery = "SELECT lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'bed_condition'";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        // Separar icono y color usando el carácter | (pipe)
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['title']] = ['icon' => $icon, 'color' => $color];
    }

    while ($unit = sqlFetchArray($unitsResult)) {
        // Contar las camas activas de la unidad
        $bedsQuery = "SELECT id FROM beds WHERE facility_id = ? AND unit_id = ? AND active = 1 AND operation <> 'Delete'";
        $bedsResult = sqlStatement($bedsQuery, [$facilityId, $unit['id']]);
        $totalBeds = sqlNumRows($bedsResult);

        // Contar las camas por condición
        $bedsConditions = [];
        foreach ($conditions as $title => $data) {
            $conditionQuery = "SELECT COUNT(*) AS count FROM beds_patients WHERE facility_id = ? AND unit_id = ? AND `condition` = ? AND active = 1";
            $conditionResult = sqlStatement($conditionQuery, [$facilityId, $unit['id'], $title]);
            $count = sqlFetchArray($conditionResult)['count'];
            if ($count > 0) {
                $bedsConditions[] = [
                    'title' => xl($title),
                    'icon' => $data['icon'],
                    'color' => $data['color'],
                    'count' => $count
                ];
            }
        }

        $units[] = [
            'id' => $unit['id'],
            'unit_name' => $unit['unit_name'],
            'unit_floor' => $unit['floor_title'] ?? xl('Unknown'),
            'total_beds' => $totalBeds,
            'bed_conditions' => $bedsConditions
        ];
    }

    return $units;
}

// Función para obtener los datos de los cuartos (rooms) con camas de una unidad
function getRoomsWithBedsData($unitId, $facilityId) {
    // Consulta para obtener los cuartos activos dentro de la unidad especificada
    $roomsQuery = "SELECT r.id, r.room_name, r.sector, lo.title AS room_type_title, ls.title AS room_sector_title 
                   FROM rooms AS r 
                   LEFT JOIN list_options AS lo ON r.room_type = lo.option_id AND lo.list_id = 'rooms_type'
                   LEFT JOIN list_options AS ls ON r.sector = ls.option_id AND ls.list_id = 'room_sector'
                   WHERE r.unit_id = ? AND r.active = 1";
    $roomsResult = sqlStatement($roomsQuery, [$unitId]);
    $rooms = [];

    // Obtener las condiciones de las camas, iconos y colores
    $bedConditionsQuery = "SELECT lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'bed_condition'";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        // Separar icono y color usando el carácter | (pipe)
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['title']] = ['icon' => $icon, 'color' => $color];
    }

    // Iterar sobre cada cuarto para contar sus camas
    while ($room = sqlFetchArray($roomsResult)) {
        // Contar las camas activas del cuarto
        $bedsQuery = "SELECT id FROM beds WHERE facility_id = ? AND room_id = ? AND active = 1 AND operation <> 'Delete'";
        $bedsResult = sqlStatement($bedsQuery, [$facilityId, $room['id']]);
        $totalBeds = sqlNumRows($bedsResult);

        // Contar las camas por condición
        $bedsConditions = [];
        foreach ($conditions as $title => $data) {
            $conditionQuery = "SELECT COUNT(*) AS count FROM beds_patients WHERE facility_id = ? AND room_id = ? AND `condition` = ? AND active = 1";
            $conditionResult = sqlStatement($conditionQuery, [$facilityId, $room['id'], $title]);
            $count = sqlFetchArray($conditionResult)['count'];
            if ($count > 0) {
                $bedsConditions[] = [
                    'title' => xl($title),
                    'icon' => $data['icon'],
                    'color' => $data['color'],
                    'count' => $count
                ];
            }
        }

        // Añadir el cuarto y sus datos a la lista
        $rooms[] = [
            'id' => $room['id'],
            'room_name' => $room['room_name'],
            'room_sector' => $room['room_sector_title'] ?? $room['sector'],
            'room_type' => $room['room_type_title'] ?? xl('Unknown'),
            'total_beds' => $totalBeds,
            'bed_conditions' => $bedsConditions
        ];
    }

    return $rooms;
}

// Función para obtener los detalles de las camas
function getBedsPatientsData($roomId) {
    // Obtener las condiciones de las camas, iconos y colores
    $bedConditionsQuery = "SELECT lo.option_id, lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'bed_condition'";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['option_id']] = [
            'option_id' => $row['option_id'],
            'title' => $row['title'], // Mantener original para lógica
            'icon' => "../images/" . $icon, 
            'color' => $color
        ];
    }

    // Obtener los estados de las camas, textos y colores
    $bedStatusQuery = "SELECT lo.option_id, lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'beds_status'";
    $statusResult = sqlStatement($bedStatusQuery);
    $statuses = [];
    while ($row = sqlFetchArray($statusResult)) {
        $statuses[$row['option_id']] = [
            'option_id' => $row['option_id'],
            'text' => $row['title'], // Mantener original para lógica
            'color' => $row['notes']
        ];
    }

    // Consulta para obtener los datos de las camas del cuarto seleccionado que están ocupadas
    $bedsQuery = "
        SELECT bp.*, 
               lo_type.title AS bed_type_title, 
               lo_status.title AS bed_status_title, 
               lo_cond.title AS condition_title,
               lo_cond.notes AS condition_notes
        FROM beds_patients AS bp
        LEFT JOIN list_options AS lo_type ON bp.bed_type = lo_type.option_id AND lo_type.list_id = 'beds_type'
        LEFT JOIN list_options AS lo_status ON bp.bed_status = lo_status.option_id AND lo_status.list_id = 'beds_status'
        LEFT JOIN list_options AS lo_cond ON bp.condition = lo_cond.option_id AND lo_cond.list_id = 'bed_condition'
        WHERE bp.room_id = ? AND bp.active = 1";
    
    $bedsResult = sqlStatement($bedsQuery, [$roomId]);
    $bedPatients = [];

    while ($row = sqlFetchArray($bedsResult)) {
        $conditionsList = [];
        // Usar el valor crudo del campo 'condition' para la lógica (ej: 'Occupied', 'Vacant')
        $conditionID = $row['condition'];
        
        $icon = "";
        $color = "#000000";
        if (!empty($row['condition_notes'])) {
            list($icon, $color) = explode('|', $row['condition_notes']);
            $icon = "../images/" . $icon;
        }
        
        // Siempre agregamos la condición actual a la lista para que load_beds.php la detecte
        $conditionsList[] = [
            'title' => $conditionID, 
            'icon' => $icon,
            'color' => $color
        ];

        // Verificar si la cama está ocupada para cargar datos del paciente
        $isOccupied = ($conditionID === 'Occupied');

        $bedStatus = [
            'text' => $row['bed_status_title'] ?? $row['bed_status'] ?? 'Unknown',
            'color' => '#000000'
        ];

        // Obtener fechas y horas de asignación e internación
        // Para cargar se debe usar la funcion oeTimestampFormatDateTime() con strtotime()
        // Ejemplo: value="<?= oeTimestampFormatDateTime(strtotime($bedPatient['datetime_modif']))
        $assignedDateFormat = new DateTime($row['assigned_date']);


        // Manejar fecha de alta: si no existe, se usa la fecha actual
        $dischargeDate = !empty($row['change_date']) ? new DateTime($row['change_date']) : new DateTime();
        //$dischargeDateFormatted = $dischargeDate->format('Y-m-d'); // Fecha de alta
        //$dischargeTimeFormatted = $dischargeDate->format('H:i');   // Hora de alta

        // Calcular horas o días totales entre asignación y alta (o la fecha actual si no hay alta)
        $interval = $assignedDateFormat->diff($dischargeDate);
        // Calcular la duración en días y horas
        $days = $interval->format('%a');
        $hours = $interval->format('%h');
        // Formatear la duración utilizando xl*()
        $totalHoursDays = sprintf('%s %s, %s %s', 
            $days, 
            xlt('days'), // Traducción de "días"
            $hours, 
            xlt('hours') // Traducción de "horas"
        );

        // Datos del paciente (solo si está ocupada)
        $bedPatientName = "";
        $bedPatientDNI = "";
        $bedPatientAge = "";
        $bedPatientSex = "";
        $bedPatientInsuranceName = "";
        $bedPatientCare = [];

        if ($isOccupied && !empty($row['patient_id'])) {
            $bedPatientId = $row['patient_id'];
            // Obtener datos del paciente
            $patientData = getPatientData($bedPatientId, "DOB, sex");
            if ($patientData) {
                $bedPatientAge = getPatientAge(str_replace('-', '', $patientData['DOB'] ?? ''));
                $bedPatientSex = text($patientData['sex'] ?? '');
            }
            
            $result = getPatientData($bedPatientId, "fname,lname,mname,pubpid");
            if ($result) {
                $bedPatientName = text($result['lname'] ?? '') . ", " . text($result['fname'] ?? '') . " " . text($result['mname'] ?? '');
                $bedPatientDNI = text($result['pubpid'] ?? '');
            }

            // Obtener datos del seguro
            $insuranceData = getInsuranceData($bedPatientId, "primary", "insd.*, ic.name as provider_name");
            $bedPatientInsuranceName = text($insuranceData['provider_name'] ?? '');

            $bedsPatientsId = $row['id'];
            // Obtener los datos del cuidado del paciente
            $bedPatientCare = getBedPatientCare($bedsPatientsId , $bedPatientId);
        } else {
            $bedPatientId = 0;
        }

        // Añadir la cama a la lista
        $bedPatients[] = [
            'id' => $row['id'],
            'bed_id' => $row['bed_id'],
            'bed_name' => $row['bed_name'],
            'bed_type' => $row['bed_type_title'] ?? xl('Unknown'),
            'bed_notes' => $row['notes'],
            'room_id' => $row['room_id'],
            'unit_id' => $row['unit_id'],
            'facility_id' => $row['facility_id'],
            'responsible_user_id' => $row['responsible_user_id'],
            'inpatient_physical_restrictions' => $row['inpatient_physical_restrictions'],
            'inpatient_sensory_restrictions' => $row['inpatient_sensory_restrictions'],
            'inpatient_cognitive_restrictions' => $row['inpatient_cognitive_restrictions'],
            'inpatient_behavioral_restrictions' => $row['inpatient_behavioral_restrictions'],
            'inpatient_dietary_restrictions' => $row['inpatient_dietary_restrictions'],
            'inpatient_other_restrictions' => $row['inpatient_other_restrictions'],
            'user_modif' => $row['user_modif'],
            'datetime_modif' => $row['datetime_modif'],
            'bed_status' => $bedStatus['text'],
            'status_color' => $bedStatus['color'],
            'bed_patient_id' => $bedPatientId,
            'bed_patient_name' => $bedPatientName,
            'bed_patient_dni' => $bedPatientDNI,
            'bed_patient_care' => $bedPatientCare,
            'conditions' => $conditionsList,
            'isOccupied' => $isOccupied,
            'bed_patient_age' => $bedPatientAge,
            'bed_patient_sex' => $bedPatientSex,
            'bed_patient_insurance_name' => $bedPatientInsuranceName,
            'assigned_date' => $row['assigned_date'],
            'change_date' => $dischargeDate,
            'total_hours_days' => $totalHoursDays
        ];
    }

    return $bedPatients;
}

/**
 * Obtiene los datos de cuidado del paciente para un registro específico de beds_patients.
 *
 * @param int $id El ID del registro en la tabla beds_patients.
 * @param int $patientId El ID del paciente.
 * @return array|null Retorna un array con los datos del cuidado del paciente o null si no se encuentra.
 */
function getBedPatientCare($id, $patientId) {
    // Verificar que ambos parámetros son enteros positivos
    if (!is_numeric($id) || !is_numeric($patientId) || $id <= 0 || $patientId <= 0) {
        return null;
    }

    // Consulta para obtener el valor de patient_care desde beds_patients usando el id del paciente
    $query = "SELECT patient_care 
              FROM beds_patients 
              WHERE id = ?
              AND patient_id = ?
              AND active = 1
              LIMIT 1";
    
    // Ejecutar la consulta usando sqlStatement con los parámetros bedPatientId y patientId
    $result = sqlStatement($query, [$id, $patientId]);

    // Obtener el resultado con sqlFetchArray
    $row = sqlFetchArray($result);

    // Verificar si hay un resultado
    if ($row) {
        $patientCare = $row['patient_care'];
        // error_log('Patient Care: ' . $patientCare); // Descomentar para depuración

        // Consulta para obtener el título y el icono desde list_options
        $queryCare = "SELECT title, notes 
                      FROM list_options 
                      WHERE list_id = 'inpatient_care' 
                        AND title = ?";
        
        // Ejecutar la consulta para obtener los datos del cuidado del paciente
        $resultCare = sqlStatement($queryCare, [$patientCare]);
        $careRow = sqlFetchArray($resultCare);

        // Verificar si se encontró el resultado en la segunda consulta
        if ($careRow) {
            $careTitle = $careRow['title'];
            $iconName = trim($careRow['notes']); // Obtener el nombre del icono desde notes y eliminar espacios en blanco

            // Retornar los datos del cuidado del paciente
            return [
                'care_title' => $careTitle,
                'care_icon' => "../images/{$iconName}" // Ajustar la ruta del archivo SVG del icono
            ];
        }
    }
    
    // Retornar null o un valor por defecto en caso de no encontrar datos
    return null;
}
function debugLog($message) {
    $logFile = '/var/log/nginx/logfile.txt'; // Asegúrate de que este archivo sea escribible
    file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND);
}

function createPrescriptionsSupply($schedule_id) {
    // Obtener datos de prescriptions_schedule usando sqlStatement() y sqlFetchArray()
    $schedule_query = "SELECT * FROM prescriptions_schedule WHERE schedule_id = ?";
    $schedule_result = sqlStatement($schedule_query, array($schedule_id));
    $schedule = sqlFetchArray($schedule_result);

    if (!$schedule) return;

    $patient_id = $schedule['patient_id'];
    $start_date = new DateTime($schedule['start_date']);
    $unit_frequency = (int)$schedule['unit_frequency']; // Ejemplo: 12
    $time_frequency = $schedule['time_frequency']; // Ejemplo: 'hours'
    $unit_duration = (int)$schedule['unit_duration']; // Ejemplo: 0
    $time_duration = $schedule['time_duration']; // Ejemplo: ''

    // Depuración de los valores
    //debugLog("Unit Frequency: $unit_frequency");
    //debugLog("Time Frequency: $time_frequency");
    //debugLog("Unit Duration: $unit_duration");
    //debugLog("Time Duration: $time_duration");

    // Si no hay duration, establecer una duración predeterminada o manejar el error
    if ($unit_duration <= 0 || empty($time_duration)) {
        // Establecer una duración predeterminada (ejemplo: 24 horas)
        $unit_duration = 1;
        $time_duration = 'days';
        //debugLog("No se definió la duración. Se usa la duración predeterminada: $unit_duration $time_duration.");
    }

    // Calcular end_date
    $end_date = null;

    if (!empty($schedule['end_date'])) {
        $end_date = new DateTime($schedule['end_date']);
    } else if ($unit_duration > 0 && !empty($time_duration)) {
        // Calcular el end_date basado en la duración
        $duration_interval = DateInterval::createFromDateString("{$unit_duration} {$time_duration}");
        $end_date = clone $start_date; 
        $end_date->add($duration_interval);
        
        // Depuración del end_date calculado
        //debugLog("Calculated End Date: " . $end_date->format('Y-m-d H:i:s'));
    } else {
        //debugLog("No se pudo calcular el end_date: unidad de duración o tiempo de duración no definidos.");
        return;
    }

    //debugLog("Start Date: " . $start_date->format('Y-m-d H:i:s'));
    //debugLog("End Date: " . ($end_date ? $end_date->format('Y-m-d H:i:s') : 'No se pudo calcular.'));

    // Crear el intervalo de repetición
    if ($unit_frequency > 0) {
        $frequency_interval = DateInterval::createFromDateString("$unit_frequency $time_frequency");
    } else {
        //debugLog("Frequency Interval no válido.");
        return;
    }

    // Calcular el número máximo de repeticiones basado en la duración
    $max_repetitions = null;
    if ($end_date) {
        // Calcular la duración total desde el start_date hasta el end_date en segundos
        $total_duration_seconds = $end_date->getTimestamp() - $start_date->getTimestamp();
        
        // Convertir el intervalo de frecuencia a segundos
        $frequency_seconds = 0;
        switch ($time_frequency) {
            case 'seconds':
                $frequency_seconds = $unit_frequency;
                break;
            case 'minutes':
                $frequency_seconds = $unit_frequency * 60;
                break;
            case 'hours':
                $frequency_seconds = $unit_frequency * 3600;
                break;
            case 'days':
                $frequency_seconds = $unit_frequency * 86400;
                break;
            case 'weeks':
                $frequency_seconds = $unit_frequency * 604800;
                break;
            case 'months':
                $frequency_seconds = $unit_frequency * 2592000; // Aproximadamente 30 días
                break;
        }

        // Calcular el número de repeticiones
        if ($frequency_seconds > 0) {
            $max_repetitions = (int)($total_duration_seconds / $frequency_seconds);
            //$max_repetitions = (int)($total_duration_seconds / $frequency_seconds) + 2; // +2 para incluir la primera y última dosis
        }
    } else {
        // Si no hay end_date, usar un valor predeterminado
        $max_repetitions = 1;  // Usar solo una dosis si no se puede calcular la duración
    }

    //debugLog("Max Repetitions Calculated: $max_repetitions");

    // Crear el periodo de repeticiones
    if ($max_repetitions > 0) {
        $period = new DatePeriod($start_date, $frequency_interval, $max_repetitions - 1);  // Ajuste para que incluya la primera y última dosis
    } else {
        //debugLog("No hay repeticiones válidas.");
        return;
    }
    $user_id = $_SESSION['authUserID']; // Obtener el ID del usuario
    // Iterar por el periodo definido
    $dose_number = 1;  // Inicializar el número de dosis
    foreach ($period as $current_date) {
        // Calcular las alarmas antes de la administración (opcional)
        $alarm1_datetime = null;
        if (!empty($schedule['alarm1_unit']) && !empty($schedule['alarm1_time'])) {
            $alarm1_interval = DateInterval::createFromDateString("{$schedule['alarm1_unit']} {$schedule['alarm1_time']}");
            $alarm1_datetime = clone $current_date;
            $alarm1_datetime->sub($alarm1_interval);  // Restar la alarma del tiempo actual
        }

        $alarm2_datetime = null;
        if (!empty($schedule['alarm2_unit']) && !empty($schedule['alarm2_time'])) {
            $alarm2_interval = DateInterval::createFromDateString("{$schedule['alarm2_unit']} {$schedule['alarm2_time']}");
            $alarm2_datetime = clone $current_date;
            $alarm2_datetime->sub($alarm2_interval);  // Restar la alarma del tiempo actual
        }

        // Insertar el suministro en prescriptions_supply
        handlePrescriptionSupply($schedule_id, $patient_id, $dose_number, $max_repetitions, $current_date, $alarm1_datetime, $alarm2_datetime, $user_id);
        $dose_number++;  // Incrementar el número de dosis
    }
}

function deactivatePreviousSchedule($schedule_id, $user_id) {
    $deactivate_query = "UPDATE prescriptions_schedule 
                         SET active = 0, 
                             modification_datetime = NOW(), 
                             modified_by = ? 
                         WHERE schedule_id = ? AND active = 1";
    sqlStatement($deactivate_query, array($user_id, $schedule_id));
}

function deactivatePreviousSupply($schedule_id, $user_id) {
    $deactivate_query = "UPDATE prescriptions_supply 
                         SET active = 0, 
                             modification_datetime = NOW(), 
                             modified_by = ? 
                         WHERE schedule_id = ? AND active = 1";
    sqlStatement($deactivate_query, array($user_id, $schedule_id));
}

function handlePrescriptionSupply($schedule_id, $patient_id, $dose_number, $max_repetitions, DateTime $schedule_datetime, DateTime $alarm1_datetime = null, DateTime $alarm2_datetime = null, $user_id) {
    // Convertir las fechas a string en el formato correcto antes de hacer la consulta
    $schedule_datetime_formatted = $schedule_datetime->format('Y-m-d H:i:s');
    $alarm1_datetime_formatted = $alarm1_datetime ? $alarm1_datetime->format('Y-m-d H:i:s') : null;
    $alarm2_datetime_formatted = $alarm2_datetime ? $alarm2_datetime->format('Y-m-d H:i:s') : null;

    // Consulta para insertar la nueva fila en prescriptions_supply
    $insert_query = "INSERT INTO prescriptions_supply (
                        schedule_id, 
                        patient_id, 
                        dose_number,
                        max_dose, 
                        schedule_datetime, 
                        alarm1_datetime,
                        alarm1_active, 
                        alarm2_datetime,
                        alarm2_active, 
                        status, 
                        created_by, 
                        creation_datetime, 
                        active
                    ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, 1, 'Pending', ?, NOW(), 1)";
    
    // Usar sqlStatement para ejecutar la inserción en la base de datos y capturar errores
    $result = sqlStatement($insert_query, array(
        $schedule_id, 
        $patient_id, 
        $dose_number,
        $max_repetitions, 
        $schedule_datetime_formatted,  // Formatear schedule_datetime
        $alarm1_datetime_formatted,  // Formatear alarm1_datetime
        $alarm2_datetime_formatted,  // Formatear alarm2_datetime
        $user_id  // ID del usuario que crea el registro
    ));
    
    // Revisar si ocurrió algún error
    if (!$result) {
        // Obtener el último error SQL y mostrarlo
        echo "Error en la consulta: " . sqlStatementError();
    } else {
        echo "Inserción exitosa: Dosis {$dose_number} programada para {$schedule_datetime_formatted}<br>";
    }
}

function getDoseDetails($supply_id) {
    if (!$supply_id) {
        die(xlt('No supply ID provided.'));
    }

    // Definir la consulta
    $dose_query = "
        SELECT ps.supply_id, ps.schedule_datetime, ps.alarm1_datetime, ps.alarm1_active, ps.alarm2_datetime, ps.alarm2_active, 
            ps.status, ps.dose_number, ps.max_dose, DATE_FORMAT(ps.schedule_datetime, '%h:%i %p') AS hs, 
            CONCAT(p.lname, ', ', p.fname, 
            IF(p.mname IS NOT NULL AND p.mname != '', CONCAT(' ', p.mname), '')) AS patient_name,
            bp.bed_id, bp.room_id, bp.unit_id, bp.facility_id, 
            f.name AS facility_name, u.unit_name AS unit_name, r.room_name AS room_name, b.bed_name AS bed_name,
            ps.schedule_id
        FROM prescriptions_supply ps
        LEFT JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
        LEFT JOIN patient_data AS p ON sch.patient_id = p.pid
        LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid
        LEFT JOIN facility AS f ON f.id = bp.facility_id
        LEFT JOIN units AS u ON u.id = bp.unit_id
        LEFT JOIN rooms AS r ON r.id = bp.room_id
        LEFT JOIN beds AS b ON b.id = bp.bed_id
        WHERE ps.supply_id = ?
        GROUP BY ps.supply_id
        ORDER BY ps.schedule_datetime;
    ";

    // Ejecutar la consulta y devolver el resultado
    return sqlQuery($dose_query, [$supply_id]);

}

/**
 * Obtiene la descripción de los medicamentos programados para un `schedule_id` específico.
 *
 * @param int $schedule_id El ID del programa de medicamentos (`schedule_id`).
 * @return string Una cadena que describe los medicamentos programados.
 */
function getMedicationsDetails($schedule_id) {
    // Consulta SQL para obtener detalles del medicamento.
    $med_query = "
        SELECT p.drug, p.size, p.dosage, ps.unit_frequency, ps.time_frequency, ps.scheduled, ps.start_date,
            (SELECT title FROM list_options WHERE option_id = p.unit AND list_id = 'drug_units') AS unit_title,
            p.route, ps.intravenous, iv.vehicle, iv.catheter_type, iv.infusion_rate, iv.iv_route, iv.concentration, iv.concentration_units 
        FROM prescriptions AS p
        INNER JOIN prescriptions_schedule AS ps ON ps.prescription_id = p.id
        LEFT JOIN prescriptions_intravenous AS iv ON iv.schedule_id = ps.schedule_id
        WHERE ps.schedule_id = ?";

    // Ejecutar la consulta con el `schedule_id`.
    $med_result = sqlStatement($med_query, array($schedule_id));
    
    // Inicializar el array de descripciones de medicamentos.
    $medications = [];
    
    // Iterar sobre los resultados y construir la descripción de cada medicamento.
    // Obtener todas las rutas de administración y construir un mapeo
    $routes_map = [];
    $route_result = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'drug_route'");
    while ($route_row = sqlFetchArray($route_result)) {
        $routes_map[$route_row['option_id']] = $route_row['title'];
    }

    while ($med_row = sqlFetchArray($med_result)) {
        // Descripción inicial del medicamento
        $medication_description = $med_row['dosage'] . ' ' . xlt('dose') . ' ' . xlt($med_row['drug']) . ' ' . $med_row['size'] . ' ' . xlt($med_row['unit_title']);
        
        // Obtener la ruta desde el mapeo, si existe
        $route_title = isset($routes_map[$med_row['route']]) ? $routes_map[$med_row['route']] : $med_row['route'];
        $medication_description .= ' ' . xlt($route_title);

        // Verificar si la frecuencia está programada y agregarla
        if ($med_row['scheduled'] == 1) {
            $medication_description .= ' ' . xlt('Every') . ' ' . $med_row['unit_frequency'] . ' ' . xlt($med_row['time_frequency']);
        }

        // Verificar si es intravenoso y agregar la descripción correspondiente
        if ($med_row['intravenous'] == 1) {
            $iv_text = $med_row['concentration'] . $med_row['concentration_units'] . ' ' . xlt('in') . ' ' . xlt($med_row['vehicle']) . ' ' . xlt('on') . ' ' . xlt($med_row['iv_route']) . ' - ' . xlt($med_row['catheter_type']);
            $medication_description .= ' -' . $iv_text;
        }

        // Agregar la descripción al array
        $medications[] = $medication_description;
    }

    // Devolver la descripción combinada de todos los medicamentos.
    return implode(', ', $medications);
}
