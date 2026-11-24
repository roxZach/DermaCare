<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BiopsyOrder;
use App\Models\LesionAnalysis;

class PatientController extends Controller
{
    private function me()
    {
        return auth()->user()->patient ?? abort(403, 'No patient record is linked to this account.');
    }

    public function dashboard()
    {
        $p = $this->me();
        return view('patient.dashboard', [
            'patient' => $p,
            'upcoming' => $p->appointments()->with('doctor.user')->where('status', 'booked')
                ->whereDate('date', '>=', today())->orderBy('date')->orderBy('time')->take(5)->get(),
            'reports' => $this->verifiedAnalyses($p)->take(3)->get(),
            'unpaid' => $p->invoices()->where('status', 'unpaid')->get(),
            'tele' => $p->teleRequests()->latest()->take(3)->get(),
        ]);
    }

    // Use case: Manage Profile
    public function profile()
    {
        return view('patient.profile', ['patient' => $this->me()]);
    }

    public function updateProfile()
    {
        $p = $this->me();
        $data = request()->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:255',
            'allergies' => 'nullable|string|max:1000',
            'medical_history' => 'nullable|string|max:2000',
            'sun_exposure' => 'nullable|in:low,moderate,high',
            'family_history_skin_cancer' => 'nullable|boolean',
            'personal_history_skin_cancer' => 'nullable|boolean',
        ]);
        $data['family_history_skin_cancer'] = request()->boolean('family_history_skin_cancer');
        $data['personal_history_skin_cancer'] = request()->boolean('personal_history_skin_cancer');
        $p->update($data);
        auth()->user()->update(['name' => $data['name'], 'phone' => $data['phone']]);
        AuditLog::record('patient_profile_updated', $p->code);
        return back()->with('success', 'Profile updated.');
    }

    // Use case: Track My Lesions (Body Map)
    public function lesions()
    {
        $p = $this->me();
        $lesions = $p->lesions()->with(['images.analyses' => fn ($q) => $q->whereIn('status', ['confirmed', 'overridden'])])->get();
        return view('patient.lesions', compact('lesions', 'p'));
    }

    // Use case: View Prescriptions & History
    public function history()
    {
        $p = $this->me();
        return view('patient.history', [
            'consultations' => $p->consultations()->with('doctor.user')->latest()->get(),
            'prescriptions' => $p->prescriptions()->with('items', 'doctor.user')->latest()->get(),
            'biopsies' => BiopsyOrder::with('doctor.user')->where('patient_id', $p->id)->latest()->get(),
        ]);
    }

    // Use case: View Verified Analysis Report (only dermatologist-reviewed results)
    public function reports()
    {
        return view('patient.reports', ['analyses' => $this->verifiedAnalyses($this->me())->get()]);
    }

    private function verifiedAnalyses($p)
    {
        return LesionAnalysis::with('image.lesion', 'reviewer')
            ->whereIn('status', ['confirmed', 'overridden'])
            ->whereHas('image.lesion', fn ($q) => $q->where('patient_id', $p->id))
            ->latest('reviewed_at');
    }
}
