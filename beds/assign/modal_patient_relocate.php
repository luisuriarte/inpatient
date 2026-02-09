<?php

$relocateReason = sqlStatement("SELECT title FROM list_options WHERE list_id = 'patient_relocation_reason'");

?>

<!-- Modal para Discharge del paciente -->
<div class="modal fade" id="relocateBedPatientModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="relocateBedPatientModalLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="relocateForm<?= $bedPatient['id'] ?>" action="save_patient_relocate.php" method="POST">
                    <input type="hidden" name="beds_patients_id" value="<?= $bedPatient['bp_id']; ?>">
                    <input type="hidden" name="from_id_beds_patients" value="<?= $fromIdBedsPatients; ?>">
                    <input type="hidden" name="from_bed_id" value="<?= $fromBedId; ?>">
                    <input type="hidden" name="from_room_id" value="<?= $fromRoomId; ?>">
                    <input type="hidden" name="from_unit_id" value="<?= $fromUnitId; ?>">
                    <input type="hidden" name="from_facility_id" value="<?= $fromFacilityId; ?>">
                    <!-- Campo oculto para el ID del paciente -->
                    <input type="hidden" name="patient_id" value="<?= $patientData['id'] ?>" />
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
                
                <div class="modal-header">
                    <h5 class="modal-title" id="relocateBedPatientModalLabel<?= $bedPatient['id'] ?>">
                        <?= xlt('Patient') ?> <?= htmlspecialchars($patientData['name']) ?> <?= xlt('Moves to this bed') ?>: <?= htmlspecialchars($bedPatient['bed_name']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <label for="relocate_reason" class="form-label"><?php echo xlt('Relocate Reason'); ?></label>
                        <select class="form-select" id="relocate_reason" name="relocate_reason">
                        <option value="" selected style="color: lightgray;"><?php echo xl('-- Select One --'); ?></option>
                            <?php while ($row = sqlFetchArray($relocateReason)): ?>
                                <option value="<?= htmlspecialchars($row['title']) ?>"><?= htmlspecialchars($row['title']) ?></option>
                            <?php endwhile; ?>
                        </select>

                    <!-- Notas -->
                    <div class="mb-3">
                        <label for="relocateNotes" class="form-label"><?= xlt('Notes'); ?></label>
                        <textarea class="form-control" id="relocateNotes" name="relocateNotes" rows="3"></textarea>
                    </div>
                    
                    <!-- Persona Responsable -->
                    <div class="section-title" style="background-color: #dcedc8; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <h4><?= xlt('Responsible Person'); ?></h4>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12 text-center">
                            <!-- Selección de Persona Responsable -->
                            <input type="text" id="autocompleteRelocate<?= $bedPatient['id'] ?>" placeholder="<?= xl('Type to search...'); ?>" autocomplete="off" style="width: 300px;">
                            <input type="hidden" name="responsible_user_id_relocate" id="responsibleRelocate<?= $bedPatient['id'] ?>">
                         </div>
                    </div>
                   
                     <!-- Nombre del operador -->
                    <div class="mb-3">
                        <label class="form-label"><?= xlt('Operator'); ?>:</label>
                        <span class="form-text"><?= htmlspecialchars($userFullName); ?></span>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                    <!-- Botón de Reserva -->
                    <button type="button" class="btn btn-primary" 
                            onclick="isResponsiblePersonSelected(
                                'responsibleRelocate<?= $bedPatient['id'] ?>', // ID del input de usuario responsable
                                'responsibleAlertModal<?= $bedPatient['id'] ?>', // ID del modal de alerta
                                'relocateForm<?= $bedPatient['id'] ?>' // ID del formulario de descarga
                            )">
                        <?= xlt('Relocate') ?>
                    </button>

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= xlt('Close') ?>
                    </button>
                </div>
        </div>
    </div>
</div>

<!-- Script JavaScript para Validación, Autocompletado y verifica responsable functions.js -->
<script>
    $(document).ready(function() {
        // Llamada a la función setupAutocomplete para el autocompletado
        setupAutocomplete(
            'autocompleteRelocate<?= $bedPatient['id'] ?>', 
            'relocateBedPatientModal<?= $bedPatient['id'] ?>', 
            'responsibleRelocate<?= $bedPatient['id'] ?>',
            '../../search_users.php'
        );
    });
</script>

