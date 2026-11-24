@extends('layouts.app')
@section('title', 'Doctor dashboard')
@section('content')
<h3 class="page-title">Good day, {{ $doctor->name }}</h3>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="text-muted small">Today's patients</div><div class="num">{{ $today->count() }}</div></div></div></div>
    <div class="col-md-3"><a href="{{ route('doctor.analyses.pending') }}" class="card stat text-decoration-none"><div class="card-body"><div class="text-muted small">AI results to review</div><div class="num">{{ $pendingAi }}</div></div></a></div>
    <div class="col-md-3"><a href="{{ route('doctor.tele.index') }}" class="card stat text-decoration-none"><div class="card-body"><div class="text-muted small">Teledermatology requests</div><div class="num">{{ $pendingTele }}</div></div></a></div>
    <div class="col-md-3"><a href="{{ route('doctor.biopsy.index') }}" class="card stat text-decoration-none"><div class="card-body"><div class="text-muted small">Pathology reports to review</div><div class="num">{{ $biopsiesToReview }}</div></div></a></div>
</div>
<div class="card"><div class="card-header">Today's appointments ({{ today()->format('D, d M Y') }})</div><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Time</th><th>Queue</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($today as $a)
        <tr><td>{{ substr($a->time, 0, 5) }}</td><td>{{ $a->queue_no ? '#' . $a->queue_no : '-' }}</td><td>{{ $a->patient->name }} <span class="small text-muted">{{ $a->patient->code }}</span></td>
            <td class="small">{{ $a->reason }}</td><td>@include('partials.status', ['s' => $a->status])</td>
            <td class="text-end text-nowrap"><a href="{{ route('doctor.patients.show', $a->patient) }}" class="btn btn-sm btn-outline-primary">EMR</a>
                @if(in_array($a->status, ['booked', 'checked_in']))
                    <a href="{{ route('doctor.consult.create', [$a->patient, 'appointment' => $a->id]) }}" class="btn btn-sm btn-primary">Start consultation</a>
                @endif</td></tr>
    @empty <tr><td colspan="6" class="text-muted">No appointments today.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
