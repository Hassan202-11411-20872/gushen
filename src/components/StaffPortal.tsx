/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from "react";
import { Appointment, DentalService, StaffMember, PaymentInstallment } from "../types";
import { 
  Users, 
  CalendarCheck, 
  FileText, 
  Activity, 
  Search, 
  CheckCircle, 
  Plus, 
  Sparkles, 
  DollarSign, 
  Stethoscope, 
  Briefcase, 
  FilePlus, 
  AlertCircle 
} from "lucide-react";

interface StaffPortalProps {
  appointments: Appointment[];
  services: DentalService[];
  staff: StaffMember[];
  onUpdateAppointment: (apptId: string, updates: Partial<Appointment>) => void;
  onAddInstallment: (apptId: string, installment: PaymentInstallment) => void;
  triggerNotification: (apptId: string, subject: string, content: string) => void;
  onRefreshStats?: () => void;
}

export default function StaffPortal({
  appointments,
  services,
  staff,
  onUpdateAppointment,
  onAddInstallment,
  triggerNotification,
  onRefreshStats,
}: StaffPortalProps) {
  const [selectedApptId, setSelectedApptId] = useState<string>(appointments[0]?.id || "");
  const [searchTerm, setSearchTerm] = useState("");
  
  // Clinical updates state
  const [notes, setNotes] = useState("");
  const [rxText, setRxText] = useState("");
  const [planStep, setPlanStep] = useState("");
  const [statusUpdate, setStatusUpdate] = useState<Appointment["status"]>("confirmed");

  // In-clinic payment logs state
  const [payAmount, setPayAmount] = useState("");
  const [payMethod, setPayMethod] = useState<PaymentInstallment["method"]>("Cash");
  const [payNotes, setPayNotes] = useState("");
  
  // Simulated Clinical AI Assistant state
  const [aiSigns, setAiSigns] = useState("Severe throbbing lower molar pain, localized swelling on gum, heat sensitivity.");
  const [aiResult, setAiResult] = useState<{
    diagnosis: string;
    treatment: string;
    prescription: string;
    pricingEst: string;
  } | null>(null);
  const [isAiLoading, setIsAiLoading] = useState(false);

  const formatUGX = (amount: number) => {
    return new Intl.NumberFormat("en-UG", {
      style: "currency",
      currency: "UGX",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  const selectedAppt = appointments.find((a) => a.id === selectedApptId);

  // Filter queues
  const filteredAppts = appointments.filter((a) => {
    return (
      a.patientName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      a.patientPhone.includes(searchTerm)
    );
  });

  const handleSelectAppt = (appt: Appointment) => {
    setSelectedApptId(appt.id);
    setNotes(appt.clinicalNotes || "");
    setRxText(appt.rxPrescribed || "");
    setPlanStep(appt.treatmentPlanStep || "");
    setStatusUpdate(appt.status);
  };

  const handleSaveClinicalRecord = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedApptId) return;

    onUpdateAppointment(selectedApptId, {
      clinicalNotes: notes,
      rxPrescribed: rxText,
      treatmentPlanStep: planStep,
      status: statusUpdate,
    });

    // Notify Patient of Treatment Updates
    if (selectedAppt) {
      const serv = services.find((s) => s.id === selectedAppt.serviceId);
      triggerNotification(
        selectedAppt.id,
        "Goshen Dental Care - Clinical Chart Updated",
        `Hello ${selectedAppt.patientName}, your treatment records for "${serv?.name}" have been updated by Goshen clinical staff. Current Status: ${statusUpdate.replace("_", " ")}. Next Step: ${planStep || "N/A"}. Monitor your healing safely.`
      );
      
      alert("Clinical chart successfully recorded and synced with Patient & CEO portals.");
    }
  };

  const handleRecordStaffPayment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedAppt || !payAmount) return;
    const amountVal = parseFloat(payAmount);
    const balance = selectedAppt.totalCost - selectedAppt.amountPaid;

    if (isNaN(amountVal) || amountVal <= 0) {
      alert("Please specify a valid payment amount.");
      return;
    }
    if (amountVal > balance) {
      alert(`Payment is larger than outstanding balance of ${formatUGX(balance)}.`);
      return;
    }

    const newInst: PaymentInstallment = {
      id: "inst_staff_" + Date.now(),
      amount: amountVal,
      date: new Date().toISOString().split("T")[0],
      method: payMethod,
      notes: payNotes || `In-clinic payment logged by staff.`
    };

    onAddInstallment(selectedAppt.id, newInst);
    
    // Automatically trigger notification receipt
    triggerNotification(
      selectedAppt.id,
      "Goshen Dental Care - Payment Receipt Confirmed",
      `Payment of ${formatUGX(amountVal)} is logged by Goshen Dental accounting desks. Remaining outstanding recovery budget is ${formatUGX(selectedAppt.totalCost - selectedAppt.amountPaid - amountVal)}. Thank you.`
    );

    setPayAmount("");
    setPayNotes("");
    alert(`Secure financial entry added: ${formatUGX(amountVal)} logged under ${payMethod}.`);
  };

  const diagnoseWithAI = (e: React.FormEvent) => {
    e.preventDefault();
    if (!aiSigns.trim()) return;

    setIsAiLoading(true);
    setAiResult(null);

    // Dynamic stateful simulation mimicking deep medical neural network checks
    setTimeout(() => {
      const lowerQuery = aiSigns.toLowerCase();
      let match = {
        diagnosis: "Moderate pulp congestion with localized debris accumulation.",
        treatment: "Deep Ultrasonic Scaling, fluoridation, and local composite restorations.",
        prescription: "Ibuprofen 400mg twice daily for 3 days to control edema.",
        pricingEst: "Scaling (120,000 UGX) + Fillings (180,000 UGX)"
      };

      if (lowerQuery.includes("throbbing") || lowerQuery.includes("swelling") || lowerQuery.includes("sensitivity")) {
        match = {
          diagnosis: "Acute Apical Periodontitis / Deep Pulp Necrosis (Likely requiring Root Canal).",
          treatment: "Multi-Visit Endodontic Root Canal Therapy (clean, medication sealing) or Surgical Extraction.",
          prescription: "Amoxicillin 500mg (8-hourly for 5 days) + Metronidazole 400mg + Diclofenac Potassium 50mg.",
          pricingEst: "Root Canal: 450,000 UGX or Surgical Extraction: 250,000 UGX"
        };
      } else if (lowerQuery.includes("missing") || lowerQuery.includes("gap") || lowerQuery.includes("implant")) {
        match = {
          diagnosis: "Partial edentulous span with localized bone density preservation.",
          treatment: "Titanium Dental Implant placement with customized porcelain abutment.",
          prescription: "Chlorhexidine 0.2% antiseptic mouthwash rinse twice daily post-surgical insertion.",
          pricingEst: "Titanium Implant: 4,000,000 UGX"
        };
      } else if (lowerQuery.includes("crowded") || lowerQuery.includes(" braces") || lowerQuery.includes("misalignment")) {
        match = {
          diagnosis: "Class II Skeletal/Dental Malocclusion with incisor crowding.",
          treatment: "Orthodontic braces treatment (Per Arch alignment & wire expansion). Payable in installments.",
          prescription: "Ortho Wax for bracket irritation relief plus routine saline rinses.",
          pricingEst: "Orthodontics: 3,500,000 UGX per arch"
        };
      }

      setAiResult(match);
      setIsAiLoading(false);
    }, 1200);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 text-slate-700">
      
      {/* LEFT: Live Patient Queue (Admin & Nurse Desk) */}
      <div className="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-slate-100 p-5 flex flex-col h-[650px]">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Users className="w-5 h-5 text-indigo-600" />
            <h3 className="font-bold text-slate-800">Clinic Patient Queue</h3>
          </div>
          <span className="text-[10px] bg-slate-100 text-slate-600 font-mono font-bold px-2 py-0.5 rounded-full">
            {filteredAppts.length} Registered
          </span>
        </div>

        {/* Search patient queue */}
        <div className="relative mb-4">
          <Search className="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
          <input
            type="text"
            placeholder="Search queue by name..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        {/* Queue Items */}
        <div className="flex-1 overflow-y-auto space-y-2 pr-1 font-sans">
          {filteredAppts.map((appt) => {
            const serv = services.find((s) => s.id === appt.serviceId);
            const isSelected = appt.id === selectedApptId;
            const outstanding = appt.totalCost - appt.amountPaid;

            return (
              <div
                key={appt.id}
                onClick={() => handleSelectAppt(appt)}
                className={`p-3 rounded-xl border text-left cursor-pointer transition ${
                  isSelected
                    ? "border-indigo-600 bg-indigo-50/40 ring-1 ring-indigo-500/25"
                    : "border-slate-100 hover:border-slate-200 bg-slate-50/50"
                }`}
              >
                <div className="flex justify-between items-start">
                  <span className="font-bold text-xs text-slate-800 block truncate max-w-[150px]">
                    {appt.patientName}
                  </span>
                  <span className={`text-[9px] font-mono font-bold uppercase px-1.5 py-0.5 rounded ${
                    appt.status === "treatment_completed"
                      ? "bg-emerald-100 text-emerald-800"
                      : appt.status === "confirmed"
                      ? "bg-sky-100 text-sky-800"
                      : "bg-amber-100 text-amber-800"
                  }`}>
                    {appt.status.replace("_", " ")}
                  </span>
                </div>
                
                <p className="text-[10px] text-slate-500 mt-0.5 truncate">{serv?.name}</p>
                
                <div className="flex justify-between items-center mt-2.5 pt-2 border-t border-slate-100/60 text-[9.5px]">
                  <span className="text-slate-400">Scheduled: {appt.appointmentDate} - {appt.appointmentTime}</span>
                  <span className={`font-mono font-bold ${outstanding > 0 ? "text-rose-500" : "text-emerald-600"}`}>
                    {outstanding > 0 ? `Unpaid Bal: ${formatUGX(outstanding)}` : "Paid Settle"}
                  </span>
                </div>
              </div>
            );
          })}
          
          {filteredAppts.length === 0 && (
            <p className="text-center text-xs text-slate-400 italic py-8">No matching visits found in queue.</p>
          )}
        </div>
      </div>

      {/* RIGHT: Selected patient active operations (clinical charts + bills) */}
      <div className="lg:col-span-8 space-y-6">
        
        {selectedAppt ? (
          <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-8 animate-fade-in">
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-100 pb-4 mb-6">
              <div>
                <span className="text-[10px] font-mono tracking-widest text-slate-400 uppercase">ACTIVE CLINICAL WORKSPACE</span>
                <h3 className="text-xl font-bold text-slate-800">{selectedAppt.patientName}</h3>
                <p className="text-xs text-slate-500">{selectedAppt.patientPhone} | {selectedAppt.patientEmail}</p>
              </div>

              <div className="bg-slate-50 px-4 py-2 rounded-xl border border-slate-100 text-right">
                <span className="text-[9px] text-slate-400 block uppercase">Treatment Cost</span>
                <span className="font-bold text-indigo-600 text-base font-mono">
                  {formatUGX(selectedAppt.totalCost)}
                </span>
              </div>
            </div>

            {/* TABULATED FUNCTION: CLINICAL NOTES + BILL SETTLEMENT */}
            <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
              
              {/* Clinical chart editors */}
              <form onSubmit={handleSaveClinicalRecord} className="md:col-span-7 space-y-4">
                <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2 flex items-center gap-2">
                  <Stethoscope className="w-4 h-4 text-indigo-500" />
                  Clinical Procedure Charting
                </h4>

                <div className="grid grid-cols-1 gap-3">
                  <div>
                    <label className="block text-[10px] uppercase font-semibold text-slate-500 mb-1">
                      Current Treatment Plan Stage
                    </label>
                    <input
                      type="text"
                      placeholder="e.g. Step 3 of 6: Archwire alignment, checking molar retention brackets"
                      value={planStep}
                      onChange={(e) => setPlanStep(e.target.value)}
                      className="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-semibold text-slate-500 mb-1">
                      Procedure Findings & Treatment Record
                    </label>
                    <textarea
                      placeholder="Detail findings (e.g. Class II cavity restored, extracted residual fragment smoothly)"
                      value={notes}
                      onChange={(e) => setNotes(e.target.value)}
                      className="w-full p-2.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 h-24"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-semibold text-slate-500 mb-1">
                      Pharmacological Prescriptions (RX)
                    </label>
                    <input
                      type="text"
                      placeholder="e.g. Amoxicillin 500mg 8-hourly, Ibuprofen 400mg for pain control"
                      value={rxText}
                      onChange={(e) => setRxText(e.target.value)}
                      className="w-full px-3 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <span className="block text-[10px] uppercase font-semibold text-slate-500 mb-1">
                        Appointment Status
                      </span>
                      <select
                        value={statusUpdate}
                        onChange={(e) => setStatusUpdate(e.target.value as Appointment["status"])}
                        className="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white"
                      >
                        <option value="pending">Pending Queue</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="treatment_completed">Treatment Completed ✅</option>
                        <option value="cancelled">Cancelled</option>
                      </select>
                    </div>

                    <div className="flex items-end">
                      <button
                        type="submit"
                        className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
                      >
                        <CheckCircle className="w-3.5 h-3.5" />
                        Save Clinic Chart
                      </button>
                    </div>
                  </div>
                </div>
              </form>

              {/* Administrative In-clinic payments ledger */}
              <div className="md:col-span-5 bg-slate-50 p-4 border border-slate-100 rounded-xl space-y-4">
                <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-2">
                  <DollarSign className="w-4 h-4 text-emerald-500" />
                  In-Office Installments Desk
                </h4>

                <div className="space-y-2 mb-2 text-xs font-mono text-slate-600">
                  <div className="flex justify-between border-b border-slate-200 pb-1">
                    <span>Outstanding:</span>
                    <span className="font-bold text-rose-500">
                      {formatUGX(selectedAppt.totalCost - selectedAppt.amountPaid)}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span>Paid to date:</span>
                    <span className="font-bold text-emerald-600">{formatUGX(selectedAppt.amountPaid)}</span>
                  </div>
                </div>

                <form onSubmit={handleRecordStaffPayment} className="space-y-3">
                  <div>
                    <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">
                      Log Cash/Transfer (UGX)
                    </label>
                    <input
                      type="number"
                      required
                      placeholder="e.g. 100000"
                      max={selectedAppt.totalCost - selectedAppt.amountPaid}
                      value={payAmount}
                      onChange={(e) => setPayAmount(e.target.value)}
                      className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none"
                    />
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">
                      Channel Received
                    </label>
                    <select
                      value={payMethod}
                      onChange={(e) => setPayMethod(e.target.value as PaymentInstallment["method"])}
                      className="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white"
                    >
                      <option value="Cash">Cash Hand-over</option>
                      <option value="MTN MoMo">MTN MoMo Gateway</option>
                      <option value="Airtel Money">Airtel Money Desk</option>
                      <option value="Bank Transfer">Bank Wire Transfer</option>
                      <option value="Visa Card">Visa Terminal Swipe</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-[10px] uppercase font-mono text-slate-500 mb-1">
                      Internal Receipt Notes
                    </label>
                    <input
                      type="text"
                      placeholder="e.g. Cleared 2nd wire fee"
                      value={payNotes}
                      onChange={(e) => setPayNotes(e.target.value)}
                      className="w-full px-2.5 py-1.5 text-xs bg-white border border-slate-200 rounded-lg focus:outline-none"
                    />
                  </div>

                  <button
                    type="submit"
                    className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1.5"
                  >
                    <Plus className="w-3.5 h-3.5" />
                    Log Office Payment
                  </button>
                </form>
              </div>

            </div>
          </div>
        ) : (
          <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 text-center">
            <p className="text-slate-400 italic text-sm">Please select a patient from the queue to open the active clinical workspace.</p>
          </div>
        )}

        {/* CLINICAL AI COMPANION ASSISTANT FOR TREATMENT RECOMMENDATION */}
        <div className="bg-gradient-to-br from-slate-900 to-slate-950 border border-slate-800 rounded-2xl shadow-md p-6 text-white text-left">
          <div className="flex items-center gap-2 mb-3">
            <Sparkles className="w-5 h-5 text-indigo-400 animate-pulse" />
            <h3 className="font-bold text-sm tracking-wide uppercase text-indigo-300">Goshen AI Assistant - Clinical Planner</h3>
          </div>
          
          <p className="text-xs text-slate-300 mb-4 leading-relaxed">
            Diagnose dental conditions securely. Specify symptoms, tooth conditions, or jaw pain to let the AI output recommended clinical treatment procedures, prescriptions, and recommended UGX billing parameters instantly.
          </p>

          <form onSubmit={diagnoseWithAI} className="space-y-4">
            <div>
              <label className="block text-[10px] font-mono text-slate-400 uppercase mb-1">
                Clinical Signs & Patient Symptoms
              </label>
              <textarea
                value={aiSigns}
                onChange={(e) => setAiSigns(e.target.value)}
                className="w-full p-2.5 text-xs border border-slate-800 bg-slate-800/80 rounded-lg text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 h-16"
              />
            </div>

            <div className="flex justify-between items-center text-xs">
              <span className="text-[10px] text-slate-400 italic">Try searching: "missing front tooth gap" or "crowded bite alignments"</span>
              <button
                type="submit"
                disabled={isAiLoading}
                className="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg font-bold transition flex items-center gap-1.5 cursor-pointer"
              >
                {isAiLoading ? "Consulting AI..." : "Formulate Plan"}
              </button>
            </div>
          </form>

          {aiResult && (
            <div className="bg-indigo-950/40 border border-indigo-900/50 p-4 rounded-xl mt-4 animate-fade-in space-y-3 font-sans">
              <div className="flex items-center gap-1.5 text-indigo-400 text-xs font-bold border-b border-indigo-900/30 pb-1">
                <Activity className="w-3.5 h-3.5" />
                AI CLINICAL CO-CHART PROPOSAL
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs leading-normal">
                <div>
                  <span className="text-[10px] text-indigo-300 uppercase block">Provisional Diagnosis:</span>
                  <span className="text-slate-200 font-medium">{aiResult.diagnosis}</span>
                </div>
                <div>
                  <span className="text-[10px] text-indigo-300 uppercase block">Proposed Plan:</span>
                  <span className="text-slate-200 font-medium">{aiResult.treatment}</span>
                </div>
                <div>
                  <span className="text-[10px] text-indigo-300 uppercase block">Recommended Rx (Medicines):</span>
                  <span className="text-slate-200 font-mono text-[11px]">{aiResult.prescription}</span>
                </div>
                <div>
                  <span className="text-[10px] text-indigo-300 uppercase block">Estimated UGX Price Index:</span>
                  <span className="text-emerald-400 font-bold">{aiResult.pricingEst}</span>
                </div>
              </div>

              {selectedAppt && (
                <div className="flex justify-end pt-3 text-xs">
                  <button
                    type="button"
                    onClick={() => {
                      setNotes(aiResult.diagnosis + " Recommended: " + aiResult.treatment);
                      setRxText(aiResult.prescription);
                      setPlanStep(aiResult.treatment);
                      alert("AI diagnostics imported to active chart form below. Make edits as needed and press Save.");
                    }}
                    className="text-[10.5px] font-bold text-indigo-300 bg-white/5 hover:bg-white/10 px-3 py-1.5 rounded transition border border-indigo-700/40"
                  >
                    Import Recommendation to Form
                  </button>
                </div>
              )}
            </div>
          )}

        </div>

      </div>

    </div>
  );
}
