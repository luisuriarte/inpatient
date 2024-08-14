<?php
include('connection.php');

if (isset($_GET['id'])) {
    $centroId = $_GET['id'];

    $query = "SELECT * FROM facility WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $centroId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $centro = $result->fetch_assoc();

        if ($centro['inactive'] == 1) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showWarning('Centro inactivo. Actívelo antes de modificar.');
                    });
                  </script>";
        } elseif ($centro['pos_code'] != 30) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showWarning('No tiene capacidad de internación, por favor cambie el Código Pos a 30.');
                    });
                  </script>";
        } elseif ($centro['facility_taxonomy'] < 1) {
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        showWarning('Debe colocar la cantidad de unidades.');
                    });
                  </script>";
        } else {
            // Redirigir a list_units.php con los parámetros centro.id y centro.name
            $centroName = urlencode($centro['name']); // Asegúrate de codificar el nombre para pasarlo en la URL
            header("Location: list_units.php?id={$centro['id']}&name={$centroName}");
            exit;
        }

    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showWarning('centro no encontrado.');
                });
              </script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Unidades</title>
</head>
<body>
<div id="warningPopup" class="warning-popup" style="display:none;">
    <p id="warningMessage"></p>
    <button onclick="closeWarning()">Aceptar</button>
</div>

<script>
    // Mostrar la ventana emergente
    function showWarning(message) {
        console.log('Mostrando mensaje de advertencia:', message);
        document.getElementById('warningMessage').innerText = message;
        document.getElementById('warningPopup').style.display = 'block';
    }

    // Cerrar la ventana emergente
    function closeWarning() {
        document.getElementById('warningPopup').style.display = 'none';
        window.location.href = 'beds.php';  // Redirigir al index.php después de cerrar
    }
</script>
</body>
</html>
