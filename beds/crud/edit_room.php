<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$roomName = isset($_GET['room_name']) ? htmlspecialchars($_GET['room_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';

// Si el ID del cuarto no es válido, redirigir o mostrar un error
if ($roomId == 0) {
    echo "Cuarto no válido.";
    exit();
}

// Obtener los detalles del cuarto desde la base de datos utilizando sqlStatement()
$query = "SELECT * FROM rooms WHERE id = ?";

// Ejecutar la consulta pasando el parámetro correspondiente
$result = sqlStatement($query, [$roomId]);

// Verificar si la unidad existe
if (sqlNumRows($result) > 0) {
    $room = sqlFetchArray($result);
} else {
    echo "Cuarto no encontrado.";
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
    <title><?php echo xlt('Edit Room'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="update_room.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoomModalLabel"><?php echo xlt('Edit Room'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($room['uuid']); ?>">
                        <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
                        <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
                        <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">

                        <div class="mb-3">
                            <?php echo xlt('Facility') . ': ' . $centroName . '<br>';?>
                            <?php echo xlt('Unit') . ': ' . $unitName . '<br>';?>
                            <label for="roomName" class="form-label"><?php echo xlt('Room Name'); ?>:</label>
                            <input type="text" class="form-control" id="roomName" name="room_name" value="<?php echo htmlspecialchars($room['room_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="roomBeds" class="form-label"><?php echo xlt('Number of Beds'); ?>:</label>
                            <input type="number" class="form-control" id="roomBeds" name="number_of_beds" value="<?php echo htmlspecialchars($room['number_of_beds']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="roomObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="roomObs" name="obs" rows="2"><?php echo htmlspecialchars($room['obs']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="roomActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="roomActive" name="active" value="1" <?php echo ($room['active'] == 1) ? 'checked' : ''; ?>>
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
            $('#editRoomModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#editRoomModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_rooms.php?unit_id=<?php echo $unitId; ?>&unit_name=<?php echo $unitName; ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
        });
    </script>
</body>
</html>
