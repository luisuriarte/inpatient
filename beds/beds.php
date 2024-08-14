<?php
include('connection.php');
include('functions.php');

$showInactive = isset($_POST['show_inactive']) ? true : false;
$centros = getCentros($showInactive);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegir Centro para Modificar</title>
	<link rel="stylesheet" href="styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Centros</h1>
    <form id="centroForm" method="post">
        <label>
            Centros
            <label class="custom-checkbox">
                <input type="checkbox" name="show_inactive" id="show_inactive" <?php echo $showInactive ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            Mostrar Centros Inactivos
        </label>
    </form>

    <div class="centro-container mt-4">
        <?php foreach ($centros as $centro): ?>
            <div class="centro-icon" onclick="openCentro(<?php echo $centro['id']; ?>)">
                <img src="images/<?php echo $centro['inactive'] ? 'centro-inactive' : 'centro-active'; ?>.svg" alt="Centro Icon">
                    <div class="centro-name"><?php echo $centro['name']; ?></div>
						<?php if ($centro['inactive'] == 1): ?>
                    <div class="centro-inactive">(Desactivado)</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <form method="post">
        <button type="submit" name="close" class="btn btn-danger mt-3">Cerrar</button>
    </form>
</div>

<script>
    document.getElementById('show_inactive').addEventListener('change', function() {
        document.getElementById('centroForm').submit();
    });

    function openCentro(centroId) {
        window.location.href = 'facility_choice.php?id=' + centroId;
    }
</script>
</body>
</html>
