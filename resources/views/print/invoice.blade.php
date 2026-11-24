@extends('print.layout')
@section('title', $invoice->number)
@section('doc-meta')<strong>{{ $invoice->status === 'paid' ? 'RECEIPT' : 'INVOICE' }}</strong><br>{{ $invoice->number }} · {{ $invoice->created_at->format('d M Y') }}@endsection
@section('body')
<p><strong>Patient:</strong> {{ $invoice->patient->name }} ({{ $invoice->patient->code }})</p>
<table class="table"><thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr></thead><tbody>
@foreach($invoice->items as $i)<tr><td>{{ $i->description }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ number_format($i->unit_price, 2) }}</td><td class="text-end">{{ number_format($i->amount, 2) }}</td></tr>@endforeach
<tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td></tr>
@if($invoice->discount > 0)<tr><td colspan="3" class="text-end">Discount ({{ $invoice->discount_reason }})</td><td class="text-end">-{{ number_format($invoice->discount, 2) }}</td></tr>@endif
<tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end">{{ number_format($invoice->total, 2) }}</td></tr>
</tbody></table>
<h6>Payments</h6>
<table class="table table-sm"><tbody>@forelse($invoice->payments as $p)<tr><td>{{ $p->created_at->format('d M Y H:i') }}</td><td>{{ ucfirst($p->method) }}</td><td>{{ $p->reference }}</td><td class="text-end">{{ number_format($p->amount, 2) }}</td></tr>
@empty<tr><td class="text-muted">No payments yet.</td></tr>@endforelse</tbody></table>
<p class="fw-bold">Status: {{ strtoupper($invoice->status) }}</p>
@endsection
