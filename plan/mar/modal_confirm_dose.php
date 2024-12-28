<?php
// Incluir funciones y variables globales
require_once("../../functions.php");
require_once('../../../interface/globals.php');

// Verificar si se pasó el `supply_id`
$supply_id = $_POST['supply_id'] ?? null;
if (!$supply_id) {
    die(xlt('No supply ID provided.'));
}

// Obtener detalles de la dosis y del medicamento
$dose_details = getDoseDetails($supply_id);
$schedule_id = $dose_details['schedule_id'];
$medications_text = getMedicationsDetails($schedule_id);

// Obtener el nombre completo del usuario autenticado
$userId = $_SESSION['authUserID'];
$userFullName = getUserFullName($userId);

// Formatear la fecha y hora actual para la infusión
$infusion_datetime = date('Y-m-d\TH:i'); // Formato ISO para el input de fecha y hora
?>

<!-- Cuerpo de contenido de confirmación de dosis -->
<h5><?php echo xlt("Confirm Dose"); ?></h5>
<p><strong><?php echo xlt("Patient"); ?>:</strong> <?php echo text($dose_details['patient_name']); ?></p>
<p><strong><?php echo xlt("Dose"); ?>:</strong> <?php echo text($dose_details['dose_number'] . "/" . $dose_details['max_dose']); ?></p>
<p><strong><?php echo xlt("Medication"); ?>:</strong> <?php echo text($medications_text); ?></p>

<div class="form-group mb-3">
    <label for="infusion_datetime"><?php echo xlt("Infusion Date and Time"); ?>:</label>
    <input type="datetime-local" id="infusion_datetime" name="infusion_datetime" class="form-control" value="<?php echo attr($infusion_datetime); ?>" reqired>
</div>

<p><strong><?php echo xlt("Confirmed by"); ?>:</strong> <?php echo text($userFullName); ?></p>

<div class="form-group">
    <label for="dose_note"><?php echo xlt("Note"); ?>:</label>
    <textarea id="dose_note" name="dose_note" class="form-control" rows="3"></textarea>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" onclick="saveConfirmedDose(<?php echo attr($supply_id); ?>)"><?php echo xlt("Save"); ?></button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo xlt("Cancel"); ?></button>
</div>

<script>
// Función para guardar la dosis confirmada
function saveConfirmedDose(supplyId) {
    const infusionDatetime = document.getElementById('infusion_datetime').value;
    const doseNote = document.getElementById('dose_note').value;

    $.ajax({
        url: 'save_confirmed_dose.php',
        type: 'POST',
        data: {
            supply_id: supplyId,
            infusion_datetime: infusionDatetime,
            supplied_by: '<?php echo addslashes($userFullName); ?>',
            dose_note: doseNote
        },
        success: function(response) {
            alert('Dose confirmed successfully');
            $('#marActionsModal').modal('hide'); // Cierra el modal
            location.reload(); // Refresca la página para actualizar el gráfico
        },
        error: function() {
            alert('Error confirming dose');
        }
    });
}
</script>
