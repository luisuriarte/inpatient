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
    <!-- Archivo de estilos principal -->
    <link rel="stylesheet" href="../../styles.css">
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
        .filters-container {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
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
        .bg-outpatient {
            background-color: #e8f5e8; /* Verde suave para ambulatorio */
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
        .reference-outpatient {
            background-color: #e8f5e8; /* Verde suave para ambulatorio */
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
    
    <!-- Filtros -->
    <div class="row filters-container">
        <div class="col-md-12">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <label for="fromDate" class="col-form-label"><?php echo xlt('From Date'); ?></label>
                </div>
                <div class="col-auto">
                    <input type="date" id="fromDate" class="form-control">
                </div>
                <div class="col-auto">
                    <label for="toDate" class="col-form-label"><?php echo xlt('To Date'); ?></label>
                </div>
                <div class="col-auto">
                    <input type="date" id="toDate" class="form-control">
                </div>
                <div class="col-auto">
                    <label for="statusFilter" class="col-form-label"><?php echo xlt('Status'); ?></label>
                </div>
                <div class="col-auto">
                    <select id="statusFilter" class="form-select">
                        <option value=""><?php echo xlt('All Status'); ?></option>
                        <option value="admitted"><?php echo xlt('Admitted'); ?></option>
                        <option value="preadmitted"><?php echo xlt('Preadmitted'); ?></option>
                        <option value="discharged"><?php echo xlt('Discharged'); ?></option>
                        <option value="transferred"><?php echo xlt('Transferred'); ?></option>
                        <option value="outpatient"><?php echo xlt('Outpatient'); ?></option>
                    </select>
                </div>
                <div class="col-auto">
                    <!-- Switch para incluir pacientes ambulatorios -->
                    <div class="custom-slider-switch d-flex align-items-center">
                        <input type="checkbox" id="includeOutpatients" name="include_outpatients">
                        <label for="includeOutpatients" class="ms-2 mb-0"><?php echo xlt('Include Outpatients'); ?></label>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary" onclick="applyFilters()"><?php echo xlt('Apply Filters'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row search-results">
       <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th onclick="sortTable('name')"><?php echo xlt('Patient Name'); ?><span id="sortIndicatorName"></span></th>
                            <th onclick="sortTable('facility')"><?php echo xlt('Facility'); ?><span id="sortIndicatorFacility"></span></th>
                            <th onclick="sortTable('unit')"><?php echo xlt('Unit'); ?><span id="sortIndicatorUnit"></span></th>
                            <th onclick="sortTable('room')"><?php echo xlt('Room'); ?><span id="sortIndicatorRoom"></span></th>
                            <th onclick="sortTable('date')"><?php echo xlt('Date'); ?><span id="sortIndicatorDate"></span></th>
                            <th onclick="sortTable('status')"><?php echo xlt('Status'); ?><span id="sortIndicatorStatus"></span></th>
                            <th onclick="sortTable('age')"><?php echo xlt('Age'); ?><span id="sortIndicatorAge"></span></th>
                            <th onclick="sortTable('sex')"><?php echo xlt('Sex'); ?><span id="sortIndicatorSex"></span></th>
                            <th onclick="sortTable('pubpid')"><?php echo xlt('External Id'); ?><span id="sortIndicatorPubpid"></span></th>
                            <th onclick="sortTable('insurance')"><?php echo xlt('Insurance'); ?><span id="sortIndicatorInsurance"></span></th>
                            <th class="text-center"><?php echo xlt('Actions'); ?></th>
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
            <div class="reference-item">
                <span class="reference-box reference-outpatient"></span> <?php echo xlt('Outpatient'); ?>
            </div>
        </div>
    </div>
    <!-- Botón para ir a Principal Board -->
    <a href="assign.php" class="btn btn-secondary mt-4">
        <i class="fas fa-home"></i> <?php echo xl('Principal Board'); ?>
    </a>
</div>
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-clock-rotate-left"></i> Historial: <span id="historyPatientName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Razón</th>
                                <th>Desde</th>
                                <th>Hacia</th>
                                <th>Responsable</th>
                                <th>Notas</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS y jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let sortDirection = {}; // Almacenar la dirección de orden para cada columna
    let originalPatients = [];
    let filteredPatients = [];
    let dateFormat = <?php echo json_encode($date_format); ?>;

    window.onload = function() {
        // Establecer fechas por defecto
        const today = new Date();
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(today.getDate() - 7);
        
        // Formatear fechas en formato YYYY-MM-DD
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        
        // Establecer valores por defecto en los inputs
        document.getElementById('toDate').value = formatDate(today);
        document.getElementById('fromDate').value = formatDate(oneWeekAgo);
        
        // Cargar directamente los pacientes internados con su último movimiento al cargar la página
        loadAdmittedPatients();
    };

    // Cargar pacientes internados con su último movimiento
    function loadAdmittedPatients() {
        $.ajax({
            url: 'search_patients.php',
            type: 'GET',
            data: {}, // Sin parámetros carga pacientes internados por defecto
            success: function (response) {
                console.log("Datos recibidos:", response);
                originalPatients = response.results;
                filteredPatients = [...originalPatients];
                displayPatients(filteredPatients);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                console.error('Detalle:', xhr.responseText);
                alert('Error al cargar pacientes: ' + error);
            }
        });
    }

    // Reemplazar searchPatient()
    function searchPatient() {
        const searchValue = $('#searchInput').val();
        console.log("Búsqueda:", searchValue);
        
        if (searchValue.trim() !== "") {
            $.ajax({
                url: 'search_patients.php',
                type: 'GET',
                data: { query: searchValue },
                success: function (response) {
                    console.log("Resultados:", response);
                    originalPatients = response.results;
                    // Mostrar directamente los resultados de la búsqueda sin aplicar filtros adicionales
                    filteredPatients = [...originalPatients];
                    displayPatients(filteredPatients);
                    $('#searchInput').val(''); // Limpiar campo
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error en búsqueda: ' + error);
                }
            });
        } else {
            loadAdmittedPatients(); // Volver a lista completa
        }
    }

    // SIMPLIFICAR applyFilters() - Ya no hace filtrado, solo llama al servidor
    function applyFilters() {
        const fromDate = $('#fromDate').val();
        const toDate = $('#toDate').val();
        const statusFilter = $('#statusFilter').val();
        const includeOutpatients = $('#includeOutpatients').is(':checked');

        console.log("Aplicando filtros:", {fromDate, toDate, statusFilter, includeOutpatients});

        // Llamar al servidor con filtros
        $.ajax({
            url: 'search_patients.php',
            type: 'GET',
            data: {
                query: '', // Vacío para mostrar todos
                from_date: fromDate,
                to_date: toDate,
                status: statusFilter,
                include_outpatients: includeOutpatients
            },
            success: function (response) {
                originalPatients = response.results;
                filteredPatients = [...originalPatients];
                displayPatients(filteredPatients);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    // Función para mostrar pacientes en la tabla
    function displayPatients(patients) {
        // Limpiar tabla de resultados
        $('#resultTable').empty();

        if (patients.length > 0) {
            // Mostrar los resultados en la tabla
            patients.forEach(patient => {
                // Determinar la clase de color de fondo según el estado
                let rowClass = '';
                let statusDisplay = 'Outpatient'; // Valor por defecto
                
                // Usar el último movimiento para determinar el estado si está disponible
                let movementType = patient.last_movement_type || patient.status;
                
                if (movementType === 'preadmitted') {
                    rowClass = 'bg-reserved';
                    statusDisplay = 'Preadmitted';
                } else if (movementType === 'admission' || movementType === 'admitted') {
                    rowClass = 'bg-occupied';
                    statusDisplay = 'Admitted';
                } else if (movementType === 'relocation') {
                    rowClass = 'bg-occupied';
                    statusDisplay = 'Transfer';
                } else if (movementType === 'discharged' || movementType === 'discharge') {
                    rowClass = 'bg-archival';
                    statusDisplay = 'Discharged';
                } else if (movementType === 'bed_reservation') {
                    rowClass = 'bg-reserved';
                    statusDisplay = 'Reservation';
                } else if (movementType === 'bed_release') {
                    rowClass = 'bg-archival';
                    statusDisplay = 'Released';
                } else {
                    // Si no tiene estado o es un paciente ambulatorio
                    rowClass = 'bg-outpatient';
                    statusDisplay = 'Outpatient';
                }

                $('#resultTable').append(`
                    <tr class="${rowClass}">
                        <td>${patient.text}</td>
                        <td>${patient.facility_name || ''}</td>
                        <td>${patient.unit_name || ''}</td>
                        <td>${patient.room_name || ''}</td>
                        <td>${patient.assigned_date || '-'}</td>
                        <td>${statusDisplay}</td>
                        <td>${patient.age || ''}</td>
                        <td>${patient.sex || ''}</td>
                        <td>${patient.pubpid || ''}</td>
                        <td>${patient.insurance || ''}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info" onclick="viewHistory(${patient.pid}, '${patient.text}')" title="Ver Historial">
                                <i class="fas fa-history"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        } else {
            $('#resultTable').append(`
                <tr>
                    <td colspan="11" class="text-center">No se encontraron pacientes con los filtros aplicados</td>
                </tr>
            `);
        }
    }


    // Convierte la fecha antes de hacer el orden (sort)
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
                case 'date':
                    // Parsear fechas para ordenamiento
                    cellA = parseDateToTimestamp(a.cells[4].textContent.trim());
                    cellB = parseDateToTimestamp(b.cells[4].textContent.trim());
                    break;
                case 'status':
                    cellA = a.cells[5].textContent.toLowerCase();
                    cellB = b.cells[5].textContent.toLowerCase();
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
        const indicators = ['name', 'facility', 'unit', 'room', 'date', 'status', 'age', 'sex', 'pubpid', 'insurance'];
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

    // Función para convertir las fechas según el formato global
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

    function viewHistory(pid, patientName) {
        // 1. Poner el nombre del paciente en el título del modal
        $('#historyPatientName').text(patientName);

        // 2. Limpiar la tabla y mostrar mensaje de carga
        $('#historyTableBody').html('<tr><td colspan="7" class="text-center">Cargando historial...</td></tr>');

        // 3. Abrir el modal manualmente
        var myModal = new bootstrap.Modal(document.getElementById('historyModal'));
        myModal.show();

        // 4. Llamada AJAX para obtener los datos
        $.ajax({
            url: 'get_patient_history.php',
            type: 'GET',
            data: { pid: pid },
            success: function(response) {
                console.log("Respuesta del historial:", response);
                let html = '';
                if (response && Array.isArray(response) && response.length > 0) {
                    response.forEach(move => {
                        // Determinar el color del badge según el tipo de movimiento
                        let movementColor = 'secondary'; // Valor por defecto
                        
                        switch(move.movement_type) {
                            case 'admission':
                                movementColor = 'success'; // Verde para admisión
                                break;
                            case 'relocation':
                                movementColor = 'warning'; // Amarillo para reubicación
                                break;
                            case 'discharge':
                                movementColor = 'danger'; // Rojo para alta
                                break;
                            case 'bed_reservation':
                                movementColor = 'info'; // Azul para reserva
                                break;
                            case 'bed_release':
                                movementColor = 'dark'; // Oscuro para liberación
                                break;
                            default:
                                movementColor = 'secondary'; // Gris para otros
                        }
                        
                        html += `
                        <tr>
                            <td>${move.move_date || ''}</td>
                            <td><span class="badge bg-${movementColor}">${move.movement_type || ''}</span></td>
                            <td>${move.reason || ''}</td>
                            <td>${move.bed_from || '-'}<br><small>${move.from_location || ''}</small></td>
                            <td>${move.bed_to || '-'}<br><small>${move.to_location || ''}</small></td>
                            <td>${move.user || ''}</td>
                            <td><small>${move.notes || ''}</small></td>
                        </tr>`;
                    });
                } else {
                    html = '<tr><td colspan="7" class="text-center">No hay movimientos registrados para este paciente.</td></tr>';
                }
                $('#historyTableBody').html(html);
            },
            error: function(xhr, status, error) {
                console.error("Error en la solicitud AJAX:", error);
                console.error("Estado:", status);
                console.error("Respuesta del servidor:", xhr.responseText);
                $('#historyTableBody').html(`<tr><td colspan="7" class="text-center text-danger">Error al conectar con el servidor: ${error}</td></tr>`);
            }
        });
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