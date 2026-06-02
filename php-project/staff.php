<?php
/**
 * Goshen Dental Care - Staff & Doctor Care Terminal
 * Built with HTML, CSS, Bootstrap 5 and secure parameter-bound PHP PDO.
 */

require_once __DIR__ . '/db.php';

// Form Actions: Write Clinical Notes & Diagnose Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_clinical_record') {
    $appt_id = $_POST['appt_id'];
    $notes = trim($_POST['clinical_notes']);
    $plan_step = trim($_POST['treatment_plan_step']);
    $rx = trim($_POST['rx_prescribed']);

    if (!empty($appt_id)) {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE appointments SET clinical_notes = :notes, treatment_plan_step = :plan_step, rx_prescribed = :rx, status = 'treatment_completed' WHERE id = :id");
                $stmt->execute([':notes' => $notes, ':plan_step' => $plan_step, ':rx' => $rx, ':id' => $appt_id]);
                $_SESSION['flash_msg'] = "Clinical procedure recorded successfully for Appointment ID: $appt_id. Slot status updated to Completed.";
            } catch (PDOException $e) {
                $_SESSION['err_msg'] = "Database write error: " . $e->getMessage();
            }
        } else {
            foreach ($_SESSION['mock_appointments'] as &$ap) {
                if ($ap['id'] === $appt_id) {
                    $ap['clinical_notes'] = $notes;
                    $ap['treatment_plan_step'] = $plan_step;
                    $ap['rx_prescribed'] = $rx;
                    $ap['status'] = 'treatment_completed';
                }
            }
            $_SESSION['flash_msg'] = "[Mock Mode] Clinical diagnostics and treatment updates successfully adjusted in active memory.";
        }
    }
    header('Location: staff.php?selected_appt_id=' . $appt_id);
    exit();
}

// Form Actions: Record payment installment receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment_installment') {
    $inst_id = 'inst_' . time();
    $appt_id = $_POST['appt_id'];
    $amount = floatval($_POST['payment_amount']);
    $method = $_POST['payment_method'];
    $notes = trim($_POST['payment_notes'] ?? '');

    if (!empty($appt_id) && $amount > 0) {
        if ($pdo) {
            try {
                $pdo->beginTransaction();
                
                // 1. Insert installment downpayment record
                $stmt = $pdo->prepare("INSERT INTO payment_installments (id, appointment_id, amount, date, method, notes) VALUES (:id, :appt_id, :amount, NOW(), :method, :notes)");
                $stmt->execute([':id' => $inst_id, ':appt_id' => $appt_id, ':amount' => $amount, ':method' => $method, ':notes' => $notes]);
                
                // 2. Adjust parent appointment amounts
                $appt_stmt = $pdo->prepare("SELECT total_cost, amount_paid FROM appointments WHERE id = :appt_id");
                $appt_stmt->execute([':appt_id' => $appt_id]);
                $appt = $appt_stmt->fetch();
                
                if ($appt) {
                    $new_paid = floatval($appt['amount_paid']) + $amount;
                    $total_cost = floatval($appt['total_cost']);
                    $payment_status = $new_paid >= $total_cost ? 'fully_paid' : 'partial';
                    
                    $update_stmt = $pdo->prepare("UPDATE appointments SET amount_paid = :paid, payment_status = :p_status WHERE id = :appt_id");
                    $update_stmt->execute([':paid' => $new_paid, ':p_status' => $payment_status, ':appt_id' => $appt_id]);
                }
                
                $pdo->commit();
                $_SESSION['flash_msg'] = "Installment payment of " . formatUGX($amount) . " logged securely.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['err_msg'] = "Payment documentation failed: " . $e->getMessage();
            }
        } else {
            // Memory mock session logic
            $installment_record = ['id' => $inst_id, 'appointment_id' => $appt_id, 'amount' => $amount, 'date' => date('Y-m-d H:i:s'), 'method' => $method, 'notes' => $notes];
            $_SESSION['mock_installments'][] = $installment_record;
            
            foreach ($_SESSION['mock_appointments'] as &$ap) {
                if ($ap['id'] === $appt_id) {
                    $ap['amount_paid'] += $amount;
                    $ap['payment_status'] = $ap['amount_paid'] >= $ap['total_cost'] ? 'fully_paid' : 'partial';
                }
            }
            $_SESSION['flash_msg'] = "[Mock Mode] Installment copay of " . formatUGX($amount) . " documented to memory.";
        }
    }
    header('Location: staff.php?selected_appt_id=' . $appt_id);
    exit();
}

