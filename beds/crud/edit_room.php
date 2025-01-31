<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$roomName = isset($_GET['room_name']) ? htmlspecialchars($_GET['room_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';

if ($roomId == 0) {
    echo "Cuarto no válido.";
    exit();
}

$query = "SELECT * FROM rooms WHERE id = ?";
$result = sqlStatement($query, [$roomId]);

if (sqlNumRows($result) > 0) {
    $room = sqlFetchArray($result);
} else {
    echo "Cuarto no encontrado.";
    exit();
}

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

// Obtener datos dinámicos para los dropdowns
$sectors = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_sector'");
$roomTypes = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_type'");
$isolationLevels = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'isolation_level'");
$roomStatuses = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_status'");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Edit Room'); ?></title>
    <link rel="stylesheet" href="../../styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- Material Icons -->
</head>
<body>
    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
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

                        <!-- Room Name and Number of Beds -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="roomName" class="form-label"><?php echo xlt('Room Name'); ?>:</label>
                                <input type="text" class="form-control" id="roomName" name="room_name" value="<?php echo htmlspecialchars($room['room_name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="roomBeds" class="form-label"><?php echo xlt('Number of Beds'); ?>:</label>
                                <input type="number" class="form-control" id="roomBeds" name="number_of_beds" value="<?php echo htmlspecialchars($room['number_of_beds']); ?>" required>
                            </div>
                        </div>

                        <!-- Sector and Room Type -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sector" class="form-label"><?php echo xlt('Sector'); ?>:</label>
                                <select class="form-select" id="sector" name="sector" required>
                                    <?php while ($sector = sqlFetchArray($sectors)): ?>
                                        <option value="<?php echo attr($sector['option_id']); ?>" <?php echo ($room['sector'] == $sector['option_id']) ? 'selected' : ''; ?>>
                                            <?php echo text($sector['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="roomType" class="form-label"><?php echo xlt('Room Type'); ?>:</label>
                                <select class="form-select" id="roomType" name="room_type" required>
                                    <?php while ($roomType = sqlFetchArray($roomTypes)): ?>
                                        <option value="<?php echo attr($roomType['option_id']); ?>" <?php echo ($room['room_type'] == $roomType['option_id']) ? 'selected' : ''; ?>>
                                            <?php echo text($roomType['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
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
                                                    <input class="form-check-input" type="checkbox" id="oxigen" name="oxigen" value="1" <?php echo ($room['oxigen'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="oxigen">
                                                        <i class="material-icons icon-oxygen">air</i> <?php echo xlt('Oxygen Connections'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="suction" name="suction" value="1" <?php echo ($room['suction'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="suction">
                                                        <i class="material-icons icon-plumbing">plumbing</i> <?php echo xlt('Suction System'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="cardiac_monitor" name="cardiac_monitor" value="1" <?php echo ($room['cardiac_monitor'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="cardiac_monitor">
                                                        <i class="material-icons icon-monitor">monitor_heart</i> <?php echo xlt('Cardiac Monitor'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="ventilator" name="ventilator" value="1" <?php echo ($room['ventilator'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="ventilator">
                                                        <i class="material-icons icon-fan">heat_pump</i> <?php echo xlt('Ventilator'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="infusion_pump" name="infusion_pump" value="1" <?php echo ($room['infusion_pump'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="infusion_pump">
                                                        <i class="material-icons icon-medication">medication</i> <?php echo xlt('Infusion Pumps'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="defibrillator" name="defibrillator" value="1" <?php echo ($room['defibrillator'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="defibrillator">
                                                        <i class="material-icons icon-flash">flash_on</i> <?php echo xlt('Defibrillator'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="physiotherapy" name="physiotherapy" value="1" <?php echo ($room['physiotherapy'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="physiotherapy">
                                                        <i class="material-icons icon-fitness">fitness_center</i> <?php echo xlt('Physiotherapy Equipment'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="heater" name="heater" value="1" <?php echo ($room['heater'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="heater">
                                                        <i class="material-icons icon-crib">crib</i> <?php echo xlt('Crib Heater'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="air_purifier" name="air_purifier" value="1" <?php echo ($room['air_purifier'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="air_purifier">
                                                        <i class="material-icons icon-sync">sync_alt</i> <?php echo xlt('Air Purifier'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold"><?php echo xlt('Amenities'); ?>:</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="wifi" name="wifi" value="1" <?php echo ($room['wifi'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="wifi">
                                                        <i class="material-icons icon-wifi">wifi</i> <?php echo xlt('WiFi'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="television" name="television" value="1" <?php echo ($room['television'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="television">
                                                        <i class="material-icons icon-tv">tv</i> <?php echo xlt('Television'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="entertainment_system" name="entertainment_system" value="1" <?php echo ($room['entertainment_system'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="entertainment_system">
                                                        <i class="material-icons icon-play">play_circle</i> <?php echo xlt('Entertainment System'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="personalized_menu" name="personalized_menu" value="1" <?php echo ($room['personalized_menu'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="personalized_menu">
                                                        <i class="material-icons icon-menu">restaurant_menu</i> <?php echo xlt('Personalized Menu'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="companion_space" name="companion_space" value="1" <?php echo ($room['companion_space'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="companion_space">
                                                        <i class="material-icons icon-chair">chair</i> <?php echo xlt('Companion Space'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="private_bathroom" name="private_bathroom" value="1" <?php echo ($room['private_bathroom'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="private_bathroom">
                                                        <i class="material-icons icon-bathroom">bathroom</i> <?php echo xlt('Private Bathroom'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="friendly_decor" name="friendly_decor" value="1" <?php echo ($room['friendly_decor'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="friendly_decor">
                                                        <i class="material-icons icon-smile">sentiment_very_satisfied</i> <?php echo xlt('Friendly Decor'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="light_mode" name="light_mode" value="1" <?php echo ($room['light_mode'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="light_mode">
                                                        <i class="material-icons icon-light">light_mode</i> <?php echo xlt('Light Mode'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="thermostat" name="thermostat" value="1" <?php echo ($room['thermostat'] == 1) ? 'checked' : ''; ?>>
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

                        <!-- Isolation Level and Room Status -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="isolationLevel" class="form-label"><?php echo xlt('Isolation Level'); ?>:</label>
                                <select class="form-select" id="isolationLevel" name="isolation_level" required>
                                    <?php while ($isolationLevel = sqlFetchArray($isolationLevels)): ?>
                                        <option value="<?php echo attr($isolationLevel['option_id']); ?>" <?php echo ($room['isolation_level'] == $isolationLevel['option_id']) ? 'selected' : ''; ?>>
                                            <?php echo text($isolationLevel['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="roomStatus" class="form-label"><?php echo xlt('Room Status'); ?>:</label>
                                <select class="form-select" id="roomStatus" name="status" required>
                                    <?php while ($roomStatus = sqlFetchArray($roomStatuses)): ?>
                                        <option value="<?php echo attr($roomStatus['option_id']); ?>" <?php echo ($room['status'] == $roomStatus['option_id']) ? 'selected' : ''; ?>>
                                            <?php echo text($roomStatus['title']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="roomObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="roomObs" name="obs" rows="2"><?php echo htmlspecialchars($room['obs']); ?></textarea>
                        </div>

                        <!-- Active/Inactive -->
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