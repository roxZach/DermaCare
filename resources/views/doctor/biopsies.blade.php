@extends('layouts.app')
@section('title', 'Biopsy orders')
@section('content')
<h3 class="page-title">My biopsy orders</h3>
<p class="text-muted">An order stays open until you review the pathology report.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Ordered</th><th>Patient</th><th>Test</th><th>Site</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($orders as $o)
        <tr class="{{ $o->status === 'completed' ? 'table-warning' : '' }}"><td>{{ $o->created_at->format('d M Y') }}</td><td>{{ $o->patient->name }}</td><td>{{ $o->test_type }}</td><td class="small">{{ $o->site }}</td>
            <td>@include('partials.status', ['s' => $o->status])</td><td><a href="{{ route('doctor.biopsy.show', $o) }}" class="btn btn-sm btn-outline-primary">{{ $o->status === 'completed' ? 'Review report' : 'Open' }}</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No biopsy orders.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
