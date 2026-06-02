import express from "express";
import fs from "fs";
import path from "path";

const app = express();
const PORT = 3000;

app.use(express.urlencoded({ extended: true }));
app.use(express.json());

// Serving styles.css directly
app.get("/styles.css", (req, res) => {
  res.sendFile(path.join(process.cwd(), "styles.css"));
});

// Database State matching db.php mock dataset perfectly so changes persist actively!
interface Service {
  id: string;
  name: string;
  price: number;
  category: string;
  description: string;
}

interface Staff {
  id: string;
  name: string;
  role: string;
  procedures_completed: number;
  revenue_generated: number;
  patient_rating: number;
}

interface Inventory {
  id: string;
  name: string;
  quantity: number;
  threshold: number;
  unit: string;
  cost_per_unit: number;
}

interface Expense {
  id: string;
  category: string;
  amount: number;
  date: string;
  description: string;
}

interface Appointment {
  id: string;
  patient_name: string;
  patient_phone: string;
  patient_email: string;
  service_id: string;
  appointment_date: string;
  appointment_time: string;
  status: string;
  clinician_id: string;
  total_cost: number;
  amount_paid: number;
  payment_status: string;
  clinical_notes: string;
  treatment_plan_step?: string;
  rx_prescribed?: string;
  reminders_sent: number;
  created_at: string;
}

interface Installment {
  id: string;
  appointment_id: string;
  amount: number;
  date: string;
  method: string;
  notes: string;
}

interface Notification {
  id: string;
  appointment_id: string;
  patient_email: string;
  patient_name: string;
  subject: string;
  content: string;
  sent_at: string;
  status: string;
}

let services: Service[] = [
  { id: 'srv_1', name: 'Routine Clinical Examination', price: 50000, category: 'Preventive', description: 'Full digital diagnostics...' },
  { id: 'srv_2', name: 'Scaling & Polishing (Standard)', price: 120000, category: 'Preventive', description: 'Complete removal of calculus...' },
  { id: 'srv_3', name: 'Deep Root Canal Therapy', price: 450000, category: 'Curative', description: 'Nerve extirpation and core build-up...' },
  { id: 'srv_4', name: 'Surgical Wisdom Tooth Extraction', price: 280000, category: 'Surgical', description: 'Atraumatic removal of third molars...' },
  { id: 'srv_5', name: 'Orthodontic Braces Alignment', price: 3500000, category: 'Aesthetic', description: 'Premium bracket system alignment...' }
];

let staff: Staff[] = [
  { id: 'st_1', name: 'Dr. Kato Michael, DDS', role: 'Lead Orthodontic Surgeon', procedures_completed: 142, revenue_generated: 14200000, patient_rating: 4.95 },
  { id: 'st_2', name: 'Dr. Nakimera Brenda', role: 'Senior Endodontist', procedures_completed: 118, revenue_generated: 9820000, patient_rating: 4.88 },
  { id: 'st_3', name: 'Dr. Lwanga Arthur', role: 'General Dental Practitioner', procedures_completed: 89, revenue_generated: 5400000, patient_rating: 4.90 },
  { id: 'st_4', name: 'Sr. Nsubuga Joan', role: 'Registered Clinical Hygienist', procedures_completed: 205, revenue_generated: 3100000, patient_rating: 4.85 }
];

let inventory: Inventory[] = [
  { id: 'inv_1', name: 'Latex Medical Gloves (Box 100s)', quantity: 12, threshold: 20, unit: 'Boxes', cost_per_unit: 35000 },
  { id: 'inv_2', name: 'Local Anesthesia Vials (Lignocaine)', quantity: 75, threshold: 50, unit: 'Vials', cost_per_unit: 4500 },
  { id: 'inv_3', name: 'Dental Composite Bonding Agent', quantity: 6, threshold: 10, unit: 'Syringes', cost_per_unit: 120000 }
];

let expenses: Expense[] = [
  { id: 'exp_1', category: 'Rent', amount: 1200000, date: '2026-06-01', description: 'Kampala HQ clinical facility lease' },
  { id: 'exp_2', category: 'Utilities', amount: 340000, date: '2026-06-02', description: 'Grid Umeme power billing' }
];

