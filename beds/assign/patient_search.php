<?php
require_once("../../functions.php");
require_once("../../../interface/globals.php");

$date_format = isset($GLOBALS['date_display_format']) ? $GLOBALS['date_display_format'] : 0;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo xlt('Patients Search'); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Carga de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .search-container {
            margin-top: 50px;
        }
        .search-results {
            margin-top: 20px;
        }
        .table-responsive {
            max-height: 400px;
        }
        /* Colores para los diferentes estados */
        .bg-reserved {
            background-color: #fff9c4; /* Amarillo suave */
        }
        .bg-archival {
            background-color: #ffebee; /* Rojo suave */
        }
        .bg-occupied {
            background-color: #bbdefb; /* Azul suave */
        }

        .reference-reserved {
            background-color: #fff9c4; /* Amarillo suave */
        }
        .reference-archival {
            background-color: #ffebee; /* Rojo suave */
        }
        .reference-occupied {
            background-color: #bbdefb; /* Azul suave */
        }
        .reference-container {
            display: flex;
            justify-content: space-between; /* Espaciado igual entre los elementos */
            align-items: center; /* Centra verticalmente los elementos */
            width: 100%; /* Asegúrate de que el contenedor ocupe todo el ancho */
        }

        .reference-item {
            display: flex; /* Para alinear el rectángulo y el texto */
            align-items: center; /* Centra verticalmente el texto y la caja */
        }

        .reference-box {
            width: 80px; /* Ancho del rectángulo */
            height: 20px; /* Altura del rectángulo */
            margin-right: 5px; /* Espacio entre la caja y el texto */
            display: inline-block; /* Para que se respete el tamaño */
            border: 1px solid #000; /* Borde del rectángulo, opcional */
        }
    </style>
</head>
<body>

<div class="container search-container">
    <h2 class="text-center mb-4"><?php echo xlt('Patient Search'); ?></h2>
    <div class="row justify-content-center">
        <div class="col-md-8">
        <div class="input-group mb-3">
            <input type="text" class="form-control" id="searchInput" placeholder="<?php echo xlt('Enter Patient Name...'); ?>" aria-label="Patient Search" aria-describedby="button-addon2" onkeydown="checkEnterKey(event)">
            <button class="btn btn-primary" type="button" id="button-addon2" onclick="searchPatient()"><?php echo xlt('Search'); ?></button>
        </div>
        </div>
    </div>
    <div class="row search-results">
       <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th onclick="sortTable('name')"><?php echo xlt('Patients Search'); ?><span id="sortIndicatorName"></span></th>
                            <th onclick="sortTable('facility')"><?php echo xlt('Facility'); ?><span id="sortIndicatorFacility"></span></th>
                            <th onclick="sortTable('unit')"><?php echo xlt('Unit'); ?><span id="sortIndicatorUnit"></span></th>
                            <th onclick="sortTable('room')"><?php echo xlt('Room'); ?><span id="sortIndicatorRoom"></span></th>
                            <th onclick="sortTable('checkin')"><?php echo xlt('Check-In'); ?><span id="sortIndicatorCheckin"></span></th>
                            <th onclick="sortTable('checkout')"><?php echo xlt('Check-Out'); ?><span id="sortIndicatorCheckout"></span></th>
                            <th onclick="sortTable('age')"><?php echo xlt('Age'); ?><span id="sortIndicatorAge"></span></th>
                            <th onclick="sortTable('sex')"><?php echo xlt('Sex'); ?><span id="sortIndicatorSex"></span></th>
                            <th onclick="sortTable('pubpid')"><?php echo xlt('External Id'); ?><span id="sortIndicatorPubpid"></span></th>
                            <th onclick="sortTable('insurance')"><?php echo xlt('Insurance'); ?><span id="sortIndicatorInsurance"></span></th>
                        </tr>
                    </thead>
                    <tbody id="resultTable">
                        <!-- Las filas de datos serán añadidas aquí -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <!-- Referencias de los colores -->
    <div class="row mt-4">
        <div class="reference-container">
            <div class="reference-item">
                <span class="reference-box reference-reserved"></span> <?php echo xlt('Pre-admitted'); ?>
            </div>
            <div class="reference-item">
                <span class="reference-box reference-archival"></span> <?php echo xlt('Discharged');?>
            </div>
            <div class="reference-item">
                <span class="reference-box reference-occupied"></span> <?php echo xlt('Inpatient'); ?>
            </div>
        </div>
    </div>
    <!-- Botón para ir a Principal Board -->
    <a href="assign.php" class="btn btn-secondary mt-4">
        <i class="fas fa-home"></i> <?php echo xl('Principal Board'); ?>
    </a>
</div>

<!-- Bootstrap JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>

let sortDirection = {}; // Almacenar la dirección de orden para cada columna

