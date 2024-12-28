<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

$unit_id = intval($_POST['unit_id']);
$options = '<option value="">' . xlt('Select Room') . '</option>';

if ($unit_id) {
    $rooms = sqlStatement("SELECT id, room_name FROM rooms WHERE unit_id = ?  AND active = 1 ORDER BY room_name ASC", [$unit_id]);
    while ($row = sqlFetchArray($rooms)) {
        $options .= '<option value="' . intval($row['id']) . '">' . text($row['room_name']) . '</option>';
    }
}

echo $options;
