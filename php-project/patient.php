<?php
/**
 * Goshen Dental Care - Guest & Patient Booking Panel & MoMo Installment Tracker
 * Built with HTML, CSS, Bootstrap 5 and secure parameter-bound PHP PDO.
 */

require_once __DIR__ . '/db.php';

// Form Actions: Patient scheduling registration request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_booking') {
    $appt_id = 'appt_' . time();
    $name = trim($_POST['patient_name']);
    $phone = trim($_POST['patient_phone']);
    $email = trim($_POST['patient_email']);
    $service_id = $_POST['service_id'];
    $clinician_id = $_POST['clinician_id'];
    $date = $_POST['booking_date'];
    $time = $_POST['booking_time'] . ':00';
    $notes = trim($_POST['booking_notes']);

    // Retrieve service cost index
    $total_cost = 50000;
    $services_data_temp = $pdo ? $pdo->query("SELECT * FROM services")->fetchAll() : $_SESSION['mock_services'];
    foreach ($services_data_temp as $s) {
        if ($s['id'] === $service_id) {
            $total_cost = floatval($s['price']);
            break;
        }
    }

    if (!empty($name) && !empty($phone)) {
        if ($pdo) {
            try {
                $pdo->beginTransaction();

                // 1. Insert appointment booking
                $stmt = $pdo->prepare("INSERT INTO appointments (id, patient_name, patient_phone, patient_email, service_id, appointment_date, appointment_time, status, clinician_id, total_cost, amount_paid, payment_status, clinical_notes, reminders_sent, created_at) 
                                       VALUES (:id, :name, :phone, :email, :service_id, :date, :time, 'pending', :clinician_id, :total_cost, 0, 'unpaid', :notes, 0, NOW())");
                $stmt->execute([':id' => $appt_id, ':name' => $name, ':phone' => $phone, ':email' => $email, ':service_id' => $service_id, ':date' => $date, ':time' => $time, ':clinician_id' => $clinician_id, ':total_cost' => $total_cost, ':notes' => $notes]);
                
                // 2. Insert notification log
                $notif_id = 'notif_' . time();
                $subject = "Awaiting Goshen Medical Confirmation";
                $content = "Hullo, $name. We've received your orthodontics schedule for $date at $time. We are setting up your customized installment invoice.";
                
                $notif_stmt = $pdo->prepare("INSERT INTO notifications (id, appointment_id, patient_email, patient_name, subject, content, sent_at, status) 
                                             VALUES (:id, :appt_id, :email, :name, :subject, :content, NOW(), 'sent')");
                $notif_stmt->execute([':id' => $notif_id, ':appt_id' => $appt_id, ':email' => $email, ':name' => $name, ':subject' => $subject, ':content' => $content]);

                $pdo->commit();
                $_SESSION['flash_msg'] = "Appointment slot booked successfully! Automated confirmation notification dispatched to: $email";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['err_msg'] = "Booking request failed: " . $e->getMessage();
            }
        } else {
            // Memory mock session logic
            $mock_record = [
                'id' => $appt_id, 'patient_name' => $name, 'patient_phone' => $phone, 'patient_email' => $email, 'service_id' => $service_id,
                'appointment_date' => $date, 'appointment_time' => $time, 'status' => 'pending', 'clinician_id' => $clinician_id, 'total_cost' => $total_cost, 'amount_paid' => 0,
                'payment_status' => 'unpaid', 'clinical_notes' => $notes, 'reminders_sent' => 0, 'created_at' => date('Y-m-d H:i:s')
            ];
            
            $_SESSION['mock_appointments'][] = $mock_record;
            
            // Add notification entry
            $_SESSION['mock_notifications'][] = [
                'id' => 'notif_' . time(), 'appointment_id' => $appt_id, 'patient_email' => $email, 'patient_name' => $name, 
                'subject' => "Awaiting Goshen Medical Confirmation", 'content' => "Hullo, $name. We've received your orthodontics schedule for $date at $time.", 'sent_at' => date('Y-m-d H:i:s'), 'status' => 'sent'
            ];
            
            $_SESSION['flash_msg'] = "[Mock Mode] Appointment registered! Treatment total estimate indexed at " . formatUGX($total_cost);
        }
    }
    header('Location: patient.php');
    exit();
}

// Form Actions: Submit Mobile Money payment simulator installment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_momo_payment') {
    $inst_id = 'inst_' . time();
    $appt_id = $_POST['appt_id'];
    $amount = floatval($_POST['momo_amount']);
    $wallet_num = $_POST['momo_phone'];
    $channel_momo = $_POST['momo_channel'];

    if (!empty($appt_id) && $amount > 0) {
        if ($pdo) {
            try {
                $pdo->beginTransaction();

                // 1. Save installment downpayment record
                $stmt = $pdo->prepare("INSERT INTO payment_installments (id, appointment_id, amount, date, method, notes) VALUES (:id, :appt_id, :amount, NOW(), :method, :notes)");
                $stmt->execute([':id' => $inst_id, ':appt_id' => $appt_id, ':amount' => $amount, ':method' => $channel_momo, ':notes' => "Wallet: $wallet_num"]);

                // 2. Modify parent appointment totals
                $appt_stmt = $pdo->prepare("SELECT total_cost, amount_paid, patient_email, patient_name FROM appointments WHERE id = :appt_id");
                $appt_stmt->execute([':appt_id' => $appt_id]);
                $appt = $appt_stmt->fetch();

                if ($appt) {
                    $new_paid = floatval($appt['amount_paid']) + $amount;
                    $total_cost = floatval($appt['total_cost']);
                    $payment_status = $new_paid >= $total_cost ? 'fully_paid' : 'partial';

                    $update_stmt = $pdo->prepare("UPDATE appointments SET amount_paid = :paid, payment_status = :p_status WHERE id = :appt_id");
                    $update_stmt->execute([':paid' => $new_paid, ':p_status' => $payment_status, ':appt_id' => $appt_id]);

                    // Send ledger update email
                    $notif_id = 'notif_' . time();
                    $subject = "Installment Payment Confirmed - Goshen Dental";
                    $content = "Hullo, {$appt['patient_name']}. Your installment payment of " . formatUGX($amount) . " via $channel_momo has been received successfully. Remaining outstanding balance is " . formatUGX($total_cost - $new_paid) . ".";
                    
                    $notif_stmt = $pdo->prepare("INSERT INTO notifications (id, appointment_id, patient_email, patient_name, subject, content, sent_at, status) VALUES (:id, :appt_id, :email, :name, :subject, :content, NOW(), 'sent')");
                    $notif_stmt->execute([':id' => $notif_id, ':appt_id' => $appt_id, ':email' => $appt['patient_email'], ':name' => $appt['patient_name'], ':subject' => $subject, ':content' => $content]);
                }

                $pdo->commit();
                $_SESSION['flash_msg'] = "Simulated MTN/Airtel MoMo transaction processed! Account updated.";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $_SESSION['err_msg'] = "Mobile Money payment simulation failed: " . $e->getMessage();
            }
        } else {
            // Memory mock session logic
            $installment_record = ['id' => $inst_id, 'appointment_id' => $appt_id, 'amount' => $amount, 'date' => date('Y-m-d H:i:s'), 'method' => $channel_momo, 'notes' => "Sim MoMo: $wallet_num"];
            $_SESSION['mock_installments'][] = $installment_record;

            $patient_email_temp = "patient@gmail.com";
            $patient_name_temp = "Goshen Patient";
            foreach ($_SESSION['mock_appointments'] as &$ap) {
                if ($ap['id'] === $appt_id) {
                    $ap['amount_paid'] += $amount;
                    $ap['payment_status'] = $ap['amount_paid'] >= $ap['total_cost'] ? 'fully_paid' : 'partial';
                    $patient_email_temp = $ap['patient_email'];
                    $patient_name_temp = $ap['patient_name'];
                }
            }

            // Post notification
            $_SESSION['mock_notifications'][] = [
                'id' => 'notif_' . time(), 'appointment_id' => $appt_id, 'patient_email' => $patient_email_temp, 'patient_name' => $patient_name_temp,
                'subject' => "Installment Payment Confirmed - Goshen", 'content' => "Hullo. Your installment payment of " . formatUGX($amount) . " via $channel_momo has been loaded.", 'sent_at' => date('Y-m-d H:i:s'), 'status' => 'sent'
            ];

            $_SESSION['flash_msg'] = "[Mock Mode] MoMo transaction processed in memory. Remaining ledger balanced!";
        }
    }
    header('Location: patient.php?checkin_phone=' . urlencode($wallet_num));
    exit();
}

