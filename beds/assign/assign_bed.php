<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Iniciar sesión y usar un subespacio
//session_start();
$sessionKey = 'bed_management';

// Obtener usuario autenticado
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Recoger parámetros GET
$bedAction = $_GET['bed_action'] ?? 'Assign';
$view = $_GET['view'] ?? 'facilities';
$facilityId = $_GET['facility_id'] ?? ($_SESSION[$sessionKey]['context']['facility_id'] ?? null);
$facilityName = $_GET['facility_name'] ?? ($_SESSION[$sessionKey]['context']['facility_name'] ?? '');
$unitId = $_GET['unit_id'] ?? ($_SESSION[$sessionKey]['context']['unit_id'] ?? null);
$unitName = $_GET['unit_name'] ?? ($_SESSION[$sessionKey]['context']['unit_name'] ?? '');

// Datos del paciente: Priorizar variables globales de OpenEMR, luego GET, luego sesión
$patient_id = isset($patient_id) ? $patient_id : ($_GET['patient_id'] ?? $_GET['patient_id_relocate'] ?? $_SESSION['pid'] ?? ($_SESSION[$sessionKey]['patient_id'] ?? null));
$patient_name = isset($patient_name) ? $patient_name : ($_GET['patient_name'] ?? $_GET['patient_name_relocate'] ?? $_SESSION['patient_name'] ?? ($_SESSION[$sessionKey]['patient_name'] ?? null));

$patientData = [
    'id' => $patient_id,
    'name' => $patient_name,
    'dni' => isset($patient_dni) ? $patient_dni : ($_GET['patient_dni_relocate'] ?? $_SESSION[$sessionKey]['patient_dni'] ?? null),
    'age' => isset($patient_age) ? $patient_age : ($_GET['patient_age_relocate'] ?? $_SESSION[$sessionKey]['patient_age'] ?? null),
    'sex' => isset($patient_sex) ? $patient_sex : ($_GET['patient_sex_relocate'] ?? $_SESSION[$sessionKey]['patient_sex'] ?? null),
    'pubpid' => $_GET['patient_pubpid_relocate'] ?? ($_SESSION[$sessionKey]['patient_pubpid'] ?? null),
    'insurance' => isset($insurance_name) ? $insurance_name : ($_GET['insurance_name_relocate'] ?? ($_SESSION[$sessionKey]['insurance_name'] ?? null)),
];

// Si tenemos ID pero no nombre (p.ej. vino del PID global), buscar el nombre
if ($patientData['id'] && empty($patientData['name'])) {
    $patient_res = getPatientData($patientData['id'], "fname, lname");
    if ($patient_res) {
        $patientData['name'] = $patient_res['fname'] . ' ' . $patient_res['lname'];
    }
}

// Guardar paciente en sesión si viene de Assign o Relocation
if ($_GET['patient_id'] || $_GET['patient_id_relocate']) {
    $_SESSION[$sessionKey]['patient_id'] = $patientData['id'];
    $_SESSION[$sessionKey]['patient_name'] = $patientData['name'];
    $_SESSION[$sessionKey]['patient_dni'] = $patientData['dni'];
    $_SESSION[$sessionKey]['patient_age'] = $patientData['age'];
    $_SESSION[$sessionKey]['patient_sex'] = $patientData['sex'];
    $_SESSION[$sessionKey]['insurance_name'] = $patientData['insurance'];
}

// Variables para el manejo de vistas
$facilityName = '';
$units = [];
$rooms = [];

// Configurar título, color y modo según bedAction y presencia de paciente
if ($patientData['id']) {
    switch ($bedAction) {
        case 'Assign':
            $bedActionTitle = xlt('Assign Bed to Patient');
            $backgroundPatientCard = '#c2f9bc';
            $modeText = 'Operations Mode';
            break;
        case 'Relocation':
            $bedActionTitle = xlt('Relocate Patient');
            $backgroundPatientCard = '#f9e0bc';
            $modeText = 'Relocation Mode';
            break;
        default:
            $bedActionTitle = xlt('Manage Bed Assignments');
            $backgroundPatientCard = null;
            $modeText = '';
    }
} else {
    $bedActionTitle = xlt('Manage Bed Assignments');
    $backgroundPatientCard = null;
    $modeText = '';
}

