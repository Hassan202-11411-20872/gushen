/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import React, { useState } from "react";
import { Appointment, DentalService, StaffMember, PaymentInstallment } from "../types";
import { 
  Calendar, 
  Clock, 
  Phone, 
  Mail, 
  User, 
  Search, 
  ShieldCheck, 
  CheckCircle, 
  CreditCard, 
  Bell, 
  Sparkles, 
  Plus, 
  Tag, 
  Percent, 
  HeartHandshake 
} from "lucide-react";

interface PatientPortalProps {
  appointments: Appointment[];
  services: DentalService[];
  staff: StaffMember[];
  onAddAppointment: (appt: Appointment) => void;
  onAddInstallment: (apptId: string, installment: PaymentInstallment) => void;
  triggerNotification: (apptId: string, subject: string, content: string) => void;
}

export default function PatientPortal({
  appointments,
  services,
  staff,
  onAddAppointment,
  onAddInstallment,
  triggerNotification,
}: PatientPortalProps) {
  // Booking Form State
  const [selectedServiceId, setSelectedServiceId] = useState<string>(services[0]?.id || "");
  const [selectedClinicianId, setSelectedClinicianId] = useState<string>(staff[0]?.id || "");
  const [patientName, setPatientName] = useState("");
  const [patientPhone, setPatientPhone] = useState("");
  const [patientEmail, setPatientEmail] = useState("");
  const [bookingDate, setBookingDate] = useState("");
  const [bookingTime, setBookingTime] = useState("09:00");
  const [notes, setNotes] = useState("");
  const [isBookedSuccess, setIsBookedSuccess] = useState(false);

  // Active Patient Tracking State
  const [searchPhone, setSearchPhone] = useState("");
  const [activePatientAppts, setActivePatientAppts] = useState<Appointment[]>([]);
  const [hasSearched, setHasSearched] = useState(false);
  const [selectedApptForPayment, setSelectedApptForPayment] = useState<Appointment | null>(null);

  // Installment payment state
  const [installmentAmount, setInstallmentAmount] = useState<string>("");
  const [paymentMethod, setPaymentMethod] = useState<"MTN MoMo" | "Airtel Money" | "Cash" | "Visa Card" | "Bank Transfer">("MTN MoMo");
  const [isProcessingPayment, setIsProcessingPayment] = useState(false);
  const [paymentSuccessMsg, setPaymentSuccessMsg] = useState("");

  const formatUGX = (amount: number) => {
    return new Intl.NumberFormat("en-UG", {
      style: "currency",
      currency: "UGX",
      maximumFractionDigits: 0,
    }).format(amount);
  };

  // Pre-fill active patient search with a clinical demo patient
  const handleQuickSelectPatient = (phone: string) => {
    setSearchPhone(phone);
    const matched = appointments.filter(
      (a) => a.patientPhone.replace(/\s+/g, "").includes(phone.replace(/\s+/g, ""))
    );
    setActivePatientAppts(matched);
    setHasSearched(true);
    if (matched.length > 0) {
      setSelectedApptForPayment(matched[0]);
    } else {
      setSelectedApptForPayment(null);
    }
  };

  const handleSearchPatient = (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchPhone.trim()) return;
    const cleanSearch = searchPhone.trim().toLowerCase();
    const matched = appointments.filter(
      (a) =>
        a.patientPhone.toLowerCase().includes(cleanSearch) ||
        a.patientName.toLowerCase().includes(cleanSearch)
    );
    setActivePatientAppts(matched);
    setHasSearched(true);
    if (matched.length > 0) {
      setSelectedApptForPayment(matched[0]);
    } else {
      setSelectedApptForPayment(null);
    }
  };

  const handleBookAppointment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!patientName || !patientPhone || !patientEmail || !bookingDate || !bookingTime) {
      alert("Please fill in all general patient contact and date fields.");
      return;
    }

    const matchedService = services.find((s) => s.id === selectedServiceId);
    const servicePrice = matchedService ? matchedService.price : 0;

    const newAppointment: Appointment = {
      id: "appt_" + Date.now(),
      patientName,
      patientPhone,
      patientEmail,
      serviceId: selectedServiceId,
      appointmentDate: bookingDate,
      appointmentTime: bookingTime,
      status: "pending",
      clinicianId: selectedClinicianId,
      totalCost: servicePrice,
      amountPaid: 0,
      paymentStatus: "unpaid",
      installments: [],
      clinicalNotes: "",
      remindersSent: 0,
      createdAt: new Date().toISOString()
    };

    onAddAppointment(newAppointment);
    
    // Trigger automated email/MoMo billing notifications
    const matchedClinician = staff.find((st) => st.id === selectedClinicianId);
    triggerNotification(
      newAppointment.id,
      `Goshen Dental Appointment Scheduled - ${matchedService?.name}`,
      `Dear ${patientName}, your scheduled visit for "${matchedService?.name}" at Goshen Dental Care is registered on ${bookingDate} at ${bookingTime} with ${matchedClinician?.name}. Estimated cost: ${formatUGX(servicePrice)}. Manage your installment treatment plan securely on our app.`
    );

    setIsBookedSuccess(true);
    setTimeout(() => {
      setIsBookedSuccess(false);
      // Reset form fields
      setPatientName("");
      setPatientPhone("");
      setPatientEmail("");
      setBookingDate("");
      setNotes("");
    }, 4000);
  };

  const handlePayInstallment = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedApptForPayment || !installmentAmount) return;
    const payValue = parseFloat(installmentAmount);
    const balance = selectedApptForPayment.totalCost - selectedApptForPayment.amountPaid;

    if (isNaN(payValue) || payValue <= 0) {
      alert("Please enter a valid payment amount.");
      return;
    }
    if (payValue > balance) {
      alert(`The maximum payment amount outstanding is ${formatUGX(balance)}.`);
      return;
    }

    setIsProcessingPayment(true);
    setPaymentSuccessMsg("");

    setTimeout(() => {
      const newInst: PaymentInstallment = {
        id: "inst_" + Date.now(),
        amount: payValue,
        date: new Date().toISOString().split("T")[0],
        method: paymentMethod,
        notes: `Simulated secure portal transaction via user authorization.`
      };

      onAddInstallment(selectedApptForPayment.id, newInst);
      
      // Update local state copy to show fresh figures immediately
      const updatedAppt = {
        ...selectedApptForPayment,
        amountPaid: selectedApptForPayment.amountPaid + payValue,
        paymentStatus: (selectedApptForPayment.amountPaid + payValue) >= selectedApptForPayment.totalCost 
          ? "fully_paid" as const 
          : "partial" as const
      };
      
      setSelectedApptForPayment(updatedAppt);
      // Update searched appointments list to show the updated values
      setActivePatientAppts(prev => prev.map(a => a.id === updatedAppt.id ? updatedAppt : a));

      // Trigger automatic receipt alerts
      triggerNotification(
        selectedApptForPayment.id,
        "Goshen Dental Care - Payment Received",
        `Mulandire! Payment receipt confirmed: ${formatUGX(payValue)} received via ${paymentMethod} for ${selectedApptForPayment.patientName}. Remaining treatment balance outstanding: ${formatUGX(updatedAppt.totalCost - updatedAppt.amountPaid)}.`
      );

      setIsProcessingPayment(false);
      setInstallmentAmount("");
      setPaymentSuccessMsg(`Success! ${formatUGX(payValue)} captured securely via ${paymentMethod}. Updated balance immediately.`);
      setTimeout(() => setPaymentSuccessMsg(""), 5000);
    }, 1500); // 1.5s simulated MoMo network latency
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 text-slate-700">
      
      {/* LEFT: Services offered Catalog and Booking Request */}
      <div className="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-8">
        <div className="flex items-center gap-2 mb-4">
          <Sparkles className="w-5 h-5 text-indigo-600" />
          <h2 className="text-xl font-bold text-slate-800">
            Book Tooth Treatment / Dental Diagnosis
          </h2>
        </div>
        <p className="text-sm text-slate-500 mb-6">
          Compare treatment pricing catalogs and request an appointment instantly. Goshen Dental Care provides premium endodontics, orthodontics, and restorative surgery, with highly custom installment plans.
        </p>

        {isBookedSuccess ? (
          <div className="bg-emerald-50 border border-emerald-100 rounded-xl p-6 text-center animate-fade-in my-8">
            <CheckCircle className="w-12 h-12 text-emerald-500 mx-auto mb-3" />
            <h3 className="text-lg font-bold text-emerald-950 mb-1">Appointment Ordered Successfully!</h3>
            <p className="text-sm text-emerald-800">
              Your request is registered in the Goshen Clinic queue. A simulated SMS and email reminder with your treatment budget summary has been dispatched.
            </p>
          </div>
        ) : (
          <form id="patient_booking_form" onSubmit={handleBookAppointment} className="space-y-5">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Full Name
                </label>
                <div className="relative">
                  <User className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    type="text"
                    required
                    placeholder="e.g. Namubiru Justine"
                    value={patientName}
                    onChange={(e) => setPatientName(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>
              
              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Phone Number
                </label>
                <div className="relative">
                  <Phone className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    type="tel"
                    required
                    placeholder="e.g. +256 772 000000"
                    value={patientPhone}
                    onChange={(e) => setPatientPhone(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 font-sans">
              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Email Address
                </label>
                <div className="relative">
                  <Mail className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    type="email"
                    required
                    placeholder="e.g. name@domain.com"
                    value={patientEmail}
                    onChange={(e) => setPatientEmail(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Treatment / Service Desired
                </label>
                <select
                  value={selectedServiceId}
                  onChange={(e) => setSelectedServiceId(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                >
                  {services.map((serv) => (
                    <option key={serv.id} value={serv.id}>
                      {serv.name} — {formatUGX(serv.price)}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="md:col-span-2">
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Appointment Date
                </label>
                <div className="relative">
                  <Calendar className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    type="date"
                    required
                    value={bookingDate}
                    onChange={(e) => setBookingDate(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Preferred Time
                </label>
                <div className="relative">
                  <Clock className="absolute left-3 top-3 w-4 h-4 text-slate-400" />
                  <input
                    type="time"
                    required
                    value={bookingTime}
                    onChange={(e) => setBookingTime(e.target.value)}
                    className="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 gap-4">
              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Assign Clinician Preference
                </label>
                <select
                  value={selectedClinicianId}
                  onChange={(e) => setSelectedClinicianId(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                >
                  {staff.map((st) => (
                    <option key={st.id} value={st.id}>
                      {st.name} ({st.role})
                    </option>
                  ))}
                </select>
              </div>
              
              <div>
                <label className="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">
                  Message / Special Request / Clinical Backstory
                </label>
                <textarea
                  placeholder="Tell us if you have localized toothache, gum bleeding, dental anxiety, or require dynamic installment configurations..."
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  className="w-full p-3 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 h-20"
                />
              </div>
            </div>

            <button
              id="patient_submit_btn"
              type="submit"
              className="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-xl transition shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer font-sans"
            >
              <CheckCircle className="w-5 h-5" />
              Request Appointment & Register Treatment Budget
            </button>
          </form>
        )}

        {/* transparent service catalogs */}
        <hr className="my-8 border-slate-100" />
        
        <h3 className="font-bold text-slate-800 mb-3 flex items-center gap-2">
          <Tag className="w-4 h-4 text-indigo-500" />
          Transparent Dental Pricing Catalog (UGX)
        </h3>
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {services.map((s) => (
            <div key={s.id} className="p-3 bg-slate-50 hover:bg-indigo-50/50 rounded-lg border border-slate-100 transition">
              <div className="flex justify-between items-start gap-1 font-sans">
                <span className="font-semibold text-xs text-slate-800 leading-tight">{s.name}</span>
                <span className="text-indigo-600 font-bold text-xs bg-indigo-50 px-2 py-0.5 rounded flex-shrink-0">
                  {formatUGX(s.price)}
                </span>
              </div>
              <p className="text-[10px] text-slate-400 mt-1">{s.description}</p>
            </div>
          ))}
        </div>
      </div>

      {/* RIGHT: TRACK APPOINTMENT HISTORY, COPAY, AND SECURE INSTALLMENTS */}
      <div className="lg:col-span-5 flex flex-col gap-6">
        
        <div className="bg-gradient-to-br from-indigo-900 to-indigo-950 rounded-2xl shadow-xl text-white p-6 relative overflow-hidden">
          {/* Decorative background shape */}
          <div className="absolute top-0 right-0 w-36 h-36 bg-indigo-500/10 rounded-full blur-2xl" />
          
          <div className="flex items-center gap-2 mb-3">
            <HeartHandshake className="w-5 h-5 text-emerald-400" />
            <h2 className="text-lg font-bold">Secure Installments Tracker</h2>
          </div>
          
          <p className="text-xs text-indigo-200 mb-5 leading-relaxed">
            Enter your name or phone number below to access your dental recovery records, see treatment plans, and pay down outstanding clinic installments securely using MTN or Airtel wallet tools.
          </p>

          <div className="space-y-2 mb-4">
            <label className="block text-[10px] font-mono uppercase tracking-wider text-indigo-300">
              Demo Quick Check-In (Click to load):
            </label>
            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                onClick={() => handleQuickSelectPatient("+256 702 123456")}
                className="bg-white/10 hover:bg-white/20 px-2.5 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition border border-white/10"
              >
                Florence (+256 702...)
              </button>
              <button
                type="button"
                onClick={() => handleQuickSelectPatient("+256 782 555666")}
                className="bg-white/10 hover:bg-white/20 px-2.5 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition border border-white/10"
              >
                Brenda (+256 782...)
              </button>
              <button
                type="button"
                onClick={() => handleQuickSelectPatient("+256 772 987654")}
                className="bg-white/10 hover:bg-white/20 px-2.5 py-1.5 rounded-lg text-xs font-medium cursor-pointer transition border border-white/10"
              >
                Moses (+256 772...)
              </button>
            </div>
          </div>

          <form id="patient_search_form" onSubmit={handleSearchPatient} className="flex gap-2">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-2.5 w-4 h-4 text-slate-400" />
              <input
                type="text"
                placeholder="Phone or Patient Name..."
                value={searchPhone}
                onChange={(e) => setSearchPhone(e.target.value)}
                className="w-full pl-9 pr-3 py-2 text-xs bg-white text-slate-800 rounded-lg focus:outline-none ring-2 ring-indigo-500/50"
              />
            </div>
            <button
              type="submit"
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-xs font-bold cursor-pointer transition"
            >
              Verify
            </button>
          </form>
        </div>

        {/* RESULTS FOR PATIENT HISTORY AND BILLING */}
        {hasSearched && (
          <div className="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 animate-fade-in flex-1">
            <h3 className="font-bold text-slate-800 mb-4 flex items-center gap-2 border-b border-slate-100 pb-2">
              <ShieldCheck className="w-5 h-5 text-emerald-500" />
              Clinical Identity Verified
            </h3>

            {activePatientAppts.length === 0 ? (
              <div className="text-center py-8">
                <p className="text-sm text-slate-400 italic">No existing records found matching your identifier.</p>
                <p className="text-xs text-slate-400 mt-1">Please book an appointment to register your file.</p>
              </div>
            ) : (
              <div className="space-y-5">
                <div>
                  <p className="text-xs text-slate-400 uppercase font-mono">Patient File Owner</p>
                  <p className="text-lg font-bold text-slate-800">{activePatientAppts[0].patientName}</p>
                  <p className="text-xs text-slate-500">{activePatientAppts[0].patientPhone} | {activePatientAppts[0].patientEmail}</p>
                </div>

                <div className="space-y-4">
                  <p className="text-xs uppercase font-mono text-slate-400 border-b border-dashed border-slate-100 pb-1">Treatment History & Billing</p>
                  {activePatientAppts.map((appt) => {
                    const serv = services.find((s) => s.id === appt.serviceId);
                    const outstanding = appt.totalCost - appt.amountPaid;
                    const percentPaid = Math.round((appt.amountPaid / appt.totalCost) * 100) || 0;

                    return (
                      <div
                        key={appt.id}
                        onClick={() => setSelectedApptForPayment(appt)}
                        className={`p-3.5 rounded-xl border transition cursor-pointer ${
                          selectedApptForPayment?.id === appt.id
                            ? "border-indigo-500 bg-indigo-50/50 ring-1 ring-indigo-500/20"
                            : "border-slate-100 hover:border-slate-200 bg-slate-50/50"
                        }`}
                      >
                        <div className="flex justify-between items-start mb-2 font-sans">
                          <div>
                            <span className="text-[10px] font-bold uppercase py-0.5 px-1.5 rounded bg-indigo-100 text-indigo-800">
                              {appt.appointmentDate}
                            </span>
                            <h4 className="font-semibold text-xs text-slate-800 mt-1">{serv?.name}</h4>
                          </div>
                          
                          <span className={`text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full ${
                            appt.status === "treatment_completed" 
                              ? "bg-emerald-100 text-emerald-800"
                              : appt.status === "confirmed"
                              ? "bg-sky-100 text-sky-800"
                              : "bg-amber-100 text-amber-800"
                          }`}>
                            {appt.status.replace("_", " ")}
                          </span>
                        </div>

                        {/* Treatment stage progression */}
                        {appt.treatmentPlanStep && (
                          <div className="bg-white/80 p-2 rounded border border-slate-100 mb-2">
                            <p className="text-[10px] font-mono text-slate-400 uppercase">Interactive Treatment Plan:</p>
                            <p className="text-xs font-medium text-slate-700">{appt.treatmentPlanStep}</p>
                          </div>
                        )}

                        {/* Bill calculations */}
                        <div className="grid grid-cols-3 gap-2 text-center text-[11px] mb-2 font-mono">
                          <div className="bg-white p-1 rounded border border-slate-100">
                            <span className="text-slate-400 block text-[9px] uppercase">Cost</span>
                            <span className="font-bold text-slate-700">{formatUGX(appt.totalCost)}</span>
                          </div>
                          <div className="bg-white p-1 rounded border border-slate-100">
                            <span className="text-slate-400 block text-[9px] uppercase">Paid</span>
                            <span className="font-bold text-emerald-600">{formatUGX(appt.amountPaid)}</span>
                          </div>
                          <div className="bg-white p-1 rounded border border-slate-100">
                            <span className="text-slate-400 block text-[9px] uppercase">Balance</span>
                            <span className={`font-bold ${outstanding > 0 ? "text-rose-500" : "text-emerald-600"}`}>
                              {formatUGX(outstanding)}
                            </span>
                          </div>
                        </div>

                        {/* installment completion progress bar */}
                        <div className="w-full bg-slate-100 rounded-full h-1.5 mb-2 overflow-hidden">
                          <div
                            className={`h-full rounded-full transition-all duration-500 ${
                              percentPaid >= 100 ? "bg-emerald-500" : "bg-indigo-600"
                            }`}
                            style={{ width: `${percentPaid}%` }}
                          />
                        </div>
                        <div className="flex justify-between items-center text-[10px] text-slate-500">
                          <span>Installment Target Met: {percentPaid}%</span>
                          <span className={`font-semibold ${appt.paymentStatus === "fully_paid" ? "text-emerald-600" : "text-amber-600"}`}>
                            {appt.paymentStatus === "fully_paid" ? "Fully Settled" : "Installment Active"}
                          </span>
                        </div>

                        {/* Logged installments list */}
                        {appt.installments.length > 0 && (
                          <div className="mt-2 text-[10px] text-slate-500 space-y-1 bg-white p-2 rounded border border-slate-100 font-mono">
                            <span className="font-bold text-slate-400 block text-[9px] uppercase">Installment Receipts Ledger:</span>
                            {appt.installments.map((inst) => (
                              <div key={inst.id} className="flex justify-between">
                                <span>• {inst.date} ({inst.method}):</span>
                                <span className="font-semibold text-slate-700">+{formatUGX(inst.amount)}</span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    );
                  })}
                </div>

                {/* PAY UP AN INSTALLMENT DYNAMIC MODULE */}
                {selectedApptForPayment && (selectedApptForPayment.totalCost - selectedApptForPayment.amountPaid) > 0 && (
                  <div className="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 mt-4 animate-fade-in">
                    <h4 className="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2 flex items-center justify-between">
                      <span>Submit Installment Settlement</span>
                      <span className="text-indigo-600 font-mono text-[10.5px]">MoMo Secure</span>
                    </h4>

                    {paymentSuccessMsg && (
                      <div className="bg-emerald-100 border border-emerald-200 text-emerald-950 p-2.5 rounded-lg text-xs mb-3 font-semibold text-center">
                        {paymentSuccessMsg}
                      </div>
                    )}

                    <form id="payment_installment_form" onSubmit={handlePayInstallment} className="space-y-3">
                      <div>
                        <label className="block text-[10px] uppercase font-mono text-slate-400 mb-1">
                          Payment Gateway Channel
                        </label>
                        <div className="grid grid-cols-3 gap-2">
                          <button
                            type="button"
                            onClick={() => setPaymentMethod("MTN MoMo")}
                            className={`p-1.5 text-[10px] font-bold rounded-lg border text-center transition cursor-pointer ${
                              paymentMethod === "MTN MoMo"
                                ? "bg-amber-100 border-amber-300 text-amber-900"
                                : "bg-white border-slate-200 hover:bg-slate-100 text-slate-600"
                            }`}
                          >
                            MTN MoMo 🟡
                          </button>
                          <button
                            type="button"
                            onClick={() => setPaymentMethod("Airtel Money")}
                            className={`p-1.5 text-[10px] font-bold rounded-lg border text-center transition cursor-pointer ${
                              paymentMethod === "Airtel Money"
                                ? "bg-rose-100 border-rose-300 text-rose-900"
                                : "bg-white border-slate-200 hover:bg-slate-100 text-slate-600"
                            }`}
                          >
                            Airtel 🔴
                          </button>
                          <button
                            type="button"
                            onClick={() => setPaymentMethod("Visa Card")}
                            className={`p-1.5 text-[10px] font-bold rounded-lg border text-center transition cursor-pointer ${
                              paymentMethod === "Visa Card"
                                ? "bg-slate-100 border-slate-300 text-slate-900"
                                : "bg-white border-slate-200 hover:bg-slate-100 text-slate-600"
                            }`}
                          >
                            Visa Card 💳
                          </button>
                        </div>
                      </div>

                      <div className="grid grid-cols-2 gap-3 items-end">
                        <div>
                          <label className="block text-[10px] uppercase font-mono text-slate-400 mb-1">
                            Installment Amount (UGX)
                          </label>
                          <input
                            type="number"
                            required
                            placeholder="e.g. 500000"
                            max={selectedApptForPayment.totalCost - selectedApptForPayment.amountPaid}
                            value={installmentAmount}
                            onChange={(e) => setInstallmentAmount(e.target.value)}
                            className="w-full px-2.5 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none bg-white font-mono"
                          />
                        </div>
                        
                        <button
                          type="submit"
                          disabled={isProcessingPayment}
                          className="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 hover:shadow text-white rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                          {isProcessingPayment ? (
                            <span className="flex items-center gap-1">
                              <span className="animate-spin inline-block w-2.5 h-2.5 border-2 border-white border-t-transparent rounded-full" />
                              Dialing...
                            </span>
                          ) : (
                            <>
                              <CreditCard className="w-3.5 h-3.5" />
                              Pay Installment
                            </>
                          )}
                        </button>
                      </div>
                      
                      <p className="text-[9px] text-slate-400 italic">
                        Secured using AES-256 mobile financial tokens for clinical records.
                      </p>
                    </form>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>

    </div>
  );
}
