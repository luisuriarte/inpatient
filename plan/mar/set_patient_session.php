<?php
require_once('../../../interface/globals.php');

$pid = $_POST['pid'] ?? null;

if ($pid) {
    $_SESSION['pid'] = $pid;
    $GLOBALS['pid'] = $pid;
    error_log("PID guardado en la sesión: " . $_SESSION['pid']);
    echo json_encode(['status' => 'success', 'pid' => $pid]);
} else {
    error_log("Error: PID no proporcionado");
    echo json_encode(['status' => 'error', 'message' => 'PID no proporcionado']);
}
?>

