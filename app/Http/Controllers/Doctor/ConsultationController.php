<?php

namespace App\Http\Controllers\Doctor;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Patient;
use App\Support\Access;
use Illuminate\Http\Request;

/** Use case: Record Consultation Notes (<<include>> Record Skin Type & History). */
class ConsultationController extends DoctorController
{
    public function create(Request $request, Patient $patient)
    {
        $appointment = $request->query('appointment') ? Appointment::find($request->query('appointment')) : null;
        return view('doctor.consult', compact('patient', 'appointment'));
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'appointment_id' => 'nullable|exists:appointments,id',
            'complaint' => 'required|string|max:2000',
            'findings' => 'nullable|string|max:3000',
            'diagnosis' => 'required|string|max:255',
            'notes' => 'nullable|string|max:3000',
            'fitzpatrick_type' => 'required|integer|min:1|max:6',
            'sun_exposure' => 'nullable|in:low,moderate,high',
        ]);
        // Skin type & history is always recorded with the consultation (<<include>>)
        $patient->update([
            'fitzpatrick_type' => $data['fitzpatrick_type'],
            'sun_exposure' => $data['sun_exposure'] ?? $patient->sun_exposure,
            'family_history_skin_cancer' => $request->boolean('family_history_skin_cancer'),
            'personal_history_skin_cancer' => $request->boolean('personal_history_skin_cancer'),
        ]);
        $c = Consultation::create([
            'patient_id' => $patient->id, 'doctor_id' => $this->doctor()->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'complaint' => $data['complaint'], 'findings' => $data['findings'] ?? null,
            'diagnosis' => $data['diagnosis'], 'notes' => $data['notes'] ?? null,
        ]);
        if ($c->appointment) {
            $c->appointment->update(['status' => 'completed']);
            Access::notifyRole('receptionist', 'Consultation finished', "{$patient->name} is ready for billing.",
                route('reception.invoices.create', ['patient' => $patient->id, 'appointment' => $c->appointment_id]));
        }
        AuditLog::record('consultation_recorded', "{$patient->code} #{$c->id}");
        $next = $request->input('next') === 'prescribe' ? route('doctor.rx.create', [$patient, 'consultation' => $c->id])
            : route('doctor.patients.show', $patient);
        return redirect($next)->with('success', 'Consultation saved.');
    }
}
