<?php

namespace App\Http\Controllers\Doctor;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;

/** Use case: Prescribe Medication (<<extend>> Print / Send Prescription). */
class PrescriptionController extends DoctorController
{
    public function create(Request $request, Patient $patient)
    {
        return view('doctor.prescribe', ['patient' => $patient, 'consultationId' => $request->query('consultation')]);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'consultation_id' => 'nullable|exists:consultations,id',
            'advice' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.medicine' => 'required|string|max:150',
            'items.*.dosage' => 'nullable|string|max:100',
            'items.*.frequency' => 'nullable|string|max:100',
            'items.*.duration' => 'nullable|string|max:100',
            'items.*.instructions' => 'nullable|string|max:255',
        ]);
        $rx = Prescription::create([
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor()->id,
            'consultation_id' => $data['consultation_id'] ?? null,
            'advice' => $data['advice'] ?? null, 'follow_up_date' => $data['follow_up_date'] ?? null,
        ]);
        foreach ($data['items'] as $item) {
            $rx->items()->create($item);
        }
        AuditLog::record('prescription_created', "{$patient->code} #{$rx->id}");
        \App\Models\Alert::send($patient->user_id, 'New prescription', 'Your dermatologist issued a prescription.',
            route('print.prescription', $rx));
        return redirect()->route('doctor.patients.show', $patient)
            ->with('success', 'Prescription saved.')->with('print_url', route('print.prescription', $rx));
    }
}
