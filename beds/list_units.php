<?php
include('connection.php');
include('functions.php');

// Verificar y obtener el ID y nombre del Centro
$centroId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$centroName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
$showInactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '1';

// Obtener las unidades
$unitsQuery = $showInactive ? "SELECT * FROM units WHERE facility_id = ?" : "SELECT * FROM units WHERE facility_id = ? AND active = 0";
$stmt = $conn->prepare($unitsQuery);
$stmt->bind_param("i", $centroId);
$stmt->execute();
$unitsResult = $stmt->get_result();

//Imagenes path
$imagePathActive = 'images/unit_active-icon.svg';
$imagePathInactive = 'images/unit_inactive-icon.svg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Unidades</title>
    <link rel="stylesheet" href="styles.css"> <!-- Enlace al archivo CSS externo -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php if ($centroName): ?>
            <h1>Unidades para <?php echo $centroName; ?></h1>
        <?php else: ?>
            <h1>No se ha seleccionado un Centro</h1>
        <?php endif; ?>

        <form method="get" class="mb-3">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($centroId); ?>">
            <input type="hidden" name="name" value="<?php echo htmlspecialchars($centroName); ?>"> <!-- Mantener el nombre del centro -->
            <label>
                Mostrar Unidades Inactivas
                <label class="custom-checkbox">
                    <input type="checkbox" name="show_inactive" value="1" onchange="this.form.submit()" <?php echo $showInactive ? 'checked' : ''; ?>>
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
                            <img src="<?php echo $unit['active'] == 1 ? $imagePathInactive : $imagePathActive; ?>" alt="Unit Icon">
                        </div>
                        <!-- Nombre de la unidad y estado -->
                        <div class="flex-grow-1">
                            <div class="unit-name"><?php echo htmlspecialchars($unit['name']); ?></div>
                            <?php if ($unit['active'] == 1): ?>
                                <div class="centro-inactive">(Inactivo)</div>
                            <?php endif; ?>
                        </div>
                        <!-- Botones de acción -->
                        <div class="btn-group">
                            <a href="edit_unit.php?id=<?php echo htmlspecialchars($unit['id']); ?>" class="btn btn-success btn-sm me-2">Editar</a>
                            <a href="view_unit.php?id=<?php echo htmlspecialchars($unit['id']); ?>" class="btn btn-info btn-sm me-2">Ver</a>
                            <a href="delete_unit.php?id=<?php echo htmlspecialchars($unit['id']); ?>" class="btn btn-danger btn-sm">Borrar</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No existe Unidades. <a href="add_unit.php?id=<?php echo htmlspecialchars($centroId); ?>" class="btn btn-outline-primary">Presione el botón agregar</a></p>
            <?php endif; ?>
        </div>
        
        <a href="add_unit.php?id=<?php echo htmlspecialchars($centroId); ?>" class="btn btn-outline-primary mt-4">Agregar Unidad</a>
    </div>
	   <script src="style.css"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
