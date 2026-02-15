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
$dob = $patient_data['DOB'] ?? '';
$patient_age = getPatientAge(str_replace('-', '', $dob));
$patient_sex = text($patient_data['sex'] ?? '');

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

// ==========================================
// FUNCIÓN AUXILIAR: OBTENER ESTADO ACTUAL DE UNA CAMA
// ==========================================
/**
 * Obtiene el estado actual de una cama basándose en beds_patients y beds_status_log
 * 
 * @param int $bedId ID de la cama
 * @return array Estado actual de la cama con información del paciente si aplica
 */
function getCurrentBedStatus($bedId) {
    // Verificar si hay un paciente activo en la cama
    $patientQuery = "SELECT
                        bp.id as beds_patients_id,
                        bp.patient_id,
                        bp.status,
                        p.fname,
                        p.lname,
                        bp.admission_date,
                        bp.patient_care,
                        bp.responsible_user_id
                     FROM beds_patients bp
                     LEFT JOIN patient_data p ON bp.patient_id = p.id
                     WHERE bp.current_bed_id = ?
                     AND bp.status IN ('preadmitted', 'admitted')
                     LIMIT 1";

    $patientData = sqlQuery($patientQuery, [$bedId]);

    if ($patientData) {
        return [
            'condition' => ($patientData['status'] === 'preadmitted') ? 'reserved' : 'occupied',
            'patient_id' => $patientData['patient_id'],
            'patient_name' => $patientData['fname'] . ' ' . $patientData['lname'],
            'beds_patients_id' => $patientData['beds_patients_id'],
            'admission_date' => $patientData['admission_date'],
            'patient_care' => $patientData['patient_care'],
            'responsible_user_id' => $patientData['responsible_user_id'],
            'status' => $patientData['status']
        ];
    }
    
    // Si no hay paciente, obtener el último estado del log
    $statusQuery = "SELECT `condition`, changed_date, notes
                    FROM beds_status_log
                    WHERE bed_id = ?
                    ORDER BY changed_date DESC
                    LIMIT 1";
    
    $statusData = sqlQuery($statusQuery, [$bedId]);
    
    if ($statusData) {
        return [
            'condition' => $statusData['condition'],
            'patient_id' => null,
            'patient_name' => null,
            'beds_patients_id' => null,
            'last_changed' => $statusData['changed_date'],
            'notes' => $statusData['notes']
        ];
    }
    
    // Por defecto, cama vacante
    return [
        'condition' => 'vacant',
        'patient_id' => null,
        'patient_name' => null,
        'beds_patients_id' => null
    ];
}

// ==========================================
// FUNCIÓN AUXILIAR: INSERTAR EN BEDS_STATUS_LOG
// ==========================================
/**
 * Inserta un nuevo registro en beds_status_log
 * 
 * @param int $bedId ID de la cama
 * @param string $condition Condición de la cama (occupied, reserved, cleaning, vacant, archived)
 * @param int $userId ID del usuario que realiza el cambio
 * @param int|null $relatedBedsPatientsId ID de beds_patients relacionado (opcional)
 * @param string|null $notes Notas adicionales (opcional)
 * @return mixed Resultado de la inserción
 */
function insertBedStatusLog($bedId, $condition, $userId, $relatedBedsPatientsId = null, $notes = null) {
    $query = "INSERT INTO beds_status_log (
        bed_id, `condition`, changed_date, changed_by_user_id, 
        related_beds_patients_id, notes
    ) VALUES (?, ?, NOW(), ?, ?, ?)";
    
    return sqlStatement($query, [
        $bedId, $condition, $userId, $relatedBedsPatientsId, $notes
    ]);
}

// ==========================================
// FUNCIÓN: CONTAR CAMAS POR CONDICIÓN
// ==========================================
/**
 * Cuenta las camas por condición en un nivel específico (facility, unit, room)
 * 
 * @param int|null $facilityId ID de la facility (opcional)
 * @param int|null $unitId ID de la unidad (opcional)
 * @param int|null $roomId ID del cuarto (opcional)
 * @return array Array con el conteo por condición
 */
