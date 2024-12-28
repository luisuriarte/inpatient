<?php
require_once ("../../functions.php");
require_once("../../../interface/globals.php");

if (isset($_GET['centro_id'])) {
    $centroId = $_GET['centro_id'];

    // Consulta usando sqlStatement
    $query = "SELECT * FROM facility WHERE id = ?";
    $result = sqlStatement($query, [$centroId]);

    // Verifica si hay resultados
    if ($result && sqlNumRows($result) > 0) {
        $centro = sqlFetchArray($result);

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
            $centroName = urlencode($centro['name']); // Codifica el nombre del centro para URL
            header("Location: list_units.php?centro_id={$centroId}&centro_name={$centroName}");
            exit;
        }
    } else {
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    showWarning('Centro no encontrado.');
                });
              </script>";
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Estilos de Bootstrap -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
        <!-- Tu hoja de estilos personalizada -->
        <link href="../../styles.css" rel="stylesheet">
        <!-- Otras librerías JavaScript si las necesitas (opcional) -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
        <!-- Incluir el archivo functions.js -->
        <script src="functions.js"></script>
</head>
<body>
    <div id="warningPopup" class="warning-popup" style="display:none;">
        <p id="warningMessage"></p>
        <button onclick="closeWarning('facilities.php')">Aceptar</button>
    </div>
</body>
</html>
