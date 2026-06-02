-- =====================================================================
-- Goshen Dental Care Management System - MySQL Database Schema
-- Location: Kampala, Uganda (UGX Local Currency)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS goshen_dental;
USE goshen_dental;

-- --------------------------------------------------------
-- Table structure for table `services`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `services` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- Price in UGX
  `category` ENUM('Preventive', 'Curative', 'Aesthetic', 'Surgical') NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `staff`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `role` VARCHAR(100) NOT NULL,
  `procedures_completed` INT NOT NULL DEFAULT 0,
  `revenue_generated` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  `patient_rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00, -- e.g. 4.90
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `inventory`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `quantity` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `threshold` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `unit` VARCHAR(50) NOT NULL,
  `cost_per_unit` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `expenses`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` VARCHAR(50) NOT NULL,
  `category` ENUM('Rent', 'Utilities', 'Supplies', 'Marketing', 'Salaries', 'Other') NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  `date` DATE NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `appointments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` VARCHAR(50) NOT NULL,
  `patient_name` VARCHAR(150) NOT NULL,
  `patient_phone` VARCHAR(30) NOT NULL,
  `patient_email` VARCHAR(150) NOT NULL,
  `service_id` VARCHAR(50) NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `status` ENUM('pending', 'confirmed', 'treatment_completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `clinician_id` VARCHAR(50) NOT NULL,
  `total_cost` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  `amount_paid` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  `payment_status` ENUM('unpaid', 'partial', 'fully_paid') NOT NULL DEFAULT 'unpaid',
  `clinical_notes` TEXT,
  `treatment_plan_step` VARCHAR(255) DEFAULT NULL,
  `rx_prescribed` VARCHAR(255) DEFAULT NULL,
  `reminders_sent` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`clinician_id`) REFERENCES `staff` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payment_installments`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_installments` (
  `id` VARCHAR(50) NOT NULL,
  `appointment_id` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0.00, -- in UGX
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `method` ENUM('MTN MoMo', 'Airtel Money', 'Cash', 'Visa Card', 'Bank Transfer') NOT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` VARCHAR(50) NOT NULL,
  `appointment_id` VARCHAR(50) NOT NULL,
  `patient_email` VARCHAR(150) NOT NULL,
  `patient_name` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  PRIMARY KEY (`id`),
  FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- PRE-POPULATE DEFAULT SEED DATA
-- =====================================================================

-- 1. Services offered
INSERT INTO `services` (`id`, `name`, `price`, `category`, `description`) VALUES
('srv_1', 'Routine Clinical Examination', 50000.00, 'Preventive', 'Full digital diagnostics, gum measurements and initial high-contrast charting.'),
('srv_2', 'Scaling & Polishing (Standard)', 120000.00, 'Preventive', 'Complete removal of dental calculus and bacterial plaque with customized air-flow polishing.'),
('srv_3', 'Deep Root Canal Therapy', 450000.00, 'Curative', 'Nerve extirpation, master-cone obturation, and structural glass-ionomer core build-up.'),
('srv_4', 'Surgical Wisdom Tooth Extraction', 280000.00, 'Surgical', 'Atraumatic removal of impacted third molars with localized suture lines.'),
('srv_5', 'Orthodontic Braces Alignment', 3500000.00, 'Aesthetic', 'Premium ceramic/metal bracket system with dynamic monthly elastic and archwire calibration.');

-- 2. Staff Members
INSERT INTO `staff` (`id`, `name`, `role`, `procedures_completed`, `revenue_generated`, `patient_rating`) VALUES
('st_1', 'Dr. Kato Michael, DDS', 'Lead Orthodontic Surgeon', 142, 14200000.00, 4.95),
('st_2', 'Dr. Nakimera Brenda', 'Senior Endodontist', 118, 9820000.00, 4.88),
('st_3', 'Dr. Lwanga Arthur', 'General Dental Practitioner', 89, 5400000.00, 4.90),
('st_4', 'Sr. Nsubuga Joan', 'Registered Clinical Hygienist', 205, 3100000.00, 4.85);

-- 3. Inventory Supplies
INSERT INTO `inventory` (`id`, `name`, `quantity`, `threshold`, `unit`, `cost_per_unit`) VALUES
('inv_1', 'Latex Medical Gloves (Box 100s)', 12.00, 20.00, 'Boxes', 35000.00),
('inv_2', 'Local Anesthesia Vials (Lignocaine)', 75.00, 50.00, 'Vials', 4500.00),
('inv_3', 'Dental Composite Bonding Agent', 6.00, 10.00, 'Syringes', 120000.00),
('inv_4', 'Orthodontic NiTi Archwires', 45.00, 30.00, 'Units', 25000.00),
('inv_5', 'Sterilized Root Canal Gutta-Percha', 120.00, 100.00, 'Points', 2000.00);

-- 4. Expenses
INSERT INTO `expenses` (`id`, `category`, `amount`, `date`, `description`) VALUES
('exp_1', 'Rent', 1200000.00, '2026-06-01', 'Kampala HQ monthly clinical facility lease'),
('exp_2', 'Utilities', 340000.00, '2026-06-02', 'Grid Umeme power + water sanitation billing'),
('exp_3', 'Supplies', 180000.00, '2026-06-02', 'Procured box of premium sterilization packs');

-- 5. Appointments & Pre-registered Patients
INSERT INTO `appointments` (`id`, `patient_name`, `patient_phone`, `patient_email`, `service_id`, `appointment_date`, `appointment_time`, `status`, `clinician_id`, `total_cost`, `amount_paid`, `payment_status`, `clinical_notes`, `treatment_plan_step`, `rx_prescribed`, `reminders_sent`, `created_at`) VALUES
('appt_1', 'Namuli Sarah', '+256 772 123456', 'sarah.namuli@gmail.com', 'srv_3', '2026-06-03', '09:30:00', 'pending', 'st_2', 450000.00, 300000.00, 'partial', 'Patient reports sharp thermal pain in lower-left molar. Indicated 3 installment split.', 'Step 2 of 3: Root canal obturation and filling', 'Ibuprofen 400mg 8-hourly', 1, '2026-06-02 10:00:00'),
('appt_2', 'Okello Brian', '+256 701 987654', 'brian.okello@outlook.com', 'srv_2', '2026-06-02', '11:00:00', 'treatment_completed', 'st_4', 120000.00, 120000.00, 'fully_paid', 'Scaling and ultrasonic polish completed successfully. Checked for subgingival calculus.', 'Completed prophylactic scaling', 'None', 0, '2026-06-02 08:30:00'),
('appt_3', 'Agaba Juliet', '+256 755 443322', 'juliet.agaba@yahoo.com', 'srv_4', '2026-06-10', '14:00:00', 'pending', 'st_1', 280000.00, 0.00, 'unpaid', 'Wisdom tooth extraction schedule. Patient reports severe pressure. Full surgical release required.', 'Step 1 of 1: Surgical removal and sutures', 'Amoxicillin 500mg, Paracetamol 500mg', 2, '2026-06-02 15:30:00');

-- 6. Pre-poured Payment Installments
INSERT INTO `payment_installments` (`id`, `appointment_id`, `amount`, `date`, `method`, `notes`) VALUES
('inst_1', 'appt_1', 150000.00, '2026-06-02 10:15:00', 'MTN MoMo', 'Downpayment for Root Canal treatment'),
('inst_2', 'appt_1', 150000.00, '2026-06-02 17:45:00', 'Airtel Money', 'Seeding first milestone installment settlement'),
('inst_3', 'appt_2', 120000.00, '2026-06-02 11:45:00', 'Cash', 'Full upfront payment rendered in hand');

-- 7. Email Notifications / Reminders Logs
INSERT INTO `notifications` (`id`, `appointment_id`, `patient_email`, `patient_name`, `subject`, `content`, `sent_at`, `status`) VALUES
('notif_1', 'appt_1', 'sarah.namuli@gmail.com', 'Namuli Sarah', 'Goshen Appointment Confirmation: Root Canal', 'Hello Namuli Sarah, your root canal treatment session is scheduled for 2026-06-03 at 09:30 AM with Dr. Nakimera Brenda.', '2026-06-02 10:05:00', 'sent'),
('notif_2', 'appt_3', 'juliet.agaba@yahoo.com', 'Agaba Juliet', 'Upcoming Goshen Wisdom Tooth Surgery', 'Hello Agaba Juliet, this is an automated clinical reminder for your orthodontic extraction on 2026-06-10 at 02:00 PM.', '2026-06-02 15:40:00', 'sent');

CREATE INDEX idx_appt_date ON appointments(appointment_date);
CREATE INDEX idx_inst_appt ON payment_installments(appointment_id);
CREATE INDEX idx_notif_appt ON notifications(appointment_id);