function getBedConditionsCount($facilityId = null, $unitId = null, $roomId = null) {
    // Obtener las condiciones configuradas (excluyendo archived)
    $bedConditionsQuery = "SELECT lo.option_id, lo.title, lo.notes 
                          FROM list_options AS lo 
                          WHERE lo.list_id = 'bed_condition' 
                          AND lo.option_id <> 'archival' 
                          ORDER BY lo.seq ASC";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['option_id']] = [
            'title' => $row['title'],
            'icon' => $icon,
            'color' => $color,
            'count' => 0
        ];
    }
    
    // Construir WHERE clause
    $where = ["b.active = 1"];
    $params = [];
    
    if ($facilityId) {
        $where[] = "b.facility_id = ?";
        $params[] = $facilityId;
    }
    if ($unitId) {
        $where[] = "b.unit_id = ?";
        $params[] = $unitId;
    }
    if ($roomId) {
        $where[] = "b.room_id = ?";
        $params[] = $roomId;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Obtener todas las camas del nivel especificado
    $bedsQuery = "SELECT id FROM beds b WHERE $whereClause";
    $bedsResult = sqlStatement($bedsQuery, $params);
    
    $totalBeds = 0;
    while ($bed = sqlFetchArray($bedsResult)) {
        $totalBeds++;
        $status = getCurrentBedStatus($bed['id']);
        $condition = $status['condition'];
        
        if (isset($conditions[$condition])) {
            $conditions[$condition]['count']++;
        }
    }
    
    // Preparar resultado
    $result = [];
    foreach ($conditions as $key => $data) {
        $result[] = [
            'title' => xl($data['title']),
            'icon' => $data['icon'],
            'color' => $data['color'],
            'count' => $data['count']
        ];
    }
    
    return $result;
}

// Función para obtener los datos de las facilities con camas - REFACTORIZADA
function getFacilitiesWithBedsData() {
    $facilitiesQuery = "SELECT id, name FROM facility WHERE inactive = 0";
    $facilitiesResult = sqlStatement($facilitiesQuery);
    $facilities = [];

    while ($facility = sqlFetchArray($facilitiesResult)) {
        // Contar las camas activas de la facility
        $bedsQuery = "SELECT COUNT(*) as total FROM beds WHERE facility_id = ? AND active = 1";
        $bedsResult = sqlQuery($bedsQuery, [$facility['id']]);
        $totalBeds = (int)$bedsResult['total'];

        // Obtener el conteo por condición
        $bedsConditions = getBedConditionsCount($facility['id'], null, null);

        $facilities[] = [
            'id' => $facility['id'],
            'name' => $facility['name'],
            'total_beds' => $totalBeds,
            'bed_conditions' => $bedsConditions
        ];
    }

    return $facilities;
}

