<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener y validar parámetros GET
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$roomName = isset($_GET['room_name']) ? htmlspecialchars($_GET['room_name']) : '';
$bedId = isset($_GET['bed_id']) ? intval($_GET['bed_id']) : 0;  // ID de la cama que se está gestionando

$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '1';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);


// Verificar si la cama está inactiva
$bedQuery = "SELECT active FROM beds WHERE id = ?";
$bed = sqlQuery($bedQuery, array($bedId));

if ($bed && $bed['active'] == 0) {
    $warningMessage = "La cama está inactiva. Por favor, actívela para gestionarla.";
    header("Location: list_beds.php?room_id=$roomId&room_name=$roomName&unit_id=$unitId&unit_name=$unitName&centro_id=$centroId&centro_name=$centroName&warningMessage=" . urlencode($warningMessage) . "&showWarning=true");
    exit;
}

// Obtener las camas asociadas al cuarto
$bedsQuery = $showInactive ? 
    "SELECT * FROM beds WHERE room_id = ? AND operation <> 'Delete'" : 
    "SELECT * FROM beds WHERE room_id = ? AND active = 1 AND operation <> 'Delete'";

$bedsResult = sqlStatement($bedsQuery, array($roomId));

// Obtener el mensaje de advertencia si existe
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';
$showWarning = isset($_GET['showWarning']) ? $_GET['showWarning'] === 'true' : false;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('List of Beds'); ?></title>
    <!-- Enlaces a CSS de Bootstrap y estilos personalizados -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../styles.css">