// Actualizar contexto en sesión
$_SESSION[$sessionKey]['context'] = [
    'facility_id' => $facilityId,
    'facility_name' => $facilityName,
    'unit_id' => $unitId,
    'unit_name' => $unitName,
    'background_card' => $backgroundPatientCard,
];

// Manejo de la lógica de selección
if (isset($_GET['facility_id']) && !isset($_GET['unit_id'])) {
    $facilityId = $_GET['facility_id'];
    $facilityName = $_GET['facility_name'];
    $units = getUnitsWithBedsData($facilityId); // Obtener unidades de la facility seleccionada
    $view = 'units'; // Cambiar a vista de unidades
} elseif (isset($_GET['unit_id'])) {
    $unitId = $_GET['unit_id'];
    $unitName = $_GET['unit_name'];
    $facilityId = $_GET['facility_id'];
    $facilityName = $_GET['facility_name'];
    $rooms = getRoomsWithBedsData($unitId, $facilityId); // Obtener cuartos de la unidad seleccionada
    $view = 'rooms'; // Cambiar a vista de cuartos
} else {
    $facilities = getFacilitiesWithBedsData(); // Obtener listado de facilities
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $bedActionTitle; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Carga de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../../styles.css"> <!-- Enlace al archivo CSS externo -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
        :root {
            --background-color: <?php echo $backgroundPatientCard; ?>;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <!-- Mostrar encabezado solo si hay paciente seleccionado -->
        <?php if ($patientData['id']): ?>
            <div class="patient-header">
                <?php include '../../patient_header.html'; ?>
            </div>
        <?php endif; ?>

    <?php if ($view === 'facilities'): ?>
        <!-- Mostrar Centros -->
        <h1><?php echo xl('Select a Facility'); ?></h1>
        <div class="row">
            <?php foreach ($facilities as $facility): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card facility-card text-center" onclick="window.location.href='?view=units&facility_id=<?php echo urlencode($facility['id']); ?>&facility_name=<?php echo urlencode($facility['name']); ?>&bed_action=<?php echo urlencode($bedAction); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-hospital-alt" style="color: #0d47a1;"></i>
                                <?php echo htmlspecialchars($facility['name']); ?>
                            </h5>
                            <!-- <p class="card-text text-primary"><?php echo xl('Beds Total'); ?>: <span class="badge bg-primary"><?php echo $facility['total_beds']; ?></span></p> -->
                            <canvas id="chart-<?php echo $facility['id']; ?>" height="200"></canvas> <!-- Canvas para el gráfico -->

                            <!-- Script para generar el gráfico -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Calcular el total de camas para usarlo en la barra "Total"
                                    var totalBeds = <?php echo $facility['total_beds']; ?>;

                                    // Datos PHP a JavaScript
                                    var bedData = {
                                        labels: ["Total:"].concat([
                                            <?php foreach ($facility['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['title']); ?>",
                                            <?php endforeach; ?>
                                        ]),
                                        counts: [totalBeds].concat([
                                            <?php foreach ($facility['bed_conditions'] as $condition): ?>
                                                <?php echo $condition['count']; ?>,
                                            <?php endforeach; ?>
                                        ]),
                                        colors: ["#000000"].concat([
                                            <?php foreach ($facility['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['color']); ?>",
                                            <?php endforeach; ?>
                                        ])
                                    };

                                    // Verificación en consola para asegurarnos de que los datos son correctos
                                    console.log(bedData);

                                    // Registrar el plugin globalmente
                                    Chart.register(ChartDataLabels);

                                    // Configuración del gráfico de barras horizontales
                                    var ctx = document.getElementById('chart-<?php echo $facility['id']; ?>').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: bedData.labels,
                                            datasets: [{
                                                data: bedData.counts,
                                                backgroundColor: bedData.colors,
                                                borderColor: bedData.colors,
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            indexAxis: 'y', // Configura el gráfico como barras horizontales
                                            scales: {
                                                x: {
                                                    type: 'linear', // O 'category' si tus etiquetas son categóricas
                                                    ticks: {
                                                        stepSize: 1, // Establece el incremento entre cada tick
                                                        min: 0, // Establece el valor mínimo del eje
                                                        max: 10, // Establece el valor máximo del eje
                                                        callback: function(value, index, values) {
                                                            return value.toFixed(0); // Redondea al entero más cercano
                                                        }
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    display: false // Ocultar la leyenda para simplificar
                                                },
                                                tooltip: {
                                                    enabled: false // Deshabilita la interacción de tooltip
                                                },
                                                datalabels: {
                                                    display: true, // Asegura que las etiquetas se muestren
                                                    anchor: 'center', // Posiciona el texto al final de la barra
                                                    align: 'center', // Alinea el texto hacia la derecha dentro de la barra
                                                    color: '#FFFFFF', // Color blanco para el texto
                                                    formatter: function(value) {
                                                        return value; // Muestra el valor de la barra
                                                    },
                                                    clip: true, // Asegura que el texto no se salga de la barra
                                                    offset: 10, // Ajusta la posición del texto para asegurarse de que quede dentro de la barra
                                                    font: {
                                                        weight: 'bold' // Hacer el texto en negrita
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Contenedor que incluye el botón y el cuadradito con texto -->
        <div style="display: flex; justify-content: space-between; align-items: center; position: relative; width: 100%;">
            <!-- Botón -->
            <a href="assign.php" class="btn btn-secondary mt-4">
                <i class="fas fa-home"></i> <?php echo xl('Principal Board'); ?>
            </a>

            <!-- Cuadradito con texto alineado al borde derecho -->
            <div style="display: flex; align-items: center; position: absolute; right: 0;">
                <div style="width: 50px; height: 20px; background-color: <?php echo xlt($backgroundPatientCard) ?>; border: 1px solid #000; margin-right: 10px;"></div>
                <span><?php echo $modeText; ?></span>
            </div>
        </div>

    <?php elseif ($view === 'units'): ?>
        <!-- Mostrar Unidades -->
        <h1 class="mb-4">
        <i class="fas fa-hospital-alt" style="color: #0d47a1;"></i> <?php echo htmlspecialchars($facilityName); ?>: 
        <small class="text-muted"><?php echo xl('Select a Unit'); ?></small>
    </h1>
        <div class="row">
            <?php foreach ($units as $unit): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card unit-card text-center" onclick="window.location.href='?view=rooms&facility_id=<?php echo urlencode($facilityId); ?>&facility_name=<?php echo urlencode($facilityName); ?>&unit_id=<?php echo urlencode($unit['id']); ?>&unit_name=<?php echo urlencode($unit['unit_name']); ?>&bed_action=<?php echo urlencode($bedAction); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-layer-group" style="color: #00897b;"></i>
                                <?php echo htmlspecialchars($unit['unit_name']); ?>
                                <small class="text-muted d-block"><i class="fas fa-stairs" style="color: #616161;"></i> <?php echo xl('Floor'); ?>: <?php echo htmlspecialchars($unit['unit_floor']); ?></small>
                            </h5>
                            <!-- <p class="card-text text-primary"><?php echo xl('Beds Total'); ?>: <span class="badge bg-primary"><?php echo $unit['total_beds']; ?></span></p> -->
                            <canvas id="chart-<?php echo $unit['id']; ?>" height="200"></canvas> <!-- Canvas para el gráfico -->

                            <!-- Script para generar el gráfico -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Calcular el total de camas para usarlo en la barra "Total"
                                    var totalBeds = <?php echo $unit['total_beds']; ?>;

                                    // Datos PHP a JavaScript
                                    var bedData = {
                                        labels: ["Total:"].concat([
                                            <?php foreach ($unit['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['title']); ?>",
                                            <?php endforeach; ?>
                                        ]),
                                        counts: [totalBeds].concat([
                                            <?php foreach ($unit['bed_conditions'] as $condition): ?>
                                                <?php echo $condition['count']; ?>,
                                            <?php endforeach; ?>
                                        ]),
                                        colors: ["#000000"].concat([
                                            <?php foreach ($unit['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['color']); ?>",
                                            <?php endforeach; ?>
                                        ])
                                    };

                                    // Verificación en consola para asegurarnos de que los datos son correctos
                                    console.log(bedData);

                                    // Registrar el plugin globalmente
                                    Chart.register(ChartDataLabels);

                                    // Configuración del gráfico de barras horizontales
                                    var ctx = document.getElementById('chart-<?php echo $unit['id']; ?>').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: bedData.labels,
                                            datasets: [{
                                                data: bedData.counts,
                                                backgroundColor: bedData.colors,
                                                borderColor: bedData.colors,
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            indexAxis: 'y', // Configura el gráfico como barras horizontales
                                            scales: {
                                                x: {
                                                    type: 'linear', // O 'category' si tus etiquetas son categóricas
                                                    ticks: {
                                                        stepSize: 1, // Establece el incremento entre cada tick
                                                        min: 0, // Establece el valor mínimo del eje
                                                        max: 10, // Establece el valor máximo del eje
                                                        callback: function(value, index, values) {
                                                            return value.toFixed(0); // Redondea al entero más cercano
                                                        }
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    display: false // Ocultar la leyenda para simplificar
                                                },
                                                tooltip: {
                                                    enabled: false // Deshabilita la interacción de tooltip
                                                },
                                                datalabels: {
                                                    display: true, // Asegura que las etiquetas se muestren
                                                    anchor: 'center', // Posiciona el texto al final de la barra
                                                    align: 'center', // Alinea el texto hacia la derecha dentro de la barra
                                                    color: '#FFFFFF', // Color blanco para el texto
                                                    formatter: function(value) {
                                                        return value; // Muestra el valor de la barra
                                                    },
                                                    clip: true, // Asegura que el texto no se salga de la barra
                                                    offset: 10, // Ajusta la posición del texto para asegurarse de que quede dentro de la barra
                                                    font: {
                                                        weight: 'bold' // Hacer el texto en negrita
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Contenedor que incluye el botón y el cuadradito con texto -->
        <div style="display: flex; justify-content: space-between; align-items: center; position: relative; width: 100%;">
            <!-- Botón -->
            <a href="?" class="btn btn-secondary mt-4">
                <i class="fas fa-arrow-left"></i> <?php echo xl('Back to Facilities'); ?>
            </a>

            <!-- Cuadradito con texto alineado al borde derecho -->
            <div style="display: flex; align-items: center; position: absolute; right: 0;">
                <div style="width: 50px; height: 20px; background-color: <?php echo xlt($backgroundPatientCard) ?>; border: 1px solid #000; margin-right: 10px;"></div>
                <span><?php echo $modeText; ?></span>
            </div>
        </div>

    <?php elseif ($view === 'rooms'): ?>
        <!-- Mostrar Cuartos -->
        <h1 class="mb-4">
        <i class="fas fa-layer-group" style="color: #00897b;"></i> <?php echo htmlspecialchars($unitName); ?>: 
        <small class="text-muted"><?php echo xl('Select a Room'); ?></small>
    </h1>
        <div class="row">
            <?php foreach ($rooms as $room): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card room-card text-center" onclick="window.location.href='load_beds.php?room_id=<?php echo urlencode($room['id']); ?>&room_name=<?php echo urlencode($room['room_name']); ?>&room_sector=<?php echo urlencode($room['room_sector']); ?>&unit_id=<?php echo urlencode($unitId); ?>&unit_name=<?php echo urlencode($unitName); ?>&facility_id=<?php echo urlencode($facilityId); ?>&facility_name=<?php echo urlencode($facilityName); ?>&bed_action=<?php echo urlencode($bedAction); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-door-open" style="color: #e65100;"></i>
                                <?php echo htmlspecialchars($room['room_name']); ?>
                                <small class="text-muted d-block"><i class="fas fa-map-marker-alt" style="color: #7b1fa2;"></i> <?php echo xl('Type'); ?>: <?php echo htmlspecialchars($room['room_type']); ?></small>
                            </h5>
                            <!-- <p class="card-text text-primary"><?php echo xl('Beds Total'); ?>: <span class="badge bg-primary"><?php echo $room['total_beds']; ?></span></p> -->
                            <canvas id="chart-<?php echo $room['id']; ?>" height="200"></canvas> <!-- Canvas para el gráfico -->

                            <!-- Script para generar el gráfico -->
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Calcular el total de camas para usarlo en la barra "Total"
                                    var totalBeds = <?php echo $room['total_beds']; ?>;

                                    // Datos PHP a JavaScript
                                    var bedData = {
                                        labels: ["Total:"].concat([
                                            <?php foreach ($room['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['title']); ?>",
                                            <?php endforeach; ?>
                                        ]),
                                        counts: [totalBeds].concat([
                                            <?php foreach ($room['bed_conditions'] as $condition): ?>
                                                <?php echo $condition['count']; ?>,
                                            <?php endforeach; ?>
                                        ]),
                                        colors: ["#000000"].concat([
                                            <?php foreach ($room['bed_conditions'] as $condition): ?>
                                                "<?php echo htmlspecialchars($condition['color']); ?>",
                                            <?php endforeach; ?>
                                        ])
                                    };

                                    // Verificación en consola para asegurarnos de que los datos son correctos
                                    console.log(bedData);

                                    // Registrar el plugin globalmente
                                    Chart.register(ChartDataLabels);

                                    // Configuración del gráfico de barras horizontales
                                    var ctx = document.getElementById('chart-<?php echo $room['id']; ?>').getContext('2d');
                                    new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: bedData.labels,
                                            datasets: [{
                                                data: bedData.counts,
                                                backgroundColor: bedData.colors,
                                                borderColor: bedData.colors,
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            indexAxis: 'y', // Configura el gráfico como barras horizontales
                                            scales: {
                                                x: {
                                                    type: 'linear', // O 'category' si tus etiquetas son categóricas
                                                    ticks: {
                                                        stepSize: 1, // Establece el incremento entre cada tick
                                                        min: 0, // Establece el valor mínimo del eje
                                                        max: 10, // Establece el valor máximo del eje
                                                        callback: function(value, index, values) {
                                                            return value.toFixed(0); // Redondea al entero más cercano
                                                        }
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    display: false // Ocultar la leyenda para simplificar
                                                },
                                                tooltip: {
                                                    enabled: false // Deshabilita la interacción de tooltip
                                                },
                                                datalabels: {
                                                    display: true, // Asegura que las etiquetas se muestren
                                                    anchor: 'center', // Posiciona el texto al final de la barra
                                                    align: 'center', // Alinea el texto hacia la derecha dentro de la barra
                                                    color: '#FFFFFF', // Color blanco para el texto
                                                    formatter: function(value) {
                                                        return value; // Muestra el valor de la barra
                                                    },
                                                    clip: true, // Asegura que el texto no se salga de la barra
                                                    offset: 10, // Ajusta la posición del texto para asegurarse de que quede dentro de la barra
                                                    font: {
                                                        weight: 'bold' // Hacer el texto en negrita
                                                    }
                                                }
                                            }
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <!-- Contenedor que incluye el botón y el cuadradito con texto -->
        <div style="display: flex; justify-content: space-between; align-items: center; position: relative; width: 100%;">
            <a href="?view=units&facility_id=<?php echo urlencode($facilityId); ?>&facility_name=<?php echo urlencode($facilityName); ?>&bed_action=<?php echo urlencode($bedAction); ?>" class="btn btn-secondary mt-4"><i class="fas fa-arrow-left"></i> <?php echo xlt('Back to Units'); ?></a>
            <?php if ($patientData['id']): ?>
                <div style="display: flex; align-items: center; position: absolute; right: 0;">
                    <div style="width: 50px; height: 20px; background-color: <?php echo htmlspecialchars($backgroundPatientCard); ?>; border: 1px solid #000; margin-right: 10px;"></div>
                    <span><?php echo $modeText; ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<!-- Incluye functions.js -->
<script 
    src="../../functions.js">
</script>
</body>
</html>
