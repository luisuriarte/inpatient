<!-- Modal para Discharge del paciente -->
<div class="modal fade" id="modalBedRelease<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="modalBedReleaseLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="releaseForm<?= $bedPatient['id'] ?>" action="save_bed_release.php" method="POST">
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
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBedReleaseLabel<?= $bedPatient['id'] ?>">
                        <?= xlt('Bed Release') ?>: <?= htmlspecialchars($bedPatient['bed_patient_name']) ?> - <?= xlt('Bed') ?>: <?= htmlspecialchars($bedPatient['bed_name']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Notas -->
                    <div class="mb-3">
                        <label for="releaseNotes" class="form-label"><?= xlt('Notes'); ?></label>
                        <textarea class="form-control" id="releaseNotes" name="releaseNotes" rows="3"></textarea>
                    </div>
                    
                    <!-- Persona Responsable -->
                    <div class="section-title" style="background-color: #dcedc8; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 20px;">
                        <h4><?= xlt('Responsible Person'); ?></h4>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12 text-center">
                            <!-- Selección de Persona Responsable -->
                            <input type="text" id="autocompleteRelease<?= $bedPatient['id'] ?>" placeholder="<?= xl('Type to search...'); ?>" autocomplete="off" style="width: 300px;">
                            <input type="hidden" name="responsible_user_id_release" id="responsibleRelease<?= $bedPatient['id'] ?>">
                         </div>
                    </div>
                 
                    <!-- Nombre del operador -->
                    <div class="mb-3">
                        <label class="form-label"><?= xlt('Operator'); ?>:</label>
                        <span class="form-text"><?= htmlspecialchars($_SESSION['authUser']); ?></span>
                    </div>
                </div>
            </form>
            <div class="modal-footer">
                    <!-- Botón de Reserva -->
                    <button type="button" class="btn btn-primary" 
                            onclick="isResponsiblePersonSelected(
                                'responsibleRelease<?= $bedPatient['id'] ?>', // ID del input de usuario responsable
                                'responsibleAlertModal<?= $bedPatient['id'] ?>', // ID del modal de alerta
                                'releaseForm<?= $bedPatient['id'] ?>' // ID del formulario de descarga
                            )">
                        <?= xlt('Release') ?>
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
            'autocompleteRelease<?= $bedPatient['id'] ?>', 
            'modalBedRelease<?= $bedPatient['id'] ?>', 
            'responsibleRelease<?= $bedPatient['id'] ?>'
        );
    });
</script>

