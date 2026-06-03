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
$notifications_data = $pdo ? $pdo->query("SELECT * FROM notifications ORDER BY sent_at DESC LIMIT 8")->fetchAll() : ($_SESSION['mock_notifications'] ?? []);
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

  <!-- Top Doctris-Style Header -->
  <header class="navbar navbar-expand-lg navbar-light py-2 px-4 sticky-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <div class="display-logo fs-4">GOSHEN DENTAL</div>
          <span class="badge text-white px-2 py-1" style="background-color: var(--primary-indigo); font-size: 0.68rem; letter-spacing: 0.05em; font-weight: 600;">CLINICAL</span>
        </div>
        
        <!-- Search bar filtering active queue -->
        <form action="staff.php" method="GET" class="doctris-search-container d-none d-lg-block ms-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="doctris-search-icon"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" name="search" class="doctris-search-bar" placeholder="Search queue patient name..." value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>

      <div class="d-flex align-items-center gap-3">
        <!-- Location badge -->
        <span class="country-badge-doctris d-none d-md-inline-flex align-items-center gap-1.5">
          <span>🇺🇬</span> Kampala HQ
        </span>

        <!-- Notifications Bell dropdown container -->
        <div class="dropdown">
          <button class="btn border-0 p-2 position-relative" type="button" id="bellDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: 50%; width: 40px; height: 40px; background-color: #f1f5f9;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-600"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="position-absolute top-1 start-7 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 0.6rem; padding: 3px 5px;">
              <?= count($notifications_data) ?>
            </span>
          </button>
          
          <ul class="dropdown-menu dropdown-menu-end notifications-dropdown-menu p-0" aria-labelledby="bellDropdown">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light" style="border-top-left-radius: var(--radius-md); border-top-right-radius: var(--radius-md);">
              <span class="font-bold text-dark text-xs uppercase font-sans">Reminders SMS Ledger</span>
              <span class="badge bg-indigo text-white font-mono" style="font-size: 9px;"><?= count($notifications_data) ?> Sent</span>
            </div>
            <div style="max-height: 280px; overflow-y: auto;">
              <?php if (empty($notifications_data)): ?>
                <div class="text-center p-4 text-muted text-xs">No notifications sent today.</div>
              <?php else: ?>
                <?php foreach (array_slice($notifications_data, 0, 5) as $notif): ?>
                  <li class="notification-item-doctris">
                    <div class="d-flex align-items-start gap-2">
                      <div class="patient-circle-avatar" style="background: linear-gradient(135deg, #a5b4fc, #6366f1); width: 28px; height: 28px; font-size: 0.7rem; flex-shrink: 0;">
                        <?= strtoupper(substr($notif['patient_name'], 0, 2)) ?>
                      </div>
                      <div>
                        <div class="font-semibold text-slate-800 text-xs" style="line-height: 1.2;"><?= htmlspecialchars($notif['subject']) ?></div>
                        <p class="text-muted mb-0 font-sans mt-0.5" style="font-size: 10px;"><?= htmlspecialchars($notif['patient_name']) ?>: <?= htmlspecialchars($notif['content']) ?></p>
                        <span class="text-[9px] text-slate-400 font-mono d-block mt-0.5" style="font-size: 9px;"><?= $notif['sent_at'] ?></span>
                      </div>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="p-2 text-center border-top">
              <a href="index.php" class="text-indigo text-xs font-semibold text-decoration-none">View Outbound Log</a>
            </div>
          </ul>
        </div>

        <!-- Doctor Mini Profile Pill -->
        <div class="d-flex align-items-center gap-2 ps-2 border-start">
          <img src="https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=150&q=80" alt="Dr. Kato Michael" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--doctris-primary);">
          <div class="d-none d-md-block text-start">
            <div class="font-bold text-dark text-xs mb-0">Dr. Kato Michael</div>
            <span class="text-muted block font-sans" style="font-size: 9.5px; line-height: 1;">Oral Director</span>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Work Area container -->
  <div class="container-fluid flex-grow-1">
    <div class="row min-h-100">
      
      <!-- LEFT MODULE: Primary Navigation Panel -->
      <nav class="col-md-3 col-lg-2 bg-indigo-sidebar p-4 d-flex flex-column gap-4">
        <!-- Doctris Doctor Profile Card Widget -->
        <div class="doctris-doctor-card mb-2 text-center">
          <img src="https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=150&q=80" alt="Dr. Kato Michael" class="doctris-doctor-img mb-3">
          <h4 class="fs-6 font-bold text-white mb-1">Dr. Kato Michael</h4>
          <span class="text-white-50 d-block font-sans" style="font-size: 11px;">Chief Dental Officer</span>
          <div class="mt-2.5 d-flex align-items-center justify-content-center gap-1.5 font-semibold text-emerald-400 font-mono" style="font-size: 10px; opacity: 0.95;">
            <span class="ai-pulse-indicator" style="width: 6px; height: 6px; background-color: #34d399; box-shadow: 0 0 8px #34d399;"></span> Active Duty
          </div>
        </div>

        <div>
          <div class="sidebar-title mb-3 text-muted">Operating Portals</div>
          <div class="d-flex flex-column gap-2">
            <a href="index.php" class="nav-link-custom">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar-icon"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="10" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              <span>CEO Administration Desk</span>
            </a>
            <a href="staff.php" class="nav-link-custom active">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar-icon"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              <span>Clinician Queue Station</span>
            </a>
            <a href="patient.php" class="nav-link-custom">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar-icon"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
              <span>Client Booking Hub</span>
            </a>
          </div>
        </div>

        <div class="mt-auto">
          <div class="vault-widget p-4 text-white" style="background: linear-gradient(145deg, #0f172a, #13142e); border: 1px solid rgba(99, 102, 241, 0.15);">
            <div class="d-flex align-items-center gap-2 mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-400" style="color: #6366f1;"><path d="M4.8 8H12m0 0l-2.4-2.4M12 8l-2.4 2.4M19.2 16H12m0 0l2.4-2.4M12 16l2.4 2.4"/></svg>
              <div class="uppercase tracking-widest font-mono text-white-50" style="font-size: 10px; opacity: 0.75; letter-spacing: 0.1em;">CLINIC CONSOLE</div>
            </div>
            <div class="font-bold text-white font-mono" style="font-size: 11px; font-weight: 500;">SECURE EHR CHARTING</div>
            <div class="text-[10px] text-slate-400 mt-1 d-flex align-items-center gap-1.5" style="font-size: 9.5px; opacity: 0.85;">
              <span class="ai-pulse-indicator" style="width: 5px; height: 5px; background-color: #10b981; box-shadow: 0 0 6px #10b981;"></span> Encryption Verified
            </div>
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

        <!-- Doctris Elegant Welcome Hero Card -->
        <div class="doctris-welcome-hero d-flex justify-content-between align-items-center mb-5 flex-wrap gap-4">
          <div style="z-index: 2;">
            <span class="doctris-welcome-badge mb-3 d-inline-block">Clinical Diagnostics Deck</span>
            <h1 class="text-dark fs-3 mb-2 font-bold" style="font-family: var(--display-font);">Clinician Queue Station</h1>
            <p class="text-muted fs-xs max-w-2xl mb-0" style="max-width: 600px;">
              Manage orthodontic sessions, append medical history files, track dental bracket alignments, and log payment installments directly into Airtel & MTN mobile money channels.
            </p>
          </div>
          <div class="d-flex gap-2" style="z-index: 2;">
            <a href="index.php" class="btn btn-primary-custom d-flex align-items-center gap-2 py-2.5 px-4 font-bold" style="font-size: 0.82rem; border-radius: 30px; background-color: var(--deep-charcoal); border-color: var(--deep-charcoal); color: white;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="10" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              CEO Executive Desk
            </a>
            <span class="badge bg-light text-slate-700 border p-3 d-flex align-items-center justify-content-center font-mono font-bold" style="border-radius: 30px; font-size: 0.76rem;"><?= date('l, d M Y') ?></span>
          </div>
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
                    $hash = md5($ap['patient_name']);
                    $colors = [
                      ['#3882f6', '#93c5fd'],
                      ['#10b981', '#6ee7b7'],
                      ['#f59e0b', '#fcd34d'],
                      ['#8b5cf6', '#c4b5fd'],
                      ['#ec4899', '#fbcfe8'],
                      ['#14b8a6', '#99f6e4']
                    ];
                    $c_idx = hexdec(substr($hash, 0, 2)) % count($colors);
                    $grad_start = $colors[$c_idx][0];
                    $grad_end = $colors[$c_idx][1];
                    $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $ap['patient_name']), 0, 1));
                    if (strpos($ap['patient_name'], ' ') !== false) {
                      $parts = explode(' ', $ap['patient_name']);
                      $initials = strtoupper(substr(trim($parts[0]), 0, 1) . substr(trim($parts[1]), 0, 1));
                    }
                  ?>
                    <a href="staff.php?selected_appt_id=<?= $ap['id'] ?>&search=<?= urlencode($search) ?>" 
                       class="text-decoration-none text-start p-3 rounded-4 border d-block transition mb-2" 
                       style="border-color: <?= $is_selected ? 'var(--primary-indigo)' : '#f1f5f9' ?> !important; background-color: <?= $is_selected ? 'rgba(99, 102, 241, 0.05)' : '#ffffff' ?>; border-width: 1.5px !important; box-shadow: <?= $is_selected ? '0 4px 12px rgba(99, 102, 241, 0.08)' : '0 2px 4px rgba(15, 23, 42, 0.02)' ?>;">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge rounded bg-light border text-dark font-mono" style="font-size: 10px;"><?= $ap['appointment_time'] ?></span>
                        <span class="text-slate-400 font-mono" style="font-size: 9.5px;">ID: <?= $ap['id'] ?></span>
                      </div>
                      <div class="d-flex align-items-center gap-2.5">
                        <div class="patient-circle-avatar" style="background: linear-gradient(135deg, <?= $grad_start ?>, <?= $grad_end ?>); width: 32px; height: 32px; font-size: 0.75rem; flex-shrink: 0; font-family: var(--display-font);">
                          <?= $initials ?>
                        </div>
                        <div style="min-width: 0;">
                          <h4 class="fs-xs font-bold text-dark mb-0.5 text-truncate" style="font-size: 13px; line-height: 1.2;"><?= htmlspecialchars($ap['patient_name']) ?></h4>
                          <p class="text-muted mb-0 font-sans text-truncate text-[10.5px]" style="font-size: 10.5px;"><?= htmlspecialchars($ap['service_name']) ?></p>
                        </div>
                      </div>
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
