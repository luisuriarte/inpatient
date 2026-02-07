<!-- Modal para la Reserva de Cama -->
<div class="modal fade" id="reserveModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="reserveModalLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reserveForm<?= $bedPatient['id'] ?>" action="save_bed_reserve.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="reserveModalLabel<?= $bedPatient['id'] ?>">
                        <?= xlt('Reserve this Bed') ?> - <?= htmlspecialchars($bedPatient['bed_name']) ?> - <?= xlt('Patient') ?>: <?= htmlspecialchars($patient_name) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Notas de la Reserva -->
                    <div class="mb-3">
                        <label for="notes<?= $bedPatient['id'] ?>" class="form-label"><?= xlt('Notes') ?></label>
                        <textarea class="form-control" name="notes" id="notes<?= $bedPatient['id'] ?>" rows="3"></textarea>
                    </div>
                    
                    <!-- Selección de Persona Responsable -->
                    <h6><?= xlt('Responsible Person') ?></h6>
                    <div class="mb-3">
                    <input type="text" id="autocompleteReserve<?= $bedPatient['id'] ?>" placeholder="<?php echo xl('Type to search...'); ?>" autocomplete="off" style="width: 300px;">
                    <input type="hidden" name="responsible_user_id_reserve" id="responsibleReserve<?= $bedPatient['id'] ?>">
                    </div>

                    <!-- Campo oculto para el ID de la cama -->
                    <input type="hidden" name="bed_id" value="<?= $bedPatient['bed_id'] ?>" />
                    <!-- Campo oculto para el ID del paciente (puedes obtenerlo de la sesión o pasarlo aquí si es necesario) -->
                    <input type="hidden" name="patient_id" value="<?= $patient_id ?>" />
                     <!-- Campo oculto para el ID de la tabla beds_patients  -->
                    <input type="hidden" name="beds_patients_id" value="<?= $bedPatient['id']; ?>">
                    <input type="hidden" name="room_id" value="<?= $roomId; ?>">
                    <input type="hidden" name="room_name" value="<?= $roomName; ?>">
                    <input type="hidden" name="unit_id" value="<?= $unitId; ?>">
                    <input type="hidden" name="unit_name" value="<?= $unitName; ?>">
                    <input type="hidden" name="facility_id" value="<?= $facilityId; ?>">
                    <input type="hidden" name="facility_name" value="<?= $facilityName; ?>">
                    <input type="hidden" name="bed_action" value="<?= $bedAction; ?>">
                    <input type="hidden" name="background_card" value="<?= $backgroundPatientCard; ?>">                        
                </div>
                <?php echo xlt("User") . ": " . $userFullName . "<br>";?>
                <div class="modal-footer">
                    <!-- Botón de Reserva -->
                      <button type="button" class="btn btn-primary" 
                            onclick="isResponsiblePersonSelected(
                                'responsibleReserve<?= $bedPatient['id'] ?>', // ID del input de usuario responsable
                                'responsibleAlertModal<?= $bedPatient['id'] ?>', // ID del modal de alerta
                                'reserveForm<?= $bedPatient['id'] ?>' // ID del formulario de descarga
                            )">
                      <?= xlt('Reserve') ?>
                    </button>

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <?= xlt('Close') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script JavaScript para Validación, Autocompletado y verifica responsable functions.js -->
<script>
    $(document).ready(function() {
        // Llamada a la función setupAutocomplete para el autocompletado
        setupAutocomplete(
            'autocompleteReserve<?= $bedPatient['id'] ?>', 
            'reserveModal<?= $bedPatient['id'] ?>', 
            'responsibleReserve<?= $bedPatient['id'] ?>'
        );
    });
</script>

