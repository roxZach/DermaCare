<?php

namespace App\Http\Controllers\Doctor;

use App\Models\AuditLog;
use App\Models\TeleRequest;
use Illuminate\Http\Request;

/** Use case: Review Teledermatology Requests. */
class TeleController extends DoctorController
{
    public function index()
    {
        $d = $this->doctor();
        $requests = TeleRequest::with('patient', 'doctor.user')
            ->where(fn ($q) => $q->whereNull('doctor_id')->orWhere('doctor_id', $d->id))
            ->orderByRaw("CASE status WHEN 'submitted' THEN 0 ELSE 1 END")->latest()->paginate(20);
        return view('doctor.tele-index', compact('requests'));
    }

    public function show(TeleRequest $tele)
    {
        $tele->load('patient', 'lesion.images.analyses');
        return view('doctor.tele-show', compact('tele'));
    }

    public function reply(Request $request, TeleRequest $tele)
    {
        $data = $request->validate(['doctor_reply' => 'required|string|max:3000']);
        $tele->update($data + [
            'doctor_id' => $this->doctor()->id, 'status' => 'reviewed', 'replied_at' => now(),
            'visit_recommended' => $request->boolean('visit_recommended'),
        ]);
        AuditLog::record('tele_replied', "#{$tele->id}");
        \App\Models\Alert::send($tele->patient->user_id, 'Your dermatologist replied', $tele->subject, route('patient.tele.show', $tele));
        return redirect()->route('doctor.tele.index')->with('success', 'Reply sent to the patient.');
    }
}
