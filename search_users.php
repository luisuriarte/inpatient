<?php
require_once("functions.php");
require_once("../interface/globals.php");

header('Content-Type: application/json');

$query = $_GET['query'] ?? '';

$sql = "SELECT u.id, CONCAT(u.lname, ' ', COALESCE(u.mname, ''), ' ', u.fname) AS full_name
        FROM users AS u
        WHERE LOWER(CONCAT(u.lname, ' ', COALESCE(u.mname, ''), ' ', u.fname)) LIKE LOWER(?) AND ACTIVE = 1";

$binds = ["%$query%"];

$result = sqlStatement($sql, $binds);
$users = [];

while ($row = sqlFetchArray($result)) {
    $users[] = [
        'userIdR' => $row['id'],
        'textR' => $row['full_name']
    ];
}

echo json_encode(['results' => $users]);
?>
