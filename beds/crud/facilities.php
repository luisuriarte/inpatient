<?php

require_once ("../../functions.php");
require_once("../../../interface/globals.php");

$showInactive = isset($_POST['show_inactive']) ? true : false;
$centros = getCentros($showInactive);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Facilities'); ?></title>
	<link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container mt-5">
    <h1><i class="fas fa-hospital-alt" style="color: #0d47a1;"></i> <?php echo xlt('Select Facility to Modify'); ?></h1>
    <form id="centroForm" method="post">
        <label>
            <label class="custom-checkbox">
                <input type="checkbox" name="show_inactive" id="show_inactive" <?php echo $showInactive ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            <?php echo xlt('Show Inactive Facilities'); ?>
        </label>
    </form>

    <div class="centro-container mt-4">
        <?php foreach ($centros as $centro): ?>
            <div class="centro-icon" onclick="openCentro(<?php echo $centro['id']; ?>)">
                <img src="../images/<?php echo $centro['inactive'] ? 'facility_inactive_icon' : 'facility_active_icon'; ?>.svg" alt="Centro Icon">
                    <div class="centro-name"><?php echo $centro['name']; ?></div>
						<?php if ($centro['inactive'] == 1): ?>
                    <span class="badge bg-danger"><?php echo xlt('(Disabled)'); ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.getElementById('show_inactive').addEventListener('change', function() {
        document.getElementById('centroForm').submit();
    });

    function openCentro(centroId) {
        window.location.href = 'facility_choice.php?centro_id=' + encodeURIComponent(centroId);
    }
</script>
</body>
</html>
