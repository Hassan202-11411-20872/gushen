<?php
/**
 * Goshen Dental Care - CEO Administrative & Portfolio Dashboard
 * Built with HTML, CSS, Bootstrap 5 and secure parameter-bound PHP PDO.
 */

require_once __DIR__ . '/db.php';

// Form Actions: Add Dental Catalog Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_service') {
    $id = 'srv_' . time();
    $name = trim($_POST['service_name']);
    $price = floatval($_POST['service_price']);
    $category = $_POST['service_category'];
    $description = trim($_POST['service_desc']);

    if (!empty($name) && $price > 0) {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO services (id, name, price, category, description) VALUES (:id, :name, :price, :category, :description)");
                $stmt->execute([':id' => $id, ':name' => $name, ':price' => $price, ':category' => $category, ':description' => $description]);
                $_SESSION['flash_msg'] = "Dental Service catalog: '$name' created successfully!";
            } catch (PDOException $e) {
                $_SESSION['err_msg'] = "Database write error: " . $e->getMessage();
            }
        } else {
            $_SESSION['mock_services'][] = ['id' => $id, 'name' => $name, 'price' => $price, 'category' => $category, 'description' => $description];
            $_SESSION['flash_msg'] = "[Mock Mode] Dental Service catalog: '$name' recorded to active memory!";
        }
    }
    header('Location: index.php');
    exit();
}

// Form Actions: Document Clinic Business Outflow Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_expense') {
    $id = 'exp_' . time();
    $category = $_POST['exp_category'];
    $amount = floatval($_POST['exp_amount']);
    $desc = trim($_POST['exp_desc']);
    $date = date('Y-m-d');

    if ($amount > 0) {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO expenses (id, category, amount, date, description) VALUES (:id, :category, :amount, :date, :desc)");
                $stmt->execute([':id' => $id, ':category' => $category, ':amount' => $amount, ':date' => $date, ':desc' => $desc]);
                $_SESSION['flash_msg'] = "Expense payout recorded successfully!";
            } catch (PDOException $e) {
                $_SESSION['err_msg'] = "Database write error: " . $e->getMessage();
            }
        } else {
            $_SESSION['mock_expenses'][] = ['id' => $id, 'category' => $category, 'amount' => $amount, 'date' => $date, 'description' => $desc];
            $_SESSION['flash_msg'] = "[Mock Mode] Expense payout of " . formatUGX($amount) . " logged to active memory!";
        }
    }
    header('Location: index.php');
    exit();
}

// Form Actions: Restock clinical inventory supplies
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'restock_item') {
    $item_id = $_POST['item_id'];
    $qty = floatval($_POST['restock_qty']);

    if (!empty($item_id) && $qty > 0) {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity + :qty WHERE id = :id");
                $stmt->execute([':qty' => $qty, ':id' => $item_id]);
                $_SESSION['flash_msg'] = "Clinical closet restocked successfully!";
            } catch (PDOException $e) {
                $_SESSION['err_msg'] = "Database replenishment update failed: " . $e->getMessage();
            }
        } else {
            foreach ($_SESSION['mock_inventory'] as &$item) {
                if ($item['id'] === $item_id) {
                    $item['quantity'] += $qty;
                }
            }
            $_SESSION['flash_msg'] = "[Mock Mode] Inventory item count adjusted in memory.";
        }
    }
    header('Location: index.php');
    exit();
}

// --- PULL REQUISITE REPORTING BALANCES ---
$services_data = $pdo ? $pdo->query("SELECT * FROM services ORDER BY price ASC")->fetchAll() : $_SESSION['mock_services'];
$staff_data = $pdo ? $pdo->query("SELECT * FROM staff ORDER BY name ASC")->fetchAll() : $_SESSION['mock_staff'];
$inventory_data = $pdo ? $pdo->query("SELECT * FROM inventory ORDER BY name ASC")->fetchAll() : $_SESSION['mock_inventory'];
$expenses_data = $pdo ? $pdo->query("SELECT * FROM expenses ORDER BY date DESC LIMIT 8")->fetchAll() : $_SESSION['mock_expenses'];
$notifications_data = $pdo ? $pdo->query("SELECT * FROM notifications ORDER BY sent_at DESC LIMIT 8")->fetchAll() : $_SESSION['mock_notifications'];

