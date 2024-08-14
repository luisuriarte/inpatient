<?php

function getCentros($showInactive) {
    global $conn;
    $query = "SELECT * FROM facility WHERE 1";
    if (!$showInactive) {
        $query .= " AND inactive = 0";
    }
    $result = $conn->query($query);

    $centross = [];
    while ($row = $result->fetch_assoc()) {
        $centros[] = $row;
    }
    return $centros;
}

function generateUUID() {
    // Generar 16 bytes (128 bits) aleatorios
    $data = random_bytes(16);

    // Establecer la versión a 0100 (UUIDv4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);

    // Establecer los dos bits más significativos del byte 8 a 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Formatear los bytes en la forma de un UUID estándar
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function getUserFullName($userId, $conn) {
    // Consulta para obtener el nombre completo del usuario basado en su ID
    $sql = "SELECT CONCAT(lname, ', ', fnname, ' ', mname) AS full_name FROM users WHERE id = ?";
    
    // Preparar la consulta
    if ($stmt = $conn->prepare($sql)) {
        // Vincular los parámetros
        $stmt->bind_param("i", $userId);
        
        // Ejecutar la consulta
        $stmt->execute();
        
        // Obtener el resultado
        $stmt->bind_result($fullName);
        $stmt->fetch();
        
        // Cerrar la declaración
        $stmt->close();
        
        // Retornar el nombre completo del usuario
        return $fullName;
    } else {
        // En caso de error, retornar null o un mensaje de error
        return null;
    }
}
?>
