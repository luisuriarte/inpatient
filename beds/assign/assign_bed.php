<?php

require_once("../../functions.php");
require_once("../../../interface/globals.php");

// Obtener datos del usuario de la sesión
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

$patientIdRelocate = isset($_GET['patient_id_relocate']) ? htmlspecialchars($_GET['patient_id_relocate']) : null;
$patientNameRelocate = isset($_GET['patient_name_relocate']) ? htmlspecialchars($_GET['patient_name_relocate']) : null;
$patientDniRelocate = isset($_GET['patient_dni_relocate']) ? htmlspecialchars($_GET['patient_dni_relocate']) : null;
$patientAgeRelocate = isset($_GET['patient_age_relocate']) ? htmlspecialchars($_GET['patient_age_relocate']) : null;
$patientSexRelocate = isset($_GET['patient_sex_relocate']) ? htmlspecialchars($_GET['patient_sex_relocate']) : null;
$insuranceNameRelocate = isset($_GET['insurance_name_relocate']) ? htmlspecialchars($_GET['insurance_name_relocate']) : null;
$fromIdBedsPatients = htmlspecialchars($_GET['from_id_beds_patients']) ?? null;
$fromBedId = htmlspecialchars($_GET['from_bed_id']) ?? null;
$fromRoomId = htmlspecialchars($_GET['from_room_id']) ?? null;
$fromUnitId = htmlspecialchars($_GET['from_unit_id']) ?? null;
$fromFacilityId = htmlspecialchars($_GET['from_facility_id']) ?? null;
$bedAction = isset($_GET['bed_action']) ? htmlspecialchars($_GET['bed_action']) : null;

// Variables para el manejo de vistas
$view = 'facilities'; // Vista inicial
$facilityName = ''; // Nombre del centro seleccionado
$units = []; // Array para las unidades
$rooms = []; // Array para los cuartos

// Cambiar el título basado en el valor de bed_action
switch ($bedAction) {
    case 'Assign':
        $bedActionTitle = xlt('Assign Bed to Patient'); // Título para asignar cama
        $backgroundPatientCard = "#c2f9bc";
        $modeText = 'Operations Mode';
        $title_patient_name = $patient_name;
        $title_patient_dni = $patient_dni;
        $title_patient_age = $patient_age;
        $title_patient_sex = $patient_sex;
        $title_insurance_name = $insurance_name;
        break;
    case 'Relocation':
        $bedActionTitle = xlt('Relocate Patient'); // Título para mover paciente
        $backgroundPatientCard = "#f9e0bc";
        $modeText = 'Relocation Mode';
        $title_patient_name = $patientNameRelocate;
        $title_patient_dni = $patientDniRelocate;
        $title_patient_age = $patientAgeRelocate;
        $title_patient_sex = $patientSexRelocate;
        $title_insurance_name = $insuranceNameRelocate;
        break;
    default:
        $bedActionTitle = xlt('Manage Bed Assignments'); // Título por defecto
}

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

<?php
    // Validar que al menos una variable tenga un valor no vacío y sin espacios en blanco
//    if (trim($patient_id) !== '' || trim($patient_name) !== '') {
        include '../../patient_header.html';
//    }
?>


