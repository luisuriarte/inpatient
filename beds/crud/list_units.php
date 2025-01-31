<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar y obtener el ID y nombre del Centro
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '0';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

// Obtener las unidades utilizando sqlStatement()
$unitsQuery = $showInactive ? "SELECT * FROM units WHERE facility_id = ? AND operation <> 'Delete'" : 
                              "SELECT * FROM units WHERE facility_id = ? AND active = 1 AND operation <> 'Delete'";
$unitsResult = sqlStatement($unitsQuery, [$centroId]);

// Verificar si la unidad es inactiva y redirigir con advertencia
if (isset($_GET['unit_id'])) {
    $unitId = intval($_GET['unit_id']);
    $checkUnitQuery = "SELECT active FROM units WHERE id = ?";
    $result = sqlStatement($checkUnitQuery, [$unitId]);
    $unit = sqlFetchArray($result);

    if ($unit && $unit['active'] == 0) {
        // Redirigir con un mensaje de advertencia
        $warningMessage = "Unidad Inactiva, por favor actívela.";
        $redirectUrl = "list_units.php?centro_id=" . urlencode($centroId) . "&centro_name=" . urlencode($centroName) . "&showWarning=true&warningMessage=" . urlencode($warningMessage);
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
    <title><?php echo xlt('List of Units'); ?></title>
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
                            <h5 class="modal-title" id="warningModalLabel"><?php echo xlt('Warning'); ?></h5>
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
            <h1><?php echo xlt('Units for the center') . ': ' .  htmlspecialchars($centroName); ?></h1>
        <?php else: ?>
            <h1><?php echo xlt('No center selected'); ?></h1>
        <?php endif; ?>

        <form method="get" class="mb-3">
            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>"> <!-- Mantener el nombre del centro -->
            <label>
            <?php echo xlt('Show Disabled Units'); ?>
                <label class="custom-checkbox">
                    <input type="checkbox" name="show_inactive" value="0" onchange="this.form.submit()" <?php echo $showInactive ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </label>
        </form>

        <div class="unit-container mt-4">
            <?php if ($unitsResult && sqlNumRows($unitsResult) > 0): ?>
                    <?php while ($unit = sqlFetchArray($unitsResult)): ?>
                        <div class="unit d-flex align-items-center mb-3">
                            <!-- Icono de la unidad -->
                            <div class="unit-icon me-3">
                                <?php if ($unit['active'] == 1): ?>
                                    <a href="list_rooms.php?unit_id=<?php echo htmlspecialchars($unit['id']); ?>&unit_name=<?php echo htmlspecialchars($unit['unit_name']); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>">
                                        <img src="../images/unit_active_icon.svg" alt="Unit Icon">
                                    </a>
                                <?php else: ?>
                                    <a href="#" onclick="showInactiveWarning('<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                        <img src="../images/unit_inactive_icon.svg" alt="Unit Icon">
                                    </a>
                                <?php endif; ?>
                            </div>
                            <!-- Nombre de la unidad y estado -->
                            <div class="flex-grow-1">
                                <div class="unit-name"><?php echo htmlspecialchars($unit['unit_name']); ?></div>
                                <?php if ($unit['active'] == 0): ?>
                                    <div class="badge bg-danger"><?php echo xlt('(Disabled)'); ?></div>
                                <?php endif; ?>
                            </div>
                            <!-- Botones de acción -->
                            <div class="btn-group ms-auto">
                                <a href="edit_unit.php?unit_id=<?php echo htmlspecialchars($unit['id']); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-success btn-sm me-2"><?php echo xl('Edit'); ?></a>
                                <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#viewUnitModal<?php echo htmlspecialchars($unit['id']); ?>">
                                <?php echo xlt('Information'); ?>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm me-2" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteUnitModal<?php echo htmlspecialchars($unit['id']); ?>"
                                    onclick="confirmDelete('<?php echo htmlspecialchars($unit['id']); ?>', '<?php echo htmlspecialchars($unit['unit_name']); ?>')">
                                    <?php echo xlt('Remove'); ?>
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
                                            <p><strong><?php echo xlt('Facility'); ?>:</strong> <?php echo htmlspecialchars($centroName); ?></p>
                                            <p><strong><?php echo xlt('Unit'); ?>:</strong> <?php echo htmlspecialchars($unit['unit_name']); ?></p>

                                            <!-- Campo Floor -->
                                            <p><strong><?php echo xlt('Floor'); ?>:</strong> 
                                                <?php
                                                // Obtener el nombre del piso desde list_options
                                                $floorQuery = "SELECT title FROM list_options WHERE list_id = 'unit_floor' AND option_id = ?";
                                                $floorResult = sqlStatement($floorQuery, [$unit['floor']]);
                                                $floorData = sqlFetchArray($floorResult);
                                                echo htmlspecialchars($floorData['title'] ?? 'N/A'); // Mostrar el título del piso o 'N/A' si no se encuentra
                                                ?>
                                            </p>

                                            <p><strong><?php echo xlt('Number of Rooms'); ?>:</strong> <?php echo intval($unit['number_of_rooms']); ?></p>
                                            <p><strong><?php echo xlt('Notes'); ?>:</strong> <?php echo htmlspecialchars($unit['obs']); ?></p>
                                            <p><strong><?php echo xlt('Situation'); ?>:</strong> <?php echo $unit['active'] ? xlt('Active') : xlt('Inactive'); ?></p>
                                            <p><strong><?php echo xlt('Last Modified'); ?>:</strong> <?php echo htmlspecialchars($unit['datetime_modif']); ?></p>
                                            <p><strong><?php echo xlt('User'); ?>:</strong> <?php echo htmlspecialchars($unit['user_modif']); ?></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <!-- Modal para Confirmar Eliminación -->
                        <div class="modal fade" id="deleteUnitModal<?php echo htmlspecialchars($unit['id']); ?>" tabindex="-1" aria-labelledby="deleteUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteUnitModalLabel<?php echo htmlspecialchars($unit['id']); ?>"><?php echo xlt('Confirm Deletion'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><?php echo xlt('Are you sure you want to delete the drive') . ' ' . htmlspecialchars($unit['unit_name']); ?>"?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_unit.php" method="post">
                                            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unit['id']); ?>">
                                            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
                                            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Cancel'); ?></button>
                                            <button type="submit" class="btn btn-danger"><?php echo xlt('Remove'); ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                    <div class="alert alert-info" role="alert">
                    <?php echo xlt('There are no units in the facility'); ?>.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botones al final -->
        <div class="d-flex justify-content-between mt-4">
            <a href="facilities.php" class="btn btn-secondary"><?php echo xlt('Back to Facilities'); ?></a>
            <a href="add_unit.php?centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-primary"><?php echo xlt('Add Unit'); ?></a>
        </div>
        </div>
    </div>
    <script>
    function showInactiveWarning(unitName) {
        // Crear y mostrar el modal con el mensaje de advertencia
        var warningModalHtml = `
            <div class="modal fade" id="inactiveUnitWarningModal" tabindex="-1" aria-labelledby="inactiveUnitWarningModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="inactiveUnitWarningModalLabel">Advertencia</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Unidad <strong>` + unitName + `</strong> está inactiva, por favor actívela.
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Añadir el modal al cuerpo del documento
        document.body.insertAdjacentHTML('beforeend', warningModalHtml);

        // Mostrar el modal
        var inactiveUnitWarningModal = new bootstrap.Modal(document.getElementById('inactiveUnitWarningModal'));
        inactiveUnitWarningModal.show();

        // Eliminar el modal del DOM después de cerrarlo
        inactiveUnitWarningModal._element.addEventListener('hidden.bs.modal', function () {
            document.getElementById('inactiveUnitWarningModal').remove();
        });
    }
</script>

</body>
</html>
