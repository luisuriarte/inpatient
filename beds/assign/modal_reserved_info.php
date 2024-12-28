<!-- Modal para mostrar la información de la reserva -->
<div class="modal fade" id="reserveInfoModal<?= $bedPatient['id'] ?>" tabindex="-1" aria-labelledby="reserveInfoModalLabel<?= $bedPatient['id'] ?>" aria-hidden="true">
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
                <h5 class="modal-title" id="reserveInfoLabel<?= $bedPatient['bed_patient_id'] ?>"><?php echo xlt('Pre-Admitted'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Mostrar Paciente -->
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
                    <!-- Tarjeta de información principal -->
                    <div class="card compact-card flex-grow-1" style="background-color: #f8f9fa;">
                        <div class="card-body p-2 text-center">
                            <h5 class="card-title mb-1">
                                <?= htmlspecialchars($bedPatient['bed_patient_name']) ?> <?= htmlspecialchars($bedPatient['bed_patient_firstname']) ?>
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
                        <!-- Mostrar Notas -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Notes'); ?></h6>
                                <?php echo htmlspecialchars($bedPatient['bed_notes']); ?>
                            </div>
                        </div>
                        
                        <!-- Mostrar Responsable -->
                        <?php $ResponsibleuserFullName = getUserFullName($bedPatient['responsible_user_id']); ?>
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Responsible'); ?></h6>
                                <?php echo htmlspecialchars($ResponsibleuserFullName); ?>
                            </div>
                        </div>

                        <!-- Mostrar Operador -->
                        <div class="card mb-2 compact-card" style="background-color: #f8f9fa;">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Operator'); ?></h6>
                                <?php echo htmlspecialchars($bedPatient['user_modif']); ?>
                            </div>
                        </div>

                        <!-- Mostrar Fecha y Hora del Movimiento -->
                        <div class="card mb-2 compact-card">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo xlt('Booked since'); ?></h6>
                                <?php echo oeTimestampFormatDateTime(strtotime($bedPatient['datetime_modif'])); ?>
                            </div>
                        </div>
                    </div>
                    <!-- Columna derecha con Problemas -->
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
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- Botón para cerrar el modal -->
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= xlt('Close') ?></button>
            </div>
        </div>
    </div>
</div>