<div class="container mt-5">
    <?php if ($view === 'facilities'): ?>
        <!-- Mostrar Centros -->
        <h1><?php echo xl('Select a Facility'); ?></h1>
        <div class="row">
            <?php foreach ($facilities as $facility): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="card facility-card text-center" onclick="window.location.href='?facility_id=<?php echo urlencode($facility['id']); ?>
									&facility_name=<?php echo urlencode($facility['name']); ?>
                                    &patient_name_relocate=<?php echo htmlspecialchars($patientNameRelocate); ?>
                                    &patient_dni_relocate=<?php echo htmlspecialchars($patientDniRelocate); ?>
                                    &patient_age_relocate=<?php echo htmlspecialchars($patientAgeRelocate); ?>
                                    &patient_sex_relocate=<?php echo htmlspecialchars($patientSexRelocate); ?>
                                    &insurance_name_relocate=<?php echo htmlspecialchars($insuranceNameRelocate); ?>
                                    &patient_id_relocate=<?php echo htmlspecialchars($patientIdRelocate); ?>
                                    &from_id_beds_patients=<?php echo htmlspecialchars($fromIdBedsPatients); ?>
                                    &from_bed_id=<?php echo htmlspecialchars($fromBedId); ?>
                                    &from_room_id=<?php echo htmlspecialchars($fromRoomId); ?>
                                    &from_unit_id=<?php echo htmlspecialchars($fromUnitId); ?>
                                    &from_facility_id=<?php echo htmlspecialchars($fromFacilityId); ?>
									&bed_action=<?php echo $bedAction; ?>
									&background_card=<?php echo htmlspecialchars($backgroundPatientCard); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-hospital-alt"></i>
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
        <h1><?php echo xl('Select a Unit'); ?></h1>
        <div class="row">
            <?php foreach ($units as $unit): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="unit-card card text-center mx-2 mb-3" style="width: 18rem;" onclick="window.location.href='?facility_id=<?php echo urlencode($facilityId); ?>
									&facility_name=<?php echo urlencode($facilityName); ?>
									&unit_id=<?php echo urlencode($unit['id']); ?>
									&unit_name=<?php echo urlencode($unit['unit_name']); ?>
                                    &patient_name_relocate=<?php echo htmlspecialchars($patientNameRelocate); ?>
                                    &patient_dni_relocate=<?php echo htmlspecialchars($patientDniRelocate); ?>
                                    &patient_age_relocate=<?php echo htmlspecialchars($patientAgeRelocate); ?>
                                    &patient_sex_relocate=<?php echo htmlspecialchars($patientSexRelocate); ?>
                                    &insurance_name_relocate=<?php echo htmlspecialchars($insuranceNameRelocate); ?>
                                    &patient_id_relocate=<?php echo htmlspecialchars($patientIdRelocate); ?>
                                    &from_id_beds_patients=<?php echo htmlspecialchars($fromIdBedsPatients); ?>
                                    &from_bed_id=<?php echo htmlspecialchars($fromBedId); ?>
                                    &from_room_id=<?php echo htmlspecialchars($fromRoomId); ?>
                                    &from_unit_id=<?php echo htmlspecialchars($fromUnitId); ?>
                                    &from_facility_id=<?php echo htmlspecialchars($fromFacilityId); ?>
									&bed_action=<?php echo $bedAction; ?>
									&background_card=<?php echo htmlspecialchars($backgroundPatientCard); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-hospital-alt"></i>
                                <?php echo htmlspecialchars($unit['unit_name']); ?>
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
        <h1><?php echo xl('Select a Room'); ?></h1>
        <div class="row">
            <?php foreach ($rooms as $room): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                <div class="room-card card text-center mx-2 mb-3" style="width: 18rem;" onclick="window.location.href='load_beds.php?room_id=<?php echo urlencode($room['id']); ?>
														&room_name=<?php echo urlencode($room['room_name']); ?>
                                                        &room_sector=<?php echo urlencode($room['room_sector']); ?>
														&unit_id=<?php echo urlencode($unitId); ?>
														&unit_name=<?php echo urlencode($unitName); ?>
                                                        &unit_floor=<?php echo urlencode($unitFloor); ?>
														&facility_id=<?php echo urlencode($facilityId); ?>
														&facility_name=<?php echo urlencode($facilityName); ?>
                                                        &patient_name_relocate=<?php echo htmlspecialchars($patientNameRelocate); ?>
                                                        &patient_dni_relocate=<?php echo htmlspecialchars($patientDniRelocate); ?>
                                                        &patient_age_relocate=<?php echo htmlspecialchars($patientAgeRelocate); ?>
                                                        &patient_sex_relocate=<?php echo htmlspecialchars($patientSexRelocate); ?>
                                                        &insurance_name_relocate=<?php echo htmlspecialchars($insuranceNameRelocate); ?>
                                                        &patient_id_relocate=<?php echo htmlspecialchars($patientIdRelocate); ?>
                                                        &from_id_beds_patients=<?php echo htmlspecialchars($fromIdBedsPatients); ?>
                                                        &from_bed_id=<?php echo htmlspecialchars($fromBedId); ?>
                                                        &from_room_id=<?php echo htmlspecialchars($fromRoomId); ?>
                                                        &from_unit_id=<?php echo htmlspecialchars($fromUnitId); ?>
                                                        &from_facility_id=<?php echo htmlspecialchars($fromFacilityId); ?>
														&bed_action=<?php echo urlencode($bedAction); ?>
														&background_card=<?php echo urlencode($backgroundPatientCard); ?>'">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-hospital-alt"></i>
                                <?php echo htmlspecialchars($room['room_name']); ?>
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
            <!-- Botón -->
            <a href="?facility_id=<?php echo urlencode($facilityId); ?>&facility_name=<?php echo urlencode($facilityName); ?>
											&bed_action=<?php echo $bedAction; ?>
											&background_card=<?php echo htmlspecialchars($backgroundPatientCard); ?>" 
                                            class="btn btn-secondary mt-4"> 
                <i class="fas fa-arrow-left"></i> <?php echo xl('Back to Units'); ?>
            </a>

            <!-- Cuadradito con texto alineado al borde derecho -->
            <div style="display: flex; align-items: center; position: absolute; right: 0;">
                <div style="width: 50px; height: 20px; background-color: <?php echo xlt($backgroundPatientCard) ?>; border: 1px solid #000; margin-right: 10px;"></div>
                <span><?php echo $modeText; ?></span>
            </div>
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