// --- PULL QUEUE SCHEDULE DATA ---
$services_data = $pdo ? $pdo->query("SELECT * FROM services")->fetchAll() : $_SESSION['mock_services'];
$staff_data = $pdo ? $pdo->query("SELECT * FROM staff")->fetchAll() : $_SESSION['mock_staff'];

// Fetch appointments & link service titles
if ($pdo) {
    $appt_stmt = $pdo->query("SELECT a.*, s.name as service_name FROM appointments a JOIN services s ON a.service_id = s.id ORDER BY a.appointment_date ASC, a.appointment_time ASC");
    $all_appts = $appt_stmt->fetchAll();
} else {
    $all_appts = $_SESSION['mock_appointments'];
    foreach ($all_appts as &$ap) {
        foreach ($services_data as $s) {
            if ($s['id'] === $ap['service_id']) $ap['service_name'] = $s['name'];
        }
    }
}

// Check search query parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filtered_appts = [];
foreach ($all_appts as $ap) {
    if ($ap['status'] === 'pending' || $ap['status'] === 'confirmed') {
        if (empty($search) || stripos($ap['patient_name'], $search) !== false) {
            $filtered_appts[] = $ap;
        }
    }
}

// Capture currently selected appointment
$selected_appt = null;
$selected_id = isset($_GET['selected_appt_id']) ? $_GET['selected_appt_id'] : '';
if (!empty($selected_id)) {
    foreach ($all_appts as $ap) {
        if ($ap['id'] === $selected_id) {
            $selected_appt = $ap;
            break;
        }
    }
} elseif (!empty($filtered_appts)) {
    $selected_appt = $filtered_appts[0];
}

