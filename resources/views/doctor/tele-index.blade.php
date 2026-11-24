@extends('layouts.app')
@section('title', 'Teledermatology')
@section('content')
<h3 class="page-title">Teledermatology requests</h3>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Received</th><th>Patient</th><th>Subject</th><th>Assigned to</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($requests as $t)
        <tr><td>{{ $t->created_at->format('d M Y H:i') }}</td><td>{{ $t->patient->name }}</td><td>{{ $t->subject }}</td><td>{{ $t->doctor?->name ?? 'Any' }}</td>
            <td>@include('partials.status', ['s' => $t->status])</td><td><a href="{{ route('doctor.tele.show', $t) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
    @empty <tr><td colspan="6" class="text-muted">No requests.</td></tr> @endforelse
</tbody></table></div></div>
<div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
@endsection
