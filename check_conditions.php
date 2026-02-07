<?php
require_once('functions.php');
$res = sqlStatement("SELECT * FROM list_options WHERE list_id = 'bed_condition'");
while ($row = sqlFetchArray($res)) {
    echo $row['option_id'] . " | " . $row['title'] . " | " . $row['notes'] . PHP_EOL;
}
?>
