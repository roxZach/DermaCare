@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Invoices</h3>
    <a href="{{ route('reception.invoices.create') }}" class="btn btn-primary"><i class="bi bi-receipt"></i> New invoice</a></div>
<div class="btn-group mb-3">@foreach(['' => 'All', 'unpaid' => 'Unpaid', 'paid' => 'Paid'] as $k => $v)<a href="?status={{ $k }}" class="btn btn-sm btn-outline-secondary {{ $status === $k || (!$status && !$k) ? 'active' : '' }}">{{ $v }}</a>@endforeach</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Number</th><th>Date</th><th>Patient</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($invoices as $i)
        <tr><td>{{ $i->number }}</td><td>{{ $i->created_at->format('d M Y') }}</td><td>{{ $i->patient->name }}</td><td class="text-end">{{ number_format($i->total, 2) }}</td>
            <td>@include('partials.status', ['s' => $i->status])</td><td><a href="{{ route('reception.invoices.show', $i) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No invoices.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $invoices->links('pagination::bootstrap-5') }}</div>
@endsection
