<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

header('Content-Type: application/json');

try {
    $pid = intval($_GET['pid']);
    
    if (!$pid) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT
                bpt.movement_date,
                bpt.movement_type,
                bpt.reason,
                b.bed_name AS bed_from,
                b_to.bed_name AS bed_to,
                bpt.room_id_from,
                bpt.room_id_to,
                bpt.unit_id_from,
                bpt.unit_id_to,
                bpt.facility_id_from,
                bpt.facility_id_to,
                bpt.bed_condition_from,
                bpt.bed_condition_to,
                bpt.notes,
                bpt.responsible_user_id,
                r_from.room_name AS room_name_from,
                r_to.room_name AS room_name_to,
                u_from.unit_name AS unit_name_from,
                u_to.unit_name AS unit_name_to,
                f_from.name AS facility_name_from,
                f_to.name AS facility_name_to
            FROM beds_patients_tracker bpt
            LEFT JOIN beds b ON bpt.bed_id_from = b.id
            LEFT JOIN beds b_to ON bpt.bed_id_to = b_to.id
            LEFT JOIN rooms r_from ON bpt.room_id_from = r_from.id
            LEFT JOIN rooms r_to ON bpt.room_id_to = r_to.id
            LEFT JOIN units u_from ON bpt.unit_id_from = u_from.id
            LEFT JOIN units u_to ON bpt.unit_id_to = u_to.id
            LEFT JOIN facility f_from ON bpt.facility_id_from = f_from.id
            LEFT JOIN facility f_to ON bpt.facility_id_to = f_to.id
            WHERE bpt.patient_id = ?
            ORDER BY bpt.movement_date DESC";

    $result = sqlStatement($sql, [$pid]);
    $history = [];

    while ($row = sqlFetchArray($result)) {
        $row['move_date'] = oeTimestampFormatDateTime(strtotime($row['movement_date']));
        $row['from_location'] = ($row['room_name_from'] ? $row['room_name_from'] . ' - ' : '') .
                               ($row['unit_name_from'] ? $row['unit_name_from'] . ' - ' : '') .
                               ($row['facility_name_from'] ? $row['facility_name_from'] : '');
        $row['to_location'] = ($row['room_name_to'] ? $row['room_name_to'] . ' - ' : '') .
                             ($row['unit_name_to'] ? $row['unit_name_to'] . ' - ' : '') .
                             ($row['facility_name_to'] ? $row['facility_name_to'] : '');
        
        // Obtener el nombre completo del usuario responsable
        if ($row['responsible_user_id']) {
            $row['user'] = getUserFullName($row['responsible_user_id']);
        } else {
            $row['user'] = '';
        }
        
        $history[] = $row;
    }

    echo json_encode($history);
} catch (Exception $e) {
    error_log("Error en get_patient_history.php: " . $e->getMessage());
    echo json_encode([]);
}