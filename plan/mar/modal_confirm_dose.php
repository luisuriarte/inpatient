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

// Obtener usuarios autorizados para la lista desplegable
$users = sqlStatement("SELECT id, CONCAT(u.lname, ', ', u.fname, IF(u.mname IS NOT NULL AND u.mname != '', CONCAT(' ', u.mname), '')) AS full_name FROM users AS u WHERE authorized = 1");

// Formatear la fecha y hora actual para la infusión (formato yyyy-mm-dd HH:MM)
$infusion_datetime = date('Y-m-d H:i');
?>

<!-- Cuerpo de contenido de confirmación de dosis -->
<h5><?php echo xlt("Confirm Dose"); ?></h5>
<p><strong><?php echo xlt("Patient"); ?>:</strong> <?php echo text($dose_details['patient_name']); ?></p>
<p><strong><?php echo xlt("Dose"); ?>:</strong> <?php echo text($dose_details['dose_number'] . "/" . $dose_details['max_dose']); ?></p>
<p><strong><?php echo xlt("Medication"); ?>:</strong> <?php echo text($medications_text); ?></p>

<div class="form-group mb-3">
    <label for="infusion_datetime"><?php echo xlt("Infusion Date and Time"); ?>:</label>
    <input type="text" 
           id="infusion_datetime" 
           name="infusion_datetime" 
           class="form-control datepicker" 
           value="<?php echo attr($infusion_datetime); ?>"
           title="<?php echo xla('yyyy-mm-dd HH:MM'); ?>"
           required>
</div>

<div class="form-group mb-3">
    <label for="supplied_by"><?php echo xlt("Supplied By"); ?>:</label>
    <select id="supplied_by" name="supplied_by" class="form-control" required>
        <option value=""><?php echo xlt("Select User"); ?></option>
        <?php while ($row = sqlFetchArray($users)): ?>
            <option value="<?php echo attr($row['id']); ?>"><?php echo text($row['full_name']); ?></option>
        <?php endwhile; ?>
    </select>
</div>

<div class="form-group">
    <label for="dose_note"><?php echo xlt("Note"); ?>:</label>
    <textarea id="dose_note" name="dose_note" class="form-control" rows="3"></textarea>
</div>

<p><strong><?php echo xlt("Operator"); ?>:</strong> <?php echo text($_SESSION['authUser']); ?></p>

<div class="d-flex justify-content-between mt-3 pt-3 border-top">
    <button type="button" class="btn btn-secondary" onclick="closeMarModal()"><?php echo xlt("Cancel"); ?></button>
    <button type="button" class="btn btn-primary" onclick="saveConfirmedDose(<?php echo attr($supply_id); ?>)"><?php echo xlt("Save"); ?></button>
</div>
