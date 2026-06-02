<?php
/**
 * Goshen Dental Care - Unified REST API Engine
 * Manages GET / POST requests securely with parameterized PDO bindings.
 */

require_once __DIR__ . '/db.php';

// Helper to parse JSON request bodies comfortably
function get_json_input() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// Check route identifier
$resource = isset($_GET['resource']) ? trim($_GET['resource']) : '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($resource) {
    
    // ==========================================
    // DENTAL SERVICES ENDPOINT
    // ==========================================
    case 'services':
        if ($method === 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM services ORDER BY price ASC");
                $data = $stmt->fetchAll();
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } elseif ($method === 'POST') {
            $input = get_json_input();
            if (empty($input['id']) || empty($input['name']) || empty($input['price'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing ID, Name or Price inputs.']);
                exit;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO services (id, name, price, category, description) VALUES (:id, :name, :price, :category, :description)");
                $stmt->execute([
                    ':id' => $input['id'],
                    ':name' => $input['name'],
                    ':price' => floatval($input['price']),
                    ':category' => $input['category'] ?? 'Preventive',
                    ':description' => $input['description'] ?? ''
                ]);
                echo json_encode(['success' => true, 'message' => 'Dental service created successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // CLINIC STAFF ENDPOINT
    // ==========================================
    case 'staff':
        if ($method === 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM staff ORDER BY name ASC");
                $data = $stmt->fetchAll();
                
                // Format decimal types comfortably
                foreach ($data as &$st) {
                    $st['procedures_completed'] = intval($st['procedures_completed']);
                    $st['revenue_generated'] = floatval($st['revenue_generated']);
                    $st['patient_rating'] = floatval($st['patient_rating']);
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // INVENTORY & SUPPLIES ENDPOINT
    // ==========================================
    case 'inventory':
        if ($method === 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM inventory ORDER BY name ASC");
                $data = $stmt->fetchAll();
                foreach ($data as &$item) {
                    $item['quantity'] = floatval($item['quantity']);
                    $item['threshold'] = floatval($item['threshold']);
                    $item['cost_per_unit'] = floatval($item['cost_per_unit']);
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // CLINIC EXPENSES ENDPOINT
    // ==========================================
    case 'expenses':
        if ($method === 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM expenses ORDER BY date DESC");
                $data = $stmt->fetchAll();
                foreach ($data as &$exp) {
                    $exp['amount'] = floatval($exp['amount']);
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } elseif ($method === 'POST') {
            $input = get_json_input();
            if (empty($input['id']) || empty($input['category']) || empty($input['amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing essential expense details.']);
                exit;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO expenses (id, category, amount, date, description) VALUES (:id, :category, :amount, :date, :description)");
                $stmt->execute([
                    ':id' => $input['id'],
                    ':category' => $input['category'],
                    ':amount' => floatval($input['amount']),
                    ':date' => $input['date'] ?? date('Y-m-d'),
                    ':description' => $input['description'] ?? ''
                ]);
                echo json_encode(['success' => true, 'message' => 'Expense logged successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // APPOINTMENTS ENDPOINT (Inc. Installments)
    // ==========================================
    case 'appointments':
        if ($method === 'GET') {
            try {
                // Fetch all appointments
                $stmt = $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC");
                $appointments = $stmt->fetchAll();
                
                // Pull installments for each, to reconstruct correct TypeScript mapping
                foreach ($appointments as &$appt) {
                    $appt_id = $appt['id'];
                    $inst_stmt = $pdo->prepare("SELECT id, amount, date, method, notes FROM payment_installments WHERE appointment_id = :appt_id ORDER BY date ASC");
                    $inst_stmt->execute([':appt_id' => $appt_id]);
                    $installments = $inst_stmt->fetchAll();
                    
                    // Format numeric values
                    $appt['total_cost'] = floatval($appt['total_cost']);
                    $appt['amount_paid'] = floatval($appt['amount_paid']);
                    $appt['reminders_sent'] = intval($appt['reminders_sent']);
                    
                    foreach ($installments as &$inst) {
                        $inst['amount'] = floatval($inst['amount']);
                    }
                    
                    // Nested structured attachment matching types.ts
                    $appt['installments'] = $installments;
                    
                    // Rename keys back to lowerCamelCase dynamically
                    $appt['patientName'] = $appt['patient_name'];
                    $appt['patientPhone'] = $appt['patient_phone'];
                    $appt['patientEmail'] = $appt['patient_email'];
                    $appt['serviceId'] = $appt['service_id'];
                    $appt['appointmentDate'] = $appt['appointment_date'];
                    $appt['appointmentTime'] = $appt['appointment_time'];
                    $appt['clinicianId'] = $appt['clinician_id'];
                    $appt['amountPaid'] = $appt['amount_paid'];
                    $appt['totalCost'] = $appt['total_cost'];
                    $appt['paymentStatus'] = $appt['payment_status'];
                    $appt['clinicalNotes'] = $appt['clinical_notes'];
                    $appt['treatmentPlanStep'] = $appt['treatment_plan_step'];
                    $appt['rxPrescribed'] = $appt['rx_prescribed'];
                    $appt['remindersSent'] = $appt['reminders_sent'];
                    $appt['createdAt'] = $appt['created_at'];
                }
                
                echo json_encode(['success' => true, 'data' => $appointments]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } elseif ($method === 'POST') {
            $input = get_json_input();
            if (empty($input['id']) || empty($input['patientName']) || empty($input['serviceId']) || empty($input['clinicianId'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing core customer details or clinician assignment.']);
                exit;
            }
            try {
                $stmt = $pdo->prepare("INSERT INTO appointments (id, patient_name, patient_phone, patient_email, service_id, appointment_date, appointment_time, status, clinician_id, total_cost, amount_paid, payment_status, clinical_notes, treatment_plan_step, rx_prescribed, reminders_sent, created_at) 
                                       VALUES (:id, :p_name, :p_phone, :p_email, :s_id, :appt_date, :appt_time, :status, :clin_id, :total, :paid, :p_status, :notes, :plan, :rx, 0, NOW())");
                $stmt->execute([
                    ':id' => $input['id'],
                    ':p_name' => $input['patientName'],
                    ':p_phone' => $input['patientPhone'] ?? '',
                    ':p_email' => $input['patientEmail'] ?? '',
                    ':s_id' => $input['serviceId'],
                    ':appt_date' => $input['appointmentDate'] ?? date('Y-m-d'),
                    ':appt_time' => $input['appointmentTime'] ?? date('H:i:s'),
                    ':status' => $input['status'] ?? 'pending',
                    ':clin_id' => $input['clinicianId'],
                    ':total' => floatval($input['totalCost'] ?? 0.00),
                    ':paid' => floatval($input['amountPaid'] ?? 0.00),
                    ':p_status' => $input['paymentStatus'] ?? 'unpaid',
                    ':notes' => $input['clinicalNotes'] ?? '',
                    ':plan' => $input['treatmentPlanStep'] ?? null,
                    ':rx' => $input['rxPrescribed'] ?? null
                ]);
                echo json_encode(['success' => true, 'message' => 'Appointment slot booked successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // INSTALLMENTS INFLOW SUB-ENDPOINT (POST ONLY)
    // ==========================================
    case 'installments':
        if ($method === 'POST') {
            $input = get_json_input();
            if (empty($input['id']) || empty($input['appointmentId']) || empty($input['amount'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing Installment payload requirements.']);
                exit;
            }
            try {
                $pdo->beginTransaction();
                
                // 1. Insert installment Downpayment
                $stmt = $pdo->prepare("INSERT INTO payment_installments (id, appointment_id, amount, date, method, notes) 
                                       VALUES (:id, :appt_id, :amount, NOW(), :method, :notes)");
                $stmt->execute([
                    ':id' => $input['id'],
                    ':appt_id' => $input['appointmentId'],
                    ':amount' => floatval($input['amount']),
                    ':method' => $input['method'] ?? 'MTN MoMo',
                    ':notes' => $input['notes'] ?? ''
                ]);
                
                // 2. Adjust core parent appointment's amount payment fields
                $appt_stmt = $pdo->prepare("SELECT total_cost, amount_paid FROM appointments WHERE id = :appt_id");
                $appt_stmt->execute([':appt_id' => $input['appointmentId']]);
                $appt = $appt_stmt->fetch();
                
                if (!$appt) {
                    throw new Exception("Referenced dental session record does not exist");
                }
                
                $new_paid = floatval($appt['amount_paid']) + floatval($input['amount']);
                $total_cost = floatval($appt['total_cost']);
                
                // Dynamically lock state transition
                if ($new_paid >= $total_cost) {
                    $payment_status = 'fully_paid';
                } elseif ($new_paid > 0) {
                    $payment_status = 'partial';
                } else {
                    $payment_status = 'unpaid';
                }
                
                $update_stmt = $pdo->prepare("UPDATE appointments SET amount_paid = :paid, payment_status = :p_status WHERE id = :appt_id");
                $update_stmt->execute([
                    ':paid' => $new_paid,
                    ':p_status' => $payment_status,
                    ':appt_id' => $input['appointmentId']
                ]);
                
                $pdo->commit();
                echo json_encode([
                    'success' => true, 
                    'message' => 'Installment documented securely. Outstanding updated.',
                    'new_amount_paid' => $new_paid,
                    'new_payment_status' => $payment_status
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // CLINICAL CHARTING PROCEDURES (POST ONLY)
    // ==========================================
    case 'chart':
        if ($method === 'POST') {
            $input = get_json_input();
            if (empty($input['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing referenced Appointment ID.']);
                exit;
            }
            try {
                $stmt = $pdo->prepare("UPDATE appointments 
                                       SET clinical_notes = :notes, treatment_plan_step = :plan, rx_prescribed = :rx, status = :status 
                                       WHERE id = :id");
                $stmt->execute([
                    ':notes' => $input['clinicalNotes'] ?? '',
                    ':plan' => $input['treatmentPlanStep'] ?? null,
                    ':rx' => $input['rxPrescribed'] ?? null,
                    ':status' => $input['status'] ?? 'treatment_completed',
                    ':id' => $input['id']
                ]);
                echo json_encode(['success' => true, 'message' => 'Clinical procedure charts written successfully']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    // ==========================================
    // NOTIFICATIONS SYSTEM ENDPOINT
    // ==========================================
    case 'notifications':
        if ($method === 'GET') {
            try {
                $stmt = $pdo->query("SELECT * FROM notifications ORDER BY sent_at DESC");
                $data = $stmt->fetchAll();
                
                // Map keys back into TypeScript parameters
                foreach ($data as &$notif) {
                    $notif['appointmentId'] = $notif['appointment_id'];
                    $notif['patientEmail'] = $notif['patient_email'];
                    $notif['patientName'] = $notif['patient_name'];
                    $notif['sentAt'] = $notif['sent_at'];
                }
                echo json_encode(['success' => true, 'data' => $data]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => "Action route identifier '{$resource}' not found."
        ]);
        break;
}
