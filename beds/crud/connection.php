<?php
$host = 'localhost';
$user = 'openemr';
$password = '***********';
$database = 'openemr';

$conn = new mysqli($host, $user, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
