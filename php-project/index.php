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

  <!-- Top Static Header -->
  <header class="navbar navbar-expand-lg navbar-dark bg-dark py-3 px-4 border-bottom border-secondary">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center gap-2">
        <div class="display-logo text-white fs-4">GOSHEN DENTAL</div>
        <span class="badge bg-indigo text-white px-2 py-1 fs-xs" style="background-color: var(--primary-indigo)">KAMPALA</span>
      </div>
      <div>
        <span class="text-white-50 fs-xs me-3">Current Mode: <strong>Standalone PHP & MySQL System</strong></span>
        <span class="badge bg-success">● Status Online</span>
      </div>
    </div>
  </header>

  <!-- Main Grid Area Layout -->
  <div class="container-fluid flex-grow-1">
    <div class="row min-h-100">
      
      <!-- LEFT MODULE: Primary Navigation Panel -->
      <nav class="col-md-3 col-lg-2 bg-indigo-sidebar p-4 d-flex flex-column gap-4">
        <div>
          <div class="sidebar-title mb-3">Operating Portals</div>
          <div class="d-flex flex-column gap-2">
            <a href="index.php" class="nav-link-custom active">
              <span>Admin Director Dashboard</span>
            </a>
            <a href="staff.php" class="nav-link-custom">
              <span>Staff Clinician Terminal</span>
            </a>
            <a href="patient.php" class="nav-link-custom">
              <span>Client Booking Hub</span>
            </a>
          </div>
        </div>

        <div class="mt-auto">
          <div class="p-3 bg-white/5 rounded-3 border border-white/10">
            <div class="text-[10px] text-white-50 uppercase tracking-widest font-mono mb-1">Clinic Vault</div>
            <div class="font-bold text-white fs-6 font-mono"><?= formatUGX($net_operating_profit) ?></div>
            <div class="text-[10px] text-emerald-400 mt-1">Direct Profit Margins</div>
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

        <!-- Layout Header Titles -->
        <div class="d-flex justify-content-between align-items-center mb-5 flex-wrap gap-2">
          <div>
            <h1 class="text-dark">CEO Executive Administration</h1>
            <p class="text-muted fs-xs">Financial statements, dental procedure catalogs, stocks reserve grids, and outbound email alerts.</p>
          </div>
          <span class="badge bg-light text-dark border p-2 font-mono ms-auto"><?= date('l, d M Y') ?></span>
        </div>

        <!-- 4 Key Reporting Balanced KPI Cards -->
        <div class="row g-4 mb-5">
          <div class="col-sm-6 col-lg-3">
            <div class="metric-card">
              <div class="metric-label mb-2">Total Income Collected</div>
              <div class="fs-4 font-bold font-mono text-indigo"><?= formatUGX($total_revenue) ?></div>
              <div class="text-muted d-flex align-items-center gap-1 mt-1 font-sans font-xs">
                <span>Direct dental inflow payments</span>
              </div>
            </div>
          </div>
          
          <div class="col-sm-6 col-lg-3">
            <div class="metric-card">
              <div class="metric-label mb-2">Outstanding Installments</div>
              <div class="fs-4 font-bold font-mono text-warning text-amber-600"><?= formatUGX($total_due) ?></div>
              <div class="text-muted mt-1 font-sans font-xs">Locked in active recovery tracking</div>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="metric-card">
              <div class="metric-label mb-2">Documented Expenses</div>
              <div class="fs-4 font-bold font-mono text-danger"><?= formatUGX($total_expenses) ?></div>
              <div class="text-muted mt-1 font-sans font-xs">Umeme electricity, facility rents & stocks</div>
            </div>
          </div>

          <div class="col-sm-6 col-lg-3">
            <div class="metric-card">
              <div class="metric-label mb-2">Net Cash Surplus</div>
              <div class="fs-4 font-bold font-mono text-success text-emerald-600"><?= formatUGX($net_operating_profit) ?></div>
              <div class="text-muted mt-1 font-sans font-xs">Available operational ledger capital</div>
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
                            <div class="font-bold text-slate-800"><?= htmlspecialchars($ap['patient_name']) ?></div>
                            <span class="text-muted font-mono" style="font-size: 10px;"><?= htmlspecialchars($ap['patient_phone']) ?></span>
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
