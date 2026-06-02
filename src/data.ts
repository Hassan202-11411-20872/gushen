/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { DentalService, StaffMember, InventoryItem, ClinicExpense, Appointment } from "./types";

export const INITIAL_SERVICES: DentalService[] = [
  {
    id: "serv_1",
    name: "Consultation & Comprehensive Digital Diagnosis",
    price: 50000,
    category: "Preventive",
    description: "Initial clinical evaluation, status mapping, and multi-angle digital diagnostics.",
  },
  {
    id: "serv_2",
    name: "Ultrasonic Scaling & Deep Polishing",
    price: 120000,
    category: "Preventive",
    description: "Removal of dental calculus, plaque, surface stains followed by fluoridation.",
  },
  {
    id: "serv_3",
    name: "Composite Tooth-Colored Restoration (Filling)",
    price: 180000,
    category: "Curative",
    description: "High-grade dental composite restorations to fix cavities with shade matching.",
  },
  {
    id: "serv_4",
    name: "Surgical Tooth Extraction & Alveoloplasty",
    price: 250000,
    category: "Surgical",
    description: "Pain-free removal of severely impacted, decayed, or fractured dental roots.",
  },
  {
    id: "serv_5",
    name: "Multi-Visit Endodontic Therapy (Root Canal)",
    price: 450000,
    category: "Curative",
    description: "Advanced root canal cleaning, shaping, medication, and core sealing.",
  },
  {
    id: "serv_6",
    name: "Porcelain-fused-to-Metal Aesthetic Crown",
    price: 850000,
    category: "Aesthetic",
    description: "Hard-wearing customized dental crown to restore morphology and bite.",
  },
  {
    id: "serv_7",
    name: "Premium Orthodontic Braces Treatment (Per Arch)",
    price: 3500000,
    category: "Aesthetic",
    description: "Malocclusion alignment using durable metallic brackets. Payable in installments.",
  },
  {
    id: "serv_8",
    name: "Titanium Dental Implant & Abutment",
    price: 4000000,
    category: "Surgical",
    description: "Titanium post placement to fully replace roots, including premium crown.",
  }
];

export const INITIAL_STAFF: StaffMember[] = [
  {
    id: "dentist_ronald",
    name: "Dr. Ronald Okello",
    role: "Senior Orthodontist",
    proceduresCompleted: 342,
    revenueGenerated: 145000000,
    patientRating: 4.9,
  },
  {
    id: "dentist_sarah",
    name: "Dr. Sarah Nabakooza",
    role: "Maxillofacial & Oral Surgeon",
    proceduresCompleted: 218,
    revenueGenerated: 89600000,
    patientRating: 5.0,
  },
  {
    id: "dentist_david",
    name: "Dr. David Mukasa",
    role: "General Dentist Surgeon",
    proceduresCompleted: 495,
    revenueGenerated: 72400000,
    patientRating: 4.8,
  },
  {
    id: "nurse_gloria",
    name: "Nurse Gloria Atwine",
    role: "Clinical Lead Nurse",
    proceduresCompleted: 124,
    revenueGenerated: 7500000,
    patientRating: 4.9,
  }
];

export const INITIAL_INVENTORY: InventoryItem[] = [
  {
    id: "inv_1",
    name: "Composite Filler Resin Syringes (A2 & A3)",
    quantity: 45,
    threshold: 15,
    unit: "Syringes",
    costPerUnit: 45000,
  },
  {
    id: "inv_2",
    name: "Local Articaine Anesthetic (1:100,000 epinephrine)",
    quantity: 8,
    threshold: 25,
    unit: "Vials",
    costPerUnit: 12000,
  },
  {
    id: "inv_3",
    name: "Interdental Restoration Microbrushes",
    quantity: 150,
    threshold: 50,
    unit: "Pieces",
    costPerUnit: 1500,
  },
  {
    id: "inv_4",
    name: "Sterile Latex Examination Gloves (Size M/L)",
    quantity: 12,
    threshold: 30,
    unit: "Boxes",
    costPerUnit: 25000,
  },
  {
    id: "inv_5",
    name: "High-Volume Evacuation Suction Tips",
    quantity: 95,
    threshold: 30,
    unit: "Pieces",
    costPerUnit: 2000,
  },
  {
    id: "inv_6",
    name: "Glass Ionomer Restorative Crown Cement",
    quantity: 6,
    threshold: 4,
    unit: "Kits",
    costPerUnit: 180000,
  }
];

export const INITIAL_EXPENSES: ClinicExpense[] = [
  {
    id: "exp_1",
    category: "Rent",
    amount: 3200000,
    date: "2026-05-01",
    description: "Monthly clinic space lease - Kampala Road Block 4",
  },
  {
    id: "exp_2",
    category: "Utilities",
    amount: 650000,
    date: "2026-05-15",
    description: "National Water & Umeme stable power utility bills",
  },
  {
    id: "exp_3",
    category: "Supplies",
    amount: 1400000,
    date: "2026-05-18",
    description: "Dental materials RESTOCK for filling components and sterilants",
  },
  {
    id: "exp_4",
    category: "Marketing",
    amount: 450000,
    date: "2026-05-24",
    description: "Social media awareness & flyers around Kampala Central",
  },
  {
    id: "exp_5",
    category: "Salaries",
    amount: 8500000,
    date: "2026-05-28",
    description: "Staff basic allowances and dentist base payouts for May",
  }
];

