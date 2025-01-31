<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

// Verificar y obtener el ID y nombre de las Unidades y Centros
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';

$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '0';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

// Verificar si el centro está inactivo utilizando sqlStatement()
$cuartoQuery = "SELECT active FROM rooms WHERE id = ?";
$cuartoResult = sqlStatement($cuartoQuery, [$roomId]);
$cuarto = sqlFetchArray($cuartoResult);

if ($cuarto && $cuarto['active'] == 0) {
    $warningMessage = xlt("Inactive Room");
    header("Location: list_rooms.php?unit_id=" . urlencode($unitId) . "&unit_name=" . urlencode($unitName) . "&warningMessage=" . urlencode($warningMessage) . "&showWarning=true");
    exit;
}

// Obtener las unidades utilizando sqlStatement()
$roomsQuery = $showInactive ? 
    "SELECT * FROM rooms WHERE unit_id = ? AND operation <> 'Delete'" : 
    "SELECT * FROM rooms WHERE unit_id = ? AND active = 1 AND operation <> 'Delete'";

$roomsResult = sqlStatement($roomsQuery, [$unitId]);

// Obtener el mensaje de advertencia de la URL, si existe
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';
$showWarning = isset($_GET['showWarning']) ? $_GET['showWarning'] === 'true' : false;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Cuartos</title>
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
    <script src="functions.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- Material Icons -->
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
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
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

        <?php if ($unitName): ?>
            <h1><?php echo xlt('Rooms for Unity') . ' ' . $unitName; ?></h1>
        <?php else: ?>
            <h1><?php echo xlt('A Room has not been selected'); ?></h1>
        <?php endif; ?>

        <form method="get" class="mb-3">
            <input type="hidden" name="centro_id" value="<?php echo htmlspecialchars($centroId); ?>">
            <input type="hidden" name="centro_name" value="<?php echo htmlspecialchars($centroName); ?>">
            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
            <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>"> <!-- Mantener el nombre de la unidad -->
            <label>
                <?php echo xlt('Show Inactive Rooms'); ?>
                <label class="custom-checkbox">
                    <input type="checkbox" name="show_inactive" value="0" onchange="this.form.submit()" <?php echo $showInactive ? 'checked' : ''; ?>>
                    <span class="slider"></span>
                </label>
            </label>
        </form>

        <div class="room-container mt-4">
            <?php if ($roomsResult && sqlNumRows($roomsResult) > 0): ?>
                <?php while ($room = sqlFetchArray($roomsResult)): ?>
                    <div class="room d-flex align-items-center mb-3">
                        <!-- Icono del Cuarto -->
                        <div class="room-icon me-3">
                            <?php if ($room['active'] == 1): ?>
                                <a href="list_beds.php?room_id=<?php echo htmlspecialchars($room['id']); ?>&room_name=<?php echo htmlspecialchars($room['room_name']); ?>&unit_id=<?php echo htmlspecialchars($unitId); ?>&unit_name=<?php echo htmlspecialchars($unitName); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>">
                                    <img src="../images/room_active_icon.svg" alt="Room Icon">
                                </a>
                            <?php else: ?>
                                <a href="#" onclick="showInactiveWarning('<?php echo htmlspecialchars($room['room_name']); ?>')">
                                    <img src="../images/room_inactive_icon.svg" alt="Room Icon">
                                </a>
                            <?php endif; ?>
                        </div>
                        <!-- Nombre del cuarto y estado -->
                        <div class="flex-grow-1">
                            <div class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></div>
                            <?php if ($room['active'] == 0): ?>
                                <div class="badge bg-danger"><?php echo xlt('(Disabled)'); ?></div>
                            <?php endif; ?>
                        </div>
                        <!-- Botones de acción -->
                        <div class="btn-group ms-auto">
                            <a href="edit_room.php?room_id=<?php echo htmlspecialchars($room['id']); ?>$room_name=<?php echo htmlspecialchars($room['room_name']); ?>&unit_id=<?php echo htmlspecialchars($unitId); ?>&unit_name=<?php echo htmlspecialchars($unitName); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-success btn-sm me-2"><?php echo xl('Edit'); ?></a>
                            <button type="button" class="btn btn-info btn-sm me-2" data-bs-toggle="modal" data-bs-target="#viewRoomModal<?php echo htmlspecialchars($room['id']); ?>">
                            <?php echo xlt('Information'); ?>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm me-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteRoomModal<?php echo htmlspecialchars($room['id']); ?>"
                                onclick="confirmDelete('<?php echo htmlspecialchars($unit['id']); ?>', '<?php echo htmlspecialchars($unit['room_name']); ?>')">
                                <?php echo xlt('Remove'); ?>
                            </button>
                        </div>

                        <!-- Modal para Ver Unidad -->
                        <div class="modal fade" id="viewRoomModal<?php echo htmlspecialchars($room['id']); ?>" tabindex="-1" aria-labelledby="viewRoomModalLabel<?php echo htmlspecialchars($room['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewRoomModalLabel<?php echo htmlspecialchars($room['id']); ?>">
                                            <?php echo htmlspecialchars($unitName); ?> - <?php echo htmlspecialchars($room['room_name']); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Primera fila: Labels -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Facility'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Unit'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Sector'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Room'); ?></div>
                                            </div>
                                        </div>

                                        <!-- Segunda fila: Valores -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($centroName); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($unitName); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['sector']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['room_name']); ?></strong></div>
                                            </div>
                                        </div>

                                        <!-- Tercera fila: Labels -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Number of Beds'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Room Type'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Isolation Level'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Room Status'); ?></div>
                                            </div>
                                        </div>

                                        <!-- Cuarta fila: Valores -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo intval($room['number_of_beds']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['room_type']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['isolation_level']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['status']); ?></strong></div>
                                            </div>
                                        </div>

                                        <!-- Medical Equipment and Amenities (centered container) -->
                                        <div class="row justify-content-center mb-3">
                                            <div class="col-md-10">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <!-- Medical Equipment -->
                                                        <div class="row mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold"><?php echo xlt('Medical Equipment'); ?>:</label>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="oxigen" name="oxigen" value="1" <?php echo ($room['oxigen'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="oxigen">
                                                                        <i class="material-icons icon-oxygen">air</i> <?php echo xlt('Oxygen Connections'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="suction" name="suction" value="1" <?php echo ($room['suction'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="suction">
                                                                        <i class="material-icons icon-plumbing">plumbing</i> <?php echo xlt('Suction System'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="cardiac_monitor" name="cardiac_monitor" value="1" <?php echo ($room['cardiac_monitor'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="cardiac_monitor">
                                                                        <i class="material-icons icon-monitor">monitor_heart</i> <?php echo xlt('Cardiac Monitor'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="ventilator" name="ventilator" value="1" <?php echo ($room['ventilator'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="ventilator">
                                                                        <i class="material-icons icon-fan">heat_pump</i> <?php echo xlt('Ventilator'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="infusion_pump" name="infusion_pump" value="1" <?php echo ($room['infusion_pump'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="infusion_pump">
                                                                        <i class="material-icons icon-medication">medication</i> <?php echo xlt('Infusion Pumps'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="defibrillator" name="defibrillator" value="1" <?php echo ($room['defibrillator'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="defibrillator">
                                                                        <i class="material-icons icon-flash">flash_on</i> <?php echo xlt('Defibrillator'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="physiotherapy" name="physiotherapy" value="1" <?php echo ($room['physiotherapy'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="physiotherapy">
                                                                        <i class="material-icons icon-fitness">fitness_center</i> <?php echo xlt('Physiotherapy Equipment'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="heater" name="heater" value="1" <?php echo ($room['heater'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="heater">
                                                                        <i class="material-icons icon-crib">crib</i> <?php echo xlt('Crib Heater'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="air_purifier" name="air_purifier" value="1" <?php echo ($room['air_purifier'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="air_purifier">
                                                                        <i class="material-icons icon-sync">sync_alt</i> <?php echo xlt('Air Purifier'); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold"><?php echo xlt('Amenities'); ?>:</label>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="wifi" name="wifi" value="1" <?php echo ($room['wifi'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="wifi">
                                                                        <i class="material-icons icon-wifi">wifi</i> <?php echo xlt('WiFi'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="television" name="television" value="1" <?php echo ($room['television'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="television">
                                                                        <i class="material-icons icon-tv">tv</i> <?php echo xlt('Television'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="entertainment_system" name="entertainment_system" value="1" <?php echo ($room['entertainment_system'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="entertainment_system">
                                                                        <i class="material-icons icon-play">play_circle</i> <?php echo xlt('Entertainment System'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="personalized_menu" name="personalized_menu" value="1" <?php echo ($room['personalized_menu'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="personalized_menu">
                                                                        <i class="material-icons icon-menu">restaurant_menu</i> <?php echo xlt('Personalized Menu'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="companion_space" name="companion_space" value="1" <?php echo ($room['companion_space'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="companion_space">
                                                                        <i class="material-icons icon-chair">chair</i> <?php echo xlt('Companion Space'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="private_bathroom" name="private_bathroom" value="1" <?php echo ($room['private_bathroom'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="private_bathroom">
                                                                        <i class="material-icons icon-bathroom">bathroom</i> <?php echo xlt('Private Bathroom'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="friendly_decor" name="friendly_decor" value="1" <?php echo ($room['friendly_decor'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="friendly_decor">
                                                                        <i class="material-icons icon-smile">sentiment_very_satisfied</i> <?php echo xlt('Friendly Decor'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="light_mode" name="light_mode" value="1" <?php echo ($room['light_mode'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="light_mode">
                                                                        <i class="material-icons icon-light">light_mode</i> <?php echo xlt('Light Mode'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" id="thermostat" name="thermostat" value="1" <?php echo ($room['thermostat'] == 1) ? 'checked' : ''; ?> disabled>
                                                                    <label class="form-check-label" for="thermostat">
                                                                        <i class="material-icons icon-thermostat">thermostat</i> <?php echo xlt('Thermostat'); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <p><?php echo xlt('Notes') . ': '; ?><strong><?php echo htmlspecialchars($room['obs']); ?></strong></p>
                                        <!-- Quinta fila: Labels -->
                                        <div class="row mb-2">
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Situation'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Last Modified'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Type of Modification'); ?></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-label"><?php echo xlt('Modified by'); ?></div>
                                            </div>
                                        </div>

                                        <!-- Sexta fila: Valores -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo $room['active'] ? xlt("Active") : xlt("Inactive"); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['datetime_modif']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['operation']); ?></strong></div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="info-value"><strong><?php echo htmlspecialchars($room['user_modif']); ?></strong></div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de Confirmación de Eliminación -->
                        <div class="modal fade" id="deleteRoomModal<?php echo htmlspecialchars($room['id']); ?>" tabindex="-1" aria-labelledby="deleteRoomModalLabel<?php echo htmlspecialchars($room['id']); ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteRoomModalLabel<?php echo htmlspecialchars($room['id']); ?>">
                                        <?php echo xlt('Confirm Delete'); ?>
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><?php echo xlt('Are you sure you want to delete Room') . ' '; ?><strong><?php echo htmlspecialchars($room['room_name']); ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <form action="delete_room.php" method="POST">
                                            <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room['id']); ?>">
                                            <input type="hidden" name="room_name" value="<?php echo htmlspecialchars($unit['room_name']); ?>">
                                            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($unitId); ?>">
                                            <input type="hidden" name="unit_name" value="<?php echo htmlspecialchars($unitName); ?>">
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
                <p><?php echo xlt('There are no Rooms'); ?>. <a href="add_room.php?unit_id=<?php echo htmlspecialchars($unitId); ?>&unit_name=<?php echo htmlspecialchars($unitName); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-outline-primary"><?php echo xlt('Press the Add Button'); ?></a></p>
            <?php endif; ?>
        </div>

        <!-- Botón para agregar un nuevo cuarto -->
        <div class="d-flex justify-content-between mt-4">
            <a href="list_units.php?centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-secondary"><?php echo xlt('Back to Unities'); ?></a>
            <a href="add_room.php?unit_id=<?php echo htmlspecialchars($unitId); ?>&unit_name=<?php echo htmlspecialchars($unitName); ?>&centro_id=<?php echo htmlspecialchars($centroId); ?>&centro_name=<?php echo htmlspecialchars($centroName); ?>" class="btn btn-primary"><?php echo xlt('Add Room'); ?></a>
        </div>
    </div>
    <script>
    function showInactiveWarning(roomName) {
        // Crear y mostrar el modal con el mensaje de advertencia
        var warningModalHtml = `
            <div class="modal fade" id="inactiveRoomWarningModal" tabindex="-1" aria-labelledby="inactiveRoomWarningModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="inactiveRoomWarningModalLabel">Advertencia</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php echo xlt('Room'); ?> <strong>` + roomName + `</strong><?php echo ' ' . xlt('is inactive, please activate it.'); ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt('Close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Añadir el modal al cuerpo del documento
        document.body.insertAdjacentHTML('beforeend', warningModalHtml);

        // Mostrar el modal
        var inactiveRoomWarningModal = new bootstrap.Modal(document.getElementById('inactiveRoomWarningModal'));
        inactiveRoomWarningModal.show();

        // Eliminar el modal del DOM después de cerrarlo
        inactiveRoomWarningModal._element.addEventListener('hidden.bs.modal', function () {
            document.getElementById('inactiveRoomWarningModal').remove();
        });
    }
    </script>

</body>
</html>
