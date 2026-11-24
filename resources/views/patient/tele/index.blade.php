@extends('layouts.app')
@section('title', 'Teledermatology')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h3 class="page-title mb-0">Teledermatology</h3>
    <a href="{{ route('patient.tele.create') }}" class="btn btn-primary"><i class="bi bi-camera"></i> New request</a></div>
<p class="text-muted">Send photos of a skin problem to a dermatologist without visiting the clinic.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Date</th><th>Subject</th><th>Dermatologist</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($requests as $t)
        <tr><td>{{ $t->created_at->format('d M Y') }}</td><td>{{ $t->subject }}</td><td>{{ $t->doctor?->name ?? 'Any available' }}</td>
            <td>@include('partials.status', ['s' => $t->status])</td>
            <td><a href="{{ route('patient.tele.show', $t) }}" class="btn btn-sm btn-outline-primary">Open</a></td></tr>
    @empty <tr><td colspan="5" class="text-muted">No requests yet.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