export const INITIAL_APPOINTMENTS: Appointment[] = [
  {
    id: "appt_1",
    patientName: "Florence Nakimera",
    patientPhone: "+256 702 123456",
    patientEmail: "florence.nakimera@gmail.com",
    serviceId: "serv_7", // Premium Orthodontic Braces (3,500,000 UGX)
    appointmentDate: "2026-06-03",
    appointmentTime: "09:00",
    status: "confirmed",
    clinicianId: "dentist_ronald",
    totalCost: 3500000,
    amountPaid: 1500000,
    paymentStatus: "partial",
    installments: [
      {
        id: "inst_1_1",
        amount: 1000000,
        date: "2026-05-02",
        method: "MTN MoMo",
        notes: "Initial setup deposit & bracket allocation.",
      },
      {
        id: "inst_1_2",
        amount: 500000,
        date: "2026-05-28",
        method: "Airtel Money",
        notes: "1st installment adjustment and leveling wire change.",
      }
    ],
    clinicalNotes: "Patient has completed dental leveling and alignment checkups. Normal arch development. Next step: introduce stronger archwire guidance.",
    treatmentPlanStep: "Step 2 of 6: Archwire alignment adjustment and molar band checkup.",
    remindersSent: 2,
    createdAt: "2026-05-01T10:00:00Z"
  },
  {
    id: "appt_2",
    patientName: "Moses Ssewankambo",
    patientPhone: "+256 772 987654",
    patientEmail: "moses.s@yahoo.com",
    serviceId: "serv_5", // Root canal (450,000 UGX)
    appointmentDate: "2026-06-01",
    appointmentTime: "11:30",
    status: "treatment_completed",
    clinicianId: "dentist_sarah",
    totalCost: 450000,
    amountPaid: 450000,
    paymentStatus: "fully_paid",
    installments: [
      {
        id: "inst_2_1",
        amount: 250000,
        date: "2026-05-15",
        method: "MTN MoMo",
        notes: "Initial consultation & Pulpectomy treatment.",
      },
      {
        id: "inst_2_2",
        amount: 200000,
        date: "2026-06-01",
        method: "Cash",
        notes: "Thermafil obturation, sealing apex with glass endomer and composite polish.",
      }
    ],
    clinicalNotes: "Root canal therapy completed on lower-left molar (Tooth 36). Fully cleaned canals, asymptomatic. Finished with full composite core restoration.",
    treatmentPlanStep: "Completed. Post-surgery recall recommended in 4 months.",
    rxPrescribed: "Amoxicillin 500mg (3x/day, 5 Days), Paracetamol 500mg for mild discomfort.",
    remindersSent: 3,
    createdAt: "2026-05-15T14:30:00Z"
  },
  {
    id: "appt_3",
    patientName: "Anita Birungi",
    patientPhone: "+256 755 444333",
    patientEmail: "birungi.anita98@outlook.com",
    serviceId: "serv_2", // Scaling and polishing (120,000 UGX)
    appointmentDate: "2026-06-04",
    appointmentTime: "14:00",
    status: "pending",
    clinicianId: "dentist_david",
    totalCost: 120000,
    amountPaid: 0,
    paymentStatus: "unpaid",
    installments: [],
    clinicalNotes: "",
    remindersSent: 0,
    createdAt: "2026-06-02T08:15:00Z"
  },
  {
    id: "appt_4",
    patientName: "John Katumba",
    patientPhone: "+256 701 888777",
    patientEmail: "katumbajohn@gmail.com",
    serviceId: "serv_3", // Composite restoration (180,000 UGX)
    appointmentDate: "2026-06-02",
    appointmentTime: "10:00",
    status: "treatment_completed",
    clinicianId: "dentist_david",
    totalCost: 180000,
    amountPaid: 180000,
    paymentStatus: "fully_paid",
    installments: [
      {
        id: "inst_4_1",
        amount: 180000,
        date: "2026-06-02",
        method: "Cash",
        notes: "Full payment for immediate tooth-colored filling restoration.",
      }
    ],
    clinicalNotes: "Class II distal caries restoration of upper right incisor. Beautiful shade restoration match (A3). Perfect bite occlusion checks.",
    treatmentPlanStep: "Completed. Advised patient to avoid hard biting for 24 hours.",
    remindersSent: 1,
    createdAt: "2026-06-01T15:20:00Z"
  },
  {
    id: "appt_5",
    patientName: "Kabasomi Brenda",
    patientPhone: "+256 782 555666",
    patientEmail: "brendakabs@gmail.com",
    serviceId: "serv_8", // Dental Implant (4,000,000 UGX)
    appointmentDate: "2026-06-15",
    appointmentTime: "15:30",
    status: "confirmed",
    clinicianId: "dentist_sarah",
    totalCost: 4000000,
    amountPaid: 2000000,
    paymentStatus: "partial",
    installments: [
      {
        id: "inst_5_1",
        amount: 2000000,
        date: "2026-05-20",
        method: "Bank Transfer",
        notes: "Part-payment for importing titanium implant post (Osstem).",
      }
    ],
    clinicalNotes: "CT-scan reviewed. Safe bone volume (depth 12mm) at quadrant #46. Planned single implant procedure.",
    treatmentPlanStep: "Step 1 of 3: Surgical implant post drilling & insertion under local block.",
    remindersSent: 1,
    createdAt: "2026-05-20T11:00:00Z"
  }
];
