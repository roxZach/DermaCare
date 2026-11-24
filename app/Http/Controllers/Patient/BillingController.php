<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Pay Bills Online. The project uses a simulated payment gateway (sandbox).
 * To use a real gateway (e.g. SSLCommerz or Stripe), replace gateway() and confirm()
 * with the provider's checkout and callback.
 */
class BillingController extends Controller
{
    private function own(Invoice $invoice): void
    {
        abort_unless($invoice->patient_id === auth()->user()->patient?->id, 403);
    }

    public function index()
    {
        $invoices = Invoice::with('items')->where('patient_id', auth()->user()->patient?->id)->latest()->get();
        return view('patient.bills', compact('invoices'));
    }

    public function gateway(Invoice $invoice)
    {
        $this->own($invoice);
        abort_if($invoice->status === 'paid', 403, 'This bill is already paid.');
        return view('patient.gateway', compact('invoice'));
    }

    public function confirm(Invoice $invoice)
    {
        $this->own($invoice);
        abort_if($invoice->status === 'paid', 403);
        request()->validate(['card_name' => 'required|string|max:100', 'card_number' => 'required|digits_between:12,19']);
        $balance = $invoice->total - $invoice->paid_amount;
        Payment::create([
            'invoice_id' => $invoice->id, 'amount' => $balance, 'method' => 'online',
            'reference' => 'SBX-' . strtoupper(Str::random(10)),
        ]);
        $invoice->update(['status' => 'paid']);
        AuditLog::record('online_payment', $invoice->number);
        \App\Models\Alert::send(auth()->id(), 'Payment received', "{$invoice->number} has been paid.", route('print.invoice', $invoice));
        return redirect()->route('patient.bills.index')->with('success', 'Payment successful. Thank you!');
    }
}
