@extends('layouts.app')
@section('title', $invoice->number)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">{{ $invoice->number }} @include('partials.status', ['s' => $invoice->status])</h3>
    <a href="{{ route('print.invoice', $invoice) }}" target="_blank" class="btn btn-outline-secondary"><i class="bi bi-printer"></i> Print receipt</a></div>
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><div class="card-body">
        <p><strong>{{ $invoice->patient->name }}</strong> ({{ $invoice->patient->code }}) · {{ $invoice->created_at->format('d M Y H:i') }}</p>
        <table class="table"><thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr></thead><tbody>
            @foreach($invoice->items as $i)<tr><td>{{ $i->description }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ number_format($i->unit_price, 2) }}</td><td class="text-end">{{ number_format($i->amount, 2) }}</td></tr>@endforeach
            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td></tr>
            @if($invoice->discount > 0)<tr><td colspan="3" class="text-end">Discount ({{ $invoice->discount_reason }})</td><td class="text-end">-{{ number_format($invoice->discount, 2) }}</td></tr>@endif
            <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end">{{ number_format($invoice->total, 2) }}</td></tr>
            <tr><td colspan="3" class="text-end">Paid</td><td class="text-end">{{ number_format($invoice->paid_amount, 2) }}</td></tr>
        </tbody></table>
    </div></div></div>
    <div class="col-lg-5">
        @if($invoice->status === 'unpaid')
        <div class="card mb-3"><div class="card-header">Record payment</div><div class="card-body">
            <form method="POST" action="{{ route('reception.invoices.pay', $invoice) }}" class="row g-2">@csrf
                <div class="col-6"><input type="number" step="0.01" name="amount" class="form-control" value="{{ number_format($invoice->total - $invoice->paid_amount, 2, '.', '') }}" required></div>
                <div class="col-6"><select name="method" class="form-select"><option value="cash">Cash</option><option value="card">Card</option><option value="mobile">Mobile banking</option></select></div>
                <div class="col-12"><input name="reference" class="form-control" placeholder="Reference / transaction ID (optional)"></div>
                <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="print" value="1" id="pr" checked><label class="form-check-label" for="pr">Print receipt</label></div>
                <div class="col-12"><button class="btn btn-teal w-100">Record payment</button></div>
            </form></div></div>
        @endif
        <div class="card"><div class="card-header">Payments</div><ul class="list-group list-group-flush">
            @forelse($invoice->payments as $p)<li class="list-group-item d-flex justify-content-between"><span>{{ $p->created_at->format('d M Y H:i') }} · {{ ucfirst($p->method) }} <span class="small text-muted">{{ $p->reference }}</span></span><span>{{ number_format($p->amount, 2) }}</span></li>
            @empty <li class="list-group-item text-muted">No payments yet.</li> @endforelse</ul></div>
    </div>
</div>
@endsection