let appointments: Appointment[] = [
  {
    id: 'appt_1', patient_name: 'Namuli Sarah', patient_phone: '+256 772 123456', patient_email: 'sarah.namuli@gmail.com', service_id: 'srv_3', 
    appointment_date: '2026-06-03', appointment_time: '09:30:00', status: 'pending', clinician_id: 'st_2', total_cost: 450000, amount_paid: 300000, 
    payment_status: 'partial', clinical_notes: 'Patient reports dental pain.', treatment_plan_step: 'Step 2 of 3: Root canal obturation and filling', rx_prescribed: 'Ibuprofen 400mg', reminders_sent: 1, created_at: '2026-06-02 10:00:00'
  },
  {
    id: 'appt_2', patient_name: 'Okello Brian', patient_phone: '+256 701 987654', patient_email: 'brian.okello@outlook.com', service_id: 'srv_2', 
    appointment_date: '2026-06-02', appointment_time: '11:00:00', status: 'treatment_completed', clinician_id: 'st_4', total_cost: 120000, amount_paid: 120000, 
    payment_status: 'fully_paid', clinical_notes: 'Scaling completed successfully.', treatment_plan_step: 'Completed prophylactic scaling', rx_prescribed: 'None', reminders_sent: 0, created_at: '2026-06-02 08:30:00'
  },
  {
    id: 'appt_3', patient_name: 'Agaba Juliet', patient_phone: '+256 755 443322', patient_email: 'juliet.agaba@yahoo.com', service_id: 'srv_4', 
    appointment_date: '2026-06-10', appointment_time: '14:00:00', status: 'pending', clinician_id: 'st_1', total_cost: 280000, amount_paid: 0, 
    payment_status: 'unpaid', clinical_notes: 'Surgical release required.', treatment_plan_step: '', rx_prescribed: 'Amoxicillin 500mg', reminders_sent: 2, created_at: '2026-06-02 15:30:00'
  }
];

let installments: Installment[] = [
  { id: 'inst_1', appointment_id: 'appt_1', amount: 150000, date: '2026-06-02 10:15:00', method: 'MTN MoMo', notes: 'Downpayment for Root Canal treatment' },
  { id: 'inst_2', appointment_id: 'appt_1', amount: 150000, date: '2026-06-02 17:45:00', method: 'Airtel Money', notes: 'Seeding first milestone installment settlement' }
];

let notifications: Notification[] = [
  { id: 'notif_1', appointment_id: 'appt_1', patient_email: 'sarah.namuli@gmail.com', patient_name: 'Namuli Sarah', subject: 'Goshen Appointment Confirmation: Root Canal', content: 'Scheduled for 2026-06-03 with Dr. Nakimera.', sent_at: '2026-06-02 10:05:00', status: 'sent' }
];

let flash_msg: string | null = null;

function formatUGX(amount: number) {
  return "UGX " + Math.round(amount).toLocaleString();
}

function stripPHPHeader(filepath: string): string {
  const fileContent = fs.readFileSync(filepath, "utf8");
  const htmlStart = fileContent.indexOf("<!DOCTYPE html>");
  if (htmlStart !== -1) {
    return fileContent.substring(htmlStart);
  }
  return fileContent;
}

// Redirect root to index.php
app.get("/", (req, res) => {
  res.redirect("/index.php");
});