// Función para obtener los datos de las unidades con camas de una facility - REFACTORIZADA
function getUnitsWithBedsData($facilityId) {
    $unitsQuery = "SELECT u.id, u.unit_name, lo.title AS floor_title FROM units AS u 
                   LEFT JOIN list_options AS lo ON u.floor = lo.option_id AND lo.list_id = 'unit_floor'
                   WHERE u.facility_id = ? AND u.active = 1";
    $unitsResult = sqlStatement($unitsQuery, [$facilityId]);
    $units = [];

    while ($unit = sqlFetchArray($unitsResult)) {
        // Contar las camas activas de la unidad
        $bedsQuery = "SELECT COUNT(*) as total FROM beds WHERE facility_id = ? AND unit_id = ? AND active = 1";
        $bedsResult = sqlQuery($bedsQuery, [$facilityId, $unit['id']]);
        $totalBeds = (int)$bedsResult['total'];

        // Obtener el conteo por condición
        $bedsConditions = getBedConditionsCount($facilityId, $unit['id'], null);

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

// Función para obtener los datos de los cuartos (rooms) con camas de una unidad - REFACTORIZADA
function getRoomsWithBedsData($unitId, $facilityId) {
    // Consulta para obtener los cuartos activos dentro de la unidad especificada
    $roomsQuery = "SELECT r.id, r.room_name, r.sector, lo.title AS room_type_title, ls.title AS room_sector_title 
                   FROM rooms AS r 
                   LEFT JOIN list_options AS lo ON r.room_type = lo.option_id AND lo.list_id = 'rooms_type'
                   LEFT JOIN list_options AS ls ON r.sector = ls.option_id AND ls.list_id = 'room_sector'
                   WHERE r.unit_id = ? AND r.active = 1";
    $roomsResult = sqlStatement($roomsQuery, [$unitId]);
    $rooms = [];

    // Iterar sobre cada cuarto para contar sus camas
    while ($room = sqlFetchArray($roomsResult)) {
        // Contar las camas activas del cuarto
        $bedsQuery = "SELECT COUNT(*) as total FROM beds WHERE facility_id = ? AND room_id = ? AND active = 1";
        $bedsResult = sqlQuery($bedsQuery, [$facilityId, $room['id']]);
        $totalBeds = (int)$bedsResult['total'];

        // Obtener el conteo por condición
        $bedsConditions = getBedConditionsCount($facilityId, $unitId, $room['id']);

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

// ==========================================
// FUNCIÓN: OBTENER DETALLES DE LAS CAMAS DE UN CUARTO - REFACTORIZADA
// ==========================================
/**
 * Obtiene los detalles de todas las camas de un cuarto con su estado actual
 * 
 * @param int $roomId ID del cuarto
 * @return array Array con los detalles de cada cama
 */
function getBedsPatientsData($roomId) {
    // Obtener las condiciones de las camas, iconos y colores
    $bedConditionsQuery = "SELECT lo.option_id, lo.title, lo.notes FROM list_options AS lo WHERE lo.list_id = 'bed_condition'";
    $conditionsResult = sqlStatement($bedConditionsQuery);
    $conditions = [];
    while ($row = sqlFetchArray($conditionsResult)) {
        list($icon, $color) = explode('|', $row['notes']);
        $conditions[$row['option_id']] = [
            'option_id' => $row['option_id'],
            'title' => $row['title'],
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
            'text' => $row['title'],
            'color' => $row['notes']
        ];
    }

    // Consulta para obtener los datos físicos de las camas
    $bedsQuery = "
        SELECT b.id AS bed_id,
               b.bed_name,
               b.bed_type,
               b.bed_status,
               b.room_id,
               b.unit_id,
               b.facility_id,
               b.obs AS bed_notes,
               lo_type.title AS bed_type_title, 
               lo_status.title AS bed_status_title
        FROM beds AS b
        LEFT JOIN list_options AS lo_type ON b.bed_type = lo_type.option_id AND lo_type.list_id = 'beds_type'
        LEFT JOIN list_options AS lo_status ON b.bed_status = lo_status.option_id AND lo_status.list_id = 'beds_status'
        WHERE b.room_id = ? AND b.active = 1
        ORDER BY b.bed_name";
    
    $bedsResult = sqlStatement($bedsQuery, [$roomId]);
    $bedPatients = [];

    while ($bedRow = sqlFetchArray($bedsResult)) {
        $bedId = $bedRow['bed_id'];
        
        // Obtener el estado actual de la cama
        $currentStatus = getCurrentBedStatus($bedId);
        
        $conditionID = $currentStatus['condition'];
        $isOccupied = ($conditionID === 'occupied');
        
        // Obtener icono y color de la condición
        $conditionData = $conditions[$conditionID] ?? $conditions['vacant'];
        $icon = $conditionData['icon'];
        $color = $conditionData['color'];
        $currentConditionTitle = xl($conditionData['title']);
        
        $conditionsList = [[
            'key' => $conditionID,
            'title' => $currentConditionTitle, 
            'icon' => $icon,
            'color' => $color
        ]];

        // Datos del estado de la cama (bed_status de la tabla beds)
        $bedStatus = [
            'text' => $bedRow['bed_status_title'] ?? $bedRow['bed_status'] ?? 'Unknown',
            'color' => $statuses[$bedRow['bed_status']]['color'] ?? '#000000'
        ];

        // Inicializar datos del paciente
        $bedPatientId = 0;
        $bedPatientName = "";
        $bedPatientDNI = "";
        $bedPatientAge = "";
        $bedPatientSex = "";
        $bedPatientInsuranceName = "";
        $bedPatientCare = [];
        $assignedDate = null;
        $changeDate = null;
        $totalHoursDays = "";
        $bedsPatientsId = null;
        $responsibleUserId = null;
        $restrictionsData = [];

        // Si la cama está ocupada o reservada, obtener datos del paciente
        if ($isOccupied || $conditionID === 'reserved') {
            $bedPatientId = $currentStatus['patient_id'];
            $bedsPatientsId = $currentStatus['beds_patients_id'];
            
            if ($bedPatientId) {
                // Obtener datos completos de la internación
                $admissionQuery = "SELECT 
                                    bp.admission_date,
                                    bp.discharge_date,
                                    bp.responsible_user_id,
                                    bp.patient_care,
                                    bp.inpatient_physical_restrictions,
                                    bp.inpatient_sensory_restrictions,
                                    bp.inpatient_cognitive_restrictions,
                                    bp.inpatient_behavioral_restrictions,
                                    bp.inpatient_dietary_restrictions,
                                    bp.inpatient_other_restrictions,
                                    bp.notes
                                   FROM beds_patients bp
                                   WHERE bp.id = ?";
                
                $admissionData = sqlQuery($admissionQuery, [$bedsPatientsId]);
                
                if ($admissionData) {
                    $assignedDate = $admissionData['admission_date'];
                    $responsibleUserId = $admissionData['responsible_user_id'];
                    
                    // Calcular duración
                    $assignedDateFormat = new DateTime($assignedDate);
                    $dischargeDate = !empty($admissionData['discharge_date']) ? 
                                    new DateTime($admissionData['discharge_date']) : 
                                    new DateTime();
                    
                    $interval = $assignedDateFormat->diff($dischargeDate);
                    $days = $interval->format('%a');
                    $hours = $interval->format('%h');
                    $totalHoursDays = sprintf('%s %s, %s %s', 
                        $days, xlt('days'), $hours, xlt('hours')
                    );
                    
                    // Restricciones
                    $restrictionsData = [
                        'physical' => $admissionData['inpatient_physical_restrictions'],
                        'sensory' => $admissionData['inpatient_sensory_restrictions'],
                        'cognitive' => $admissionData['inpatient_cognitive_restrictions'],
                        'behavioral' => $admissionData['inpatient_behavioral_restrictions'],
                        'dietary' => $admissionData['inpatient_dietary_restrictions'],
                        'other' => $admissionData['inpatient_other_restrictions']
                    ];
                }
                
                // Obtener datos del paciente
                $patientData = getPatientData($bedPatientId, "fname,lname,mname,pubpid,DOB,sex");
                if ($patientData) {
                    $bedPatientName = text($patientData['lname']) . ", " . 
                                     text($patientData['fname']) . " " . 
                                     text($patientData['mname']);
                    $bedPatientDNI = text($patientData['pubpid']);
                    $bedPatientAge = getPatientAge(str_replace('-', '', $patientData['DOB']));
                    $bedPatientSex = text($patientData['sex']);
                }

                // Obtener seguro
                $insuranceData = getInsuranceData($bedPatientId, "primary", "insd.*, ic.name as provider_name");
                $bedPatientInsuranceName = text($insuranceData['provider_name'] ?? '');

                // Obtener datos de cuidado del paciente
                $bedPatientCare = getBedPatientCare($bedsPatientsId, $bedPatientId);
            }
        }

        // Añadir la cama a la lista
        $bedPatients[] = [
            'id' => $bedId,
            'bp_id' => $bedsPatientsId,
            'bed_id' => $bedId,
            'bed_name' => $bedRow['bed_name'],
            'bed_type' => $bedRow['bed_type_title'] ?? xl('Unknown'),
            'bed_notes' => $bedRow['bed_notes'],
            'room_id' => $bedRow['room_id'],
            'unit_id' => $bedRow['unit_id'],
            'facility_id' => $bedRow['facility_id'],
            'responsible_user_id' => $responsibleUserId,
            'inpatient_physical_restrictions' => $restrictionsData['physical'] ?? null,
            'inpatient_sensory_restrictions' => $restrictionsData['sensory'] ?? null,
            'inpatient_cognitive_restrictions' => $restrictionsData['cognitive'] ?? null,
            'inpatient_behavioral_restrictions' => $restrictionsData['behavioral'] ?? null,
            'inpatient_dietary_restrictions' => $restrictionsData['dietary'] ?? null,
            'inpatient_other_restrictions' => $restrictionsData['other'] ?? null,
            'user_modif' => null,
            'datetime_modif' => null,
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
            'assigned_date' => $assignedDate,
            'change_date' => $changeDate,
            'total_hours_days' => $totalHoursDays
        ];
    }

    return $bedPatients;
}

/**
 * Obtiene los datos de cuidado del paciente para un registro específico de beds_patients.
 * REFACTORIZADA para usar el nuevo esquema
 *
 * @param int $bedsPatientsId El ID del registro en la tabla beds_patients.
 * @param int $patientId El ID del paciente.
 * @return array|null Retorna un array con los datos del cuidado del paciente o null si no se encuentra.
 */
function getBedPatientCare($bedsPatientsId, $patientId) {
    // Verificar que ambos parámetros son enteros positivos
    if (!is_numeric($bedsPatientsId) || !is_numeric($patientId) || $bedsPatientsId <= 0 || $patientId <= 0) {
        return null;
    }

    // Consulta para obtener el valor de patient_care desde beds_patients
    $query = "SELECT patient_care 
              FROM beds_patients 
              WHERE id = ?
              AND patient_id = ?
              AND status IN ('preadmitted', 'admitted')
              LIMIT 1";
    
    $result = sqlStatement($query, [$bedsPatientsId, $patientId]);
    $row = sqlFetchArray($result);

    if ($row) {
        $patientCare = $row['patient_care'];

        // Consulta para obtener el título y el icono desde list_options
        $queryCare = "SELECT title, notes 
                      FROM list_options 
                      WHERE list_id = 'inpatient_care' 
                        AND title = ?";
        
        $resultCare = sqlStatement($queryCare, [$patientCare]);
        $careRow = sqlFetchArray($resultCare);

        if ($careRow) {
            $careTitle = $careRow['title'];
            $iconName = trim($careRow['notes']);

            return [
                'care_title' => $careTitle,
                'care_icon' => "../images/{$iconName}"
            ];
        }
    }
    
    return null;
}

// ==========================================
// NUEVAS FUNCIONES PARA EL NUEVO DISEÑO
// ==========================================

/**
 * Obtiene el historial completo de movimientos de una internación
 * 
 * @param int $bedsPatientsId ID de la internación en beds_patients
 * @return resource Resultado de la consulta con el historial
 */
function getPatientMovementHistory($bedsPatientsId) {
    $query = "SELECT 
                bpt.id,
                bpt.movement_type,
                bpt.movement_date,
                bpt.bed_id_from,
                bpt.bed_name_from,
                bpt.room_id_from,
                rf.room_name as room_name_from,
                bpt.unit_id_from,
                uf.unit_name as unit_name_from,
                bpt.facility_id_from,
                ff.name as facility_name_from,
                bpt.bed_id_to,
                bpt.bed_name_to,
                bpt.room_id_to,
                rt.room_name as room_name_to,
                bpt.unit_id_to,
                ut.unit_name as unit_name_to,
                bpt.facility_id_to,
                ft.name as facility_name_to,
                bpt.reason,
                bpt.notes,
                u.username as responsible_user,
                CONCAT(u.fname, ' ', u.lname) as responsible_user_fullname
              FROM beds_patients_tracker bpt
              LEFT JOIN rooms rf ON bpt.room_id_from = rf.id
              LEFT JOIN units uf ON bpt.unit_id_from = uf.id
              LEFT JOIN facility ff ON bpt.facility_id_from = ff.id
              LEFT JOIN rooms rt ON bpt.room_id_to = rt.id
              LEFT JOIN units ut ON bpt.unit_id_to = ut.id
              LEFT JOIN facility ft ON bpt.facility_id_to = ft.id
              LEFT JOIN users u ON bpt.responsible_user_id = u.id
              WHERE bpt.beds_patients_id = ?
              ORDER BY bpt.movement_date ASC";
    
    return sqlStatement($query, [$bedsPatientsId]);
}

/**
 * Obtiene todas las internaciones de un paciente (activas e históricas)
 * 
 * @param int $patientId ID del paciente
 * @param bool $includeActive Incluir internaciones activas (preadmitted, admitted)
 * @param bool $includeDischarged Incluir internaciones dadas de alta
 * @param int|null $limit Límite de resultados (opcional)
 * @return resource Resultado de la consulta
 */
function getPatientAdmissionsHistory($patientId, $includeActive = true, $includeDischarged = true, $limit = null) {
    $statusConditions = [];
    
    if ($includeActive) {
        $statusConditions[] = "'preadmitted'";
        $statusConditions[] = "'admitted'";
    }
    if ($includeDischarged) {
        $statusConditions[] = "'discharged'";
    }
    
    if (empty($statusConditions)) {
        return false;
    }
    
    $statusWhere = "bp.status IN (" . implode(',', $statusConditions) . ")";
    $limitClause = $limit ? "LIMIT " . intval($limit) : "";
    
    $query = "SELECT 
                bp.id,
                bp.uuid,
                bp.admission_type,
                bp.admission_date,
                bp.discharge_date,
                bp.discharge_disposition,
                bp.status,
                bp.current_bed_id,
                b.bed_name as current_bed_name,
                bp.current_room_id,
                r.room_name as current_room_name,
                bp.current_unit_id,
                u.unit_name as current_unit_name,
                bp.facility_id,
                f.name as facility_name,
                bp.patient_care,
                bp.responsible_user_id,
                CONCAT(usr.fname, ' ', usr.lname) as responsible_user_name,
                DATEDIFF(COALESCE(bp.discharge_date, NOW()), bp.admission_date) as days_hospitalized
              FROM beds_patients bp
              LEFT JOIN beds b ON bp.current_bed_id = b.id
              LEFT JOIN rooms r ON bp.current_room_id = r.id
              LEFT JOIN units u ON bp.current_unit_id = u.id
              LEFT JOIN facility f ON bp.facility_id = f.id
              LEFT JOIN users usr ON bp.responsible_user_id = usr.id
              WHERE bp.patient_id = ? AND $statusWhere
              ORDER BY bp.admission_date DESC
              $limitClause";
    
    return sqlStatement($query, [$patientId]);
}

/**
 * Libera manualmente una cama (ej. después de limpieza)
 * 
 * @param int $bedId ID de la cama
 * @param int $userId ID del usuario
 * @param string|null $notes Notas adicionales
 * @return mixed Resultado de la operación
 * @throws Exception Si hay un paciente asignado
 */
function releaseBedToVacant($bedId, $userId, $notes = null) {
    // Verificar que no haya paciente asignado
    $checkQuery = "SELECT id FROM beds_patients 
                   WHERE current_bed_id = ? 
                   AND status IN ('preadmitted', 'admitted')
                   LIMIT 1";
    $hasPatient = sqlQuery($checkQuery, [$bedId]);
    
    if ($hasPatient) {
        throw new Exception("Cannot release bed - patient is currently assigned");
    }
    
    // Insertar en log
    return insertBedStatusLog($bedId, 'vacant', $userId, null, $notes);
}

/**
 * Marca una cama como en limpieza
 * 
 * @param int $bedId ID de la cama
 * @param int $userId ID del usuario
 * @param string|null $notes Notas adicionales
 * @return mixed Resultado de la operación
 */
function setBedToCleaning($bedId, $userId, $notes = null) {
    return insertBedStatusLog($bedId, 'cleaning', $userId, null, $notes);
}

function createPrescriptionsSupply($schedule_id)
{
    global $database;

    $user_id = $_SESSION['authUserID'] ?? 0;

    // 1️⃣ Obtener schedule activo
    $schedule = sqlQuery("
        SELECT *
        FROM prescriptions_schedule
        WHERE schedule_id = ?
          AND active = 1
          AND status = 'Active'
    ", [$schedule_id]);

    if (!$schedule) {
        return false;
    }

    // 2️⃣ Validaciones básicas
    if (empty($schedule['start_date']) ||
        empty($schedule['unit_frequency']) ||
        empty($schedule['time_frequency'])) {
        return false;
    }

    $patient_id = $schedule['patient_id'];
    $start_date = new DateTime($schedule['start_date']);

    // 3️⃣ Determinar end_date correctamente
    if (!empty($schedule['end_date'])) {

        $end_date = new DateTime($schedule['end_date']);

    } elseif (!empty($schedule['unit_duration']) && !empty($schedule['time_duration'])) {

        $duration_interval = DateInterval::createFromDateString(
            $schedule['unit_duration'] . ' ' . $schedule['time_duration']
        );

        $end_date = clone $start_date;
        $end_date->add($duration_interval);

    } else {
        return false; // No hay duración válida
    }

    if ($end_date <= $start_date) {
        return false;
    }

    // 4️⃣ Crear intervalo real de frecuencia
    $frequency_interval = DateInterval::createFromDateString(
        $schedule['unit_frequency'] . ' ' . $schedule['time_frequency']
    );

    // 5️⃣ Cancelar supplies pendientes anteriores (anti-duplicado)
    sqlStatement("
        UPDATE prescriptions_supply
        SET active = 0,
            status = 'Cancelled',
            modification_datetime = NOW(),
            modified_by = ?
        WHERE schedule_id = ?
          AND status = 'Pending'
    ", [$user_id, $schedule_id]);

    // 6️⃣ Generar todas las fechas usando DatePeriod real
    $dates = [];
    $current = clone $start_date;

    while ($current < $end_date) {
        $dates[] = clone $current;
        $current->add($frequency_interval);
    }

    if (empty($dates)) {
        return false;
    }

    $max_dose = count($dates);
    $dose_number = 1;

    // 7️⃣ Insertar supplies
    foreach ($dates as $dose_datetime) {

        $alarm1_datetime = null;
        $alarm1_active = 0;

        if (!empty($schedule['alarm1_unit']) && !empty($schedule['alarm1_time'])) {
            $alarm_interval = DateInterval::createFromDateString(
                $schedule['alarm1_unit'] . ' ' . $schedule['alarm1_time']
            );
            $alarm1_datetime = clone $dose_datetime;
            $alarm1_datetime->sub($alarm_interval);
            $alarm1_active = 1;
        }

        $alarm2_datetime = null;
        $alarm2_active = 0;

        if (!empty($schedule['alarm2_unit']) && !empty($schedule['alarm2_time'])) {
            $alarm_interval = DateInterval::createFromDateString(
                $schedule['alarm2_unit'] . ' ' . $schedule['alarm2_time']
            );
            $alarm2_datetime = clone $dose_datetime;
            $alarm2_datetime->sub($alarm_interval);
            $alarm2_active = 1;
        }

        sqlStatement("
            INSERT INTO prescriptions_supply (
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
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW(), 1)
        ", [
            $schedule_id,
            $patient_id,
            $dose_number,
            $max_dose,
            $dose_datetime->format('Y-m-d H:i:s'),
            $alarm1_datetime ? $alarm1_datetime->format('Y-m-d H:i:s') : null,
            $alarm1_active,
            $alarm2_datetime ? $alarm2_datetime->format('Y-m-d H:i:s') : null,
            $alarm2_active,
            $user_id
        ]);

        $dose_number++;
    }

    return true;
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
        // Obtener el último error SQL y registrarlo en el log
        error_log("Error en la consulta handlePrescriptionSupply: " . sqlStatementError());
    }
    // Nota: No usar echo aquí, ya que esta función puede ser llamada desde APIs que retornan JSON
}
function getDoseDetails($supply_id) {
    if (!$supply_id) {
        die(xlt('No supply ID provided.'));
    }

    // Debug: Log the supply_id being queried
    error_log("getDoseDetails: Querying for supply_id=$supply_id");

    // Definir la consulta - separar en dos partes para no depender de beds_patients
    $dose_query = "
        SELECT ps.supply_id, ps.schedule_datetime, ps.alarm1_datetime, ps.alarm1_active, ps.alarm2_datetime, ps.alarm2_active,
            ps.status, ps.dose_number, ps.max_dose, DATE_FORMAT(ps.schedule_datetime, '%h:%i %p') AS hs,
            CONCAT(p.lname, ', ', p.fname,
            IF(p.mname IS NOT NULL AND p.mname != '', CONCAT(' ', p.mname), '')) AS patient_name,
            ps.schedule_id, ps.effectiveness_score, ps.effectiveness_notes, ps.reaction_description,
            ps.reaction_time, ps.reaction_severity, ps.reaction_notes,
            bp.current_bed_id AS bed_id, bp.current_room_id AS room_id, bp.current_unit_id AS unit_id, bp.facility_id,
            f.name AS facility_name, u.unit_name AS unit_name, r.room_name AS room_name, b.bed_name AS bed_name
        FROM prescriptions_supply ps
        LEFT JOIN prescriptions_schedule sch ON ps.schedule_id = sch.schedule_id
        LEFT JOIN patient_data AS p ON sch.patient_id = p.pid
        LEFT JOIN beds_patients AS bp ON bp.patient_id = p.pid AND bp.status = 'admitted'
        LEFT JOIN facility AS f ON f.id = bp.facility_id
        LEFT JOIN units AS u ON u.id = bp.current_unit_id
        LEFT JOIN rooms AS r ON r.id = bp.current_room_id
        LEFT JOIN beds AS b ON b.id = bp.current_bed_id
        WHERE ps.supply_id = ?
        LIMIT 1;
    ";

    // Ejecutar la consulta y devolver el resultado
    $result = sqlQuery($dose_query, [$supply_id]);
    
    // Debug: Log whether a result was found
    if ($result) {
        error_log("getDoseDetails: Found result for supply_id=$supply_id");
        error_log("getDoseDetails: effectiveness_score=" . ($result['effectiveness_score'] ?? 'NULL') . 
                  " reaction_description=" . ($result['reaction_description'] ?? 'NULL') .
                  " patient_name=" . ($result['patient_name'] ?? 'NULL'));
    } else {
        error_log("getDoseDetails: No result found for supply_id=$supply_id");
    }
    
    return $result;

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