</head>
<body>
    <div class="container mt-4">
        <!-- Mostrar mensaje de advertencia si existe -->
        <?php if ($showWarning && $warningMessage): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($warningMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Título de la página -->
        <h1 class="mb-4"><?php echo xlt('Beds of Room') . ': ' .  htmlspecialchars($roomName); ?></h1>

        <!-- Filtro para mostrar camas inactivas -->
        <form method="get" class="mb-3">
            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
            <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
            <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($roomId); ?>">
            <input type="hidden" name="room_name" value="<?php echo htmlspecialchars($roomName); ?>">
            <label>
            <?php echo xlt('Show inactive beds'); ?>
                <div class="custom-checkbox">
                    <input class="slider" type="checkbox" id="showInactive" name="show_inactive" value="1" <?php echo $showInactive ? 'checked' : ''; ?> onchange="this.form.submit();">
                    <label class="slider" for="showInactive"></label>
                </div>
            </label>  
            </form>

        <!-- Lista de camas -->
        <div class="list-group">
            <?php if ($bedsResult && sqlNumRows($bedsResult) > 0): ?>
                <?php while ($bed = sqlFetchArray($bedsResult)): ?>
                    <div class="list-group-item bed-row d-flex align-items-center">
                        <!-- Icono de la cama a la izquierda -->
                        <div class="me-3 bed-icon" data-bs-toggle="modal" data-bs-target="#viewBedModal<?php echo $bed['id']; ?>">
                            <img src="<?php echo $bed['active'] ? '../images/bed_active_icon.svg' : '../images/bed_inactive_icon.svg'; ?>" alt="Bed Icon" width="50" height="50">
                        </div>

                        <!-- Detalles de la cama -->
                        <div class="flex-grow-1">
                            <h5 class="mb-1"><?php echo htmlspecialchars($bed['bed_name']); ?></h5>
                            <?php if (!$bed['active']): ?>
                                <span class="badge bg-danger"><?php echo xlt('(Disabled)'); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Botones de acción a la derecha -->
                        <div class="btn-group">
                            <!-- Botón Editar -->
                            <a href="edit_bed.php?bed_id=<?php echo $bed['id']; ?>&bed_name=<?php echo urlencode($bed['bed_name']); ?>&room_id=<?php echo $roomId; ?>&room_name=<?php echo urlencode($roomName); ?>&unit_id=<?php echo $unitId; ?>&unit_name=<?php echo urlencode($unitName); ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo urlencode($centroName); ?>" class="btn btn-success btn-sm me-2"><?php echo xl('Edit'); ?></a>

                            <!-- Botón Eliminar -->
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteBedModal<?php echo $bed['id']; ?>"><?php echo xlt('Remove'); ?></button>
                        </div>
                    </div>

                    <!-- Modal Ver Información de la Cama -->
                    <div class="modal fade" id="viewBedModal<?php echo $bed['id']; ?>" tabindex="-1" aria-labelledby="viewBedModalLabel<?php echo $bed['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewBedModalLabel<?php echo $bed['id']; ?>"><?php echo xlt('Bed Information'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo xlt('Close'); ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong><?php echo xlt('Facility'); ?>:</strong> <?php echo htmlspecialchars($centroName); ?></p>
                                    <p><strong><?php echo xlt('Unit'); ?>:</strong> <?php echo htmlspecialchars($unitName); ?></p>
                                    <p><strong><?php echo xlt('Room'); ?>:</strong> <?php echo htmlspecialchars($roomName); ?></p>
                                    <p><strong><?php echo xlt('Bed'); ?>:</strong> <?php echo htmlspecialchars($bed['bed_name']); ?></p>
                                    <p><strong><?php echo xlt('Type'); ?>:</strong> <?php echo htmlspecialchars($bed['bed_type']); ?></p>
                                    <p><strong><?php echo xlt('Status'); ?>:</strong> <?php echo htmlspecialchars($bed['bed_status']); ?></p>
                                    <p><strong><?php echo xlt('Notes'); ?>:</strong> <?php echo htmlspecialchars($bed['obs']); ?></p>
                                    <p><strong><?php echo xlt('Situation'); ?>:</strong> <?php echo $bed['active'] ? 'Activa' : 'Inactiva'; ?></p>
                                    <p><strong><?php echo xlt('Last Modified'); ?>:</strong> <?php echo htmlspecialchars($bed['datetime_modif']); ?></p>
                                    <p><strong><?php echo xlt('Modified Type'); ?>:</strong> <?php echo htmlspecialchars($bed['operation']); ?></p>
                                    <p><strong><?php echo xlt('Modified by'); ?>:</strong> <?php echo htmlspecialchars($bed['user_modif']); ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Confirmar Eliminación de la Cama -->
                    <div class="modal fade" id="deleteBedModal<?php echo $bed['id']; ?>" tabindex="-1" aria-labelledby="deleteBedModalLabel<?php echo $bed['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteBedModalLabel<?php echo $bed['id']; ?>"><?php echo xlt('Confirm Delete'); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo xlt('Close'); ?>"></button>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo xlt('Are you sure you want to delete Bed') . ' '; ?> <strong><?php echo htmlspecialchars($bed['bed_name']); ?></strong>?</p>
                                </div>
                                <div class="modal-footer">
                                    <form action="delete_bed.php" method="POST">
                                        <input type="hidden" name="bed_id" value="<?php echo $bed['id']; ?>">
                                        <input type="hidden" name="bed_name" value="<?php echo htmlspecialchars($bed['bed_name']); ?>">
                                        <input type="hidden" name="room_id" value="<?php echo $roomId; ?>">
                                        <input type="hidden" name="room_name" value="<?php echo htmlspecialchars($roomName); ?>">
                                        <input type="hidden" name="unit_id" value="<?php echo $unitId; ?>">
                                        <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
                                        <input type="hidden" name="centro_id" value="<?php echo $centroId; ?>">
                                        <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Cancel'); ?></button>
                                        <button type="submit" class="btn btn-danger"><?php echo xlt('Remove'); ?></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info" role="alert">
                    <?php echo xlt('There are no Beds in this Room'); ?>.
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botones de navegación -->
        <div class="d-flex justify-content-between mt-4">
            <a href="list_rooms.php?unit_id=<?php echo $unitId; ?>&unit_name=<?php echo urlencode($unitName); ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo urlencode($centroName); ?>" class="btn btn-secondary"><?php echo xlt('Back to Rooms'); ?></a>
            <a href="add_bed.php?room_id=<?php echo $roomId; ?>&room_name=<?php echo urlencode($roomName); ?>&unit_id=<?php echo $unitId; ?>&unit_name=<?php echo urlencode($unitName); ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo urlencode($centroName); ?>" class="btn btn-primary"><?php echo xlt('Add New Bed'); ?></a>
        </div>
    </div>

    <!-- Enlaces a JavaScript de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
