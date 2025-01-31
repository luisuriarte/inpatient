<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Verify and get the ID and name of the Units and Centers
$centroId = isset($_GET['centro_id']) ? intval($_GET['centro_id']) : 0;
$centroName = isset($_GET['centro_name']) ? htmlspecialchars($_GET['centro_name']) : '';
$unitId = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;
$unitName = isset($_GET['unit_name']) ? htmlspecialchars($_GET['unit_name']) : '';

$userId = $_SESSION['authUserID'];
$userFullName = getuserFullName($userId);

$uuid = generateUUID();

// Get the warning message from the URL, if it exists
$warningMessage = isset($_GET['warningMessage']) ? urldecode($_GET['warningMessage']) : '';

// Get dynamic data for dropdowns
$sectors = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_sector'");
$roomTypes = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_type'");
$isolationLevels = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'isolation_level'");
$roomStatuses = sqlStatement("SELECT option_id, title FROM list_options WHERE list_id = 'room_status'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Add Rooms'); ?></title>
    <link rel="stylesheet" href="../../styles.css"> <!-- External CSS file -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> <!-- Material Icons -->
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg"> <!-- Larger modal -->
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

                        <!-- Room Name and Number of Beds (in the same row) -->
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="roomName" class="form-label"><?php echo xlt('Room Name'); ?>:</label>
                                <input type="text" class="form-control" id="roomName" name="room_name" required>
                            </div>
                            <div class="col-md-4">
                                <label for="roomBeds" class="form-label"><?php echo xlt('Number of Beds'); ?>:</label>
                                <input type="number" class="form-control" id="roomBeds" name="number_of_beds" required>
                            </div>
                        </div>

                        <!-- Sector and Room Type (in the same row) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="sector" class="form-label"><?php echo xlt('Sector'); ?>:</label>
                                <select class="form-select" id="sector" name="sector" required>
                                    <?php while ($sector = sqlFetchArray($sectors)): ?>
                                        <option value="<?php echo attr($sector['option_id']); ?>"><?php echo text($sector['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="roomType" class="form-label"><?php echo xlt('Room Type'); ?>:</label>
                                <select class="form-select" id="roomType" name="room_type" required>
                                    <?php while ($roomType = sqlFetchArray($roomTypes)): ?>
                                        <option value="<?php echo attr($roomType['option_id']); ?>"><?php echo text($roomType['title']); ?></option>
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
                                                    <input class="form-check-input" type="checkbox" id="oxigen" name="oxigen" value="1">
                                                    <label class="form-check-label" for="oxigen">
                                                        <i class="material-icons icon-oxygen">air</i> <?php echo xlt('Oxygen Connections'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="suction" name="suction" value="1">
                                                    <label class="form-check-label" for="suction">
                                                        <i class="material-icons icon-plumbing">plumbing</i> <?php echo xlt('Suction System'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="cardiac_monitor" name="cardiac_monitor" value="1">
                                                    <label class="form-check-label" for="cardiac_monitor">
                                                        <i class="material-icons icon-monitor">monitor_heart</i> <?php echo xlt('Cardiac Monitor'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="ventilator" name="ventilator" value="1">
                                                    <label class="form-check-label" for="ventilator">
                                                        <i class="material-icons icon-fan">heat_pump</i> <?php echo xlt('Ventilator'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="infusion_pump" name="infusion_pump" value="1">
                                                    <label class="form-check-label" for="infusion_pump">
                                                        <i class="material-icons icon-medication">medication</i> <?php echo xlt('Infusion Pumps'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="defibrillator" name="defibrillator" value="1">
                                                    <label class="form-check-label" for="defibrillator">
                                                        <i class="material-icons icon-flash">flash_on</i> <?php echo xlt('Defibrillator'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="physiotherapy" name="physiotherapy" value="1">
                                                    <label class="form-check-label" for="physiotherapy">
                                                        <i class="material-icons icon-fitness">fitness_center</i> <?php echo xlt('Physiotherapy Equipment'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="heater" name="heater" value="1">
                                                    <label class="form-check-label" for="heater">
                                                        <i class="material-icons icon-crib">crib</i> <?php echo xlt('Crib Heater'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="air_purifier" name="air_purifier" value="1">
                                                    <label class="form-check-label" for="air_purifier">
                                                        <i class="material-icons icon-sync">sync_alt</i> <?php echo xlt('Air Purifier'); ?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold"><?php echo xlt('Amenities'); ?>:</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="wifi" name="wifi" value="1">
                                                    <label class="form-check-label" for="wifi">
                                                        <i class="material-icons icon-wifi">wifi</i> <?php echo xlt('WiFi'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="television" name="television" value="1">
                                                    <label class="form-check-label" for="television">
                                                        <i class="material-icons icon-tv">tv</i> <?php echo xlt('Television'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="entertainment_system" name="entertainment_system" value="1">
                                                    <label class="form-check-label" for="entertainment_system">
                                                        <i class="material-icons icon-play">play_circle</i> <?php echo xlt('Entertainment System'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="personalized_menu" name="personalized_menu" value="1">
                                                    <label class="form-check-label" for="personalized_menu">
                                                        <i class="material-icons icon-menu">restaurant_menu</i> <?php echo xlt('Personalized Menu'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="companion_space" name="companion_space" value="1">
                                                    <label class="form-check-label" for="companion_space">
                                                        <i class="material-icons icon-chair">chair</i> <?php echo xlt('Companion Space'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="private_bathroom" name="private_bathroom" value="1">
                                                    <label class="form-check-label" for="private_bathroom">
                                                        <i class="material-icons icon-bathroom">bathroom</i> <?php echo xlt('Private Bathroom'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="friendly_decor" name="friendly_decor" value="1">
                                                    <label class="form-check-label" for="friendly_decor">
                                                        <i class="material-icons icon-smile">sentiment_very_satisfied</i> <?php echo xlt('Friendly Decor'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="light_mode" name="light_mode" value="1">
                                                    <label class="form-check-label" for="light_mode">
                                                        <i class="material-icons icon-light">light_mode</i> <?php echo xlt('Light Mode'); ?>
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="thermostat" name="thermostat" value="1">
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

                        <!-- Isolation Level and Room Status (in the same row) -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="isolationLevel" class="form-label"><?php echo xlt('Isolation Level'); ?>:</label>
                                <select class="form-select" id="isolationLevel" name="isolation_level" required>
                                    <?php while ($isolationLevel = sqlFetchArray($isolationLevels)): ?>
                                        <option value="<?php echo attr($isolationLevel['option_id']); ?>"><?php echo text($isolationLevel['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="roomStatus" class="form-label"><?php echo xlt('Room Status'); ?>:</label>
                                <select class="form-select" id="roomStatus" name="status" required>
                                    <?php while ($roomStatus = sqlFetchArray($roomStatuses)): ?>
                                        <option value="<?php echo attr($roomStatus['option_id']); ?>"><?php echo text($roomStatus['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="roomObs" class="form-label"><?php echo xlt('Notes'); ?>:</label>
                            <textarea class="form-control" id="roomObs" name="obs" rows="2"></textarea>
                        </div>

                        <!-- Active/Inactive -->
                        <div class="mb-3">
                            <label for="roomActive" class="form-label"><?php echo xlt('Active'); ?>:</label>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="roomActive" name="active" value="1" checked>
                                <span class="slider"></span>
                            </label>                        </div>
                        <?php echo xlt('User') . ': ' . $userFullName . '<br>'; ?>
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
    <script>
        // Show the modal automatically when the page loads
        $(document).ready(function () {
            $('#addRoomModal').modal('show');
        });

        // Redirect when the modal is closed
        $('#addRoomModal').on('hidden.bs.modal', function () {
            window.location.href = 'list_rooms.php?unit_id=<?php echo $unitId; ?>&unit_name=<?php echo $unitName; ?>&centro_id=<?php echo $centroId; ?>&centro_name=<?php echo $centroName; ?>';
        });
    </script>
</body>
</html>