// Load installments linked to selected patient
$selected_installments = [];
if ($selected_appt) {
    $appt_id = $selected_appt['id'];
    if ($pdo) {
        $inst_stmt = $pdo->prepare("SELECT * FROM payment_installments WHERE appointment_id = :id ORDER BY date DESC");
        $inst_stmt->execute([':id' => $appt_id]);
        $selected_installments = $inst_stmt->fetchAll();
    } else {
        foreach ($_SESSION['mock_installments'] as $inst) {
            if ($inst['appointment_id'] === $appt_id) {
                $selected_installments[] = $inst;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goshen Dental Care - Staff Terminal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

  <!-- Top Static Header -->
  <header class="navbar navbar-expand-lg navbar-dark bg-dark py-3 px-4 border-bottom border-secondary">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <div class="display-logo text-white fs-4">GOSHEN DENTAL</div>
        <span class="badge bg-indigo text-white px-2 py-1 fs-xs" style="background-color: var(--primary-indigo)">CLINICAL</span>
      </div>
      <div>
        <span class="badge bg-success">● Staff Node Connected</span>
      </div>
    </div>
  </header>

  <!-- Work Area container -->
  <div class="container-fluid flex-grow-1">
    <div class="row min-h-100">
      
      <!-- LEFT MODULE: Primary Navigation Panel -->
      <nav class="col-md-3 col-lg-2 bg-indigo-sidebar p-4 d-flex flex-column gap-4">
        <div>
          <div class="sidebar-title mb-3">Operating Portals</div>
          <div class="d-flex flex-column gap-2">
            <a href="index.php" class="nav-link-custom">
              <span>Admin Director Dashboard</span>
            </a>
            <a href="staff.php" class="nav-link-custom active">
              <span>Staff Clinician Terminal</span>
            </a>
            <a href="patient.php" class="nav-link-custom">
              <span>Client Booking Hub</span>
            </a>
          </div>
        </div>
      </nav>

      <!-- RIGHT MODULE: Working Care Terminal Grid Space -->
      <main class="col-md-9 col-lg-10 p-4 p-md-5">

        <?php if (isset($_SESSION['flash_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <strong>✓ Chart Updated:</strong> <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <?php unset($_SESSION['flash_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="mb-5 text-left">
          <h1 class="text-dark">Staff Dental Queue & Charting Terminal</h1>
          <p class="text-muted fs-xs">Access queue schedules, adjust bracket placements, log composite restoration procedures, and register installment checks.</p>
        </div>

        <div class="row g-4">
          
          <!-- Column 1: Patient queue browser -->
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100" style="max-height: 650px; overflow-y: auto;">
              <h3 class="fs-6 font-bold text-dark mb-3">Clinic Patient Queue</h3>
              
              <!-- Search Queue Input -->
              <form action="staff.php" method="GET" class="mb-3">
                <div class="input-group">
                  <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control text-xs" style="font-size: 13px;" placeholder="Search name...">
                  <button type="submit" class="btn btn-outline-secondary btn-sm px-3">Find</button>
                </div>
              </form>

              <!-- Queue Entries -->
              <div class="d-flex flex-column gap-2 mt-2">
                <?php if (empty($filtered_appts)): ?>
                  <div class="text-center text-muted py-5 text-xs">No pending patients in clinic queue.</div>
                <?php else: ?>
                  <?php foreach ($filtered_appts as $ap): 
                    $is_selected = ($selected_appt && $selected_appt['id'] === $ap['id']);
                  ?>
                    <a href="staff.php?selected_appt_id=<?= $ap['id'] ?>&search=<?= urlencode($search) ?>" 
                       class="text-decoration-none text-start p-3 rounded-3 border d-block transition" 
                       style="border-color: <?= $is_selected ? 'var(--primary-indigo)' : '#f1f5f9' ?> !important; background-color: <?= $is_selected ? 'rgba(99, 102, 241, 0.05)' : '#ffffff' ?>;">
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="badge rounded bg-light border text-dark font-mono text-[10px]" style="font-size: 10px;"><?= $ap['appointment_time'] ?></span>
                        <span class="text-slate-400 font-mono text-[10px]" style="font-size: 10px;">ID: <?= $ap['id'] ?></span>
                      </div>
                      <h4 class="fs-xs font-bold text-dark mt-2 mb-1"><?= htmlspecialchars($ap['patient_name']) ?></h4>
                      <p class="text-muted mb-0 font-sans text-[11px]" style="font-size: 11px;"><?= htmlspecialchars($ap['service_name']) ?></p>
                    </a>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Column 2: Clinical Procedure chart and Billing downpayment forms -->
          <div class="col-lg-8">
            <?php if (!$selected_appt): ?>
              <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white h-100 d-flex flex-column justify-content-center align-items-center">
                <p class="text-slate-450">Please select an active patient from the clinical queue list to access medical charting terminals.</p>
              </div>
            <?php else: ?>
              <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
                
                <!-- Patient Metadata Summary header -->
                <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-start border">
                  <div>
                    <span class="text-[10px] text-muted uppercase tracking-wider font-mono">Active Consultation Record</span>
                    <h2 class="fs-5 font-bold text-dark mb-1"><?= htmlspecialchars($selected_appt['patient_name']) ?></h2>
                    <p class="text-slate-500 mb-0 font-sans" style="font-size: 12px;">Contact: <?= htmlspecialchars($selected_appt['patient_phone']) ?> | <?= htmlspecialchars($selected_appt['patient_email']) ?></p>
                  </div>
                  <div class="text-end">
                    <span class="text-[10px] text-slate-450 block uppercase font-mono">Procedure Budget Index</span>
                    <span class="fs-5 font-bold text-indigo font-mono" style="color: var(--primary-indigo)"><?= formatUGX($selected_appt['total_cost']) ?></span>
                  </div>
                </div>

                <div class="row g-4">
                  
                  <!-- Form Actions: Save Clinical Chart -->
                  <div class="col-md-7">
                    <h3 class="fs-6 font-bold text-dark mb-3">Clinical Procedure Charting Form</h3>
                    
                    <form action="staff.php" method="POST">
                      <input type="hidden" name="action" value="save_clinical_record">
                      <input type="hidden" name="appt_id" value="<?= htmlspecialchars($selected_appt['id']) ?>">

                      <div class="mb-3">
                        <label class="form-label font-bold text-slate-600 fs-xs uppercase">Active treatment step / Orthodontic milestone</label>
                        <input type="text" name="treatment_plan_step" required value="<?= htmlspecialchars($selected_appt['treatment_plan_step'] ?? '') ?>" class="form-control fs-xs" placeholder="e.g. Step 3 of 6: Archwire alignment, checking molar brackets">
                      </div>

                      <div class="mb-3">
                        <label class="form-label font-bold text-slate-600 fs-xs uppercase">Treatment Findings & Procedure Notes</label>
                        <textarea name="clinical_notes" required rows="4" class="form-control" style="font-size: 13px;" placeholder="Detail active findings (e.g. Extracted premium impacted composite successfully, irrigated localized suture lines)"><?= htmlspecialchars($selected_appt['clinical_notes'] ?? '') ?></textarea>
                      </div>

                      <div class="mb-3">
                        <label class="form-label font-bold text-slate-600 fs-xs uppercase">Medicines Prescribed (Rx)</label>
                        <input type="text" name="rx_prescribed" value="<?= htmlspecialchars($selected_appt['rx_prescribed'] ?? '') ?>" class="form-control fs-xs" placeholder="e.g. Amoxicillin 500mg 8-hourly, Paracetamol 1g for relief">
                      </div>

                      <button type="submit" class="btn btn-primary-custom w-100 fs-xs py-2.5">Save Clinical Chart Notes</button>
                    </form>
                  </div>

                  <!-- Ledger installments & Payment tracking -->
                  <div class="col-md-5">
                    <h3 class="fs-6 font-bold text-dark mb-3">Secure Office Payment Copay</h3>
                    
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                      <div class="text-[10px] text-muted uppercase font-mono">Invoice Ledger status</div>
                      <div class="row mt-1 text-center font-mono">
                        <div class="col-6 border-end text-start">
                          <span class="text-[9px] text-slate-450 d-block">PAID DOWN</span>
                          <strong class="font-bold text-success" style="font-size: 12px;"><?= formatUGX($selected_appt['amount_paid']) ?></strong>
                        </div>
                        <div class="col-6 text-start ps-3">
                          <span class="text-[9px] text-slate-450 d-block">OUTSTANDING</span>
                          <strong class="font-bold text-danger" style="font-size: 12px;"><?= formatUGX($selected_appt['total_cost'] - $selected_appt['amount_paid']) ?></strong>
                        </div>
                      </div>
                    </div>

                    <?php if (($selected_appt['total_cost'] - $selected_appt['amount_paid']) > 0): ?>
                      <form action="staff.php" method="POST" class="p-3 border rounded-3 bg-white">
                        <input type="hidden" name="action" value="add_payment_installment">
                        <input type="hidden" name="appt_id" value="<?= htmlspecialchars($selected_appt['id']) ?>">
                        
                        <div class="mb-2">
                          <label class="form-label font-bold text-slate-600" style="font-size: 10px;">INSTALLMENT AMOUNT</label>
                          <input type="number" name="payment_amount" required max="<?= $selected_appt['total_cost'] - $selected_appt['amount_paid'] ?>" min="100" class="form-control text-xs" style="font-size: 11px;" placeholder="UGX Amount">
                        </div>

                        <div class="row g-2 mb-2">
                          <div class="col-6">
                            <label class="form-label font-bold text-slate-600" style="font-size: 10px;">CHANNEL METHOD</label>
                            <select name="payment_method" class="form-select" style="font-size: 11px;">
                              <option value="Cash">Cash</option>
                              <option value="Visa Card">Visa Card</option>
                              <option value="Bank Transfer">Bank Transfer</option>
                              <option value="MTN MoMo">MTN MoMo</option>
                            </select>
                          </div>
                          
                          <div class="col-6">
                            <label class="form-label font-bold text-slate-600" style="font-size: 10px;">TRANSACTION ID</label>
                            <input type="text" name="payment_notes" class="form-control" style="font-size: 11px;" placeholder="Receipt Number">
                          </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 fs-xs py-2 mt-1" style="border-radius: 8px;">Log Office Installment Payment</button>
                      </form>
                    <?php else: ?>
                      <div class="alert alert-success fs-xs border-0 py-3 text-center rounded-3">
                        ✓ Patient has completed 100% of the dental balance. Account completely clear.
                      </div>
                    <?php endif; ?>

                    <!-- Past installments list -->
                    <div class="mt-4">
                      <h4 class="fs-xs font-bold text-slate-700 uppercase tracking-widest font-mono mb-2">Installment ledger Logs</h4>
                      <div class="d-flex flex-column gap-2" style="max-height: 180px; overflow-y: auto;">
                        <?php if (empty($selected_installments)): ?>
                          <div class="text-center text-slate-450 py-3 text-xs italic">No prior installments logged.</div>
                        <?php else: ?>
                          <?php foreach ($selected_installments as $inst): ?>
                            <div class="p-2 border rounded-3 bg-light text-xs font-sans">
                              <div class="d-flex justify-content-between align-items-center">
                                <span class="font-bold text-dark"><?= formatUGX($inst['amount']) ?></span>
                                <span class="badge bg-secondary font-mono text-[9px]" style="font-size: 9px;"><?= htmlspecialchars($inst['method']) ?></span>
                              </div>
                              <div class="d-flex justify-content-between align-items-center text-slate-500 mt-1" style="font-size: 10px;">
                                <span>Ref: <?= htmlspecialchars($inst['notes'] ?: 'Office Direct Receipt') ?></span>
                                <span class="font-mono"><?= $inst['date'] ?></span>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </div>
                    </div>

                  </div>

                </div>

              </div>
            <?php endif; ?>
          </div>

        </div>

      </main>

    </div>
  </div>

  <footer class="bg-dark text-white-50 text-center py-4 border-top border-secondary fs-xs mt-auto">
    <div class="container d-flex flex-col flex-md-row justify-content-between align-items-center gap-2">
      <p class="mb-0 text-start text-white-50">© 2026 Goshen Dental Care management system. Fully tailored design with automated billing reminders for dental operations in Kampala, Uganda.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
