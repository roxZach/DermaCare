@extends('layouts.app')
@section('title', 'My dashboard')
@section('content')
<h3 class="page-title">Hello, {{ $patient->name }}</h3>
<p class="text-muted">Patient ID {{ $patient->code }}</p>
<div class="row g-3 mb-4">
    <div class="col-md-3"><a href="{{ route('patient.appointments.create') }}" class="card text-decoration-none h-100"><div class="card-body text-center">
        <i class="bi bi-calendar-plus fs-2 text-primary"></i><div class="fw-semibold mt-1">Book appointment</div></div></a></div>
    <div class="col-md-3"><a href="{{ route('patient.tele.create') }}" class="card text-decoration-none h-100"><div class="card-body text-center">
        <i class="bi bi-camera fs-2 text-primary"></i><div class="fw-semibold mt-1">Ask a dermatologist online</div></div></a></div>
    <div class="col-md-3"><a href="{{ route('patient.lesions') }}" class="card text-decoration-none h-100"><div class="card-body text-center">
        <i class="bi bi-person-bounding-box fs-2 text-primary"></i><div class="fw-semibold mt-1">My lesions (body map)</div></div></a></div>
    <div class="col-md-3"><a href="{{ route('patient.bills.index') }}" class="card text-decoration-none h-100"><div class="card-body text-center">
        <i class="bi bi-credit-card fs-2 text-primary"></i><div class="fw-semibold mt-1">Bills
            @if($unpaid->count())<span class="badge text-bg-danger">{{ $unpaid->count() }} unpaid</span>@endif</div></div></a></div>
</div>
<div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Upcoming appointments</div><ul class="list-group list-group-flush">
        @forelse($upcoming as $a)
            <li class="list-group-item d-flex justify-content-between"><span>{{ $a->doctor->name }}<br><span class="small text-muted">{{ $a->reason }}</span></span>
                <span class="text-end">{{ $a->date->format('D, d M') }}<br>{{ substr($a->time, 0, 5) }}</span></li>
        @empty <li class="list-group-item text-muted">No upcoming appointments.</li> @endforelse
    </ul></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Latest verified reports</div><ul class="list-group list-group-flush">
        @forelse($reports as $r)
            <li class="list-group-item d-flex justify-content-between"><span>{{ $r->image->lesion->label }}<br>
                <span class="small text-muted">{{ $r->final_diagnosis }}</span></span>
                <a href="{{ route('print.report', $r) }}" class="btn btn-sm btn-outline-primary align-self-center" target="_blank">Report</a></li>
        @empty <li class="list-group-item text-muted">No reports yet.</li> @endforelse
    </ul></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Teledermatology requests</div><ul class="list-group list-group-flush">
        @forelse($tele as $t)
            <a href="{{ route('patient.tele.show', $t) }}" class="list-group-item list-group-item-action d-flex justify-content-between">
                <span>{{ $t->subject }}</span>@include('partials.status', ['s' => $t->status])</a>
        @empty <li class="list-group-item text-muted">No requests yet.</li> @endforelse
    </ul></div></div>
</div>
@endsection
