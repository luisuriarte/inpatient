<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

$uuid = generateUUID();

// Obtener el mensaje de advertencia de la URL, si existe
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';
?>

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Add Units'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="save_unit.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUnitModalLabel"><?php echo xlt('Add Unit'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($uuid); ?>">
                        <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                        <input type="hidden" name="operation" value="Add">
                        <input type="hidden" name="user_modif" value="<?php echo htmlspecialchars($userFullName); ?>">
                        <input type="hidden" name="datetime_modif" value="<?php echo date('Y-m-d H:i:s'); ?>">

                        <div class="mb-3">
                            <?php echo "$centroName . '  --  ' . $centroId<br><br>";?>
                            <label for="unitName" class="form-label"><?php echo xlt('Unit Name'); ?>:</label>
                            <input type="text" class="form-control" id="unitName" name="unit_name" required>
                        </div>
                        <!-- Campo Floor -->
                        <div class="mb-3">
                            <label for="unitFloor" class="form-label"><?php echo xlt('Floor'); ?>:</label>
                            <select class="form-select" id="unitFloor" name="floor" required>
                                <?php
                                // Obtener los pisos desde la tabla list_options
                                $floors = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'unit_floor'");
                                while ($floor = sqlFetchArray($floors)):
                                ?>
                                    <option value="<?php echo htmlspecialchars($floor['option_id']); ?>">
                                        <?php echo htmlspecialchars($floor['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="unitRooms" class="form-label"><?php echo xlt('Number of Rooms'); ?>:</label>
                            <input type="number" class="form-control" id="unitRooms" name="number_of_rooms" required>
                        </div>
                        <div class="mb-3">
                            <label for="unitObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="unitObs" name="obs" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="unitActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="unitActive" name="active" value="1" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php echo xlt('User') . ': ' . $userFullName . '<br>';?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><?php echo xlt('Add'); ?></button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="functions.js"></script>
    <script>
        // Mostrar el modal automáticamente al cargar la página
        $(document).ready(function(){
            $('#addUnitModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#addUnitModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_units.php?centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
        });
    </script>
        <script>
        // Mostrar la ventana emergente con el mensaje de advertencia
        function showWarningPopup(message) {
            document.getElementById('warningMessage').innerText = message;
            document.getElementById('warningPopup').style.display = 'block';
        }

        // Cerrar la ventana emergente
        function closeWarningPopup() {
            document.getElementById('warningPopup').style.display = 'none';
        }

        // Mostrar mensaje de advertencia al cargar la página
        window.onload = function() {
            // Se puede utilizar PHP para pasar un mensaje a JavaScript, si es necesario
            <?php if (!empty($warningMessage)): ?>
                showWarningPopup("<?php echo addslashes($warningMessage); ?>");
            <?php endif; ?>
        };
    </script>
</body>
</html>