// GET ROUTE: index.php (Director Dashboard)
app.get("/index.php", (req, res) => {
  let html = stripPHPHeader("./index.php");

  // Re-calculate aggregations
  let total_revenue = 0;
  let total_due = 0;
  let total_expenses = 0;

  for (const ap of appointments) {
    if (ap.status === "treatment_completed") {
      total_revenue += Number(ap.total_cost);
    } else {
      total_revenue += Number(ap.amount_paid);
    }
    total_due += (Number(ap.total_cost) - Number(ap.amount_paid));
  }

  for (const exp of expenses) {
    total_expenses += Number(exp.amount);
  }

  const net_operating_profit = total_revenue - total_expenses;

  // Render Flash alerts
  let flashAlertHTML = "";
  if (flash_msg) {
    flashAlertHTML = `
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>✓ Process Completed:</strong> ${flash_msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    flash_msg = null; // Clear after use
  }

  const advisoryAlertHTML = `
    <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
      <strong>ℹ Sandbox Cloud Preview:</strong> Operational in active memory simulation (mock database mode). Changes persist actively during your session. Download project to run with full PDO & local MySQL databases!
    </div>
  `;

  // Render appointments table rows
  const apptRowsHTML = appointments.map(ap => {
    const service = services.find(s => s.id === ap.service_id);
    const service_name = service ? service.name : "Custom Dental Diagnostics";
    const clinician = staff.find(st => st.id === ap.clinician_id);
    const clinician_name = clinician ? clinician.name : "Senior Clinician";
    
    let status_class = "status-unpaid";
    if (ap.payment_status === "fully_paid") status_class = "status-paid";
    if (ap.payment_status === "partial") status_class = "status-partial";
    
    return `
      <tr>
        <td>
          <div class="font-bold text-slate-800">${ap.patient_name}</div>
          <span class="text-muted font-mono" style="font-size: 10px;">${ap.patient_phone}</span>
        </td>
        <td>
          <div class="font-semibold text-slate-700">${service_name}</div>
          <span class="text-muted font-mono" style="font-size: 10px;">with ${clinician_name}</span>
        </td>
        <td>
          <div class="text-indigo font-bold" style="font-size: 11px;">${ap.appointment_date}</div>
          <span class="text-slate-400" style="font-size: 10px;">${ap.appointment_time}</span>
        </td>
        <td>
          <div class="font-bold text-slate-800 font-mono">${formatUGX(ap.total_cost)}</div>
          <span class="text-muted font-mono" style="font-size: 10px;">Paid: ${formatUGX(ap.amount_paid)}</span>
        </td>
        <td>
          <span class="status-badge ${status_class}">
            ${ap.payment_status}
          </span>
        </td>
      </tr>
    `;
  }).join("\n");

  const inventoryRowsHTML = inventory.map(inv => {
    const is_low = inv.quantity <= inv.threshold;
    const status_badge = is_low 
      ? `<span class="badge bg-danger rounded-pill px-3">Critical Shortage</span>`
      : `<span class="badge bg-success rounded-pill px-3">Adequate Stock</span>`;
      
    return `
      <tr>
        <td class="font-bold text-dark">${inv.name}</td>
        <td class="font-mono font-bold text-indigo">${inv.quantity} ${inv.unit}</td>
        <td class="font-mono text-muted">${inv.threshold}</td>
        <td class="font-mono">${formatUGX(inv.cost_per_unit)}</td>
        <td>${status_badge}</td>
      </tr>
    `;
  }).join("\n");

  const restockOptionsHTML = inventory.map(inv => {
    return `<option value="${inv.id}">${inv.name} (Current: ${inv.quantity})</option>`;
  }).join("\n");

  const notificationRowsHTML = notifications.map(notif => {
    return `
      <tr>
        <td class="font-bold text-dark">${notif.patient_name}</td>
        <td class="font-semibold text-slate-700">${notif.subject}</td>
        <td class="text-slate-500 font-sans font-xs" style="font-size: 11px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${notif.content}</td>
        <td class="font-mono text-muted">${notif.sent_at}</td>
        <td>
          <span class="badge bg-success rounded-pill px-2.5">Dispatched Out</span>
        </td>
      </tr>
    `;
  }).join("\n");

  // Dynamic replacements template matching
  const replacements: Record<string, string> = {
    "<?= formatUGX($net_operating_profit) ?>": formatUGX(net_operating_profit),
    "<?= formatUGX($total_revenue) ?>": formatUGX(total_revenue),
    "<?= formatUGX($total_due) ?>": formatUGX(total_due),
    "<?= formatUGX($total_expenses) ?>": formatUGX(total_expenses),
    "<?= date('l, d M Y') ?>": new Date().toLocaleDateString("en-US", { weekday: "long", day: "2-digit", month: "short", year: "numeric" }),
    "<?php if (isset($_SESSION['flash_msg'])): ?>": flashAlertHTML,
    "<?php unset($_SESSION['flash_msg']); ?>": "",
    "<?php endif; ?>": "",
    "<?php if (isset($_SESSION['db_error'])): ?>": advisoryAlertHTML,
  };

  // Run replacements
  for (const [key, val] of Object.entries(replacements)) {
    html = html.split(key).join(val);
  }

  // Handle the customized loop ranges
  // 1. Pre-registered schedules table
  const apptRegex = /<\?php if \(empty\(\$appts\)\):\ ?>([\s\S]*?)<\?php endif;\ ?>/;
  html = html.replace(apptRegex, apptRowsHTML);

  // 2. Inventory threshold material closet
  const inventoryRegex = /<\?php foreach \(\$inventory_data as \$inv\):[\s\S]*?<\?php endforeach;\ ?>/;
  html = html.replace(inventoryRegex, inventoryRowsHTML);

  // 3. Select restock closet material
  const restockRegex = /<\?php foreach \(\$inventory_data as \$inv\):\ ?>[\s\S]*?<\?php endforeach;\ ?>/;
  html = html.replace(restockRegex, restockOptionsHTML);

  // 4. Notifications reports list
  const notifRegex = /<\?php if \(empty\(\$notifications_data\)\):[\s\S]*?<\?php endif;\ ?>/;
  html = html.replace(notifRegex, notificationRowsHTML);

  res.send(html);
});

// POST ACTION: index.php forms processing
app.post("/index.php", (req, res) => {
  const action = req.body.action;

  if (action === "add_service") {
    const id = "srv_" + Date.now();
    const name = String(req.body.service_name).trim();
    const price = parseFloat(req.body.service_price);
    const category = req.body.service_category;
    const description = String(req.body.service_desc || "").trim();

    if (name && price > 0) {
      services.push({ id, name, price, category, description });
      flash_msg = `Dental Service catalog: '${name}' created successfully!`;
    }
  } else if (action === "add_expense") {
    const id = "exp_" + Date.now();
    const category = req.body.exp_category;
    const amount = parseFloat(req.body.exp_amount);
    const desc = String(req.body.exp_desc || "").trim();
    const date = new Date().toISOString().substring(0, 10);

    if (amount > 0) {
      expenses.push({ id, category, amount, date, description: desc });
      flash_msg = `Expense payout of ${formatUGX(amount)} logged directly!`;
    }
  } else if (action === "restock_item") {
    const item_id = req.body.item_id;
    const qty = parseFloat(req.body.restock_qty);

    if (item_id && qty > 0) {
      const match = inventory.find(inv => inv.id === item_id);
      if (match) {
        match.quantity += qty;
        flash_msg = `Clinical inventory closet replenished: '${match.name}' count altered by +${qty}!`;
      }
    }
  }

  res.redirect("/index.php");
});

// GET ROUTE: patient.php (Client / Guest Booking panel)
app.get("/patient.php", (req, res) => {
  let html = stripPHPHeader("./patient.php");

  // Dynamic dropdown list mapping
  const serviceOptionsHTML = services.map(s => {
    return `<option value="${s.id}">${s.name} (${formatUGX(s.price)})</option>`;
  }).join("\n");

  const staffOptionsHTML = staff.map(st => {
    return `<option value="${st.id}">${st.name} - ${st.role}</option>`;
  }).join("\n");

  const serviceCardsHTML = services.map(s => {
    return `
      <div class="col-sm-6">
        <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
          <div>
            <h4 class="fs-6 font-bold text-slate-800 mb-1" style="font-size: 13.5px;">${s.name}</h4>
            <p class="text-slate-400 mb-3" style="font-size: 11px;">${s.description || 'Primary clinic therapy'}</p>
          </div>
          <span class="badge text-indigo bg-light text-start border p-2 font-mono fs-xs" style="color: var(--primary-indigo); background-color: rgba(99, 102, 241, 0.05); width: fit-content;">${formatUGX(s.price)}</span>
        </div>
      </div>
    `;
  }).join("\n");

  // Handle Search matches logic
  const checkin_phone = req.query.checkin_phone ? String(req.query.checkin_phone).trim() : "";
  let searchResultHTML = "";

  if (checkin_phone) {
    const found_appointments = appointments.filter(ap => {
      return ap.patient_name.toLowerCase().includes(checkin_phone.toLowerCase()) || 
             ap.patient_phone.toLowerCase().includes(checkin_phone.toLowerCase());
    });

    if (found_appointments.length === 0) {
      searchResultHTML = `<div class="text-center text-muted py-5 text-sm">No scheduled dental folder matches: '<strong>${checkin_phone}</strong>'</div>`;
    } else {
      const recordsHTML = found_appointments.map(appt => {
        const service = services.find(s => s.id === appt.service_id);
        const service_name = service ? service.name : "Custom Dental Diagnostics";
        const outstanding = Number(appt.total_cost) - Number(appt.amount_paid);
        const percent = appt.total_cost > 0 ? (appt.amount_paid / appt.total_cost) * 100 : 0;

        let actionFormHTML = "";
        if (outstanding > 0) {
          actionFormHTML = `
            <div class="mt-4 p-3 bg-light rounded-3 border">
              <h5 class="fs-xs font-bold text-dark uppercase tracking-widest font-mono mb-3">Installment Cashless Settlement</h5>
              <form action="patient.php" method="POST" onsubmit="return alertUSSDSim(this)">
                <input type="hidden" name="action" value="submit_momo_payment">
                <input type="hidden" name="appt_id" value="${appt.id}">
                
                <div class="mb-2">
                  <label class="form-label font-bold text-slate-500 text-[10px]" style="font-size: 10px;">INSTALLMENT AMOUNT UGX</label>
                  <input type="number" name="momo_amount" required max="${outstanding}" min="100" class="form-control text-xs" style="font-size: 12px;" placeholder="UGX Amount to Pay">
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
                    <input type="text" name="momo_phone" required value="${checkin_phone}" class="form-control text-xs" style="font-size: 11px;" placeholder="07xx xxxxxx">
                  </div>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 fs-xs py-2">Trigger Secure MoMo Push</button>
              </form>
            </div>
          `;
        } else {
          actionFormHTML = `
            <div class="text-success fs-xs font-bold font-sans mt-3 text-center p-2 bg-emerald-50 rounded-3 border border-emerald-100">
              ✓ This clinic treatment ledger has been fully cleared! Thank you.
            </div>
          `;
        }

        return `
          <div class="pb-4 border-bottom last-border-0">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <span class="badge bg-light text-dark font-mono text-[9px]" style="font-size: 10px;">${appt.appointment_date}</span>
                <h4 class="fs-6 font-bold text-dark mt-1.5 mb-1">${service_name}</h4>
                <p class="text-slate-400 mb-0 font-sans text-[11.5px]" style="font-size: 11px;">Status: <span class="badge bg-secondary font-mono text-[9px]">${appt.status}</span></p>
              </div>
              <span class="badge bg-indigo-sub text-indigo font-bold font-mono" style="color: var(--primary-indigo); background-color: rgba(99, 102, 241, 0.05);">${formatUGX(appt.total_cost)}</span>
            </div>

            <div class="mt-3">
              <div class="d-flex justify-content-between text-[10px] font-mono mb-1" style="font-size: 10.5px;">
                <span>Ledger Settled: ${Math.round(percent)}%</span>
                <span>Remaining: ${formatUGX(outstanding)}</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar" role="progressbar" style="width: ${percent}%; background-color: ${percent >= 100 ? 'var(--coral-emerald)' : 'var(--primary-indigo)'};"></div>
              </div>
            </div>
            ${actionFormHTML}
          </div>
        `;
      }).join("\n");

      searchResultHTML = `
        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
          <h3 class="fs-6 font-bold text-dark mb-4">Account Matching Invoices</h3>
          <div class="d-flex flex-column gap-4">
            ${recordsHTML}
          </div>
        </div>
      `;
    }
  }

  // Render Flash alerts
  let flashAlertHTML = "";
  if (flash_msg) {
    flashAlertHTML = `
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>✓ Process Completed:</strong> ${flash_msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    flash_msg = null; // Clear
  }

  // Run replacements
  const replacements: Record<string, string> = {
    "<?= htmlspecialchars($checkin_phone) ?>": checkin_phone,
    "<?php if (isset($_SESSION['flash_msg'])): ?>": flashAlertHTML,
    "<?php unset($_SESSION['flash_msg']); ?>": "",
    "<?php endif; ?>": "",
  };

  for (const [key, val] of Object.entries(replacements)) {
    html = html.split(key).join(val);
  }

  // Inject Booking services dropdown
  const sDropdownRegex = /<\?php foreach \(\$services_data as \$serv\):[\s\S]*?<\/select>/;
  html = html.replace(sDropdownRegex, `${serviceOptionsHTML}</select>`);

  // Inject Staff option options
  const staffDropdownRegex = /<\?php foreach \(\$staff_data as \$st\):[\s\S]*?<\/select>/;
  html = html.replace(staffDropdownRegex, `${staffOptionsHTML}</select>`);

  // Inject service catalog listings cards
  const cardGridRegex = /<\?php foreach \(\$services_data as \$s\):[\s\S]*?<\?php endforeach;\ ?>/;
  html = html.replace(cardGridRegex, serviceCardsHTML);

  // Inject lookup output matching panel
  const lookupPanelRegex = /<\?php if \(!empty\(\$checkin_phone\)\): ?>[\s\S]*?<\?php endif;\ ?>/;
  html = html.replace(lookupPanelRegex, searchResultHTML);

  res.send(html);
});

// POST ACTION: patient.php form processor
app.post("/patient.php", (req, res) => {
  const action = req.body.action;

  if (action === "request_booking") {
    const appt_id = "appt_" + Date.now();
    const name = String(req.body.patient_name).trim();
    const phone = String(req.body.patient_phone).trim();
    const email = String(req.body.patient_email).trim();
    const service_id = req.body.service_id;
    const clinician_id = req.body.clinician_id;
    const date = req.body.booking_date;
    const time = req.body.booking_time + ":00";
    const notes = String(req.body.booking_notes || "").trim();

    // Get price estimate
    const matchedService = services.find(s => s.id === service_id);
    const total_cost = matchedService ? matchedService.price : 50000;

    if (name && phone) {
      appointments.push({
        id: appt_id,
        patient_name: name,
        patient_phone: phone,
        patient_email: email,
        service_id,
        clinician_id,
        appointment_date: date,
        appointment_time: time,
        status: "pending",
        total_cost,
        amount_paid: 0,
        payment_status: "unpaid",
        clinical_notes: notes,
        reminders_sent: 0,
        created_at: new Date().toISOString()
      });

      notifications.push({
        id: "notif_" + Date.now(),
        appointment_id: appt_id,
        patient_email: email,
        patient_name: name,
        subject: "Awaiting Goshen Medical Confirmation",
        content: `Hullo, ${name}. We've received your orthodontics schedule for ${date} at ${time}. We are setting up your customized installment invoice.`,
        sent_at: new Date().toLocaleString(),
        status: "sent"
      });

      flash_msg = `Appointment registered! Treatment total estimate indexed at ${formatUGX(total_cost)}`;
    }
    res.redirect("/patient.php?checkin_phone=" + encodeURIComponent(phone));
  } else if (action === "submit_momo_payment") {
    const appt_id = req.body.appt_id;
    const amount = parseFloat(req.body.momo_amount);
    const wallet_num = req.body.momo_phone;
    const channel_momo = req.body.momo_channel;

    if (appt_id && amount > 0) {
      const parent = appointments.find(ap => ap.id === appt_id);
      if (parent) {
        parent.amount_paid += amount;
        parent.payment_status = parent.amount_paid >= parent.total_cost ? "fully_paid" : "partial";

        installments.push({
          id: "inst_" + Date.now(),
          appointment_id: appt_id,
          amount,
          date: new Date().toLocaleString(),
          method: channel_momo,
          notes: `Sim MoMo Wallet: ${wallet_num}`
        });

        notifications.push({
          id: "notif_" + Date.now(),
          appointment_id: appt_id,
          patient_email: parent.patient_email,
          patient_name: parent.patient_name,
          subject: "Installment Payment Confirmed - Goshen",
          content: `Hullo. Your installment payment of ${formatUGX(amount)} via ${channel_momo} has been loaded.`,
          sent_at: new Date().toLocaleString(),
          status: "sent"
        });

        flash_msg = `Simulated Mobile Money transaction of ${formatUGX(amount)} processed! Local accounts adjusted.`;
      }
    }
    res.redirect("/patient.php?checkin_phone=" + encodeURIComponent(wallet_num));
  } else {
    res.redirect("/patient.php");
  }
});

// GET ROUTE: staff.php (Clinical queue terminal planner)
app.get("/staff.php", (req, res) => {
  let html = stripPHPHeader("./staff.php");

  // Search queues
  const search = req.query.search ? String(req.query.search).trim() : "";
  const filtered_appts = appointments
    .filter(ap => ap.status === "pending" || ap.status === "confirmed")
    .filter(ap => !search || ap.patient_name.toLowerCase().includes(search.toLowerCase()));

  // Capture selection priority matches
  let selected_appt_id = req.query.selected_appt_id ? String(req.query.selected_appt_id).trim() : "";
  let selected_appt: Appointment | null = appointments.find(ap => ap.id === selected_appt_id) || null;
  if (!selected_appt && filtered_appts.length > 0) {
    selected_appt = filtered_appts[0];
  }

  // Prior payouts array
  const current_installments = selected_appt
    ? installments.filter(inst => inst.appointment_id === selected_appt!.id).sort((a,b) => b.date.localeCompare(a.date))
    : [];

  // Generate Queue Listing HTML
  const queueListHTML = filtered_appts.length === 0
    ? `<div class="text-center text-muted py-5 text-xs">No pending patients in clinic queue.</div>`
    : filtered_appts.map(ap => {
        const service = services.find(s => s.id === ap.service_id);
        const service_name = service ? service.name : "Custom Dental Diagnostics";
        const is_selected = selected_appt && selected_appt.id === ap.id;
        
        return `
          <a href="staff.php?selected_appt_id=${ap.id}&search=${encodeURIComponent(search)}" 
             class="text-decoration-none text-start p-3 rounded-3 border d-block transition mb-2" 
             style="border-color: ${is_selected ? 'var(--primary-indigo)' : '#f1f5f9'} !important; background-color: ${is_selected ? 'rgba(99, 102, 241, 0.05)' : '#ffffff'};">
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge rounded bg-light border text-dark font-mono text-[10px]" style="font-size: 10px;">${ap.appointment_time}</span>
              <span class="text-slate-400 font-mono text-[10px]" style="font-size: 10px;">ID: ${ap.id}</span>
            </div>
            <h4 class="fs-xs font-bold text-dark mt-2 mb-1">${ap.patient_name}</h4>
            <p class="text-muted mb-0 font-sans text-[11px]" style="font-size: 11px;">${service_name}</p>
          </a>
        `;
      }).join("\n");

  // Render selected record detail card pane
  let selectedRecordHTML = "";
  if (!selected_appt) {
    selectedRecordHTML = `
      <div class="card border-0 shadow-sm p-5 text-center rounded-4 bg-white h-100 d-flex flex-column justify-content-center align-items-center">
        <p class="text-slate-450">Please select an active patient from the clinical queue list to access medical charting terminals.</p>
      </div>
    `;
  } else {
    // Generate payments logs entries list
    const instLogHTML = current_installments.length === 0
      ? `<div class="text-center text-slate-450 py-3 text-xs italic">No prior installments logged.</div>`
      : current_installments.map(inst => {
          return `
            <div class="p-2 border rounded-3 bg-light text-xs font-sans mb-1">
              <div class="d-flex justify-content-between align-items-center">
                <span class="font-bold text-dark">${formatUGX(inst.amount)}</span>
                <span class="badge bg-secondary font-mono text-[9px]" style="font-size: 9px;">${inst.method}</span>
              </div>
              <div class="d-flex justify-content-between align-items-center text-slate-500 mt-1" style="font-size: 10px;">
                <span>Ref: ${inst.notes || 'Office Direct Receipt'}</span>
                <span class="font-mono">${inst.date}</span>
              </div>
            </div>
          `;
        }).join("\n");

    const cashLedgerSettled = selected_appt.amount_paid;
    const cashLedgerOutstanding = selected_appt.total_cost - selected_appt.amount_paid;

    let checkoutFormHTML = "";
    if (cashLedgerOutstanding > 0) {
      checkoutFormHTML = `
        <form action="staff.php" method="POST" class="p-3 border rounded-3 bg-white">
          <input type="hidden" name="action" value="add_payment_installment">
          <input type="hidden" name="appt_id" value="${selected_appt.id}">
          
          <div class="mb-2">
            <label class="form-label font-bold text-slate-600" style="font-size: 10px;">INSTALLMENT AMOUNT</label>
            <input type="number" name="payment_amount" required max="${cashLedgerOutstanding}" min="100" class="form-control text-xs" style="font-size: 11px;" placeholder="UGX Amount">
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
      `;
    } else {
      checkoutFormHTML = `
        <div class="alert alert-success fs-xs border-0 py-3 text-center rounded-3">
          ✓ Patient has completed 100% of the dental balance. Account completely clear.
        </div>
      `;
    }

    selectedRecordHTML = `
      <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
        
        <!-- Patient Metadata Summary header -->
        <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-start border">
          <div>
            <span class="text-[10px] text-muted uppercase tracking-wider font-mono">Active Consultation Record</span>
            <h2 class="fs-5 font-bold text-dark mb-1">${selected_appt.patient_name}</h2>
            <p class="text-slate-500 mb-0 font-sans" style="font-size: 12px;">Contact: ${selected_appt.patient_phone} | ${selected_appt.patient_email}</p>
          </div>
          <div class="text-end">
            <span class="text-[10px] text-slate-400 block uppercase font-mono">Procedure Budget Index</span>
            <span class="fs-5 font-bold text-indigo font-mono" style="color: var(--primary-indigo)">${formatUGX(selected_appt.total_cost)}</span>
          </div>
        </div>

        <div class="row g-4">
          
          <!-- Form Actions: Save Clinical Chart -->
          <div class="col-md-7">
            <h3 class="fs-6 font-bold text-dark mb-3">Clinical Procedure Charting Form</h3>
            
            <form action="staff.php" method="POST">
              <input type="hidden" name="action" value="save_clinical_record">
              <input type="hidden" name="appt_id" value="${selected_appt.id}">

              <div class="mb-3">
                <label class="form-label font-bold text-slate-600 fs-xs uppercase">Active treatment step / Orthodontic milestone</label>
                <input type="text" name="treatment_plan_step" required value="${selected_appt.treatment_plan_step || ""}" class="form-control fs-xs" placeholder="e.g. Step 3 of 6: Archwire alignment, checking molar brackets">
              </div>

              <div class="mb-3">
                <label class="form-label font-bold text-slate-600 fs-xs uppercase">Treatment Findings & Procedure Notes</label>
                <textarea name="clinical_notes" required rows="4" class="form-control" style="font-size: 13px;" placeholder="Detail active findings (e.g. Extracted premium impacted composite successfully, irrigated localized suture lines)">${selected_appt.clinical_notes || ""}</textarea>
              </div>

              <div class="mb-3">
                <label class="form-label font-bold text-slate-600 fs-xs uppercase">Medicines Prescribed (Rx)</label>
                <input type="text" name="rx_prescribed" value="${selected_appt.rx_prescribed || ""}" class="form-control fs-xs" placeholder="e.g. Amoxicillin 500mg 8-hourly, Paracetamol 1g for relief">
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
                  <span class="text-[9px] text-slate-400 d-block">PAID DOWN</span>
                  <strong class="font-bold text-success" style="font-size: 12px;">${formatUGX(cashLedgerSettled)}</strong>
                </div>
                <div class="col-6 text-start ps-3">
                  <span class="text-[9px] text-slate-400 d-block">OUTSTANDING</span>
                  <strong class="font-bold text-danger" style="font-size: 12px;">${formatUGX(cashLedgerOutstanding)}</strong>
                </div>
              </div>
            </div>

            ${checkoutFormHTML}

            <!-- Past installments list -->
            <div class="mt-4">
              <h4 class="fs-xs font-bold text-slate-700 uppercase tracking-widest font-mono mb-2">Installment ledger Logs</h4>
              <div class="d-flex flex-column gap-2" style="max-height: 180px; overflow-y: auto;">
                ${instLogHTML}
              </div>
            </div>

          </div>

        </div>

      </div>
    `;
  }

  // Render Flash alerts
  let flashAlertHTML = "";
  if (flash_msg) {
    flashAlertHTML = `
      <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <strong>✓ Chart Updated:</strong> ${flash_msg}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;
    flash_msg = null; // Clear
  }

  const replacements: Record<string, string> = {
    "<?= htmlspecialchars($search) ?>": search,
    "<?php if (isset($_SESSION['flash_msg'])): ?>": flashAlertHTML,
    "<?php unset($_SESSION['flash_msg']); ?>": "",
    "<?php endif; ?>": "",
  };

  for (const [key, val] of Object.entries(replacements)) {
    html = html.split(key).join(val);
  }

  // Inject Queue items layout
  const queueGroupRegex = /<\?php if \(empty\(\$filtered_appts\)\): ?>[\s\S]*?<\/div>\s*<\/div>/;
  html = html.replace(queueGroupRegex, `${queueListHTML}</div>`);

  // Inject Selected Detail workspace pane
  const recordGridRegex = /<\?php if \(!\$selected_appt\): ?>[\s\S]*?<\?php endif;\ ?>/;
  html = html.replace(recordGridRegex, selectedRecordHTML);

  res.send(html);
});

// POST ACTION: staff.php form handler
app.post("/staff.php", (req, res) => {
  const action = req.body.action;

  if (action === "save_clinical_record") {
    const appt_id = req.body.appt_id;
    const notes = String(req.body.clinical_notes).trim();
    const plan_step = String(req.body.treatment_plan_step).trim();
    const rx = String(req.body.rx_prescribed).trim();

    if (appt_id) {
      const match = appointments.find(ap => ap.id === appt_id);
      if (match) {
        match.clinical_notes = notes;
        match.treatment_plan_step = plan_step;
        match.rx_prescribed = rx;
        match.status = "treatment_completed";
        flash_msg = "Clinical diagnostics and treatment procedure saved successfully! Queue status marked: Completed.";
      }
    }
    res.redirect("/staff.php?selected_appt_id=" + encodeURIComponent(appt_id));
  } else if (action === "add_payment_installment") {
    const appt_id = req.body.appt_id;
    const amount = parseFloat(req.body.payment_amount);
    const method = req.body.payment_method;
    const notes = String(req.body.payment_notes || "").trim();

    if (appt_id && amount > 0) {
      const parent = appointments.find(ap => ap.id === appt_id);
      if (parent) {
        parent.amount_paid += amount;
        parent.payment_status = parent.amount_paid >= parent.total_cost ? "fully_paid" : "partial";

        installments.push({
          id: "inst_" + Date.now(),
          appointment_id: appt_id,
          amount,
          date: new Date().toLocaleString(),
          method,
          notes: notes || "Office Direct Cash receipt"
        });

        flash_msg = `Installment cash receipt of ${formatUGX(amount)} recorded successfully!`;
      }
    }
    res.redirect("/staff.php?selected_appt_id=" + encodeURIComponent(appt_id));
  } else {
    res.redirect("/staff.php");
  }
});

// Compile error logs/health check
app.get("/api/health", (req, res) => {
  res.json({ status: "ok", mode: "native_php_preprocessor" });
});

// Serve everything else statics
app.use(express.static(process.cwd()));

app.listen(PORT, "0.0.0.0", () => {
  console.log(`Native PHP Live Simulator running on http://0.0.0.0:${PORT}`);
});
