<?php
require_once '../../includes/db.php';
require_once '../../includes/session.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Verificar sesión y permisos
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar datos del formulario
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$medication_id = isset($_POST['medication_id']) ? intval($_POST['medication_id']) : 0;
$dosage = isset($_POST['dosage']) ? trim($_POST['dosage']) : '';
$route = isset($_POST['route']) ? trim($_POST['route']) : '';
$frequency = isset($_POST['frequency']) ? trim($_POST['frequency']) : '';
$start_date = isset($_POST['start_date']) ? trim($_POST['start_date']) : '';
$end_date = isset($_POST['end_date']) ? trim($_POST['end_date']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'active';

// Validaciones básicas
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de orden inválido']);
    exit;
}

if ($medication_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Seleccione un medicamento']);
    exit;
}

if (empty($dosage) || empty($route) || empty($frequency) || empty($start_date)) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos requeridos']);
    exit;
}

try {
    // Verificar que la orden existe y pertenece al paciente/hospital correcto
    // (Aquí deberías agregar verificaciones adicionales según tu estructura de DB)
    
    // Actualizar la orden en la base de datos
    $stmt = $pdo->prepare("
        UPDATE medication_orders 
        SET medication_id = ?, 
            dosage = ?, 
            route = ?, 
            frequency = ?, 
            start_date = ?, 
            end_date = ?, 
            notes = ?, 
            status = ?, 
            updated_at = NOW(), 
            updated_by = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $medication_id,
        $dosage,
        $route,
        $frequency,
        $start_date,
        $end_date,
        $notes,
        $status,
        $_SESSION['user_id'],
        $order_id
    ]);
    
    // Verificar si se actualizó correctamente
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Orden actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o la orden no existe']);
    }
} catch (PDOException $e) {
    error_log('Error al actualizar orden de medicación: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar la orden']);
}