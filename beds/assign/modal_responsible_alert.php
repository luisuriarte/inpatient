<!-- Modal de Alerta si no se selecciona Persona Responsable -->
<div class="modal fade" id="responsibleAlertModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="responsibleAlertModalLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responsibleAlertModalLabel<?= $bedPatient['id'] ?>"><?= xlt('Warning') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?= xlt('You must select a responsible person.') ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= xlt('Close') ?></button>
            </div>
        </div>
    </div>
</div>

