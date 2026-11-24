<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\BiopsyOrder;
use App\Models\LesionAnalysis;
use App\Models\Patient;
use App\Models\TeleRequest;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    protected function doctor()
    {
        return auth()->user()->doctor ?? abort(403, 'No doctor profile is linked to this account.');
    }

    public function dashboard()
    {
        $d = $this->doctor();
        return view('doctor.dashboard', [
            'doctor' => $d,
            'today' => $d->appointments()->with('patient')->whereDate('date', today())
                ->where('status', '!=', 'cancelled')->orderBy('time')->get(),
            'pendingAi' => LesionAnalysis::where('status', 'pending_review')->count(),
            'pendingTele' => TeleRequest::where('status', 'submitted')
                ->where(fn ($q) => $q->whereNull('doctor_id')->orWhere('doctor_id', $d->id))->count(),
            'biopsiesToReview' => BiopsyOrder::where('doctor_id', $d->id)->where('status', 'completed')->count(),
        ]);
    }

    // Use case: View Appointment Schedule
    public function schedule(Request $request)
    {
        $date = $request->query('date', today()->toDateString());
        $appointments = $this->doctor()->appointments()->with('patient')
            ->whereDate('date', $date)->orderBy('time')->get();
        $week = $this->doctor()->appointments()->where('status', 'booked')
            ->whereBetween('date', [today(), today()->addDays(7)])->count();
        return view('doctor.schedule', compact('appointments', 'date', 'week'));
    }

    public function patients(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $patients = Patient::when($q, fn ($query) => $query->where(function ($w) use ($q) {
            $w->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('email', 'like', "%$q%");
            if (preg_match('/^P-?0*(\d+)$/i', $q, $m)) {
                $w->orWhere('id', (int) $m[1]);
            }
        }))->orderBy('name')->paginate(20)->withQueryString();
        return view('doctor.patients', compact('patients', 'q'));
    }

    // Use case: View Patient EMR
    public function patient(Patient $patient)
    {
        $patient->load([
            'lesions.images', 'consultations.doctor.user', 'prescriptions.items', 'prescriptions.doctor.user',
            'biopsyOrders.doctor.user', 'teleRequests', 'appointments.doctor.user',
        ]);
        AuditLog::record('emr_viewed', $patient->code);
        $openAppointment = $patient->appointments->where('doctor_id', $this->doctor()->id)
            ->whereIn('status', ['booked', 'checked_in'])->filter(fn ($a) => $a->date->isToday())->first();
        return view('doctor.patient', compact('patient', 'openAppointment'));
    }

    // Use case: Record Skin Type & History (included in Record Consultation Notes)
    public function updateSkin(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'fitzpatrick_type' => 'nullable|integer|min:1|max:6',
            'sun_exposure' => 'nullable|in:low,moderate,high',
            'allergies' => 'nullable|string|max:1000',
            'medical_history' => 'nullable|string|max:2000',
        ]);
        $data['family_history_skin_cancer'] = $request->boolean('family_history_skin_cancer');
        $data['personal_history_skin_cancer'] = $request->boolean('personal_history_skin_cancer');
        $patient->update($data);
        AuditLog::record('skin_profile_updated', $patient->code);
        return back()->with('success', 'Skin type and history saved.');
    }
}
