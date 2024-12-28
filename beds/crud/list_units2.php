<?php
include('connection.php');
include('functions.php');

require_once("../../../interface/globals.php");
require_once("$srcdir/encounter.inc");
require_once($GLOBALS['fileroot'] . "/library/forms.inc.php");

// Verificar y obtener el ID y nombre del Centro
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '0';

$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Obtener las unidades
$unitsQuery = $showInactive ? "SELECT * FROM units WHERE facility_id = ? AND operation <> 'Delete'" : "SELECT * FROM units WHERE facility_id = ? AND active = 1 AND operation <> 'Delete'";
$stmt = $conn->prepare($unitsQuery);
$stmt->bind_param("i", $centroId);
$stmt->execute();
$unitsResult = $stmt->get_result();

// Verificar si la unidad es inactiva y redirigir con advertencia
if (isset($_GET['unit_id'])) {
    $unitId = intval($_GET['unit_id']);
    $checkUnitQuery = "SELECT active FROM units WHERE id = ?";
    $stmt = $conn->prepare($checkUnitQuery);
    $stmt->bind_param("i", $unitId);
    $stmt->execute();
    $result = $stmt->get_result();
    $unit = $result->fetch_assoc();
echo $unitId;
    if ($unit && $unit['active'] == 0) {
        // Redirigir con un mensaje de advertencia
        $warningMessage = "Unidad Inactiva, por favor activela.";
        $redirectUrl = "list_units.php?centro_id=$centroId&centro_name=" . urlencode($centroName) . "&showWarning=true&warningMessage=" . urlencode($warningMessage);
        header("Location: $redirectUrl");
        exit();
    }
}

// Obtener el mensaje de advertencia de la URL, si existe
if (isset($_GET['warningMessage'])) {
    $warningMessage = urldecode($_GET['warningMessage']);
    $showWarning = isset($_GET['showWarning']) ? $_GET['showWarning'] === 'true' : false;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Unidades</title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="functions.js"></script>
</head>
<body>
    <div class="container mt-4">
        <!-- Mostrar el mensaje de advertencia en una ventana emergente -->
        <?php if ($showWarning && $warningMessage): ?>
            <div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="warningModalLabel">Advertencia</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php echo htmlspecialchars($warningMessage); ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                // Mostrar el modal cuando la página se carga
                document.addEventListener('DOMContentLoaded', function() {
                    var warningModal = new bootstrap.Modal(document.getElementById('warningModal'));
                    warningModal.show();
                });
            </script>
        <?php endif; ?>

        <?php if ($centroName): ?>
            <h1>Unidades para en Centro <?php echo $centroName; ?></h1>
        <?php else: ?>
            <h1>No se ha seleccionado un Centro</h1>
        <?php endif; ?>

        <form method="get" class="mb-3">
            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>"> <!-- Mantener el nombre del centro -->
            <label>
                Mostrar Unidades Inactivas
                <label class="custom-checkbox">
                    <input type="checkbox" name="show_inactive" value="0" onchange="this.form.submit()" <?php echo $showInactive ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </label>
        </form>

        <div class="unit-container mt-4">
            <?php if ($unitsResult && $unitsResult->num_rows > 0): ?>
                <?php while ($unit = $unitsResult->fetch_assoc()): ?>
                    <div class="unit d-flex align-items-center mb-3">
                        <!-- Icono de la unidad -->
                        <div class="unit-icon me-3">
                            <a href="list_rooms.php?unit_id=<?php echo htmlspecialchars($unit['id']); ?>&unit_name=<?php echo htmlspecialchars($unit['unit_name']); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>">
                                <img src="images/<?php echo $unit['active'] == 1 ? 'unit_active_icon' : 'unit_inactive_icon'; ?>.svg" alt="Unit Icon">
                            </a>
                        </div>
                        <!-- Nombre de la unidad y estado -->
                        <div class="flex-grow-1">
                            <div class="unit-name"><?php echo htmlspecialchars($unit['unit_name']); ?></div>
                            <?php if ($unit['active'] == 0): ?>
                                <div class="unit-inactive">(Inactivo)</div>
                            <?php endif; ?>
                        </div>
                        <!-- Botones de acción -->
                        <div class="btn-group ms-auto">
                            <a href="edit_unit.php?unit_id=<?php echo htmlspecialchars($unit['id']); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-success btn-sm me-2">Editar</a>
                            <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#viewUnitModal<?php echo htmlspecialchars($unit['id']); ?>">
                                Información
                            </button>
                            <button type="button" class="btn btn-danger btn-sm me-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteUnitModal<?php echo htmlspecialchars($unit['id']); ?>"
                                onclick="confirmDelete('<?php echo htmlspecialchars($unit['id']); ?>', '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                Eliminar
                            </button>
                        </div>

                        <!-- Modal para Ver Unidad -->
                        <div class="modal fade" id="viewUnitModal<?php echo htmlspecialchars($unit['id']); ?>" tabindex="-1" aria-labelledby="viewUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>">
                                            <?php echo htmlspecialchars($centroName); ?> - <?php echo htmlspecialchars($unit['unit_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Centro:</strong> <?php echo htmlspecialchars($centroName); ?></p>
                                        <p><strong>Unidad:</strong> <?php echo htmlspecialchars($unit['unit_name']); ?></p>
                                        <p><strong>Cantidad de Cuartos:</strong> <?php echo intval($unit['number_of_rooms']); ?></p>
                                        <p><strong>Estado:</strong> <?php echo $unit['active'] ? 'Activa' : 'Inactiva'; ?></p>
                                        <p><strong>Última Modificación:</strong> <?php echo htmlspecialchars($unit['datetime_modif']); ?></p>
                                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($unit['user_modif']); ?></p>
                                        <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($unit['obs']); ?></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal para Confirmar Eliminación -->
                        <div class="modal fade" id="deleteUnitModal<?php echo htmlspecialchars($unit['id']); ?>" tabindex="-1" aria-labelledby="deleteUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>">Confirmar Eliminación</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>¿Está seguro que desea eliminar la unidad "<?php echo htmlspecialchars($unit['unit_name']); ?>"?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_unit.php" method="post">
                                            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unit['id']); ?>">
                                            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                                            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-danger">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                <p>No existe Unidades. <a href="add_unit.php?centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-outline-primary">Presione el botón agregar</a></p>
            <?php endif; ?>
        </div>

        <!-- Botones al final -->
        <div class="d-flex justify-content-between mt-4">
            <a href="facilities.php" class="btn btn-secondary">Volver</a>
            <a href="add_unit.php?centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-primary">Agregar Unidad</a>
        </div>
        </div>
    </div>
</body>
</html>
