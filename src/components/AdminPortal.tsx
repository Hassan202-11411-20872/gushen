/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from "react";
import { 
  Appointment, 
  DentalService, 
  StaffMember, 
  InventoryItem, 
  ClinicExpense,
  EmailNotification 
} from "../types";
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Activity, 
  Box, 
  Briefcase, 
  Sliders, 
  Plus, 
  AlertTriangle, 
  CheckCircle, 
  ShoppingBag, 
  Bell, 
  Star, 
  ArrowRight, 
  RefreshCw 
} from "lucide-react";

interface AdminPortalProps {
  appointments: Appointment[];
  services: DentalService[];
  staff: StaffMember[];
  inventory: InventoryItem[];
  expenses: ClinicExpense[];
  notifications: EmailNotification[];
  onAddService: (service: DentalService) => void;
  onAddExpense: (expense: ClinicExpense) => void;
  onRestockInventory: (itemId: string, qty: number) => void;
}

export default function AdminPortal({
  appointments,
  services,
  staff,
  inventory,
  expenses,
  notifications,
  onAddService,
  onAddExpense,
  onRestockInventory,
}: AdminPortalProps) {
  // Navigation tabs of Admin Panel
  const [activeTab, setActiveTab] = useState<"finance" | "supplies" | "services" | "performance" | "reminders">("finance");

  // Form states
  const [newServiceName, setNewServiceName] = useState("");
  const [newServicePrice, setNewServicePrice] = useState("");
  const [newServiceCategory, setNewServiceCategory] = useState<DentalService["category"]>("Preventive");
  const [newServiceDesc, setNewServiceDesc] = useState("");

  const [expenseAmount, setExpenseAmount] = useState("");
  const [expenseCategory, setExpenseCategory] = useState<ClinicExpense["category"]>("Supplies");
  const [expenseDesc, setExpenseDesc] = useState("");

  const formatUGX = (amount: number) => {
    return new Intl.NumberFormat("en-UG", {
      style: "currency",
      currency: "UGX",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // FINANCIAL FORMULAS
  // Total Revenue = SUM of all installment payments actually received
  const totalRevenue = appointments.reduce((sum, appt) => {
    const installmentSum = appt.installments.reduce((iSum, inst) => iSum + inst.amount, 0);
    return sum + installmentSum;
  }, 0);

  // Remaining receivables = SUM of (totalCost - amountPaid) across non-cancelled appointments
  const totalReceivables = appointments
    .filter((a) => a.status !== "cancelled")
    .reduce((sum, appt) => sum + (appt.totalCost - appt.amountPaid), 0);

  // Total Operating Expenses
  const totalExpenses = expenses.reduce((sum, exp) => sum + exp.amount, 0);

  // Net Cash surplus/profit
  const netProfit = totalRevenue - totalExpenses;

  // Inventory Warning counts
  const lowStockCount = inventory.filter((item) => item.quantity < item.threshold).length;

  // Assemble chronological transactions stream
  const transactionsStream: {
    id: string;
    patientName: string;
    procedure: string;
    date: string;
    amount: number;
    channel: string;
  }[] = [];

  appointments.forEach((appt) => {
    const serv = services.find((s) => s.id === appt.serviceId);
    appt.installments.forEach((inst) => {
      transactionsStream.push({
        id: inst.id,
        patientName: appt.patientName,
        procedure: serv?.name || "Dental Care Treatment",
        date: inst.date,
        amount: inst.amount,
        channel: inst.method
      });
    });
  });

  // Sort transaction dates chronologically (newest first)
  transactionsStream.sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());

  // Handle Add Service Form
  const handleCreateService = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newServiceName || !newServicePrice) return;
    const priceVal = parseFloat(newServicePrice);
    if (isNaN(priceVal) || priceVal <= 0) {
      alert("Please enter a valid price index for services offered.");
      return;
    }

    onAddService({
      id: "serv_" + Date.now(),
      name: newServiceName,
      price: priceVal,
      category: newServiceCategory,
      description: newServiceDesc || "Registered clinical dental specialty service."
    });

    setNewServiceName("");
    setNewServicePrice("");
    setNewServiceDesc("");
    alert(`Success! "${newServiceName}" service has been registered in Kampala pricing books.`);
  };

  // Handle Add Expense Form
  const handleLogExpense = (e: React.FormEvent) => {
    e.preventDefault();
    if (!expenseAmount || !expenseDesc) return;
    const amountVal = parseFloat(expenseAmount);
    if (isNaN(amountVal) || amountVal <= 0) {
      alert("Please specify a valid expense amount in UGX.");
      return;
    }

    onAddExpense({
      id: "exp_" + Date.now(),
      category: expenseCategory,
      amount: amountVal,
      date: new Date().toISOString().split("T")[0],
      description: expenseDesc
    });

    setExpenseAmount("");
    setExpenseDesc("");
    alert(`Expense logged successfully! Added to May/June administrative balance sheet.`);
  };

  return (
    <div className="space-y-8 text-slate-700">
      
      {/* 1. FINANCIAL BENTO METRICS GRID */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        <div id="stat_revenue" className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div className="p-3.5 bg-emerald-50 text-emerald-600 rounded-xl">
            <TrendingUp className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Gross Receipts</span>
            <span className="text-sm font-bold text-slate-800 font-mono leading-tight">{formatUGX(totalRevenue)}</span>
            <span className="text-[9px] text-slate-400 block mt-0.5">Realized income stream</span>
          </div>
        </div>

        <div id="stat_profit" className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div className={`p-3.5 rounded-xl ${netProfit >= 0 ? "bg-indigo-50 text-indigo-600" : "bg-rose-50 text-rose-600"}`}>
            <DollarSign className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Net Surplus profit</span>
            <span className={`text-sm font-bold font-mono leading-tight ${netProfit >= 0 ? "text-indigo-600" : "text-rose-500"}`}>
              {formatUGX(netProfit)}
            </span>
            <span className="text-[9px] text-slate-400 block mt-0.5">Direct ledger margin</span>
          </div>
        </div>

        <div id="stat_receivables" className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div className="p-3.5 bg-amber-50 text-amber-600 rounded-xl">
            <Sliders className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Outstanding Bills</span>
            <span className="text-sm font-bold text-slate-800 font-mono leading-tight">{formatUGX(totalReceivables)}</span>
            <span className="text-[9px] text-slate-500 font-medium text-amber-600 block mt-0.5">Installments Pending</span>
          </div>
        </div>

        <div id="stat_expenses" className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
          <div className="p-3.5 bg-rose-50 text-rose-500 rounded-xl">
            <TrendingDown className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Total Expenses</span>
            <span className="text-sm font-bold text-slate-800 font-mono leading-tight">{formatUGX(totalExpenses)}</span>
            <span className="text-[9px] text-slate-400 block mt-0.5">Operations cost lease</span>
          </div>
        </div>

        <div id="stat_supplies" className={`p-5 rounded-2xl border shadow-sm flex items-center gap-4 ${
          lowStockCount > 0 ? "bg-rose-50/50 border-rose-100 text-rose-950" : "bg-white border-slate-100"
        }`}>
          <div className={`p-3.5 rounded-xl ${lowStockCount > 0 ? "bg-rose-100 text-rose-600" : "bg-sky-50 text-sky-600"}`}>
            <Box className="w-5 h-5" />
          </div>
          <div>
            <span className="text-[10px] text-slate-400 font-bold block uppercase tracking-wider">Supply alert</span>
            <span className="text-sm font-bold font-mono leading-tight flex items-center gap-1">
              {lowStockCount} items
              {lowStockCount > 0 && <AlertTriangle className="w-3.5 h-3.5 text-rose-500 animate-bounce" />}
            </span>
            <span className="text-[9px] text-slate-400 block mt-0.5">Critical reorder limit</span>
          </div>
        </div>

      </div>

      {/* 2. ADMIN DETAILED TABS BAR */}
      <div className="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 overflow-hidden">
        <div className="flex flex-wrap gap-2 mb-6 border-b border-slate-100 pb-4">
          <button
            onClick={() => setActiveTab("finance")}
            className={`px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition ${
              activeTab === "finance"
                ? "bg-indigo-600 text-white shadow-sm"
                : "bg-slate-50 hover:bg-slate-100 text-slate-500"
            }`}
          >
            Financial transactions & Ledger
          </button>
          <button
            onClick={() => setActiveTab("supplies")}
            className={`px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition flex items-center gap-1.5 ${
              activeTab === "supplies"
                ? "bg-indigo-600 text-white shadow-sm"
                : "bg-slate-50 hover:bg-slate-100 text-slate-500"
            }`}
          >
            Inventory & Supplies
            {lowStockCount > 0 && <span className="bg-rose-500 text-white w-2 h-2 rounded-full animate-pulse" />}
          </button>
          <button
            onClick={() => setActiveTab("services")}
            className={`px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition ${
              activeTab === "services"
                ? "bg-indigo-600 text-white shadow-sm"
                : "bg-slate-50 hover:bg-slate-100 text-slate-500"
            }`}
          >
            Register offered Services
          </button>
          <button
            onClick={() => setActiveTab("performance")}
            className={`px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition ${
              activeTab === "performance"
                ? "bg-indigo-600 text-white shadow-sm"
                : "bg-slate-50 hover:bg-slate-100 text-slate-500"
            }`}
          >
            Clinician Performance Metrics
          </button>
          <button
            onClick={() => setActiveTab("reminders")}
            className={`px-4 py-2 rounded-lg text-xs font-bold cursor-pointer transition flex items-center gap-1 ${
              activeTab === "reminders"
                ? "bg-indigo-600 text-white shadow-sm"
                : "bg-slate-50 hover:bg-slate-100 text-slate-500"
            }`}
          >
            Automated Reminder log
            <span className="bg-indigo-100 text-indigo-700 font-mono text-[9px] px-1.5 rounded-full">
              {notifications.length}
            </span>
          </button>
        </div>

        {/* TAB 1: FINANCIAL TRANSACTIONS LEDGER AND EXPENSES */}
        {activeTab === "finance" && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-fade-in text-left">
            {/* Left side: Transactions Receipts stream */}
            <div className="lg:col-span-8 space-y-4">
              <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2">
                Real-Time Chronological Receipts
              </h4>
              <div className="border border-slate-100 rounded-xl overflow-hidden font-sans">
                <table className="w-full text-left bg-white text-xs">
                  <thead className="bg-slate-50 border-b border-slate-100 text-slate-500 font-mono uppercase text-[9px]">
                    <tr>
                      <th className="p-3">Reference Date</th>
                      <th className="p-3">Patient File</th>
                      <th className="p-3">Procedure Category</th>
                      <th className="p-3">Payment Channel</th>
                      <th className="p-3 text-right">Captured Sum</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 text-slate-600">
                    {transactionsStream.map((trans) => (
                      <tr key={trans.id} className="hover:bg-slate-50/50 transition">
                        <td className="p-3 font-mono text-slate-400">{trans.date}</td>
                        <td className="p-3 font-bold text-slate-800">{trans.patientName}</td>
                        <td className="p-3 text-slate-500 truncate max-w-[200px]">{trans.procedure}</td>
                        <td className="p-3">
                          <span className={`px-2 py-0.5 rounded text-[9.5px] font-bold ${
                            trans.channel.includes("MoMo") 
                              ? "bg-amber-100 text-amber-800" 
                              : trans.channel.includes("Airtel")
                              ? "bg-rose-100 text-rose-800"
                              : "bg-slate-100 text-slate-700"
                          }`}>
                            {trans.channel}
                          </span>
                        </td>
                        <td className="p-3 text-right font-bold font-mono text-emerald-600">+{formatUGX(trans.amount)}</td>
                      </tr>
                    ))}
                    {transactionsStream.length === 0 && (
                      <tr>
                        <td colSpan={5} className="p-4 text-center text-slate-400 italic">No payments logged in system yet.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>

            {/* Right side: Clinic Cost Ledger log */}
            <div className="lg:col-span-4 space-y-5 bg-slate-50/50 p-4 rounded-xl border border-slate-100">
              <div>
                <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider mb-1">
                  Log Operating Expense
                </h4>
                <p className="text-[10px] text-slate-400">Record rents, logistics, supplies costs directly into Ledger margins.</p>
              </div>

              <form onSubmit={handleLogExpense} className="space-y-3">
                <div>
                  <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Cost categories Offered</label>
                  <select
                    value={expenseCategory}
                    onChange={(e) => setExpenseCategory(e.target.value as ClinicExpense["category"])}
                    className="w-full px-2 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700"
                  >
                    <option value="Supplies">Dental Supplies & Resin Pcs</option>
                    <option value="Rent">Kampala Space Lease Lease</option>
                    <option value="Utilities">Water / Umeme Grid Payouts</option>
                    <option value="Marketing">Social / Flyers Marketing</option>
                    <option value="Salaries">Basic Salaries & Commissions</option>
                    <option value="Other">Miscellaneous Overheads</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Expense amount (UGX)</label>
                  <input
                    type="number"
                    required
                    placeholder="e.g. 500000"
                    value={expenseAmount}
                    onChange={(e) => setExpenseAmount(e.target.value)}
                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Log detailed descriptions</label>
                  <textarea
                    required
                    placeholder="Describe usage parameters..."
                    value={expenseDesc}
                    onChange={(e) => setExpenseDesc(e.target.value)}
                    className="w-full p-2 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none h-14"
                  />
                </div>

                <button
                  type="submit"
                  className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs transition flex items-center justify-center gap-1 cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" />
                  Commit Expense entry
                </button>
              </form>

              <hr className="border-slate-200 my-4" />

              <div>
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Past Operating Costs Ledger:</span>
                <div className="space-y-1.5 max-h-[160px] overflow-y-auto pr-1 text-xs">
                  {expenses.map((exp) => (
                    <div key={exp.id} className="p-2 bg-white rounded border border-slate-150 flex justify-between items-center">
                      <div>
                        <span className="font-bold text-[9px] uppercase px-1.5 bg-rose-50 text-rose-700 rounded block w-fit mb-0.5">
                          {exp.category}
                        </span>
                        <p className="text-[10px] text-slate-500 italic max-w-[150px] truncate">{exp.description}</p>
                      </div>
                      <span className="font-mono font-bold text-rose-500 text-[11px] flex-shrink-0">
                        -{formatUGX(exp.amount)}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        )}

        {/* TAB 2: INVENTORY AND SUPPLIES RE-ORDER STAGE */}
        {activeTab === "supplies" && (
          <div className="space-y-5 animate-fade-in text-left font-sans">
            <div className="flex justify-between items-center">
              <div>
                <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider">
                  Clinic Supply Inventory Tracker
                </h4>
                <p className="text-[11px] text-slate-400">Restock clinic materials and dental tools. Low quantities trigger alerts in inventory logs.</p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {inventory.map((item) => {
                const isLow = item.quantity < item.threshold;
                return (
                  <div
                    key={item.id}
                    className={`p-4 rounded-xl border transition ${
                      isLow ? "bg-rose-50/50 border-rose-100 ring-1 ring-rose-50" : "bg-slate-50/30 border-slate-100"
                    }`}
                  >
                    <div className="flex justify-between items-start mb-2">
                      <span className="font-bold text-xs text-slate-800">{item.name}</span>
                      {isLow && (
                        <span className="bg-rose-100 text-rose-800 text-[8.5px] font-extrabold uppercase px-1.5 py-0.5 rounded flex items-center gap-0.5">
                          <AlertTriangle className="w-2.5 h-2.5 animate-pulse text-rose-600" />
                          Low Stock
                        </span>
                      )}
                    </div>

                    <div className="flex items-baseline gap-1 mt-4 mb-2">
                      <span className="text-2xl font-bold font-mono text-slate-800">{item.quantity}</span>
                      <span className="text-xs text-slate-400 font-mono">{item.unit}</span>
                    </div>

                    <div className="text-[10px] text-slate-500">
                      <span>Threshold Min: {item.threshold} {item.unit}</span>
                      <span className="mx-2 font-light">•</span>
                      <span>Restock Cost / unit: {formatUGX(item.costPerUnit)}</span>
                    </div>

                    {/* Quick restock actions */}
                    <div className="mt-4 flex gap-2">
                      <button
                        onClick={() => {
                          onRestockInventory(item.id, 20);
                          alert(`Triggered restocking: added +20 ${item.unit} of "${item.name}" to inventory storage!`);
                        }}
                        className="flex-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold py-1 px-2.5 rounded text-[10.5px] cursor-pointer transition flex items-center justify-center gap-1"
                      >
                        <RefreshCw className="w-3 h-3 text-indigo-500" />
                        Restock (+20 Units)
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* TAB 3: REGISTER NEW SERVICES OFFERED */}
        {activeTab === "services" && (
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 animate-fade-in text-left">
            
            {/* List current catalogues */}
            <div className="lg:col-span-7 space-y-4">
              <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider">
                Current Registered Clinical Operations Catalog
              </h4>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {services.map((s) => (
                  <div key={s.id} className="p-3 bg-slate-50 border border-slate-100 rounded-xl leading-normal font-sans">
                    <div className="flex justify-between items-start gap-1">
                      <span className="font-bold text-xs text-slate-800">{s.name}</span>
                      <span className="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 rounded-full whitespace-nowrap">
                        {formatUGX(s.price)}
                      </span>
                    </div>
                    <span className="text-[9px] uppercase font-bold text-slate-400 block mt-1">{s.category} Category</span>
                    <p className="text-[10px] text-slate-400 mt-1">{s.description}</p>
                  </div>
                ))}
              </div>
            </div>

            {/* Registration Form */}
            <div className="lg:col-span-5 bg-slate-50 p-5 rounded-xl border border-slate-100 space-y-4">
              <div>
                <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider mb-1">
                  Add New Clinical Service
                </h4>
                <p className="text-[10px] text-slate-400">Register customized periodontal or crown treatments so patients can book immediately.</p>
              </div>

              <form onSubmit={handleCreateService} className="space-y-3 font-sans">
                <div>
                  <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Service Specialty Name</label>
                  <input
                    type="text"
                    required
                    placeholder="e.g. Wisdom Tooth Apicoectomy"
                    value={newServiceName}
                    onChange={(e) => setNewServiceName(e.target.value)}
                    className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Pricing (UGX)</label>
                    <input
                      type="number"
                      required
                      placeholder="e.g. 350000"
                      value={newServicePrice}
                      onChange={(e) => setNewServicePrice(e.target.value)}
                      className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none font-mono"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Specialty Class</label>
                    <select
                      value={newServiceCategory}
                      onChange={(e) => setNewServiceCategory(e.target.value as DentalService["category"])}
                      className="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white text-slate-700"
                    >
                      <option value="Preventive">Preventive Care</option>
                      <option value="Curative">Curative Therapy</option>
                      <option value="Aesthetic">Aesthetic Dental</option>
                      <option value="Surgical">Surgical Maxillo</option>
                    </select>
                  </div>
                </div>

                <div>
                  <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">Description</label>
                  <textarea
                    placeholder="Detail of the procedure parameters..."
                    value={newServiceDesc}
                    onChange={(e) => setNewServiceDesc(e.target.value)}
                    className="w-full p-2 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none h-16"
                  />
                </div>

                <button
                  type="submit"
                  className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs transition flex items-center justify-center gap-1 cursor-pointer"
                >
                  <Plus className="w-3.5 h-3.5" />
                  Register Dental Specialty
                </button>
              </form>
            </div>

          </div>
        )}

        {/* TAB 4: CLINIC CLINICIAN PERFORMANCE METRICS */}
        {activeTab === "performance" && (
          <div className="space-y-4 animate-fade-in text-left font-sans">
            <div>
              <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider">
                Clinician Performance Indicators (HR Metrics)
              </h4>
              <p className="text-[11px] text-slate-400">Review cumulative treatment procedural records, generated income sums, and patient ratings metrics across your clinician team.</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {staff.map((member) => {
                // Calculate dynamic total revenue based on this staff member's finished treatments
                const assignedAppts = appointments.filter(
                  (a) => a.clinicianId === member.id && a.status === "treatment_completed"
                );
                
                const dynamicRevenue = assignedAppts.reduce((sum, current) => {
                  return sum + current.amountPaid;
                }, 0);

                const proceduresCount = member.proceduresCompleted + assignedAppts.length;

                return (
                  <div key={member.id} className="p-4 bg-slate-50 border border-slate-100 rounded-2xl relative overflow-hidden leading-snug">
                    <span className="text-[9px] uppercase tracking-widest text-slate-400 block font-mono">
                      {member.role}
                    </span>
                    <h5 className="font-bold text-sm text-slate-800 mt-1 mb-2">{member.name}</h5>
                    
                    <div className="space-y-3 mt-4 text-xs font-sans">
                      <div className="flex justify-between border-b border-slate-200/60 pb-1.5">
                        <span className="text-slate-500">Completed procedures:</span>
                        <span className="font-bold text-slate-700 font-mono">{proceduresCount} procedures</span>
                      </div>
                      <div className="flex justify-between border-b border-slate-200/60 pb-1.5">
                        <span className="text-slate-500">Revenue Contribution:</span>
                        <span className="font-bold text-emerald-600 font-mono">{formatUGX(member.revenueGenerated + dynamicRevenue)}</span>
                      </div>
                      <div className="flex justify-between items-center">
                        <span className="text-slate-500">Average Patient Rating:</span>
                        <div className="flex items-center gap-1">
                          <span className="font-bold text-slate-700 font-mono">{member.patientRating}</span>
                          <Star className="w-3.5 h-3.5 fill-amber-400 text-amber-400 inline-block" />
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* TAB 5: REMINDERS QUEUE */}
        {activeTab === "reminders" && (
          <div className="space-y-4 animate-fade-in text-left font-sans">
            <div>
              <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider">
                Automated Notification Logs
              </h4>
              <p className="text-[11px] text-slate-400 font-mono">System-triggered email alerts and SMS notifications dispatched to patients upon appointment bookings or MoMo balance payments.</p>
            </div>

            <div className="space-y-2.5 max-h-[400px] overflow-y-auto pr-2">
              {notifications.map((notif) => (
                <div key={notif.id} className="p-3 bg-slate-50 border border-slate-100 rounded-xl hover:bg-slate-100/50 transition">
                  <div className="flex justify-between items-start text-[10px] text-slate-400 font-mono mb-1">
                    <span>Email & SMS Dispatch ID: #{notif.id}</span>
                    <span>Sent: {new Date(notif.sentAt).toLocaleTimeString()}</span>
                  </div>
                  
                  <h5 className="font-bold text-xs text-slate-800">
                    Subject: {notif.subject}
                  </h5>
                  <p className="text-xs text-slate-500 mt-1 font-sans">{notif.content}</p>
                  
                  <div className="flex gap-2 items-center mt-2.5 pt-2 border-t border-slate-100 text-[9.5px]">
                    <span className="text-slate-400">Recipient Address:</span>
                    <span className="font-semibold text-indigo-600">{notif.patientEmail}</span>
                    <span className="bg-emerald-50 text-emerald-700 font-bold px-1.5 rounded uppercase">
                      Delivered via SMTP Gateway
                    </span>
                  </div>
                </div>
              ))}

              {notifications.length === 0 && (
                <div className="text-center py-8">
                  <p className="text-xs text-slate-400 italic">No automated notification triggers have ran in this demo cycle yet.</p>
                  <p className="text-xs text-slate-400 mt-1">Book a treatment or finalize an installment to trigger system notifications.</p>
                </div>
              )}
            </div>
          </div>
        )}

      </div>

    </div>
  );
}
