@extends('layouts.app')
@section('title', 'Lab orders')
@section('content')
<h3 class="page-title">Pending biopsy / test orders</h3>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Ordered</th><th>Patient</th><th>Test</th><th>Site</th><th>Doctor</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($orders as $o)
        <tr><td>{{ $o->created_at->format('d M Y H:i') }}</td><td>{{ $o->patient->name }} <span class="small text-muted">{{ $o->patient->code }}</span></td><td>{{ $o->test_type }}</td><td class="small">{{ $o->site }}</td>
            <td>{{ $o->doctor->name }}</td><td>@include('partials.status', ['s' => $o->status])</td><td><a href="{{ route('lab.orders.show', $o) }}" class="btn btn-sm btn-primary">Open</a></td></tr>
    @empty <tr><td colspan="7" class="text-muted">No pending orders.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
