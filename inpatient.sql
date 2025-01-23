CREATE TABLE IF NOT EXISTS `beds` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(64) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `bed_name` varchar(255) DEFAULT NULL,
  `bed_type` varchar(255) DEFAULT NULL COMMENT 'list_options, list_id=Beds_Type -> Somnier, Articulada, etc..',
  `bed_status` varchar(255) DEFAULT NULL COMMENT 'list_options, list_id=Beds_Status -> Normal, In reapir, Defective',
  `obs` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL COMMENT '1 -> Active 0 -> Inactive',
  `operation` varchar(15) DEFAULT NULL COMMENT 'Add, Edit or Delete',
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `beds_patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bed_id` int(11) DEFAULT NULL,
  `room_id` int(11) NOT NULL,
  `unit_id` int(11) NOT NULL,
  `facility_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `responsible_user_id` int(11) DEFAULT NULL,
  `assigned_date` datetime DEFAULT NULL,
  `change_date` datetime DEFAULT NULL,
  `discharge_disposition` varchar(100) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL COMMENT 'occupied, cleaning, free. list_options -> list_id = ''bed_condition''',
  `patient_care` varchar(50) DEFAULT NULL COMMENT 'stable, serious, very serious, critical, Postoperative, Sepsis, Respiratory failure, Heart failure, Kidney failure, Coma\r\n. list_options -> list_id = ''inpatient_care''',
  `inpatient_physical_restrictions` varchar(50) DEFAULT NULL,
  `inpatient_sensory_restrictions` varchar(50) DEFAULT NULL,
  `inpatient_cognitive_restrictions` varchar(50) DEFAULT NULL,
  `inpatient_behavioral_restrictions` varchar(50) DEFAULT NULL,
  `inpatient_dietary_restrictions` varchar(50) DEFAULT NULL,
  `inpatient_other_restrictions` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `operation` varchar(50) DEFAULT NULL,
  `user_modif` varchar(100) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  `bed_name` varchar(50) DEFAULT NULL,
  `bed_status` varchar(100) DEFAULT NULL COMMENT 'Bed status during assignment - beds table->bed_status',
  `bed_type` varchar(100) DEFAULT NULL COMMENT 'Bed type during assignment - beds table->bed_type',
  `active` tinyint(1) DEFAULT 1 COMMENT 'Active 1 = In Use, Active 0 = Historical',
  PRIMARY KEY (`id`),
  KEY `id_index` (`id`) USING BTREE,
  KEY `bed_id` (`bed_id`)
) ENGINE=InnoDB AUTO_INCREMENT=138 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- openemr.rooms definition
CREATE TABLE `rooms` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(64) DEFAULT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `room_name` varchar(255) DEFAULT NULL,
  `number_of_beds` int(3) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL COMMENT '1 -> Active 0 ->Inactive',
  `operation` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Add, Edit or Delete',
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  `room_type` varchar(50) DEFAULT NULL,
  `oxigen` tinyint(1) DEFAULT NULL,
  `suction` tinyint(1) DEFAULT NULL,
  `cardiac_monitor` tinyint(1) DEFAULT NULL,
  `ventilator` tinyint(1) DEFAULT NULL,
  `infusion_pump` tinyint(1) DEFAULT NULL,
  `defibrillator` tinyint(1) DEFAULT NULL,
  `physioterapy` tinyint(1) DEFAULT NULL,
  `wifi` tinyint(1) DEFAULT NULL,
  `television` tinyint(1) DEFAULT NULL,
  `entertainment_system` tinyint(1) DEFAULT NULL,
  `personalized_menu` tinyint(1) DEFAULT NULL,
  `companion_space` tinyint(1) DEFAULT NULL,
  `private_bathroom` tinyint(1) DEFAULT NULL,
  `isolation_level` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `current_capacity` int(11) DEFAULT NULL,
  `sector` varchar(50) DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- openemr.units definition
CREATE TABLE `units` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` binary(64) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `unit_name` varchar(255) DEFAULT NULL COMMENT 'Unit Name',
  `number_of_rooms` int(3) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `obs` text DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL COMMENT '1-> Active, 0->Inactive',
  `operation` varchar(15) DEFAULT NULL COMMENT 'Add, Edit or Delete',
  `user_modif` varchar(255) DEFAULT NULL,
  `datetime_modif` datetime DEFAULT NULL,
  UNIQUE KEY `uuid` (`uuid`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `prescriptions_schedule` (
	`schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
	`prescription_id` INT(11) NOT NULL,
	`patient_id` INT(11) NOT NULL,
	`intravenous` TINYINT(4) NULL DEFAULT NULL COMMENT '1=Yes, 0=No',
	`scheduled` TINYINT(4) NULL DEFAULT NULL COMMENT '1=Yes, 0=No',
	`notifications` TINYINT(4) NULL DEFAULT NULL COMMENT '1=Yes, 0=No',
	`start_date` DATETIME NULL DEFAULT NULL,
	`end_date` DATETIME NULL DEFAULT NULL,
	`unit_frequency` INT(11) NULL DEFAULT NULL,
	`time_frequency` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`unit_duration` INT(11) NULL DEFAULT NULL,
	`time_duration` VARCHAR(25) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`alarm1_unit` INT(11) NULL DEFAULT NULL,
	`alarm1_time` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`alarm2_unit` INT(11) NULL DEFAULT NULL,
	`alarm2_time` VARCHAR(15) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`administered_by` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`administered_datetime` DATETIME NULL DEFAULT NULL,
	`active` TINYINT(4) NULL DEFAULT NULL,
	`modification_reason` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`modifed_by` INT(11) NULL DEFAULT NULL,
	`modification_datetime` DATETIME NULL DEFAULT NULL,
	`suspended_reason` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`suspended_by` INT(11) NULL DEFAULT NULL,
	`suspension_datetime` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`schedule_id`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `prescriptions_supply` (
	`supply_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	`schedule_id` INT(11) NULL DEFAULT NULL,
	`patient_id` INT(11) NULL DEFAULT NULL,
	`supply_datetime` DATETIME NOT NULL,
	`schedule_datetime` DATETIME NOT NULL,
	`supplied_by` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`dose_number` INT(11) NULL DEFAULT NULL,
	`max_dose` INT(11) NULL DEFAULT NULL,
	`supply_notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`alarm1_datetime` DATETIME NULL DEFAULT NULL,
	`alarm1_active` TINYINT(4) NULL DEFAULT NULL,
	`alarm2_datetime` DATETIME NULL DEFAULT NULL,
	`alarm2_active` TINYINT(4) NULL DEFAULT NULL,
	`status` VARCHAR(20) NULL DEFAULT 'Pending' COMMENT 'Pending, Confirmed, Cancelled, Suspended, Modified' COLLATE 'utf8mb4_general_ci',
	`confirmation_datetime` DATETIME NULL DEFAULT NULL,
	`modification_datetime` DATETIME NULL DEFAULT NULL,
	`active` TINYINT(4) NULL DEFAULT NULL,
	`effectiveness_score` VARCHAR(50) NULL DEFAULT NULL COMMENT 'SELECT title FROM list_options WHERE list_id=\'drug_effectiveness\';' COLLATE 'utf8mb4_general_ci',
	`effectiveness_notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`reaction_description` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`reaction_time` DATETIME NULL DEFAULT NULL,
	`reaction_notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`reaction_severity` VARCHAR(50) NULL DEFAULT NULL COMMENT 'SELECT title FROM list_options WHERE list_id=\'severity_ccda\';' COLLATE 'utf8mb4_general_ci',
	`created_by` INT(11) NULL DEFAULT NULL,
	`modified_by` INT(11) NULL DEFAULT NULL,
	`creation_datetime` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`supply_id`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;

CREATE TABLE `prescriptions_intravenous` (
	`intravenous_id` INT(11) NOT NULL AUTO_INCREMENT,
	`prescription_id` INT(11) NOT NULL,
	`schedule_id` INT(11) NOT NULL,
	`vehicle` VARCHAR(100) NULL DEFAULT NULL COMMENT 'SELECT option_id, title FROM list_options WHERE list_id=\'intravenous_vehicle\';' COLLATE 'utf8mb4_general_ci',
	`catheter_type` VARCHAR(100) NULL DEFAULT NULL COMMENT 'SELECT option_id, title FROM list_options WHERE list_id=\'catheter_type\';' COLLATE 'utf8mb4_general_ci',
	`infusion_rate` DECIMAL(5,2) NULL DEFAULT NULL,
	`iv_route` VARCHAR(100) NULL DEFAULT NULL COMMENT 'SELECT option_id, title FROM list_options WHERE list_id=\'intravenous_route\';' COLLATE 'utf8mb4_general_ci',
	`concentration` DECIMAL(5,2) NULL DEFAULT NULL,
	`concentration_units` VARCHAR(20) NULL DEFAULT '' COMMENT 'SELECT option_id, title FROM list_options WHERE list_id=\'proc_unit\';' COLLATE 'utf8mb4_general_ci',
	`total_volume` DECIMAL(5,2) NULL DEFAULT NULL,
	`iv_duration` DECIMAL(5,2) NULL DEFAULT NULL,
	`status` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Active, Changed' COLLATE 'utf8mb4_general_ci',
	`modify_datetime` DATETIME NULL DEFAULT NULL,
	`user_modify` INT(11) NULL DEFAULT NULL,
	PRIMARY KEY (`intravenous_id`) USING BTREE
)
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('bed_condition', 'cleaning', 'Cleaning', 2, 0, 0, '', 'bed_cleaning_icon.svg|#0c34ea', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('bed_condition', 'occupied', 'Occupied', 3, 0, 0, '', 'bed_occupied_icon.svg|#C70039', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('bed_condition', 'reserved', 'Reserved', 4, 0, 0, '', 'bed_reserved_icon.svg|#8c9333', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('bed_condition', 'vacant', 'Vacant', 1, 1, 0, '', 'bed_vacant_icon.svg|#2b8c25', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Status', 'defective', 'Defective', 3, 0, 0, '', '#0c34ea', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Status', 'in_repair', 'In Repair', 2, 0, 0, '', '#C70039', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Status', 'normal', 'Normal', 1, 1, 0, '', '#2b8c25', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'book', 'Book-style', 7, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'elec_articulate', 'Electrically articulated', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'fixed', 'Fixed', 1, 1, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'levitation', 'Levitation', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'man_articulate', 'Manually articulated', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'orthopedic', 'Orthopedic', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('Beds_Type', 'strike', 'Stryker or electrocircular', 6, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'coma', 'Coma', 10, 0, 0, '', 'coma_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'contagion_risk', 'Contagion risk', 12, 0, 0, '', 'contagion_risk_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'critical', 'Critical', 4, 0, 0, '', 'critical_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'heart_failure', 'Heart failure', 9, 0, 0, '', 'heart_failure_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'infection_risk', 'Infection risk', 11, 0, 0, '', 'infection_risk_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'kidney_failure', 'Kidney failure', 8, 0, 0, '', 'kidney_failure_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'postoperative', 'Postoperative', 5, 0, 0, '', 'postoperative_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'respiratory_failure', 'Respiratory failure', 7, 0, 0, '', 'respiratory_failure_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'serius', 'Serius', 2, 0, 0, '', 'serius_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'stable', 'Stable', 1, 1, 0, '', 'stable_level_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'transmission_risk', 'Transmission risk', 13, 0, 0, '', 'transmission_risk_icon.svg', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_care', 'very_serious', 'Very Serious', 3, 0, 0, '', 'very_serius_level_icon.svg', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'celiac', 'Celiac', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'food_allergies', 'Food allergies', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'food_intolerances', 'Food intolerances', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'liquid_diet', 'Liquid diet', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'restricted_diet', 'Restricted diet (e.g., low sodium, low potassium)', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'soft_diet', 'Soft diet', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'veganism', 'Veganism', 8, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_dietary_restrictions', 'vegetarianism', 'Vegetarianism', 7, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'complete_immobilization', 'Complete immobilization', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'crutch_use', 'Crutch use', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'extremity_restriction', 'Extremity restriction', 7, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'fall_risk', 'Fall risk', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'latex_allergy', 'Latex allergy', 9, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'partial_immobilization', 'Partial immobilization', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'walker_use', 'Walker use', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'weight_bearing_restrictions', 'Weight bearing restrictions', 8, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_physical_restrictions', 'wheelchair_bound', 'Wheelchair-bound', 3, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'blindness', 'Blindness', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'deafness', 'Deafness', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'impaired_hearing', 'Impaired hearing', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'impaired_smell', 'Impaired smell', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'impaired_taste', 'Impaired taste', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_sensory_restrictions', 'impaired_vision', 'Impaired vision', 1, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'confusion', 'Confusion', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'delirium', 'Delirium', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'dementia', 'Dementia', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'difficulty_following_instructions', 'Difficulty following instructions', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'disorientation', 'Disorientation', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_cognitive_restrictions', 'language_impairment', 'Language impairment', 5, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_other_restrictions', 'activity_limitations', 'Activity limitations', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_other_restrictions', 'isolation_precautions', 'Isolation precautions (contact, droplet, airborne)', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_other_restrictions', 'requires_constant_monitoring', 'Requires constant monitoring', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_other_restrictions', 'suicide_risk', 'Suicide risk', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_other_restrictions', 'visitation_restrictions', 'Visitation restrictions', 4, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'aggression', 'Aggression', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'anxiety', 'Anxiety', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'depression', 'Depression', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'impulsivity', 'Impulsivity', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'restlessness', 'Restlessness', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('inpatient_behavioral_restrictions', 'self_harm', 'Self-harm', 2, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'bed_availability', 'Bed availability', 5, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'change_condition', 'Change in patient\'s condition', 1, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'change_policy', 'Changes in hospital policy', 10, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'development_complications.', 'Development of complications', 3, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'isolation', 'Isolation', 6, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'medical_procedures', 'Medical procedures', 4, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'more_intensive_care', 'Need for more or less intensive care', 2, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'patient_distribution', 'Patient distribution', 7, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'patient_family_request', 'Patient or family request', 11, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('patient_relocation_reason', 'room_maintenance', 'Room maintenance', 9, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'antecubital', 'Antecubital fossa', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'dorsum_foot', 'Dorsum of the foot', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'dorsum_hand', 'Dorsum of the hand', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'forearm', 'Forearm', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'jugular', 'External jugular vein', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'subclavian', 'Subclavian vein', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_route', 'wrist', 'Wrist', 0, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'albumin', 'Albumin', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'dextran', 'Dextran', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'dextrose', '5% dextrose in water (D5W)', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'half_normal_saline', 'Half-normal saline (0.45% sodium chloride)', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'hetastarch', 'Hetastarch', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'lactated', 'Lactated Ringer\'s', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'normal_saline', 'Normal saline (0.9% sodium chloride)', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'plasma', 'Plasma', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'platelets', 'Platelets', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'red_blood_cells', 'packed red blood cells', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'sodium_chloride', '0.45% sodium chloride', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('intravenous_vehicle', 'whole_blood', 'Whole blood', 0, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('catheter_type', 'cvc', 'Tunneled central venous catheter (CVC)', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('catheter_type', 'hc', 'Hickman catheter', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('catheter_type', 'ip', 'Implanted port', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('catheter_type', 'lpicc', 'Peripherally inserted central catheter (PICC)', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('catheter_type', 'picc', 'Peripheral intravenous catheter (PICC)', 0, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'days', 'Days', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'hours', 'Hours', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'minutes', 'Minutes', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'months', 'Months', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'seconds', 'Seconds', 0, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('time_unit', 'weeks', 'Weeks', 0, 0, 0, '', '', '');

INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('drug_effectiveness', 'effective', 'Effective', 10, 1, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('drug_effectiveness', 'ineffective', 'Ineffective', 20, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('drug_effectiveness', 'mild', 'Mild', 30, 0, 0, '', '', '');
INSERT INTO `list_options` (`list_id`, `option_id`, `title`, `seq`, `is_default`, `option_value`, `mapping`, `notes`, `codes`) VALUES ('drug_effectiveness', 'moderate', 'Moderate', 40, 0, 0, '', '', '');

INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_status', 'available', 'Available', 1, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_status', 'occupied', 'Occupied', 2, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_status', 'reserved', 'Reserved', 4, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_status', 'under-maintenance', 'Under Maintenance', 3, 0, 0.0, '', '', '');

INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'er', 'Emergency Room', 10, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'maternity', 'Maternity', 6, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'palliative-care', 'Palliative Care Unit', 9, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'pediatric-unit', 'Pediatric Unit', 7, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'private', 'Private', 3, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'rehabilitation-unit', 'Rehabilitation Unit', 8, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'semi-private', 'Semi-private', 2, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'standard', 'Standard', 1, 1, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'step-down', 'Intermediate Care Unit', 5, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'uci-icu', 'Intensive Care Unit', 4, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('room_type', 'vip', 'VIP Room', 11, 0, 0.0, '', '', '');

INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('unit_floor', '1st_floor', '1st Floor', 3, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('unit_floor', '2nd_floor', '2nd Floor', 4, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('unit_floor', '3nd_loor', '3nd Floor', 5, 0, 0.0, '', '', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('unit_floor', 'll1', 'LL1', 2, 0, 0.0, '', 'Low Level 1', '');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes) VALUES('unit_floor', 'll2', 'LL2', 1, 0, 0.0, '', 'Low Level 2', '');