// Reconstruct complete procedural appointments matching the parent grid
if ($pdo) {
    $appt_stmt = $pdo->query("SELECT a.*, s.name as service_name, st.name as clinician_name FROM appointments a JOIN services s ON a.service_id = s.id JOIN staff st ON a.clinician_id = st.id ORDER BY a.created_at DESC");
    $appts = $appt_stmt->fetchAll();
} else {
    $appts = $_SESSION['mock_appointments'];
    // Reconstruct fields manually
    foreach ($appts as &$ap) {
        foreach ($services_data as $s) {
            if ($s['id'] === $ap['service_id']) $ap['service_name'] = $s['name'];
        }
        foreach ($staff_data as $st) {
            if ($st['id'] === $ap['clinician_id']) $ap['clinician_name'] = $st['name'];
        }
    }
}

// Global aggregated report analytics
$total_revenue = 0.00;
$total_due = 0.00;
$total_expenses = 0.00;

foreach ($appts as $ap) {
    if ($ap['status'] === 'treatment_completed') {
        $total_revenue += floatval($ap['total_cost']);
    } else {
        $total_revenue += floatval($ap['amount_paid']); // Payments done for pending slots
    }
    // outstanding debt check
    $total_due += (floatval($ap['total_cost']) - floatval($ap['amount_paid']));
}

foreach ($expenses_data as $exp) {
    $total_expenses += floatval($exp['amount']);
}

$net_operating_profit = $total_revenue - $total_expenses;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Goshen Dental Care - Executive Board</title>
  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Custom Geometric Stylesheet -->
  <link rel="stylesheet" href="styles.css">
