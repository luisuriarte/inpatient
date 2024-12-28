<!-- Modal para mostrar la información del paciente -->
<div class="modal fade" id="patientInfoModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="patientInfoLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <!-- Icono de sexo -->
                <?php if ($bedPatient['bed_patient_sex'] === 'Male'): ?>
                    <img src="../images/male_icon.svg" alt="Male Icon" class="me-2" style="width: 40px; height: 40px;">
                <?php elseif ($bedPatient['bed_patient_sex'] === 'Female'): ?>
                    <img src="../images/female_icon.svg" alt="Female Icon" class="me-2" style="width: 40px; height: 40px;">
                <?php else: ?>
                    <img src="../images/non_binary_icon.svg" alt="Non-Binary Icon" class="me-2" style="width: 40px; height: 40px;">
                <?php endif; ?>
                <h5 class="modal-title" id="patientInfoLabel<?= $bedPatient['bed_patient_id'] ?>"><?php echo xlt('Patient Information'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Contenedor de tarjetas centrado con fila -->
            <div class="row justify-content-center mb-4">
                <!-- Contenedor de columnas para centrar las tarjetas -->
                <div class="col-8 d-flex">
                    <!-- Tarjeta de imagen a la izquierda -->
                    <div class="card-body p-2 d-flex align-items-center justify-content-center">
                        <img data-bind="attr: {src: patient_picture}" 
                            class="img-thumbnail" 
                            width="80" 
                            height="80" 
                            onerror="this.src = '../images/default_patient.svg'" 
                            src="/controller.php?document&amp;retrieve&amp;patient_id=<?php echo $bedPatient['bed_patient_id']; ?>&amp;document_id=-1&amp;as_file=false&amp;original_file=true&amp;disable_exit=false&amp;show_original=true&amp;context=patient_picture">
                    </div>
                    <?php
                    // Dentro de modal_patient_info.php
                    //var_dump($bedPatient);
                    ?> 
            <div class="modal-body">
                    <!-- Tarjeta de información principal -->
                    <div class="card compact-card flex-grow-1" style="background-color: #f8f9fa;">
                        <div class="card-body p-2 text-center">
                            <h5 class="card-title mb-1">
                                <?= htmlspecialchars($bedPatient['bed_patient_name']) ?>
                            </h5>
                            <p class="card-text mb-1">
                                <strong><?php echo xlt('External ID'); ?>:</strong> <?= htmlspecialchars($bedPatient['bed_patient_dni']) ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong><?php echo xlt('Age'); ?>:</strong> <?= htmlspecialchars($bedPatient['bed_patient_age']) ?> <?php echo xlt('Years'); ?>
                            </p>
                            <p class="card-text mb-1">
                                <strong><?php echo xlt('Insurance'); ?>:</strong> <?= htmlspecialchars($bedPatient['bed_patient_insurance_name']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Contenido dividido en dos columnas -->
                <div class="row">
                    <!-- Columna izquierda con tarjetas actuales -->
                    <div class="col-md-6">
                        <!-- Tarjeta de Medicamentos -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Medications'); ?></h6>
                                <!-- Mostrar medicamentos -->
                                <?php 
                                $medications = array_filter(getPatientConditions($bedPatient['bed_patient_id']), function($condition) {
                                    return $condition['type'] === 'medication';
                                });
                                if (!empty($medications)) {
                                    foreach ($medications as $medication) {
                                        echo "<li class='list-group-item'>";
                                        foreach ($medication as $key => $value) {
                                            if (!empty($value) && $key !== 'type') {
                                                echo xlt($value) . "<br>";
                                            }
                                        }
                                        echo "</li>";
                                    }
                                } else {
                                    echo "<li class='list-group-item'>No hay medicamentos registrados.</li>";
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Alergias -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Allergies'); ?></h6>
                                <!-- Mostrar alergias -->
                                <?php 
                                $allergies = array_filter(getPatientConditions($bedPatient['bed_patient_id']), function($condition) {
                                    return $condition['type'] === 'allergy';
                                });
                                if (!empty($allergies)) {
                                    foreach ($allergies as $allergy) {
                                        echo "<li class='list-group-item'>";
                                        foreach ($allergy as $key => $value) {
                                            if (!empty($value) && $key !== 'type') {
                                                echo xlt($value) . "<br>";
                                            }
                                        }
                                        echo "</li>";
                                    }
                                } else {
                                    echo "<li class='list-group-item'>" . xl('There are no allergies recorded') . "</li>";
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Problemas Médicos -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Medical Problems'); ?></h6>
                                <!-- Mostrar problemas médicos -->
                                <?php 
                                $medicalProblems = array_filter(getPatientConditions($bedPatient['bed_patient_id']), function($condition) {
                                    return $condition['type'] === 'medical_problem';
                                });
                                if (!empty($medicalProblems)) {
                                    foreach ($medicalProblems as $problem) {
                                        echo "<li class='list-group-item'>";
                                        foreach ($problem as $key => $value) {
                                            if (!empty($value) && $key !== 'type') {
                                                echo xlt($value) . "<br>";
                                            }
                                        }
                                        echo "</li>";
                                    }
                                } else {
                                    echo "<li class='list-group-item'>" . xl('There are no recorded cares.') . "</li>";
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Cuidados del Paciente -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2 text-center">
                                <h6 class="card-title"><?php echo xlt('Medical Care'); ?></h6>
                                <?php if (isset($bedPatient['bed_patient_care']) && !empty($bedPatient['bed_patient_care']['care_title'])): ?>
                                    <!-- Mostrar el título y el icono si existen -->
                                    <?php if (!empty($bedPatient['bed_patient_care']['care_icon'])): ?>
                                        <img src="<?= htmlspecialchars($bedPatient['bed_patient_care']['care_icon']) ?>" class="care-icon card-icon mb-1" alt="Care Icon">
                                    <?php else: ?>
                                        <!-- Mostrar un icono por defecto si no existe un icono específico -->
                                        <img src="../images/default_care_icon.svg" class="care-icon card-icon mb-1" alt="Default Care Icon">
                                    <?php endif; ?>
                                    
                                    <!-- Mostrar el título del cuidado del paciente -->
                                    <p class="mb-0 fw-bold"><?= htmlspecialchars(xlt($bedPatient['bed_patient_care']['care_title'])) ?></p>
                                <?php else: ?>
                                    <?php echo 'Salida: ';
                                        var_dump(getBedPatientCare($bedPatient['id'], $bedPatient['patient_id']));
                                    ?>
                                    <!-- Mensaje si no hay cuidados registrados -->
                                    <p class="mb-0"><?php echo xlt('There are no recorded cares'); ?>.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Columna derecha con las restricciones -->
                    <div class="col-md-6">
                        <h6 class="mb-2 text-center"><?php echo xlt('Known Restrictions'); ?></h6>

                        <!-- Tarjeta de Restricciones Físicas -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('physical Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_physical_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_physical_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Restricciones Sensoriales -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Sensory Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_sensory_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_sensory_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Restricciones Cognitivas -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Cognitive Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_cognitive_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_cognitive_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Restricciones Conductuales -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Behavioral Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_behavioral_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_behavioral_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tarjeta de Restricciones Dietéticas -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Dietary Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_dietary_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_dietary_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>


                        <!-- Tarjeta de Otras Restricciones -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Other Restrictions'); ?></h6>
                                <?php if (!empty($bedPatient['inpatient_other_restrictions'])): ?>
                                    <!-- Mostrar el contenido en negrita con color personalizado -->
                                    <p class="card-text fw-bold" style="color: #007bff;">
                                        <?= xlt(htmlspecialchars($bedPatient['inpatient_other_restrictions'])); ?>
                                    </p>
                                <?php else: ?>
                                    <!-- Mostrar "No data available" en gris oscuro si no hay datos -->
                                    <p class="card-text text-muted"><?= xlt('No data available'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
