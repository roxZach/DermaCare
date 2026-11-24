<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Http\Request;

/** Generate Invoice (<<extend>> Apply Discount / Package), Record Payment (<<extend>> Print Receipt). */
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $invoices = Invoice::with('patient')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(20);
        return view('reception.invoices', compact('invoices', 'status'));
    }

    public function create(Request $request)
    {
        $appointment = $request->query('appointment') ? Appointment::with('doctor')->find($request->query('appointment')) : null;
        return view('reception.invoice-form', [
            'patient' => $request->query('patient') ? Patient::find($request->query('patient')) : null,
            'appointment' => $appointment,
            'services' => Service::where('is_active', true)->orderBy('category')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:150',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.unit_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:150',
        ]);
        $subtotal = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
        $discount = min((float) ($data['discount'] ?? 0), $subtotal);
        $invoice = Invoice::create([
            'number' => Invoice::nextNumber(), 'patient_id' => $data['patient_id'],
            'appointment_id' => $data['appointment_id'] ?? null, 'subtotal' => $subtotal, 'discount' => $discount,
            'discount_reason' => $discount > 0 ? ($data['discount_reason'] ?? 'Discount') : null,
            'total' => $subtotal - $discount, 'status' => $subtotal - $discount <= 0 ? 'paid' : 'unpaid',
            'created_by' => auth()->id(),
        ]);
        foreach ($data['items'] as $i) {
            $invoice->items()->create($i + ['amount' => $i['quantity'] * $i['unit_price']]);
        }
        AuditLog::record('invoice_created', $invoice->number);
        Alert::send($invoice->patient->user_id, 'New bill', "{$invoice->number}: " . number_format($invoice->total, 2),
            route('patient.bills.index'));
        return redirect()->route('reception.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'payments', 'patient');
        return view('reception.invoice', compact('invoice'));
    }

    public function pay(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Already paid.');
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,mobile',
            'reference' => 'nullable|string|max:100',
        ]);
        $balance = $invoice->total - $invoice->paid_amount;
        $data['amount'] = min($data['amount'], $balance);
        Payment::create($data + ['invoice_id' => $invoice->id, 'received_by' => auth()->id()]);
        if ($invoice->fresh()->paid_amount >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        }
        AuditLog::record('payment_recorded', "{$invoice->number} {$data['amount']}");
        $msg = 'Payment recorded.';
        return redirect()->route('reception.invoices.show', $invoice)->with('success', $msg)
            ->with('print_url', $request->boolean('print') ? route('print.invoice', $invoice) : null);
    }
}
