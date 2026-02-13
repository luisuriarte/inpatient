-- =====================================================
-- Script SQL para Funcionalidad de Suspensión de Órdenes
-- =====================================================

-- 1. Agregar campos de suspensión a prescriptions_supply (si no existen)
-- Nota: El campo 'status' ya existe y puede contener 'Suspended'
-- Solo agregamos campos de auditoría si no existen

ALTER TABLE prescriptions_supply 
ADD COLUMN IF NOT EXISTS suspended_at DATETIME NULL COMMENT 'Fecha y hora de suspensión',
ADD COLUMN IF NOT EXISTS suspended_by INT(11) NULL COMMENT 'ID del usuario que suspendió',
ADD COLUMN IF NOT EXISTS suspension_reason TEXT NULL COMMENT 'Razón de la suspensión';

-- 2. Verificar que los campos de suspensión existan en prescriptions_schedule
-- Estos campos ya están en la definición de la tabla según inpatient.sql
-- suspended_reason, suspended_by, suspension_datetime

-- 3. Crear tabla de auditoría para prescripciones (opcional pero recomendado)
CREATE TABLE IF NOT EXISTS `prescriptions_audit_log` (
  `log_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` INT(11) NOT NULL COMMENT 'FK a prescriptions_schedule',
  `action_type` VARCHAR(50) NOT NULL COMMENT 'SUSPEND, MODIFY, CANCEL, REACTIVATE',
  `action_by` INT(11) NOT NULL COMMENT 'ID del usuario que realizó la acción',
  `action_datetime` DATETIME NOT NULL,
  `action_details` TEXT NULL COMMENT 'Detalles en formato JSON',
  PRIMARY KEY (`log_id`),
  KEY `idx_schedule_id` (`schedule_id`),
  KEY `idx_action_datetime` (`action_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Registro de auditoría para cambios en prescripciones';

-- 4. Crear list_options para razones de suspensión/discontinuación
-- Primero crear la lista principal si no existe
INSERT INTO list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, activity)
VALUES ('lists', 'reason_discontinue_medication', 'Medication Discontinuation Reasons', 1, 0, 0, '', '', '', 1)
ON DUPLICATE KEY UPDATE title = 'Medication Discontinuation Reasons';

-- Insertar razones comunes de suspensión
INSERT INTO list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, activity) VALUES
('reason_discontinue_medication', 'adverse_reaction', 'Adverse Reaction', 10, 0, 0, '', 'Reacción adversa al medicamento', '', 1),
('reason_discontinue_medication', 'medical_order', 'Medical Order Change', 20, 0, 0, '', 'Cambio en la orden médica', '', 1),
('reason_discontinue_medication', 'patient_refusal', 'Patient Refusal', 30, 0, 0, '', 'Rechazo del paciente', '', 1),
('reason_discontinue_medication', 'drug_interaction', 'Drug Interaction', 40, 0, 0, '', 'Interacción medicamentosa', '', 1),
('reason_discontinue_medication', 'clinical_improvement', 'Clinical Improvement', 50, 0, 0, '', 'Mejoría clínica del paciente', '', 1),
('reason_discontinue_medication', 'transfer_discharge', 'Transfer or Discharge', 60, 0, 0, '', 'Traslado o alta del paciente', '', 1),
('reason_discontinue_medication', 'treatment_completed', 'Treatment Completed', 70, 0, 0, '', 'Tratamiento completado', '', 1),
('reason_discontinue_medication', 'ineffective', 'Medication Ineffective', 80, 0, 0, '', 'Medicamento inefectivo', '', 1),
('reason_discontinue_medication', 'duplicate_therapy', 'Duplicate Therapy', 90, 0, 0, '', 'Terapia duplicada', '', 1),
('reason_discontinue_medication', 'contraindication', 'Contraindication Identified', 100, 0, 0, '', 'Contraindicación identificada', '', 1),
('reason_discontinue_medication', 'cost_issues', 'Cost or Availability Issues', 110, 0, 0, '', 'Problemas de costo o disponibilidad', '', 1),
('reason_discontinue_medication', 'prescriber_request', 'Prescriber Request', 120, 0, 0, '', 'Solicitud del prescriptor', '', 1),
('reason_discontinue_medication', 'other', 'Other (Specify in Notes)', 999, 0, 0, '', 'Otra razón (especificar en notas)', '', 1)
ON DUPLICATE KEY UPDATE 
    title = VALUES(title),
    notes = VALUES(notes),
    activity = VALUES(activity);

-- 5. Actualizar el campo status en prescriptions_supply para incluir 'Suspended' si no está
-- El campo ya permite este valor según la definición actual

-- 6. Crear índices para mejorar el rendimiento de consultas de suspensión
CREATE INDEX IF NOT EXISTS idx_supply_status ON prescriptions_supply(status);
CREATE INDEX IF NOT EXISTS idx_supply_schedule_status ON prescriptions_supply(schedule_id, status);
CREATE INDEX IF NOT EXISTS idx_schedule_active ON prescriptions_schedule(active);

-- =====================================================
-- Consultas útiles para verificar suspensiones
-- =====================================================

-- Ver todas las órdenes suspendidas
/*
SELECT 
    ps.schedule_id,
    CONCAT(p.lname, ', ', p.fname) AS patient_name,
    pr.drug,
    ps.suspended_reason,
    ps.suspension_datetime,
    CONCAT(u.lname, ', ', u.fname) AS suspended_by_user,
    COUNT(psu.supply_id) as total_doses,
    SUM(CASE WHEN psu.status = 'Suspended' THEN 1 ELSE 0 END) as suspended_doses
FROM prescriptions_schedule ps
LEFT JOIN prescriptions pr ON ps.prescription_id = pr.id
LEFT JOIN patient_data p ON ps.patient_id = p.pid
LEFT JOIN users u ON ps.suspended_by = u.id
LEFT JOIN prescriptions_supply psu ON psu.schedule_id = ps.schedule_id
WHERE ps.active = 0 AND ps.suspended_reason IS NOT NULL
GROUP BY ps.schedule_id
ORDER BY ps.suspension_datetime DESC;
*/

-- Ver historial de auditoría
/*
SELECT 
    pal.log_id,
    pal.schedule_id,
    pal.action_type,
    CONCAT(u.lname, ', ', u.fname) AS action_by_user,
    pal.action_datetime,
    pal.action_details
FROM prescriptions_audit_log pal
LEFT JOIN users u ON pal.action_by = u.id
ORDER BY pal.action_datetime DESC
LIMIT 100;
*/
