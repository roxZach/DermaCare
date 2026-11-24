@extends('layouts.app')
@section('title', 'Schedule')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><h3 class="page-title mb-0">Appointment schedule</h3>
    <form class="d-flex gap-2"><input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
        <a href="?date={{ \Carbon\Carbon::parse($date)->subDay()->toDateString() }}" class="btn btn-outline-secondary">&larr;</a>
        <a href="?date={{ \Carbon\Carbon::parse($date)->addDay()->toDateString() }}" class="btn btn-outline-secondary">&rarr;</a></form></div>
<p class="text-muted">{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }} · {{ $week }} booked appointment(s) in the next 7 days</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
    <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($appointments as $a)
        <tr><td>{{ substr($a->time, 0, 5) }}</td><td>{{ $a->patient->name }}</td><td class="small">{{ $a->reason }}</td>
            <td>@include('partials.status', ['s' => $a->status])</td>
            <td class="text-end"><a href="{{ route('doctor.patients.show', $a->patient) }}" class="btn btn-sm btn-outline-primary">EMR</a></td></tr>
    @empty <tr><td colspan="5" class="text-muted">No appointments on this day.</td></tr> @endforelse
</tbody></table></div></div>
@endsection