</head>
<body>

  <!-- Top Doctris-Style Header -->
  <header class="navbar navbar-expand-lg navbar-light py-2 px-4 sticky-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
          <div class="display-logo fs-4">GOSHEN DENTAL</div>
          <span class="badge text-white px-2 py-1" style="background-color: var(--primary-indigo); font-size: 0.68rem; letter-spacing: 0.05em; font-weight: 600;">KAMPALA</span>
        </div>
        
        <!-- Doctris Search bar -->
        <div class="doctris-search-container d-none d-lg-block ms-4">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="doctris-search-icon"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="text" class="doctris-search-bar" placeholder="Search patients, procedures...">
        </div>
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

  <!-- Main Grid Area Layout -->
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
            <a href="index.php" class="nav-link-custom active">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="sidebar-icon"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="10" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
              <span>CEO Administration Desk</span>
            </a>
            <a href="staff.php" class="nav-link-custom">
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
          <div class="vault-widget p-4 text-white">
            <div class="d-flex align-items-center gap-2 mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-400" style="color: #818cf8;"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              <div class="uppercase tracking-widest font-mono text-white-50" style="font-size: 10px; opacity: 0.75; letter-spacing: 0.08em;">CLINIC VAULT</div>
            </div>
            <div class="font-bold text-white fs-5 font-mono"><?= formatUGX($net_operating_profit) ?></div>
            <div class="text-[10px] text-emerald-400 mt-1 d-flex align-items-center gap-1.5" style="font-size: 10px;">
              <span class="ai-pulse-indicator" style="width: 5px; height: 5px; background-color: #10b981; box-shadow: 0 0 6px #10b981;"></span> Direct Profit Margins
            </div>
          </div>
        </div>
      </nav>

      <!-- RIGHT MODULE: Dynamic workspace -->
      <main class="col-md-9 col-lg-10 p-4 p-md-5">

        <!-- Flash alerts handler -->
        <?php if (isset($_SESSION['flash_msg'])): ?>
          <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <strong>✓ Process Completed:</strong> <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <?php unset($_SESSION['flash_msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['db_error'])): ?>
          <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
            <strong>ℹ Connection Advisory:</strong> <?= $_SESSION['db_error'] ?>
          </div>
        <?php endif; ?>

        <!-- Doctris Elegant Welcome Hero Card -->
        <div class="doctris-welcome-hero d-flex justify-content-between align-items-center mb-5 flex-wrap gap-4">
          <div style="z-index: 2;">
            <span class="doctris-welcome-badge mb-3 d-inline-block">Welcome back Executive Director</span>
            <h1 class="text-dark fs-3 mb-2 font-bold" style="font-family: var(--display-font);">Good Day, Dr. Kato Michael!</h1>
            <p class="text-muted fs-xs max-w-2xl mb-0" style="max-width: 600px;">
              Goshen Orthodontics & Oral Surgery systems are active. Today you have active patient sessions on-deck. Your direct mobile money ledger feeds represent Kampala's top dental portfolio margins.
            </p>
          </div>
          <div class="d-flex gap-2" style="z-index: 2;">
            <a href="staff.php" class="btn btn-primary-custom d-flex align-items-center gap-2 py-2.5 px-4 font-bold" style="font-size: 0.82rem; border-radius: 30px;">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Go to Clinician Queue
            </a>
            <span class="badge bg-light text-slate-700 border p-3 d-flex align-items-center justify-content-center font-mono font-bold" style="border-radius: 30px; font-size: 0.76rem;"><?= date('l, d M Y') ?></span>
          </div>
        </div>

        <!-- 4 Key Reporting Balanced KPI Cards -->
        <div class="row g-4 mb-5">
          <div class="col-sm-6 col-lg-3">
            <div class="metric-card metric-primary">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="metric-label">Total Income Collected</div>
                <div style="background-color: rgba(79, 70, 229, 0.08); padding: 8px; border-radius: 8px; color: var(--primary-indigo);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
              </div>
              <div class="d-flex align-items-baseline gap-1">
                <div class="fs-4 font-bold font-mono text-dark"><?= formatUGX($total_revenue) ?></div>
              </div>
              <div class="text-muted mt-2 font-sans d-flex align-items-center justify-content-between" style="font-size: 11px;">
                <span>Direct dental payments</span>
                <span class="trend-pill trend-up">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> +18.4%
                </span>
              </div>
            </div>
          </div>
          
          <div class="col-sm-6 col-lg-3">
            <div class="metric-card metric-warning">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="metric-label">Outstanding Installments</div>
                <div style="background-color: rgba(245, 158, 11, 0.08); padding: 8px; border-radius: 8px; color: var(--amber-gold);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/></svg>
                </div>
              </div>
              <div class="d-flex align-items-baseline gap-1">
                <div class="fs-4 font-bold font-mono text-dark"><?= formatUGX($total_due) ?></div>
              </div>
              <div class="text-muted mt-2 font-sans d-flex align-items-center justify-content-between" style="font-size: 11px;">
                <span>Active recovery tracking</span>
                <span class="trend-pill trend-down">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg> -2.5%
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="metric-card metric-danger">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="metric-label">Documented Expenses</div>
                <div style="background-color: rgba(239, 68, 110, 0.08); padding: 8px; border-radius: 8px; color: var(--danger-crimson);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4 4 4-4"/><path d="M7 20V4"/><path d="M21 8l-4-4-4 4"/><path d="M17 4v16"/></svg>
                </div>
              </div>
              <div class="d-flex align-items-baseline gap-1">
                <div class="fs-4 font-bold font-mono text-dark"><?= formatUGX($total_expenses) ?></div>
              </div>
              <div class="text-muted mt-2 font-sans d-flex align-items-center justify-content-between" style="font-size: 11px;">
                <span>Utilities, rents & stocks</span>
                <span class="trend-pill trend-up" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> +4.1%
                </span>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="metric-card metric-success">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="metric-label">Net Cash Surplus</div>
                <div style="background-color: rgba(16, 185, 129, 0.08); padding: 8px; border-radius: 8px; color: var(--coral-emerald);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
              </div>
              <div class="d-flex align-items-baseline gap-1">
                <div class="fs-4 font-bold font-mono text-dark"><?= formatUGX($net_operating_profit) ?></div>
              </div>
              <div class="text-muted mt-2 font-sans d-flex align-items-center justify-content-between" style="font-size: 11px;">
                <span>Available ledger capital</span>
                <span class="trend-pill trend-up">
                  <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg> +21.9%
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Procedures and Dynamic Catalogues Forms -->
        <div class="row g-4 mb-5">
          
          <!-- Dental Procedures Table -->
          <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
              <h3 class="fs-6 font-bold text-dark mb-3">Pre-registered Patient Schedules & Ledgers</h3>
              <div class="table-responsive">
                <table class="table clinical-table w-100">
                  <thead>
                    <tr>
                      <th>Patient Name</th>
                      <th>Procedure / Care Assigned</th>
                      <th>Date / Time</th>
                      <th>Clinic Budget Mapping</th>
                      <th>Payment Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($appts)): ?>
                      <tr>
                        <td colspan="5" class="text-center text-muted py-4">No active appointments logged.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($appts as $ap): 
                        $status_class = 'status-unpaid';
                        if ($ap['payment_status'] === 'fully_paid') $status_class = 'status-paid';
                        if ($ap['payment_status'] === 'partial') $status_class = 'status-partial';
                      ?>
                        <tr>
                          <td>
                            <div class="d-flex align-items-center gap-2.5">
                              <?php 
                                $hash = md5($ap['patient_name']);
                                $colors = [
                                  ['#3b82f6', '#93c5fd'], // Blue
                                  ['#10b981', '#6ee7b7'], // Green
                                  ['#f59e0b', '#fcd34d'], // Amber
                                  ['#8b5cf6', '#c4b5fd'], // Violet
                                  ['#ec4899', '#fbcfe8'], // Pink
                                  ['#14b8a6', '#99f6e4']  // Teal
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
                              <div class="patient-circle-avatar" style="background: linear-gradient(135deg, <?= $grad_start ?>, <?= $grad_end ?>);">
                                <?= $initials ?>
                              </div>
                              <div>
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($ap['patient_name']) ?></div>
                                <span class="text-muted font-mono" style="font-size: 10px;"><?= htmlspecialchars($ap['patient_phone']) ?></span>
                              </div>
                            </div>
                          </td>
                          <td>
                            <div class="font-semibold text-slate-700"><?= htmlspecialchars($ap['service_name'] ?? 'Custom Dental Diagnostics') ?></div>
                            <span class="text-muted font-mono" style="font-size: 10px;">with <?= htmlspecialchars($ap['clinician_name'] ?? 'Senior Clinician') ?></span>
                          </td>
                          <td>
                            <div class="text-indigo font-bold" style="font-size: 11px;"><?= $ap['appointment_date'] ?></div>
                            <span class="text-slate-400" style="font-size: 10px;"><?= $ap['appointment_time'] ?></span>
                          </td>
                          <td>
                            <div class="font-bold text-slate-800 font-mono"><?= formatUGX($ap['total_cost']) ?></div>
                            <span class="text-muted font-mono" style="font-size: 10px;">Paid: <?= formatUGX($ap['amount_paid']) ?></span>
                          </td>
                          <td>
                            <span class="status-badge <?= $status_class ?>">
                              <?= htmlspecialchars($ap['payment_status']) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Add Service to dental menu catalog -->
          <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
              <h3 class="fs-6 font-bold text-dark mb-3">Register New Dental Service Offering</h3>
              <form action="index.php" method="POST">
                <input type="hidden" name="action" value="add_service">
                
                <div class="mb-3">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Service Name</label>
                  <input type="text" name="service_name" required class="form-control" placeholder="e.g. Laser Root Canal Therapy">
                </div>

                <div class="row g-2 mb-3">
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Price index (UGX)</label>
                    <input type="number" name="service_price" required min="1000" class="form-control" placeholder="350000">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Category</label>
                    <select name="service_category" class="form-select">
                      <option value="Preventive">Preventive</option>
                      <option value="Curative">Curative</option>
                      <option value="Aesthetic">Aesthetic</option>
                      <option value="Surgical">Surgical</option>
                    </select>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Detailed Description</label>
                  <textarea name="service_desc" rows="2" class="form-control" placeholder="Detail clinical procedure pathways..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100">Activate Catalog Offer</button>
              </form>
            </div>

            <!-- Expense Document Panel -->
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
              <h3 class="fs-6 font-bold text-dark mb-3">Log Medical / Facility Expense</h3>
              <form action="index.php" method="POST">
                <input type="hidden" name="action" value="add_expense">

                <div class="row g-2 mb-3">
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Category</label>
                    <select name="exp_category" class="form-select">
                      <option value="Rent">Rent</option>
                      <option value="Utilities">Utilities</option>
                      <option value="Supplies">Supplies</option>
                      <option value="Salaries">Salaries</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label font-bold text-slate-600 fs-xs uppercase">Amount (UGX)</label>
                    <input type="number" name="exp_amount" required min="500" class="form-control" placeholder="180000">
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Description / Vendor Name</label>
                  <input type="text" name="exp_desc" class="form-control" placeholder="Procured boxes of nitrile gloves">
                </div>

                <button type="submit" class="btn btn-dark w-100 fs-xs font-bold py-2" style="border-radius: 10px;">Add Outflow Entry</button>
              </form>
            </div>

          </div>

        </div>

        <!-- Inventory and Stock closet -->
        <div class="row g-4 mb-5">
          
          <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
              <h3 class="fs-6 font-bold text-dark mb-3">Dentistry Inventory Supply Thresholds</h3>
              <div class="table-responsive">
                <table class="table clinical-table w-100">
                  <thead>
                    <tr>
                      <th>Supply Material Name</th>
                      <th>Closet reserves</th>
                      <th>Minimum Threshold</th>
                      <th>Unit Cost</th>
                      <th>Status alert</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($inventory_data as $inv): 
                      $is_low = floatval($inv['quantity']) <= floatval($inv['threshold']);
                    ?>
                      <tr>
                        <td class="font-bold text-dark"><?= htmlspecialchars($inv['name']) ?></td>
                        <td class="font-mono font-bold text-indigo"><?= number_format($inv['quantity'], 0) ?> <?= htmlspecialchars($inv['unit']) ?></td>
                        <td class="font-mono text-muted"><?= number_format($inv['threshold'], 0) ?></td>
                        <td class="font-mono"><?= formatUGX($inv['cost_per_unit']) ?></td>
                        <td>
                          <?php if ($is_low): ?>
                            <span class="badge bg-danger rounded-pill px-3">Critical Shortage</span>
                          <?php else: ?>
                            <span class="badge bg-success rounded-pill px-3">Adequate Stock</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
              <h3 class="fs-6 font-bold text-dark mb-3">Restock Inventory Material Closets</h3>
              <p class="text-slate-400 fs-xs">Replenish medical composites, latex gloves, local anesthetic solutions to maintain active clinic operations.</p>
              
              <form action="index.php" method="POST" class="mt-3">
                <input type="hidden" name="action" value="restock_item">
                
                <div class="mb-3">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Select Material Category</label>
                  <select name="item_id" required class="form-select">
                    <?php foreach ($inventory_data as $inv): ?>
                      <option value="<?= $inv['id'] ?>"><?= htmlspecialchars($inv['name']) ?> (Current: <?= $inv['quantity'] ?>)</option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="mb-4">
                  <label class="form-label font-bold text-slate-600 fs-xs uppercase">Stock Units to add</label>
                  <input type="number" name="restock_qty" required min="1" class="form-control" placeholder="10">
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-2">Adjust Closet Inventory Count</button>
              </form>
            </div>
          </div>

        </div>

        <!-- Outbound Medical Notification Dispatch Feed -->
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
          <h3 class="fs-6 font-bold text-dark mb-3">Outgoing SMS / Email Installments Reminder Alerts log</h3>
          <div class="table-responsive">
            <table class="table clinical-table w-100">
              <thead>
                <tr>
                  <th>Patient Target User</th>
                  <th>Reminders Subject</th>
                  <th>Content Message preview</th>
                  <th>Dispatched timestamp</th>
                  <th>System Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($notifications_data)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">No active notifications sent.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($notifications_data as $notif): ?>
                    <tr>
                      <td class="font-bold text-dark"><?= htmlspecialchars($notif['patient_name']) ?></td>
                      <td class="font-semibold text-slate-700"><?= htmlspecialchars($notif['subject']) ?></td>
                      <td class="text-slate-500 font-sans font-xs" style="font-size: 11px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($notif['content']) ?></td>
                      <td class="font-mono text-muted"><?= $notif['sent_at'] ?></td>
                      <td>
                        <span class="badge bg-success rounded-pill px-2.5">Dispatched Out</span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>

    </div>
  </div>

  <footer class="bg-dark text-white-50 text-center py-4 border-top border-secondary fs-xs mt-auto">
    <div class="container d-flex flex-col flex-md-row justify-content-between align-items-center gap-2">
      <p class="mb-0 text-start text-white-50">© 2026 Goshen Dental Care management system. Fully tailored design with automated billing reminders for dental operations in Kampala, Uganda.</p>
      <div class="d-flex gap-3">
         <span class="text-white-50">Regulatory Standards Compliance</span>
         <span>|</span>
         <span class="text-white-50">Mobile Money secure platform</span>
      </div>
    </div>
  </footer>

  <!-- Bootstrap 5 Bundle CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
