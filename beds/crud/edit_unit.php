<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';

// Si el ID de la unidad no es válido, redirigir o mostrar un error
if ($unitId == 0) {
    echo "Unidad no válida.";
    exit();
}

// Obtener los detalles de la unidad desde la base de datos utilizando sqlStatement()
$query = "SELECT * FROM units WHERE id = ?";

// Ejecutar la consulta pasando el parámetro correspondiente
$result = sqlStatement($query, [$unitId]);

// Verificar si la unidad existe
if (sqlNumRows($result) > 0) {
    $unit = sqlFetchArray($result);
} else {
    echo "Unidad no encontrada.";
    exit();
}

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Edit Unit'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="editUnitModal" tabindex="-1" aria-labelledby="editUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="update_unit.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUnitModalLabel"><?php echo xlt('Edit Unit'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unit['id']); ?>">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($unit['uuid']); ?>">
                        <input type="hidden" name="centroId" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="centroName" value="<?php echo htmlspecialchars($centroName); ?>">

                        <div class="mb-3">
                            <?php echo xlt('Facility') . ': ' . $centroName . '<br>';?>
                            <label for="unitName" class="form-label"><?php echo xlt('Unit Name'); ?>:</label>
                            <input type="text" class="form-control" id="unitName" name="unit_name" value="<?php echo htmlspecialchars($unit['unit_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="unitRooms" class="form-label"><?php echo xlt('Number of Rooms'); ?>:</label>
                            <input type="number" class="form-control" id="unitRooms" name="number_of_rooms" value="<?php echo htmlspecialchars($unit['number_of_rooms']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="unitObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="unitObs" name="obs" rows="2"><?php echo htmlspecialchars($unit['obs']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="unitActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="unitActive" name="active" value="1" <?php echo ($unit['active'] == 1) ? 'checked' : ''; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php echo xlt('User') . ': ' . $userFullName . '<br>';?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><?php echo xlt('Save'); ?></button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar el modal automáticamente al cargar la página
        $(document).ready(function(){
            $('#editUnitModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#editUnitModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_units.php?centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
        });
    </script>
</body>
</html>
