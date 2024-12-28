<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar y obtener el ID y nombre de las Unidades y Centros
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

$uuid = generateUUID();

// Obtener el mensaje de advertencia de la URL, si existe
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Add Rooms'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="save_room.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoomModalLabel"><?php echo xlt('Add Room'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($uuid); ?>">
                        <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
                        <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
                        <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                        <input type="hidden" name="operation" value="Add">
                        <input type="hidden" name="user_modif" value="<?php echo htmlspecialchars($userFullName); ?>">
                        <input type="hidden" name="datetime_modif" value="<?php echo date('Y-m-d H:i:s'); ?>">

                        <div class="mb-3">
                            <?php echo "$unitName . '  --  ' . $unitId<br><br>";?>
                            <label for="roomName" class="form-label"><?php echo xlt('Room Name'); ?>:</label>
                            <input type="text" class="form-control" id="roomName" name="room_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="roomBeds" class="form-label"><?php echo xlt('Number of Beds'); ?>:</label>
                            <input type="number" class="form-control" id="roomBeds" name="number_of_beds" required>
                        </div>
                        <div class="mb-3">
                            <label for="roomObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="roomObs" name="obs" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="roomActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="roomActive" name="active" value="1" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php echo xlt('User') . ': ' . $userFullName . '<br>';?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success"><?php echo xlt('Add'); ?></button>
                        <button type="reset" class="btn btn-secondary"><?php echo xlt('Reset'); ?></button>
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
            $('#addRoomModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#addRoomModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_rooms.php?unit_id=<?php echo $unitId; ?>&unit_name=<?php echo $unitName; ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
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
