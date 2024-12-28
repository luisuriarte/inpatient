<?php

// Obtener las opciones para las restricciones
$dietaryRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_dietary_restrictions'");
$physicalRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_physical_restrictions'");
$sensoryRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_sensory_restrictions'");
$cognitiveRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_cognitive_restrictions'");
$behavioralRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_behavioral_restrictions'");
$otherRestrictions = sqlStatement("SELECT title FROM list_options WHERE list_id = 'inpatient_other_restrictions'");

// Consulta SQL para obtener los cuidados iniciales
$careOptions = sqlStatement("SELECT title, notes FROM list_options WHERE list_id = 'inpatient_care'");
echo $bedPatient['id'];
?>
<!-- Estilos adicionales -->
<style>

    #assignBedPatientModal {
        z-index: 1051; 
    }

    #alertModal {
        z-index: 1061; 
    }
 
    .modal-title {
        font-size: 1.75rem;
        font-weight: bold;
    }
    .section-title {
        font-size: 1.5rem;
        font-weight: bold;
        background-color: #ffe0b2;
        padding: 10px;
        border-radius: 5px;
        text-align: center;
        margin-bottom: 20px;
    }
</style>

<!-- Modal para asignar una cama a un paciente -->
<div class="modal fade" id="assignBedPatientModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="assignBedPatientModalLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="assignForm<?= $bedPatient['id'] ?>" action="save_bed_assign.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-center" id="assignBedPatientModalLabel">
                        <!-- Nombre del paciente y la cama como título principal -->
                        <?= xlt('Assign this Bed') ?> - <?= htmlspecialchars($bedPatient['bed_name']) ?> - <?= xlt('Patient') ?>: <?= htmlspecialchars($patient_name) ?>
                        <?php echo $bedPatient['id']; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    
                    <!-- Cuidados Iniciales -->
                    <div class="section-title" style="background-color: #e0f7fa; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <h4><?php echo xlt('Initial Care'); ?></h4>
                    </div>
                    <div class="row mb-3">
                    <div class="col-md-6">
                            <select class="form-select" id="care<?= $bedPatient['id'] ?>" name="patientCare" onchange="updatePatientCareTitle(<?= $bedPatient['id'] ?>)">
                                <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select Care --'); ?></option>
                                <?php while ($row = sqlFetchArray($careOptions)): ?>
                                    <option value="<?= "../images/" . htmlspecialchars($row['notes']) ?>" data-title="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <input type="hidden" name="patient_care_title" id="patientCareTitle<?= $bedPatient['id']; ?>">
                        </div>
                        <!-- El contenedor del ícono -->
                        <div class="col-md-6 text-center">
                            <div id="care-icon-container" class="mt-2" style="padding: 10px;">
                                <!-- Icono más grande -->
                                <img id="care-icon-<?= $bedPatient['id'] ?>" src="" alt="Care Icon" onerror="this.style.display='none';" style="display:none; width: 100px; height: 20px;" />
                            </div>
                        </div>
                    </div>
                    <!-- Restricciones -->
                    <div class="section-title" style="background-color: #ffe0b2; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <h4><?php echo xlt('Restrictions'); ?></h4>
                    </div>

                    <!-- Physical y Sensory Restrictions en la misma fila -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="physical_restrictions" class="form-label"><?php echo xlt('Physical Restrictions'); ?></label>
                            <select class="form-select" id="physical_restrictions" name="physical_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($physicalRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="sensory_restrictions" class="form-label"><?php echo xlt('Sensory Restrictions'); ?></label>
                            <select class="form-select" id="sensory_restrictions" name="sensory_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($sensoryRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Cognitive y Behavioral Restrictions en la misma fila -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cognitive_restrictions" class="form-label"><?php echo xlt('Cognitive Restrictions'); ?></label>
                            <select class="form-select" id="cognitive_restrictions" name="cognitive_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($cognitiveRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="behavioral_restrictions" class="form-label"><?php echo xlt('Behavioral Restrictions'); ?></label>
                            <select class="form-select" id="behavioral_restrictions" name="behavioral_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($behavioralRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Dietary y Others Restrictions en la misma fila -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="dietary_restrictions" class="form-label"><?php echo xlt('Dietary Restrictions'); ?></label>
                            <select class="form-select" id="dietary_restrictions" name="dietary_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($dietaryRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="other_restrictions" class="form-label"><?php echo xlt('Other Restrictions'); ?></label>
                            <select class="form-select" id="other_restrictions" name="other_restrictions">
                            <option value="" selected style="color: lightgray;"><?php echo xlt('-- Select One --'); ?></option>
                                <?php while ($row = sqlFetchArray($otherRestrictions)): ?>
                                    <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Campo oculto para el ID de la tabla beds_patients  -->
                    <input type="hidden" name="beds_patients_id" value="<?= $bedPatient['id']; ?>">
                    <!-- Campo oculto para el ID del paciente -->
                    <input type="hidden" name="patient_id" value="<?= $patient_id ?>" />
                    <!-- Campo oculto para el ID y datos de la cama -->
                    <input type="hidden" name="bed_id" value="<?= $bedPatient['bed_id'] ?>" />
                    <input type="hidden" name="bed_name" value="<?= $bedPatient['bed_name'] ?>" />
                    <input type="hidden" name="bed_status" value="<?= $bedPatient['bed_status']; ?>">
                    <input type="hidden" name="bed_type" value="<?= $bedPatient['bed_type']; ?>">
                    <input type="hidden" name="room_id" value="<?= $roomId; ?>">
                    <input type="hidden" name="room_name" value="<?= $roomName; ?>">
                    <input type="hidden" name="unit_id" value="<?= $unitId; ?>">
                    <input type="hidden" name="unit_name" value="<?= $unitName; ?>">
                    <input type="hidden" name="facility_id" value="<?= $facilityId; ?>">
                    <input type="hidden" name="facility_name" value="<?= $facilityName; ?>">
                    <input type="hidden" name="bed_action" value="<?= $bedAction; ?>">
                    <input type="hidden" name="background_card" value="<?= $backgroundPatientCard; ?>">
                                   
                    <!-- Persona Responsable -->
                    <div class="section-title" style="background-color: #dcedc8; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <h4><?php echo xlt('Responsible Person'); ?></h4>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12 text-center">
                            <!-- Selección de Persona Responsable -->
                            <input type="text" id="autocompleteAssign<?= $bedPatient['id'] ?>" placeholder="<?php echo xl('Type to search...'); ?>" autocomplete="off" style="width: 300px;">
                            <input type="hidden" name="responsible_user_id_assign" id="responsibleAssign<?= $bedPatient['id'] ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes<?= $bedPatient['id'] ?>" class="form-label"><?= xlt('Notes') ?></label>
                        <textarea class="form-control" name="notes" id="notes<?= $bedPatient['id'] ?>" rows="3"></textarea>
                    </div>
                </div>
                <?php echo xlt("User") . ": " . $userFullName . "<br>";?>
            </form>
                <div class="modal-footer">
                    <!-- Botón de Reserva -->
                    <button type="button" class="btn btn-primary" 
                        onclick="isResponsiblePersonSelected(
                            'responsibleAssign<?= $bedPatient['id'] ?>', // ID del input de usuario responsable
                            'responsibleAlertModal<?= $bedPatient['id'] ?>', // ID del modal de alerta
                            'assignForm<?= $bedPatient['id'] ?>' // ID del formulario de descarga
                        )">
                        <?= xlt('Assign') ?>
                    </button>

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= xlt('Close') ?>
                    </button>
                </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Evento que se dispara cuando se muestra el modal
    $('#assignBedPatientModal<?= $bedPatient['id'] ?>').on('shown.bs.modal', function () {

        // Verifica si el elemento #care existe en el DOM
        var $careSelect = $('#care<?= $bedPatient['id'] ?>');  // Asegúrate de que el ID es único
        if ($careSelect.length > 0) {
            // Quita cualquier evento previo y luego añade el listener al select
            $careSelect.off('change').on('change', function() {
                updateCareIcon();  // Llama a la función cuando cambie la selección
            });
        } else {
            console.error("Element #care not found in DOM");
        }
    });

    function updateCareIcon() {
        // Selecciona la opción elegida con jQuery
        var $selectedOption = $('#care<?= $bedPatient['id'] ?> option:selected');
        var selectedCareIcon = $selectedOption.val();  // Obtiene el valor de la opción seleccionada
        var selectedTitle = $selectedOption.data('title');  // Obtiene el atributo 'data-title'
        var $careIcon = $('#care-icon-<?= $bedPatient['id'] ?>');
        var $careTitle = $('#care-title-<?= $bedPatient['id'] ?>');

        if (!selectedCareIcon) {
            $careIcon.attr('src', '../images/critical_level_icon.svg');  // Ícono por defecto
            $careIcon.show();
            $careTitle.text('No care icon selected');
        } else {
            $careIcon.attr('src', selectedCareIcon);  // Establece el ícono seleccionado
            $careIcon.show();
            $careTitle.text(selectedTitle);  // Muestra el título correspondiente
        }
    }
});

// Script JavaScript para Validación, Autocompletado y verifica responsable functions.js -->
    $(document).ready(function() {
        // Llamada a la función setupAutocomplete para el autocompletado
        setupAutocomplete(
            'autocompleteAssign<?= $bedPatient['id'] ?>', 
            'assignBedPatientModal<?= $bedPatient['id'] ?>', 
            'responsibleAssign<?= $bedPatient['id'] ?>'
        );
    });


    function updatePatientCareTitle(bedPatientId) {
    var select = document.getElementById('care' + bedPatientId);
    var selectedOption = select.options[select.selectedIndex];
    var patientCareTitle = selectedOption.getAttribute('data-title');
    
    document.getElementById('patientCareTitle' + bedPatientId).value = patientCareTitle;
    }
</script>
