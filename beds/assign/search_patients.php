<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');

try {
    // Parámetros de búsqueda
    $searchQuery = $_GET['query'] ?? ''; // Búsqueda por nombre
    $fromDate = $_GET['from_date'] ?? null;
    $toDate = $_GET['to_date'] ?? null;
    $statusFilter = $_GET['status'] ?? null; // admitted, preadmitted, discharged, transferred, outpatient
    $includeOutpatients = filter_var($_GET['include_outpatients'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
    // Construir WHERE clause dinámicamente
    $whereClauses = [];
    $binds = [];
    
    // Si hay búsqueda por nombre, solo buscar esos pacientes
    if (!empty($searchQuery)) {
        $whereClauses[] = "LOWER(CONCAT(pd.lname, ' ', pd.fname, ' ', COALESCE(pd.mname, ''))) LIKE LOWER(?)";
        $binds[] = "%" . $searchQuery . "%";
    }
    
    // Filtro por status
    if ($statusFilter && $statusFilter !== 'outpatient') {
        $whereClauses[] = "bp.status = ?";
        $binds[] = $statusFilter;
    } elseif ($statusFilter === 'outpatient') {
        // Para outpatient, buscar pacientes SIN internación o con internación cerrada hace tiempo
        $whereClauses[] = "(bp.id IS NULL OR bp.status = 'discharged')";
    } elseif (empty($searchQuery) && $statusFilter === '') {
        // Si no hay búsqueda y no hay filtro de estado (All Status)
        if ($includeOutpatients) {
            // Incluir pacientes ambulatorios (sin internación activa) y pacientes con internación activa
            // No agregar cláusula WHERE para status, permitir todos los estados
        } else {
            // Solo incluir pacientes con internación activa
            $whereClauses[] = "bp.status IN ('preadmitted', 'admitted')";
        }
    } elseif (empty($searchQuery)) {
        // Si no hay búsqueda pero hay filtro de estado específico, mostrar solo activos por defecto
        $whereClauses[] = "bp.status IN ('preadmitted', 'admitted')";
    }
    
    // Construir consulta base
    $whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
    
    $sql = "SELECT 
                pd.pid,
                CONCAT(pd.lname, ' ', pd.fname, ' ', COALESCE(pd.mname, '')) AS patient_full_name,
                pd.DOB,
                pd.sex,
                pd.pubpid,
                bp.id as beds_patients_id,
                bp.status,
                bp.admission_date,
                bp.discharge_date,
                bp.current_bed_id,
                bp.current_room_id,
                bp.current_unit_id,
                bp.facility_id,
                b.bed_name,
                r.room_name,
                u.unit_name,
                f.name as facility_name,
                bpt.movement_type as last_movement_type,
                bpt.movement_date as last_movement_date
            FROM patient_data pd
            LEFT JOIN beds_patients bp ON pd.pid = bp.patient_id 
                AND bp.id = (
                    SELECT id FROM beds_patients 
                    WHERE patient_id = pd.pid 
                    ORDER BY admission_date DESC 
                    LIMIT 1
                )
            LEFT JOIN beds b ON bp.current_bed_id = b.id
            LEFT JOIN rooms r ON bp.current_room_id = r.id
            LEFT JOIN units u ON bp.current_unit_id = u.id
            LEFT JOIN facility f ON bp.facility_id = f.id
            LEFT JOIN (
                SELECT patient_id, movement_type, movement_date
                FROM beds_patients_tracker bpt1
                WHERE id = (
                    SELECT MAX(id) 
                    FROM beds_patients_tracker bpt2 
                    WHERE bpt2.patient_id = bpt1.patient_id
                )
            ) bpt ON pd.pid = bpt.patient_id
            $whereSQL
            ORDER BY bp.admission_date DESC, pd.lname ASC";
    
    $result = sqlStatement($sql, $binds);
    
    $patients = [];
    
    while ($row = sqlFetchArray($result)) {
        // Obtener edad
        $age = getPatientAge(str_replace('-', '', $row['DOB']));
        
        // Obtener seguro
        $insuranceData = getInsuranceData($row['pid'], "primary", "insd.*, ic.name as provider_name");
        $insuranceName = $insuranceData['provider_name'] ?? '';
        
        // Determinar fecha para filtrado
        $filterDate = $row['last_movement_date'] ?: $row['admission_date'];
        
        // Aplicar filtros de fecha EN PHP (más confiable)
        if ($fromDate || $toDate) {
            if ($filterDate) {
                $recordDate = new DateTime($filterDate);
                
                if ($fromDate) {
                    $fromDateTime = new DateTime($fromDate);
                    if ($recordDate < $fromDateTime) {
                        continue; // Saltar este registro
                    }
                }
                
                if ($toDate) {
                    $toDateTime = new DateTime($toDate);
                    $toDateTime->setTime(23, 59, 59); // Incluir todo el día
                    if ($recordDate > $toDateTime) {
                        continue; // Saltar este registro
                    }
                }
            } else {
                // Si no tiene fecha, excluir si se está filtrando por fecha
                continue;
            }
        }
        
        // Formatear fecha para mostrar
        $formattedDate = '';
        if ($filterDate) {
            $formattedDate = oeTimestampFormatDateTime(strtotime($filterDate));
        }
        
        // Determinar status para display
        $displayStatus = $row['last_movement_type'] ?: $row['status'] ?: 'outpatient';
        
        $patients[] = [
            'pid' => $row['pid'],
            'text' => $row['patient_full_name'],
            'room_name' => $row['room_name'] ?? '',
            'unit_name' => $row['unit_name'] ?? '',
            'facility_name' => $row['facility_name'] ?? '',
            'DOB' => $row['DOB'],
            'age' => $age,
            'sex' => xlt($row['sex']),
            'pubpid' => $row['pubpid'],
            'insurance' => $insuranceName,
            'assigned_date' => $formattedDate,
            'change_date' => $row['discharge_date'] ? oeTimestampFormatDateTime(strtotime($row['discharge_date'])) : '',
            'status' => $row['status'] ?? 'outpatient',
            'last_movement_type' => $displayStatus,
            'beds_patients_id' => $row['beds_patients_id']
        ];
    }
    
    echo json_encode(['results' => $patients]);
    
} catch (Exception $e) {
    error_log("Error en search_patients.php: " . $e->getMessage());
    echo json_encode(['results' => [], 'error' => $e->getMessage()]);
}
?>