<?php
include('connection.php');
include('functions.php');

require_once("../../../interface/globals.php");
require_once("$srcdir/encounter.inc");
require_once($GLOBALS['fileroot'] . "/library/forms.inc.php");

$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$user = $_SESSION['authUserID'];

// Obtener el nombre completo del usuario
$queryUser = "SELECT lname, mname, fname FROM users WHERE id = ?";
$stmtUser = $conn->prepare($queryUser);
$stmtUser->bind_param("i", $user);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$userData = $userResult->fetch_assoc();
$userFullName = $userData['lname'] . ', ' . $userData['fname'] . ' ' . $userData['mname'];

$uuid = generateUUID();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Unidad</title>
    <link rel="stylesheet" href="styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="addUnitModal" tabindex="-1" aria-labelledby="addUnitModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="save_unit.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUnitModalLabel">Agregar Unidad</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($uuid); ?>">
                        <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="user_modif" value="<?php echo htmlspecialchars($userFullName); ?>">
                        <input type="hidden" name="datetime_modif" value="<?php echo date('Y-m-d H:i:s'); ?>">

                        <div class="mb-3">
                            <label for="unitName" class="form-label">Nombre de la Unidad:</label>
                            <input type="text" class="form-control" id="unitName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="unitRooms" class="form-label">Cantidad de Cuartos:</label>
                            <input type="number" class="form-control" id="unitRooms" name="number_of_rooms" required>
                        </div>
                        <div class="mb-3">
                            <label for="unitObs" class="form-label">Observaciones:</label>
                            <textarea class="form-control" id="unitObs" name="obs" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="unitActive" class="form-label">Activo:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="unitActive" name="active" value="1">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Agregar</button>
                        <button type="reset" class="btn btn-secondary">Volver a entrar</button>
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cerrar</button>
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
            $('#addUnitModal').modal('show');
        });

        // Redireccionar al cerrar el modal
        $('#addUnitModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_units.php?id=<?php echo $centroId; ?>';
        });
    </script>
</body>
</html>
