/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

export interface DentalService {
  id: string;
  name: string;
  price: number; // in UGX
  category: "Preventive" | "Curative" | "Aesthetic" | "Surgical";
  description: string;
}

export interface StaffMember {
  id: string;
  name: string;
  role: string;
  proceduresCompleted: number;
  revenueGenerated: number;
  patientRating: number; // e.g. 4.9
}

export interface InventoryItem {
  id: string;
  name: string;
  quantity: number;
  threshold: number;
  unit: string;
  costPerUnit: number; // in UGX
}

export interface ClinicExpense {
  id: string;
  category: "Rent" | "Utilities" | "Supplies" | "Marketing" | "Salaries" | "Other";
  amount: number; // in UGX
  date: string;
  description: string;
}

export interface PaymentInstallment {
  id: string;
  amount: number; // in UGX
  date: string;
  method: "MTN MoMo" | "Airtel Money" | "Cash" | "Visa Card" | "Bank Transfer";
  notes: string;
}

export interface Appointment {
  id: string;
  patientName: string;
  patientPhone: string;
  patientEmail: string;
  serviceId: string;
  appointmentDate: string;
  appointmentTime: string;
  status: "pending" | "confirmed" | "treatment_completed" | "cancelled";
  clinicianId: string;
  totalCost: number; // in UGX
  amountPaid: number; // in UGX
  paymentStatus: "unpaid" | "partial" | "fully_paid";
  installments: PaymentInstallment[];
  clinicalNotes: string;
  treatmentPlanStep?: string;
  rxPrescribed?: string;
  remindersSent: number;
  createdAt: string;
}

export interface EmailNotification {
  id: string;
  appointmentId: string;
  patientEmail: string;
  patientName: string;
  subject: string;
  content: string;
  sentAt: string;
  status: "sent" | "failed";
}
