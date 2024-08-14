<?php
$host = 'localhost';
$user = 'openemr';
$password = '***********';
$database = 'openemr';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
