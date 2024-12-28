<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

$facility_id = intval($_POST['facility_id']);
$options = '<option value="">' . xlt('Select Unit') . '</option>';

if ($facility_id) {
    $units = sqlStatement("SELECT id, unit_name FROM units WHERE facility_id = ?  AND active = 1 ORDER BY unit_name ASC", [$facility_id]);
    while ($row = sqlFetchArray($units)) {
        $options .= '<option value="' . intval($row['id']) . '">' . text($row['unit_name']) . '</option>';
    }
}

echo $options;
