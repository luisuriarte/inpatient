<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$roomName = isset($_GET['room_name']) ? htmlspecialchars($_GET['room_name']) : '';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

$uuid = generateUUID();

// Obtener el mensaje de advertencia de la URL, si existe
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';

// Consulta para obtener los tipos de camas
$queryBedType = "SELECT title FROM list_options WHERE list_id = 'Beds_Type' ORDER BY option_id";
$resultBedType = sqlStatement($queryBedType);

// Consulta para obtener los estados de camas
$queryBedStatus = "SELECT title FROM list_options WHERE list_id = 'Beds_Status' ORDER BY option_id";
$resultBedStatus = sqlStatement($queryBedStatus);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Add Beds'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="addBedModal" tabindex="-1" aria-labelledby="addBedModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="save_bed.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addBedModalLabel"><?php echo xlt('Add Bed'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($uuid); ?>">
                        <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($roomId); ?>">
                        <input type="hidden" name="room_name" value="<?php echo htmlspecialchars($roomName); ?>">
                        <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
                        <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
                        <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                        <input type="hidden" name="operation" value="Add">
                        <input type="hidden" name="user_modif" value="<?php echo htmlspecialchars($userFullName); ?>">
                        <input type="hidden" name="datetime_modif" value="<?php echo date('Y-m-d H:i:s'); ?>">

                        <div class="mb-3">
                            <?php echo xlt('facility') . ': ' . $centroName . '<br>'; ?>
                            <?php echo xlt('Unit') . ': ' . $unitName . '<br>';?>
                            <?php echo xlt('Room') . ': ' . $roomName . '<br>';?>
                            <label for="bedName" class="form-label"><?php echo xlt('Bed Name'); ?>:</label>
                            <input type="text" class="form-control" id="bedName" name="bed_name" required>
                        </div>

                        <!-- Dropdown para Tipo de Cama -->
                        <div class="mb-3">
                            <label for="bedType" class="form-label"><?php echo xlt('Bed Type'); ?>:</label>
                            <select class="form-select" id="bedType" name="bed_type" required>
                                <option value=""><?php echo xlt('Select Type'); ?></option>
                                <?php
                                // Ejecutar la consulta para obtener los tipos de camas
                                $queryBedType = "SELECT title, is_default FROM list_options WHERE list_id = 'Beds_Type' ORDER BY option_id";
                                $resultBedType = sqlStatement($queryBedType);

                                // Verificar y mostrar los resultados
                                if (sqlNumRows($resultBedType) > 0) {
                                    while ($row = sqlFetchArray($resultBedType)) {
                                        $selected = ($row['is_default'] == 1) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row['title']) . '" ' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Dropdown para Estado de la Cama -->
                        <div class="mb-3">
                            <label for="bedStatus" class="form-label"><?php echo xlt('Bed Status'); ?>:</label>
                            <select class="form-select" id="bedStatus" name="bed_status" required>
                                <option value=""><?php echo xlt('Select Status'); ?></option>
                                <?php
                                // Ejecutar la consulta para obtener los estados de camas
                                $queryBedStatus = "SELECT title, is_default FROM list_options WHERE list_id = 'Beds_Status' ORDER BY option_id";
                                $resultBedStatus = sqlStatement($queryBedStatus);

                                // Verificar y mostrar los resultados
                                if (sqlNumRows($resultBedStatus) > 0) {
                                    while ($row = sqlFetchArray($resultBedStatus)) {
                                        $selected = ($row['is_default'] == 1) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row['title']) . '" ' . $selected . '>' . htmlspecialchars($row['title']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="bedObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="bedObs" name="obs" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="bedActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="bedActive" name="active" value="1" checked>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <?php echo xlt("User") . ": " . $userFullName . "<br>";?>
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
    <script src="functions.js"></script>
    <script>
        // Mostrar el modal automáticamente al cargar la página
        $(document).ready(function(){
            $('#addBedModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#addBedModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_beds.php?room_id=<?php echo $roomId; ?>&room_name=<?php echo $roomName; ?>&unit_id=<?php echo $unitId; ?>&unit_name=<?php echo $unitName; ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
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
