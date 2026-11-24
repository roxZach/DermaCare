<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\BiopsyOrder;
use Illuminate\Http\Request;

/**
 * View Biopsy / Test Orders, Update Sample Status,
 * Upload Pathology Report (<<include>> Notify Dermatologist, <<extend>> Attach Histopathology Images),
 * View Report History.
 */
class LabController extends Controller
{
    public function dashboard()
    {
        $orders = BiopsyOrder::with('patient', 'doctor.user')->whereIn('status', BiopsyOrder::LAB_STATUSES)->oldest()->get();
        return view('lab.dashboard', compact('orders'));
    }

    public function history()
    {
        $orders = BiopsyOrder::with('patient', 'doctor.user')->whereIn('status', ['completed', 'reviewed'])
            ->latest('completed_at')->paginate(20);
        return view('lab.history', compact('orders'));
    }

    public function show(BiopsyOrder $order)
    {
        $order->load('patient', 'doctor.user', 'lesion');
        return view('lab.order', compact('order'));
    }

    public function status(Request $request, BiopsyOrder $order)
    {
        abort_unless(in_array($order->status, BiopsyOrder::LAB_STATUSES), 403);
        $status = $request->validate(['status' => 'required|in:' . implode(',', BiopsyOrder::LAB_STATUSES)])['status'];
        $order->update(['status' => $status, 'lab_user_id' => auth()->id()]);
        AuditLog::record('sample_status_updated', "order #{$order->id}: {$status}");
        return back()->with('success', 'Sample status updated.');
    }

    public function report(Request $request, BiopsyOrder $order)
    {
        abort_unless(in_array($order->status, BiopsyOrder::LAB_STATUSES), 403);
        $data = $request->validate([
            'findings' => 'required|string|max:5000',
            'report' => 'required|file|mimes:pdf|max:10240',
            'histo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:10240',
        ]);
        $update = [
            'findings' => $data['findings'], 'status' => 'completed', 'completed_at' => now(), 'lab_user_id' => auth()->id(),
            'report_path' => $request->file('report')->store("pathology/{$order->patient_id}", 'local'),
        ];
        if ($request->hasFile('histo_image')) {
            $update['histo_image_path'] = $request->file('histo_image')->store("pathology/{$order->patient_id}", 'local');
        }
        $order->update($update);
        AuditLog::record('pathology_report_uploaded', "order #{$order->id}");
        // <<include>> Notify Dermatologist
        Alert::send($order->doctor->user_id, 'Pathology report ready', "{$order->test_type} for {$order->patient->name}",
            route('doctor.biopsy.show', $order));
        return redirect()->route('lab.dashboard')->with('success', 'Report uploaded. The dermatologist has been notified.');
    }
}