// --- PULL SERVICES AND STAFF DATA ---
$services_data = $pdo ? $pdo->query("SELECT * FROM services")->fetchAll() : $_SESSION['mock_services'];
$staff_data = $pdo ? $pdo->query("SELECT * FROM staff")->fetchAll() : $_SESSION['mock_staff'];

// Quick client check-in session lookup
$checkin_phone = isset($_GET['checkin_phone']) ? trim($_GET['checkin_phone']) : '';
$found_appointments = [];

if (!empty($checkin_phone)) {
    // Collect all appointments matching name or phone
    $all_appts = $pdo ? $pdo->query("SELECT * FROM appointments ORDER BY created_at DESC")->fetchAll() : $_SESSION['mock_appointments'];
    foreach ($all_appts as $ap) {
        if (stripos($ap['patient_phone'], $checkin_phone) !== false || stripos($ap['patient_name'], $checkin_phone) !== false) {
            // Find service details
            foreach ($services_data as $s) {
                if ($s['id'] === $ap['service_id']) {
                    $ap['service_name'] = $s['name'];
                    break;
                }
            }
            $found_appointments[] = $ap;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goshen Dental Care - Patient Portal</title>
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
        <span class="badge text-white px-2 py-1 fs-xs" style="background-color: var(--primary-indigo)">GUEST CENTER</span>
      </div>
      <div>
        <span class="badge bg-success">● Encryption Standard: Active</span>
      </div>
    </div>
  </header>

  <!-- Container Grid workspace -->
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
            <a href="staff.php" class="nav-link-custom">
              <span>Staff Clinician Terminal</span>
            </a>
            <a href="patient.php" class="nav-link-custom active">
              <span>Client Booking Hub</span>
            </a>
          </div>
        </div>
      </nav>

      <!-- RIGHT MODULE: Working Dashboard Grid Space -->
      <main class="col-md-9 col-lg-10 p-4 p-md-5">

        <?php if (isset($_SESSION['flash_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <strong>✓ Operation Complete:</strong> <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <?php unset($_SESSION['flash_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="mb-5 text-left">
          <h1 class="text-dark">Guest & Patient Booking Hub</h1>
          <p class="text-muted fs-xs">Schedule clinical treatments, register micro installments, and pay down invoices securely via MTN MoMo or Airtel Money wallets.</p>
        </div>

        <div class="row g-4">
          
          <!-- Column 1: Booking Appointment Form and Services Pricing Menu -->
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
              <h2 class="fs-5 font-bold text-dark mb-4">Book Dental Appointment</h2>
              
              <form action="patient.php" method="POST">
                <input type="hidden" name="action" value="request_booking">

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Your Full Name</label>
                    <input type="text" name="patient_name" required class="form-control" placeholder="e.g. Namubiru Justine">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Active Phone Number (+256)</label>
                    <input type="text" name="patient_phone" required class="form-control" placeholder="e.g. +256 772 000000">
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">E-Mail Address</label>
                    <input type="email" name="patient_email" required class="form-control" placeholder="e.g. hassanharman44@gmail.com">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Assign Dental Service Class</label>
                    <select name="service_id" required class="form-select">
                      <?php foreach ($services_data as $serv): ?>
                        <option value="<?= $serv['id'] ?>"><?= htmlspecialchars($serv['name']) ?> (<?= formatUGX($serv['price']) ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="row g-3 mb-3">
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Required Consultation Date</label>
                    <input type="date" name="booking_date" required class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Assigned Slot Time</label>
                    <input type="time" name="booking_time" required class="form-control">
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Clinician Selection Preferred</label>
                  <select name="clinician_id" required class="form-select">
                    <?php foreach ($staff_data as $st): ?>
                      <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?> - <?= htmlspecialchars($st['role']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-4">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Symptoms / Medical Ledger notes</label>
                  <textarea name="booking_notes" rows="2" class="form-control" placeholder="Tell us about thermal teeth sensitivity, gum bleeding, orthodontic tracking questions..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-3">Submit Booking Nomination Request</button>
              </form>
            </div>

            <!-- Custom Pricing catalog -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
              <h3 class="fs-6 font-bold text-dark mb-3">Transparent Dental Treatment Catalog (UGX)</h3>
              <div class="row g-3">
                <?php foreach ($services_data as $s): ?>
                  <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                      <div>
                        <h4 class="fs-6 font-bold text-slate-800 mb-1" style="font-size: 13.5px;"><?= htmlspecialchars($s['name']) ?></h4>
                        <p class="text-slate-450 font-sans mb-3" style="font-size: 11px;"><?= htmlspecialchars($s['description'] ?? 'Primary clinic therapy') ?></p>
                      </div>
                      <span class="badge text-indigo bg-light text-start border p-2 font-mono fs-xs" style="color: var(--primary-indigo); background-color: rgba(99, 102, 241, 0.05); width: fit-content;"><?= formatUGX($s['price']) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Column 2: Installments lookups and simulated MoMo Push requests -->
          <div class="col-lg-5">
            <div class="momo-pay-box mb-4">
              <h2 class="fs-5 font-bold text-white mb-2">Secure Mobile Money Installments</h2>
              <p class="text-white-50 fs-xs mb-4">Search your scheduled therapies by phone number or patient name to track invoices, calculate outstanding copays, and pay micro-installments instantly through MTN or Airtel wallets.</p>

              <!-- Search Lookup -->
              <form action="patient.php" method="GET">
                <div class="input-group">
                  <input type="text" name="checkin_phone" value="<?= htmlspecialchars($checkin_phone) ?>" required class="form-control" placeholder="Patient Name or Phone...">
                  <button type="submit" class="btn btn-primary bg-indigo border-0" style="background-color: var(--primary-indigo);">Check In</button>
                </div>
              </form>

              <!-- Patient Profiles Demo trigger shortcuts -->
              <div class="mt-4">
                <div class="text-[10px] uppercase font-mono tracking-wider text-indigo-300" style="font-size: 10px; color: #a5b4fc;">Demo Quick Check-In:</div>
                <div class="d-flex flex-wrap gap-2 mt-1.5">
                  <a href="patient.php?checkin_phone=Namuli" class="badge btn-sub-card border text-decoration-none bg-white text-dark py-1.5 font-sans">Namuli Sarah</a>
                  <a href="patient.php?checkin_phone=Okello" class="badge btn-sub-card border text-decoration-none bg-white text-dark py-1.5 font-sans">Okello Brian</a>
                  <a href="patient.php?checkin_phone=Juliet" class="badge btn-sub-card border text-decoration-none bg-white text-dark py-1.5 font-sans">Agaba Juliet</a>
                </div>
              </div>
            </div>

            <!-- Search matches render loops -->
            <?php if (!empty($checkin_phone)): ?>
              <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                <h3 class="fs-6 font-bold text-dark mb-4">Account Matching Invoices</h3>
                
                <?php if (empty($found_appointments)): ?>
                  <div class="text-center text-muted py-5 text-sm">No scheduled dental folder matches: '<strong><?= htmlspecialchars($checkin_phone) ?></strong>'</div>
                <?php else: ?>
                  <div class="d-flex flex-column gap-4">
                    <?php foreach ($found_appointments as $appt): 
                      $outstanding = floatval($appt['total_cost']) - floatval($appt['amount_paid']);
                      $percent = floatval($appt['total_cost']) > 0 ? (floatval($appt['amount_paid']) / floatval($appt['total_cost'])) * 100 : 0;
                    ?>
                      <div class="pb-4 border-bottom last-border-0">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <div>
                            <span class="badge bg-light text-dark font-mono text-[9px]" style="font-size: 10px;"><?= $appt['appointment_date'] ?></span>
                            <h4 class="fs-6 font-bold text-dark mt-1.5 mb-1"><?= htmlspecialchars($appt['service_name']) ?></h4>
                            <p class="text-slate-400 mb-0 font-sans text-[11.5px]" style="font-size: 11px;">Status: <span class="badge bg-secondary font-mono text-[9px]"><?= $appt['status'] ?></span></p>
                          </div>
                          <span class="badge bg-indigo-sub text-indigo font-bold font-mono" style="color: var(--primary-indigo); background-color: rgba(99, 102, 241, 0.05);"><?= formatUGX($appt['total_cost']) ?></span>
                        </div>

                        <!-- Progress Bar styling -->
                        <div class="mt-3">
                          <div class="d-flex justify-content-between text-[10px] font-mono mb-1" style="font-size: 10.5px;">
                            <span>Ledger Settled: <?= number_format($percent, 0) ?>%</span>
                            <span>Remaining: <?= formatUGX($outstanding) ?></span>
                          </div>
                          <div class="progress" style="height: 6px;">
                            <div class="progress-bar" role="progressbar" style="width: <?= $percent ?>%; background-color: <?= $percent >= 100 ? 'var(--coral-emerald)' : 'var(--primary-indigo)' ?>;"></div>
                          </div>
                        </div>

                        <!-- Payment checkout form trigger -->
                        <?php if ($outstanding > 0): ?>
                          <div class="mt-4 p-3 bg-light rounded-3 border">
                            <h5 class="fs-xs font-bold text-dark uppercase tracking-widest font-mono mb-3">Installment Cashless Settlement</h5>

                            <!-- simulated USSD trigger -->
                            <form action="patient.php" method="POST" onsubmit="return alertUSSDSim(this)">
                              <input type="hidden" name="action" value="submit_momo_payment">
                              <input type="hidden" name="appt_id" value="<?= htmlspecialchars($appt['id']) ?>">
                              
                              <div class="mb-2">
                                <label class="form-label font-bold text-slate-500 text-[10px]" style="font-size: 10px;">INSTALLMENT AMOUNT UGX</label>
                                <input type="number" name="momo_amount" required max="<?= $outstanding ?>" min="100" class="form-control text-xs" style="font-size: 12px;" placeholder="UGX Amount to Pay">
                              </div>

                              <div class="row g-2 mb-3">
                                <div class="col-5">
                                  <label class="form-label font-bold text-slate-500 text-[10px]" style="font-size: 10px;">CHANNEL NETWORK</label>
                                  <select name="momo_channel" class="form-select text-[11px]" style="font-size: 11px;">
                                    <option value="MTN MoMo">MTN MoMo</option>
                                    <option value="Airtel Money">Airtel Money</option>
                                  </select>
                                </div>
                                <div class="col-7">
                                  <label class="form-label font-bold text-slate-500 text-[10px]" style="font-size: 10px;">PAYOR WALLET PHONE NUMBER</label>
                                  <input type="text" name="momo_phone" required value="<?= htmlspecialchars($checkin_phone) ?>" class="form-control text-xs" style="font-size: 11px;" placeholder="07xx xxxxxx">
                                </div>
                              </div>

                              <button type="submit" class="btn btn-primary-custom w-100 fs-xs py-2">Trigger Secure MoMo Push</button>
                            </form>
                          </div>
                        <?php else: ?>
                          <div class="text-success fs-xs font-bold font-sans mt-3 text-center p-2 bg-emerald-50 rounded-3 border border-emerald-100">
                            ✓ This clinic treatment ledger has been fully cleared! Thank you.
                          </div>
                        <?php endif; ?>

                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

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

  <!-- Simulated USSD Checkout script -->
  <script>
    function alertUSSDSim(form) {
      const amt = Number(form.momo_amount.value);
      const phone = form.momo_phone.value;
      const channel = form.momo_channel.value;
      
      const formattedAmt = "UGX " + amt.toLocaleString();
      
      alert(
        "⚡ [Simulated Secure MoMo Connection] ⚡\n\n" +
        "Sending " + channel + " API push notification to mobile wallet owner: " + phone + "\n" +
        "Transaction payload value: " + formattedAmt + "\n\n" +
        "⚙ PLEASE SIMULATE CUSTOMER RESPONSE: Type '4455' on your simulated phone prompt to confirm security PIN clearance..."
      );
      
      alert(
        "✓ pin success! Transaction validated and cleared by MTN/Airtel gateway ledger node.\n" +
        "Press OK to apply installment payment down and update active clinical databases."
      );
      
      return true;
    }
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
