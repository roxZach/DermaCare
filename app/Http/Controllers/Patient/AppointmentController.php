<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Support\Access;
use Illuminate\Http\Request;

/**
 * Book Appointment (<<include>> Select Doctor & Time Slot), Reschedule / Cancel Appointment, Rate Doctor.
 */
class AppointmentController extends Controller
{
    private function me()
    {
        return auth()->user()->patient ?? abort(403);
    }

    private function own(Appointment $a): void
    {
        abort_unless($a->patient_id === $this->me()->id, 403);
    }

    public function index()
    {
        $appointments = $this->me()->appointments()->with('doctor.user', 'review')
            ->orderByDesc('date')->orderByDesc('time')->paginate(15);
        return view('patient.appointments.index', compact('appointments'));
    }

    public function create(Request $request)
    {
        $doctors = Doctor::with('user')->whereHas('user', fn ($q) => $q->where('is_active', true))->get();
        return view('patient.appointments.form', [
            'doctors' => $doctors, 'appointment' => null, 'selected' => $request->query('doctor'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $doctor = Doctor::findOrFail($data['doctor_id']);
        if (! in_array($data['time'], $doctor->availableSlots($data['date']))) {
            return back()->withInput()->withErrors(['time' => 'That time slot is no longer available. Please choose another.']);
        }
        $a = Appointment::create($data + ['patient_id' => $this->me()->id, 'status' => 'booked', 'booked_by' => auth()->id()]);
        AuditLog::record('appointment_booked', "#{$a->id}");
        \App\Models\Alert::send($doctor->user_id, 'New appointment booked',
            "{$this->me()->name} on {$a->date->format('d M Y')} at {$a->time}", route('doctor.schedule'));
        \App\Models\Alert::send(auth()->id(), 'Appointment confirmed',
            "With {$doctor->name} on {$a->date->format('d M Y')} at " . substr($a->time, 0, 5), route('patient.appointments.index'));
        return redirect()->route('patient.appointments.index')->with('success', 'Your appointment is booked.');
    }

    public function edit(Appointment $appointment)
    {
        $this->own($appointment);
        abort_unless($appointment->isUpcoming(), 403, 'Only upcoming appointments can be rescheduled.');
        return view('patient.appointments.form', [
            'doctors' => Doctor::with('user')->get(), 'appointment' => $appointment, 'selected' => $appointment->doctor_id,
        ]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->own($appointment);
        abort_unless($appointment->isUpcoming(), 403);
        $data = $this->validated($request);
        $doctor = Doctor::findOrFail($data['doctor_id']);
        if (! in_array($data['time'], $doctor->availableSlots($data['date']))) {
            return back()->withInput()->withErrors(['time' => 'That time slot is not available.']);
        }
        $appointment->update($data);
        AuditLog::record('appointment_rescheduled', "#{$appointment->id}");
        return redirect()->route('patient.appointments.index')->with('success', 'Appointment rescheduled.');
    }

    public function cancel(Appointment $appointment)
    {
        $this->own($appointment);
        abort_unless($appointment->isUpcoming(), 403);
        if ($appointment->starts_at->diffInHours(now(), true) < 2) {
            return back()->with('error', 'Appointments can only be cancelled online at least 2 hours in advance. Please call the clinic.');
        }
        $appointment->update(['status' => 'cancelled']);
        AuditLog::record('appointment_cancelled', "#{$appointment->id}");
        Access::notifyRole('receptionist', 'Appointment cancelled by patient', "#{$appointment->id} on {$appointment->date->format('d M')}");
        return back()->with('success', 'Appointment cancelled.');
    }

    // Use case: Rate Doctor / Give Feedback (after a completed visit)
    public function review(Request $request, Appointment $appointment)
    {
        $this->own($appointment);
        abort_unless($appointment->status === 'completed' && ! $appointment->review, 403);
        $data = $request->validate(['rating' => 'required|integer|min:1|max:5', 'comment' => 'nullable|string|max:1000']);
        DoctorReview::create($data + [
            'patient_id' => $appointment->patient_id, 'doctor_id' => $appointment->doctor_id, 'appointment_id' => $appointment->id,
        ]);
        return back()->with('success', 'Thank you for your feedback!');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);
    }
}