function sortTable(column) {
    // Alternar la dirección de orden para la columna seleccionada
    sortDirection[column] = sortDirection[column] === 'asc' ? 'desc' : 'asc';

    // Obtener los datos de la tabla
    let table = document.getElementById('resultTable');
    let rows = Array.from(table.rows);

    // Ordenar las filas
    rows.sort((a, b) => {
        let cellA, cellB;

        // No necesitamos hacer nada especial para el encabezado ahora
        switch (column) {
            case 'name':
                cellA = a.cells[0].textContent.toLowerCase();
                cellB = b.cells[0].textContent.toLowerCase();
                break;
            case 'facility':
                cellA = a.cells[1].textContent.toLowerCase();
                cellB = b.cells[1].textContent.toLowerCase();
                break;
            case 'unit':
                cellA = a.cells[2].textContent.toLowerCase();
                cellB = b.cells[2].textContent.toLowerCase();
                break;
            case 'room':
                cellA = a.cells[3].textContent.toLowerCase();
                cellB = b.cells[3].textContent.toLowerCase();
                break;
            case 'checkin':
                cellA = parseDateToTimestamp(a.cells[4].textContent.trim());
                cellB = parseDateToTimestamp(b.cells[4].textContent.trim());
                break;
            case 'checkout':
                cellA = parseDateToTimestamp(a.cells[5].textContent.trim());
                cellB = parseDateToTimestamp(b.cells[5].textContent.trim());
                break;
            case 'age':
                cellA = parseInt(a.cells[6].textContent);
                cellB = parseInt(b.cells[6].textContent);
                break;
            case 'sex':
                cellA = a.cells[7].textContent.toLowerCase();
                cellB = b.cells[7].textContent.toLowerCase();
                break;
            case 'pubpid':
                cellA = a.cells[8].textContent.toLowerCase();
                cellB = b.cells[8].textContent.toLowerCase();
                break;
            case 'insurance':
                cellA = a.cells[9].textContent.toLowerCase();
                cellB = b.cells[9].textContent.toLowerCase();
                break;
            default:
                return 0;
        }

        if (sortDirection[column] === 'asc') {
            return cellA > cellB ? 1 : -1;
        } else {
            return cellA < cellB ? 1 : -1;
        }
    });

    // Limpiar el cuerpo de la tabla
    table.innerHTML = ''; // Esto eliminará todo, incluyendo el encabezado

    // Volver a agregar todas las filas ordenadas
    rows.forEach(row => table.appendChild(row));

    // Actualizar el indicador de orden
    updateSortIndicators(column);
}

function updateSortIndicators(sortedColumn) {
    // Limpiar todos los indicadores
    const indicators = ['name', 'facility', 'unit', 'room', 'checkin', 'checkout', 'age', 'sex', 'pubpid', 'insurance'];
    indicators.forEach(col => {
        const indicator = document.getElementById(`sortIndicator${capitalizeFirstLetter(col)}`);
        if (indicator) {
            indicator.textContent = ''; // Limpiar el indicador
        }
    });

    // Actualizar el indicador de la columna ordenada
    const direction = sortDirection[sortedColumn] === 'asc' ? '🔼' : '🔽';
    const currentIndicator = document.getElementById(`sortIndicator${capitalizeFirstLetter(sortedColumn)}`);
    if (currentIndicator) {
        currentIndicator.textContent = direction; // Actualizar el indicador
    } else {
        console.error(`No se encontró el indicador para la columna: ${sortedColumn}`);
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

window.onload = function() {
    // Inicializa tu código aquí
};

    // Backgound segun condicion. 
    function searchPatient() {
    const searchValue = $('#searchInput').val();
    if (searchValue.trim() !== "") {
        $.ajax({
            url: 'search_patients.php',  // El archivo PHP que manejará la búsqueda
            type: 'GET',
            data: { query: searchValue },  // Enviar el término de búsqueda al backend
            success: function (response) {
                // Limpiar tabla de resultados
                $('#resultTable').empty();

                // Convertir la respuesta JSON en un objeto de JavaScript
                const patients = response.results;

                if (patients.length > 0) {
                    // Mostrar los resultados en la tabla
                    patients.forEach(patient => {
                        // Determinar la clase de color de fondo según el estado
                        let rowClass = '';
                        if (patient.condition === 'Reserved') {
                            rowClass = 'bg-reserved';
                        } else if (patient.condition === 'Archival' || patient.active == 0) {
                            rowClass = 'bg-archival';
                        } else if (patient.condition === 'Occupied') {
                            rowClass = 'bg-occupied';
                        }

                        $('#resultTable').append(`
                            <tr class="${rowClass}">
                                <td>${patient.text}</td>
                                <td>${patient.facility_name}</td>
                                <td>${patient.unit_name}</td>
                                <td>${patient.room_name}</td>
                                <td>${patient.assigned_date || '-'}</td>
                                <td>${patient.change_date || '-'}</td>
                                <td>${patient.age}</td>
                                <td>${patient.sex}</td>
                                <td>${patient.pubpid}</td>
                                <td>${patient.insurance}</td>
                            </tr>
                        `);
                    });
                } else {
                    $('#resultTable').append(`
                        <tr>
                            <td colspan="9" class="text-center">No se encontraron resultados</td>
                        </tr>
                    `);
                }
            },
            error: function () {
                alert('Ocurrió un error durante la búsqueda.');
            }
        });
    }
}

// Convierte la fecha antes de hacer el orden (sort)
   // Aquí estamos "inyectando" la variable de PHP dentro de JavaScript
   var dateFormat = <?php echo json_encode($date_format); ?>;

console.log("Formato de fecha desde PHP:", dateFormat);

// Función para convertir las fechas según el formato global (que ahora está disponible en JavaScript)
function parseDateToTimestamp(dateString) {
    let parts, timestamp;

    if (dateString.includes('/')) {
        parts = dateString.split('/');
        if (parts.length === 3) {
            // Si el formato es MM/DD/YYYY
            if (dateFormat == 1) {
                timestamp = new Date(`${parts[2]}-${parts[0]}-${parts[1]}`).getTime();
            }
            // Si el formato es DD/MM/YYYY
            else if (dateFormat == 2) {
                timestamp = new Date(`${parts[2]}-${parts[1]}-${parts[0]}`).getTime();
            }
        }
    } 
    // Si la fecha ya está en formato YYYY-MM-DD
    else if (dateString.includes('-')) {
        timestamp = new Date(dateString).getTime();
    }

    return timestamp || 0; // Devuelve 0 si la fecha es inválida
}
function checkEnterKey(event) {
    if (event.key === "Enter") {
        // Llama a la función searchPatient() cuando se presiona la tecla Enter
        searchPatient();
    }
}
</script>
</body>
</html>
