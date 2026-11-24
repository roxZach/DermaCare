@extends('layouts.app')
@section('title', 'My bills')
@section('content')
<h3 class="page-title">My bills</h3>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Invoice</th><th>Date</th><th>Items</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($invoices as $i)
        <tr><td>{{ $i->number }}</td><td>{{ $i->created_at->format('d M Y') }}</td><td class="small">{{ $i->items->pluck('description')->join(', ') }}</td>
            <td class="text-end">{{ number_format($i->total, 2) }}</td><td>@include('partials.status', ['s' => $i->status])</td>
            <td class="text-end text-nowrap">
                @if($i->status === 'unpaid')<a href="{{ route('patient.bills.pay', $i) }}" class="btn btn-sm btn-teal">Pay online</a>@endif
                <a href="{{ route('print.invoice', $i) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No bills.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
