<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Doctor;
use Illuminate\Http\Request;

/** Manage Doctors & Schedules. */
class DoctorController extends Controller
{
    public function index()
    {
        return view('admin.doctors.index', ['doctors' => Doctor::with('user', 'schedules')->get()]);
    }

    public function edit(Doctor $doctor)
    {
        return view('admin.doctors.edit', ['doctor' => $doctor->load('user', 'schedules')]);
    }

    public function update(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'specialty' => 'required|string|max:100',
            'qualifications' => 'nullable|string|max:255',
            'experience_years' => 'required|integer|min:0|max:70',
            'consultation_fee' => 'required|numeric|min:0',
            'bio' => 'nullable|string|max:3000',
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.slot_minutes' => 'required|integer|min:5|max:120',
        ]);
        $doctor->update(collect($data)->except('schedules')->all());
        $doctor->schedules()->delete();
        foreach ($data['schedules'] ?? [] as $s) {
            $doctor->schedules()->create($s);
        }
        AuditLog::record('doctor_profile_updated', $doctor->name);
        return redirect()->route('admin.doctors.index')->with('success', 'Doctor profile and schedule saved.');
    }
}
