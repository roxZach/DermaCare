<?php

namespace App\Http\Controllers\Doctor;

use App\Models\AuditLog;
use App\Models\BiopsyOrder;
use App\Models\Patient;
use App\Support\Access;
use Illuminate\Http\Request;

/** Use cases: Order Biopsy, Review Pathology Report. The order stays open until it is reviewed. */
class BiopsyController extends DoctorController
{
    public function index()
    {
        $orders = BiopsyOrder::with('patient', 'lesion')->where('doctor_id', $this->doctor()->id)
            ->orderByRaw("CASE status WHEN 'completed' THEN 0 WHEN 'reviewed' THEN 2 ELSE 1 END")->latest()->paginate(20);
        return view('doctor.biopsies', compact('orders'));
    }

    public function create(Request $request, Patient $patient)
    {
        return view('doctor.biopsy-form', ['patient' => $patient->load('lesions'), 'lesionId' => $request->query('lesion')]);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'lesion_id' => 'nullable|exists:lesions,id',
            'test_type' => 'required|string|max:100',
            'site' => 'required|string|max:150',
            'clinical_note' => 'nullable|string|max:2000',
        ]);
        $order = BiopsyOrder::create($data + ['patient_id' => $patient->id, 'doctor_id' => $this->doctor()->id]);
        AuditLog::record('biopsy_ordered', "{$patient->code} order #{$order->id}");
        Access::notifyRole('lab', 'New biopsy order', "{$data['test_type']} for {$patient->name}", route('lab.orders.show', $order));
        return redirect()->route('doctor.patients.show', $patient)->with('success', 'Biopsy ordered. The lab has been notified.');
    }

    public function show(BiopsyOrder $order)
    {
        $order->load('patient', 'lesion', 'labUser');
        return view('doctor.biopsy', compact('order'));
    }

    public function review(Request $request, BiopsyOrder $order)
    {
        abort_unless($order->status === 'completed', 403, 'The pathology report has not been uploaded yet.');
        $data = $request->validate(['doctor_comment' => 'required|string|max:2000']);
        $order->update($data + ['status' => 'reviewed', 'reviewed_at' => now()]);
        AuditLog::record('pathology_reviewed', "order #{$order->id}");
        \App\Models\Alert::send($order->patient->user_id, 'Your biopsy result is available',
            'Your dermatologist has reviewed the pathology report.', route('patient.history'));
        return redirect()->route('doctor.biopsy.index')->with('success', 'Pathology report reviewed and shared with the patient.');
    }
}
