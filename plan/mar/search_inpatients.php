<?php
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Get the search term
$searchQuery = $_POST['searchQuery'] ?? '';

// SQL query to search for admitted patients
$sql = "
    SELECT 
        pd.lname, pd.fname, pd.mname, pd.pubpid, pd.pid, pd.DOB,
        bp.bed_name, r.room_name, r.sector, u.unit_name,
        lo.title AS floor_name
    FROM 
        beds_patients bp
    JOIN 
        patient_data pd ON bp.patient_id = pd.pid
    JOIN 
        rooms r ON bp.room_id = r.id
    JOIN 
        units u ON bp.unit_id = u.id
    LEFT JOIN 
        list_options lo ON u.floor = lo.option_id AND lo.list_id = 'unit_floor'
    WHERE 
        (pd.fname LIKE ? OR pd.mname LIKE ? OR pd.lname LIKE ? OR pd.pubpid LIKE ? 
         OR r.room_name LIKE ? OR u.unit_name LIKE ? OR r.sector LIKE ? OR lo.title LIKE ?)
        AND bp.condition = 'occupied'
        AND bp.active = 1
";

$result = sqlStatement($sql, [
    "%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%",
    "%$searchQuery%", "%$searchQuery%", "%$searchQuery%", "%$searchQuery%"
]);

if (sqlNumRows($result) > 0) {
    echo '<table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th><i class="material-icons" style="color: #007bff;">person</i> ' . xlt('Last Name') . '</th>
                    <th><i class="material-icons" style="color: #007bff;">person_outline</i> ' . xlt('First Name') . '</th>
                    <th><i class="material-icons" style="color: #28a745;">badge</i> ' . xlt('Ext. ID') . '</th>
                    <th><i class="material-icons" style="color: #dc3545;">bed</i> ' . xlt('Bed') . '</th>
                    <th><i class="material-icons" style="color: #fd7e14;">meeting_room</i> ' . xlt('Room') . '</th>
                    <th><i class="material-icons" style="color: #17a2b8;">location_on</i> ' . xlt('Sector') . '</th>
                    <th><i class="material-icons" style="color: #6c757d;">stairs</i> ' . xlt('Floor') . '</th>
                    <th><i class="material-icons" style="color: #ffc107;">business</i> ' . xlt('Unit') . '</th>
                    <th><i class="material-icons" style="color: #6f42c1;">check</i> ' . xlt('Select') . '</th>
                </tr>
            </thead>
            <tbody>';
    while ($row = sqlFetchArray($result)) {
        $patient_name = $row['lname'] . ', ' . $row['fname'] . ($row['mname'] ? ' ' . $row['mname'] : '');
        echo "<tr>
                <td>" . text($row['lname']) . "</td>
                <td>" . text($row['fname'] . ' ' . $row['mname']) . "</td>
                <td>" . text($row['pubpid']) . "</td>
                <td>" . text($row['bed_name']) . "</td>
                <td>" . text($row['room_name']) . "</td>
                <td>" . text(xl($row['sector'])) . "</td>
                <td>" . text($row['floor_name']) . "</td>
                <td>" . text($row['unit_name']) . "</td>
                <td>
                    <button class='btn btn-sm select-patient' 
                            style='background-color: #6f42c1; border-color: #6f42c1; color: #fff;' 
                            data-pid='" . attr($row['pid']) . "' 
                            data-name='" . attr($patient_name) . "'>
                        <i class='material-icons'>check</i> " . xlt('Select') . "
                    </button>
                </td>
              </tr>";
    }
    echo '</tbody></table>';
} else {
    echo '<div class="alert alert-warning">' . xlt('No results found') . '.</div>';
}
?>

<script src="<?php echo $GLOBALS['webroot']; ?>/library/js/jquery.min.js"></script>
<script>
$(document).on('click', '.select-patient', function() {
    const pid = $(this).data('pid');
    const name = $(this).data('name');
    console.log("Seleccionando PID:", pid, "Nombre:", name);

    // Sincronizar la sesión
    top.restoreSession();

    // Establecer el paciente activo
    top.left_nav.setPatient(name, pid, "", "", "");

    // Redirigir el marco derecho al dashboard
    top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/main/main_screen.php?pid=" + encodeURIComponent(pid);

    // Cerrar el modal con Bootstrap 5
    top.$('#inpatientSearchModal').modal('hide');

    // Depuración
    console.log("Intentando cerrar modal:", typeof top.$, top.$('#inpatientSearchModal').length);
});
</script>