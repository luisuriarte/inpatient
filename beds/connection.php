<?php
$host = 'localhost';
$user = 'openemr';
$password = 'S4nC4rl0sC3ntr0';
$database = 'openemr';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
