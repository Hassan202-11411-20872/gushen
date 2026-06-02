/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  HeartHandshake, 
  Users, 
  Settings, 
  Sparkles, 
  Bell, 
  Mail, 
  Calendar, 
  ShieldAlert, 
  Info, 
  Briefcase,
  Server
} from "lucide-react";

import DentalLogo from "./components/DentalLogo";
import PatientPortal from "./components/PatientPortal";
import StaffPortal from "./components/StaffPortal";
import AdminPortal from "./components/AdminPortal";
import PhpStandalonePortal from "./components/PhpStandalonePortal";

import { 
  INITIAL_APPOINTMENTS, 
  INITIAL_SERVICES, 
  INITIAL_EXPENSES, 
  INITIAL_INVENTORY, 
  INITIAL_STAFF 
} from "./data";

import { 
  Appointment, 
  DentalService, 
  ClinicExpense, 
  InventoryItem, 
  EmailNotification,
  PaymentInstallment
} from "./types";

export default function App() {
  // Global synchronized state
  const [appointments, setAppointments] = useState<Appointment[]>(INITIAL_APPOINTMENTS);
  const [services, setServices] = useState<DentalService[]>(INITIAL_SERVICES);
  const [expenses, setExpenses] = useState<ClinicExpense[]>(INITIAL_EXPENSES);
  const [inventory, setInventory] = useState<InventoryItem[]>(INITIAL_INVENTORY);
  const [staff, setStaff] = useState(INITIAL_STAFF);
  const [notifications, setNotifications] = useState<EmailNotification[]>([
    {
      id: "notif_welcome",
      appointmentId: "appt_1",
      patientEmail: "florence.nakimera@gmail.com",
      patientName: "Florence Nakimera",
      subject: "Goshen Dental Care - Orthodontics Budget Registered",
      content: "Welcome, Florence! Your payment schedule of 3,500,000 UGX is registered. Standard billing reminder alerts set up for automated dispatch.",
      sentAt: "2026-05-02T10:15:00Z",
      status: "sent"
    }
  ]);

  // CEO Role selector state
  const [activePortal, setActivePortal] = useState<"patient" | "staff" | "admin" | "php_export">("admin");

  // Global overlay alert popups state for visible proof of notifications
  const [alertToast, setAlertToast] = useState<{
    id: string;
    title: string;
    body: string;
    recipient: string;
  } | null>(null);

  // Triggering automated notification logging
  const handleTriggerNotification = (apptId: string, subject: string, content: string) => {
    const appt = appointments.find((a) => a.id === apptId);
    const email = appt ? appt.patientEmail : "hassanharman44@gmail.com";
    const name = appt ? appt.patientName : "Valued Goshen Patient";

    const newNotif: EmailNotification = {
      id: "notif_" + Date.now(),
      appointmentId: apptId,
      patientEmail: email,
      patientName: name,
      subject,
      content,
      sentAt: new Date().toISOString(),
      status: "sent"
    };

    setNotifications((prev) => [newNotif, ...prev]);

    // Update appointment notifications counter
    setAppointments((prev) =>
      prev.map((a) => (a.id === apptId ? { ...a, remindersSent: a.remindersSent + 1 } : a))
    );

    // Render beautiful dynamic on-screen toast alert so the CEO sees notifications firing in real-time!
    setAlertToast({
      id: newNotif.id,
      title: subject,
      body: content,
      recipient: email
    });

    // Clear alert after 6 seconds
    setTimeout(() => {
      setAlertToast((curr) => (curr?.id === newNotif.id ? null : curr));
    }, 6000);
  };

  // Add Appointment callback
  const handleAddAppointment = (newAppt: Appointment) => {
    setAppointments((prev) => [newAppt, ...prev]);
  };

  // Update Appointment status, clinical notes, etc.
  const handleUpdateAppointment = (apptId: string, updates: Partial<Appointment>) => {
    setAppointments((prev) =>
      prev.map((appt) => (appt.id === apptId ? { ...appt, ...updates } : appt))
    );
  };

  // Register mobile money, card, or cash installment dynamically
  const handleAddInstallment = (apptId: string, installment: PaymentInstallment) => {
    setAppointments((prev) =>
      prev.map((appt) => {
        if (appt.id === apptId) {
          const updatedInstallments = [...appt.installments, installment];
          const calculatedPaid = appt.amountPaid + installment.amount;
          const updatedPayStatus = calculatedPaid >= appt.totalCost 
            ? ("fully_paid" as const) 
            : ("partial" as const);

          return {
            ...appt,
            installments: updatedInstallments,
            amountPaid: calculatedPaid,
            paymentStatus: updatedPayStatus
          };
        }
        return appt;
      })
    );
  };

  // Add customized Dental Service Catalog
  const handleAddService = (newServ: DentalService) => {
    setServices((prev) => [...prev, newServ]);
  };

  // Add administrative expense entry
  const handleAddExpense = (newExp: ClinicExpense) => {
    setExpenses((prev) => [newExp, ...prev]);
  };

  // Replenish inventory closet
  const handleRestockInventory = (itemId: string, quantityToAdd: number) => {
    setInventory((prev) =>
      prev.map((item) =>
        item.id === itemId ? { ...item, quantity: item.quantity + quantityToAdd } : item
      )
    );
  };

  return (
    <div id="goshen_root" className="min-h-screen bg-slate-50 text-slate-800 flex flex-col antialiased">
      
      {/* REAL-TIME DYNAMIC NOTIFICATION OVERLAY PANEL (CEO VISUAL PROOF) */}
      <AnimatePresence>
        {alertToast && (
          <motion.div
            initial={{ opacity: 0, y: -50, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -30, scale: 0.9 }}
            className="fixed top-4 right-4 z-50 max-w-sm w-full bg-white border border-indigo-100 rounded-2xl shadow-2xl p-4 flex gap-3 text-left overflow-hidden ring-1 ring-indigo-500/10"
          >
            {/* Visual notification signal lines */}
            <div className="absolute top-0 left-0 w-full h-1 bg-indigo-600 animate-pulse" />
            
            <div className="p-2 bg-indigo-50 text-indigo-600 rounded-xl h-fit">
              <Bell className="w-5 h-5 animate-bounce" />
            </div>
            <div className="flex-1 font-sans">
              <div className="flex justify-between items-start">
                <span className="text-[10px] font-bold text-indigo-600 uppercase tracking-widest font-mono">
                  [Notification Triggered]
                </span>
                <button
                  type="button"
                  onClick={() => setAlertToast(null)}
                  className="text-slate-300 hover:text-slate-500 text-xs"
                >
                  ✕
                </button>
              </div>
              <h4 className="font-bold text-xs text-slate-800 mt-1">{alertToast.title}</h4>
              <p className="text-[10.5px] text-slate-500 mt-1 leading-relaxed">
                {alertToast.body}
              </p>
              <div className="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[9px] text-slate-400 font-mono">
                <span>Ref: SMTP / MoMo Alert</span>
                <span className="font-bold text-slate-600">{alertToast.recipient}</span>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* STICKY HEADER BRANDING */}
      <header className="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-100 py-3.5 px-6 flex flex-col md:flex-row justify-between items-center gap-4 shadow-xs">
        <DentalLogo />
        
        {/* CEO PORTAL MANAGER PANEL SELECTOR */}
        <div className="bg-slate-100/80 p-1.5 rounded-xl border border-slate-200 flex items-center gap-1.5 shadow-inner">
          <span className="text-[9.5px] uppercase font-mono tracking-wider font-extrabold text-slate-400 ml-2.5 mr-1 hidden lg:inline flex-shrink-0">
            CEO Portal Switcher:
          </span>
          <button
            onClick={() => setActivePortal("admin")}
            className={`px-3.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer transition-all flex items-center gap-1 ${
              activePortal === "admin"
                ? "bg-indigo-600 text-white shadow font-bold"
                : "text-slate-500 hover:text-slate-800 hover:bg-slate-200/50"
            }`}
          >
            <Briefcase className="w-3.5 h-3.5" />
            CEO Dashboard Admin
          </button>
          
          <button
            onClick={() => setActivePortal("staff")}
            className={`px-3.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer transition-all flex items-center gap-1 ${
              activePortal === "staff"
                ? "bg-indigo-600 text-white shadow font-bold"
                : "text-slate-500 hover:text-slate-800 hover:bg-slate-200/50"
            }`}
          >
            <Users className="w-3.5 h-3.5" />
            Staff / Doctor Desk
          </button>

          <button
            onClick={() => setActivePortal("patient")}
            className={`px-3.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer transition-all flex items-center gap-1 ${
              activePortal === "patient"
                ? "bg-indigo-600 text-white shadow font-bold"
                : "text-slate-500 hover:text-slate-800 hover:bg-slate-200/50"
            }`}
          >
            <HeartHandshake className="w-3.5 h-3.5" />
            Patient / Guest Portal
          </button>

          <button
            onClick={() => setActivePortal("php_export")}
            className={`px-3.5 py-1.5 text-xs font-bold rounded-lg cursor-pointer transition-all flex items-center gap-1 ${
              activePortal === "php_export"
                ? "bg-emerald-600 text-white shadow font-bold"
                : "text-slate-500 hover:text-slate-800 hover:bg-slate-200/50"
            }`}
          >
            <Server className="w-3.5 h-3.5 text-emerald-400" />
            PHP Standalone App Files
          </button>
        </div>
      </header>

      {/* EXECUTIVE WELCOME SUMMARY BAR */}
      <div className="bg-indigo-950 text-white py-3 px-6 text-xs flex flex-col md:flex-row justify-between items-center gap-2 font-sans font-medium">
        <div className="flex items-center gap-2">
          <Sparkles className="w-4 h-4 text-emerald-400 animate-pulse" />
          <span>
            CEO Project Mode Active: Previewing <strong>Goshen Dental Care</strong> with authentic UGX billing, medical grids, and Mobile Money receipts mockups.
          </span>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-slate-300">HQ: Kampala, Uganda</span>
          <span className="bg-indigo-900 text-[10px] font-mono px-2 py-0.5 rounded border border-indigo-800 text-emerald-400 font-bold">
            Live Testing Environment
          </span>
        </div>
      </div>

      {/* CORE PORTAL DOCK AREA WITH TRANSITION EFFECTS */}
      <main className="flex-1 max-w-7xl w-full mx-auto p-4 md:p-8">
        <AnimatePresence mode="wait">
          {activePortal === "admin" && (
            <motion.div
              key="admin_view"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.25 }}
            >
              <div className="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-left">
                <div>
                  <h1 className="text-2xl font-bold font-sans text-slate-800 leading-none">
                    CEO Administrative Matrix Board
                  </h1>
                  <p className="text-xs text-slate-400 mt-1.5 leading-none">
                    Financial statements, medical procedures, equipment reserves, and diagnostic registers.
                  </p>
                </div>
                <div className="text-xs text-slate-400 flex items-center gap-1 italic bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                  <Info className="w-3.5 h-3.5 text-indigo-500" />
                  All procedures tracked dynamically.
                </div>
              </div>
              
              <AdminPortal
                appointments={appointments}
                services={services}
                staff={staff}
                inventory={inventory}
                expenses={expenses}
                notifications={notifications}
                onAddService={handleAddService}
                onAddExpense={handleAddExpense}
                onRestockInventory={handleRestockInventory}
              />
            </motion.div>
          )}

          {activePortal === "staff" && (
            <motion.div
              key="staff_view"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.25 }}
            >
              <div className="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-left">
                <div>
                  <h1 className="text-2xl font-bold font-sans text-slate-800 leading-none">
                    Staff & Doctor Care Terminal
                  </h1>
                  <p className="text-xs text-slate-400 mt-1.5 leading-none">
                    Review incoming schedules, log symptoms, adjust orthodontic wires, and access clinical diagnostics.
                  </p>
                </div>
                <span className="text-xs bg-emerald-50 text-emerald-700 font-bold px-2.5 py-1 rounded-lg border border-emerald-100">
                  Secure Medical Node: Online
                </span>
              </div>

              <StaffPortal
                appointments={appointments}
                services={services}
                staff={staff}
                onUpdateAppointment={handleUpdateAppointment}
                onAddInstallment={handleAddInstallment}
                triggerNotification={handleTriggerNotification}
              />
            </motion.div>
          )}

          {activePortal === "patient" && (
            <motion.div
              key="patient_view"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.25 }}
            >
              <div className="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-left">
                <div>
                  <h1 className="text-2xl font-bold font-sans text-slate-800 leading-none">
                    Guest & Patient Treatment Hub
                  </h1>
                  <p className="text-xs text-slate-400 mt-1.5 leading-none">
                    Request new dental slots, monitor active braces/implant progress, and pay installments via MTN/Airtel MoMo.
                  </p>
                </div>
                <span className="text-xs bg-indigo-50 text-indigo-700 font-bold px-2.5 py-1 rounded-lg border border-indigo-100">
                  Standard Patient Inflow Port
                </span>
              </div>

              <PatientPortal
                appointments={appointments}
                services={services}
                staff={staff}
                onAddAppointment={handleAddAppointment}
                onAddInstallment={handleAddInstallment}
                triggerNotification={handleTriggerNotification}
              />
            </motion.div>
          )}

          {activePortal === "php_export" && (
            <motion.div
              key="php_export_view"
              initial={{ opacity: 0, y: 15 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -15 }}
              transition={{ duration: 0.25 }}
            >
              <PhpStandalonePortal />
            </motion.div>
          )}
        </AnimatePresence>
      </main>

      {/* FOOTER */}
      <footer className="bg-indigo-950 border-t border-indigo-900 text-slate-350 py-8 px-6 text-center text-xs mt-auto font-sans leading-relaxed">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
          <p className="text-indigo-200 text-left">
            © 2026 Goshen Dental Care Management System. All Rights Reserved. Fully tailored design with automated billing reminder engines for dental operations in Kampala, Uganda.
          </p>
          <div className="flex gap-4 text-indigo-200 font-medium font-sans">
            <span className="hover:text-white cursor-pointer transition">Regulatory Compliance</span>
            <span>|</span>
            <span className="hover:text-white cursor-pointer transition">Bank-Grade MoMo Integrations</span>
          </div>
        </div>
      </footer>

    </div>
  );
}
