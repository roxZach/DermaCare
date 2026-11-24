<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Register Walk-in Patient, Search Patient Record, Manage Appointments (<<include>> Check Doctor Availability),
 * Check-in Patient & Manage Queue, Send Appointment Reminders.
 */
class ReceptionController extends Controller
{
    public function dashboard()
    {
        $today = Appointment::with('patient', 'doctor.user')->whereDate('date', today())
            ->where('status', '!=', 'cancelled')->orderBy('time')->get();
        return view('reception.dashboard', [
            'today' => $today,
            'waiting' => $today->where('status', 'checked_in')->sortBy('queue_no'),
            'unpaid' => \App\Models\Invoice::where('status', 'unpaid')->count(),
            'tomorrow' => Appointment::whereDate('date', today()->addDay())->where('status', 'booked')->count(),
        ]);
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
        return view('reception.patients', compact('patients', 'q'));
    }

    public function createPatient()
    {
        return view('reception.patient-form');
    }

    public function storePatient(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:255',
        ]);
        $p = Patient::create($data);
        AuditLog::record('walkin_patient_registered', $p->code);
        return redirect()->route('reception.appointments.create', ['patient' => $p->id])
            ->with('success', "Patient {$p->code} registered. You can book an appointment now.");
    }

    public function patient(Patient $patient)
    {
        $patient->load(['appointments.doctor.user', 'invoices']);
        return view('reception.patient', compact('patient'));
    }

    public function appointments(Request $request)
    {
        $date = $request->query('date', today()->toDateString());
        $doctorId = $request->query('doctor');
        $appointments = Appointment::with('patient', 'doctor.user')->whereDate('date', $date)
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))->orderBy('time')->get();
        return view('reception.appointments', [
            'appointments' => $appointments, 'date' => $date, 'doctorId' => $doctorId,
            'doctors' => Doctor::with('user')->get(),
        ]);
    }

    public function createAppointment(Request $request)
    {
        return view('reception.appointment-form', [
            'appointment' => null,
            'patient' => $request->query('patient') ? Patient::find($request->query('patient')) : null,
            'doctors' => Doctor::with('user')->get(),
        ]);
    }

    public function storeAppointment(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);
        // <<include>> Check Doctor Availability: never double-book a slot
        if (! in_array($data['time'], Doctor::findOrFail($data['doctor_id'])->availableSlots($data['date']))) {
            return back()->withInput()->withErrors(['time' => 'That slot is not available.']);
        }
        $a = Appointment::create($data + ['status' => 'booked', 'booked_by' => auth()->id()]);
        AuditLog::record('appointment_booked_by_reception', "#{$a->id}");
        Alert::send($a->patient->user_id, 'Appointment booked',
            "With {$a->doctor->name} on {$a->date->format('d M Y')} at {$data['time']}", route('patient.appointments.index'));
        return redirect()->route('reception.appointments', ['date' => $data['date']])->with('success', 'Appointment booked.');
    }

    public function editAppointment(Appointment $appointment)
    {
        abort_unless($appointment->status === 'booked', 403);
        return view('reception.appointment-form', [
            'appointment' => $appointment, 'patient' => $appointment->patient, 'doctors' => Doctor::with('user')->get(),
        ]);
    }

    public function updateAppointment(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->status === 'booked', 403);
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i',
            'reason' => 'nullable|string|max:255',
        ]);
        if (! in_array($data['time'], Doctor::findOrFail($data['doctor_id'])->availableSlots($data['date']))) {
            return back()->withInput()->withErrors(['time' => 'That slot is not available.']);
        }
        $appointment->update($data);
        AuditLog::record('appointment_rescheduled_by_reception', "#{$appointment->id}");
        Alert::send($appointment->patient->user_id, 'Appointment rescheduled',
            "New time: {$appointment->date->format('d M Y')} at {$data['time']}", route('patient.appointments.index'));
        return redirect()->route('reception.appointments', ['date' => $data['date']])->with('success', 'Appointment rescheduled.');
    }

    // Check-in / complete / cancel / no-show handling and the waiting queue
    public function setStatus(Request $request, Appointment $appointment)
    {
        $status = $request->validate(['status' => 'required|in:checked_in,cancelled,booked'])['status'];
        $update = ['status' => $status];
        if ($status === 'checked_in') {
            $update['queue_no'] = (Appointment::whereDate('date', $appointment->date)->max('queue_no') ?? 0) + 1;
            Alert::send($appointment->doctor->user_id, 'Patient checked in',
                "{$appointment->patient->name} (queue #{$update['queue_no']})", route('doctor.dashboard'));
        }
        if ($status === 'booked') {
            $update['queue_no'] = null;
        }
        $appointment->update($update);
        AuditLog::record("appointment_{$status}", "#{$appointment->id}");
        return back()->with('success', 'Appointment updated.');
    }

    // Use case: Send Appointment Reminders (via the Notification Service: email + in-app)
    public function sendReminders()
    {
        $list = Appointment::with('patient', 'doctor.user')->whereDate('date', today()->addDay())
            ->where('status', 'booked')->whereNull('reminder_sent_at')->get();
        foreach ($list as $a) {
            $msg = "Reminder: you have an appointment with {$a->doctor->name} tomorrow at " . substr($a->time, 0, 5) . '.';
            if ($a->patient->email) {
                try {
                    Mail::raw($msg, fn ($m) => $m->to($a->patient->email)->subject('DermaCare AI appointment reminder'));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            Alert::send($a->patient->user_id, 'Appointment reminder', $msg, route('patient.appointments.index'));
            $a->update(['reminder_sent_at' => now()]);
        }
        AuditLog::record('reminders_sent', (string) $list->count());
        return back()->with('success', "Reminders sent for {$list->count()} appointment(s) tomorrow.");
    }
}
