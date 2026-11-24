@extends('layouts.app')
@section('title', $appointment ? 'Reschedule' : 'Book appointment')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<h3 class="page-title">{{ $appointment ? 'Reschedule appointment' : 'Book appointment' }}</h3>
<form method="POST" action="{{ $appointment ? route('reception.appointments.update', $appointment) : route('reception.appointments.store') }}" class="card" data-slots-url="{{ url('/doctors/__ID__/slots') }}"><div class="card-body row g-3">@csrf
    @if($appointment) @method('PUT') @endif
    <div class="col-12"><label class="form-label">Patient</label>
        @if($patient)
            <input type="hidden" name="patient_id" value="{{ $patient->id }}"><div class="form-control bg-light">{{ $patient->name }} ({{ $patient->code }}, {{ $patient->phone }})</div>
        @else
            <input name="patient_id" class="form-control" placeholder="Patient number, e.g. 12 for P-00012" required value="{{ old('patient_id') }}">
            <div class="form-text">Find the patient first under <a href="{{ route('reception.patients') }}">Patients</a>, or <a href="{{ route('reception.patients.create') }}">register a walk-in patient</a>.</div>
        @endif</div>
    <div class="col-12"><label class="form-label">Dermatologist</label><select name="doctor_id" id="doctor_id" class="form-select" required><option value="">Choose...</option>
        @foreach($doctors as $d)<option value="{{ $d->id }}" @selected(old('doctor_id', $appointment?->doctor_id) == $d->id)>{{ $d->name }} — {{ $d->specialty }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Date</label><input type="date" name="date" id="date" class="form-control" min="{{ today()->toDateString() }}" value="{{ old('date', $appointment?->date->toDateString() ?? today()->toDateString()) }}" required></div>
    <div class="col-md-6"><label class="form-label">Free slots</label><select name="time" id="time" class="form-select" required data-current="{{ old('time', $appointment ? substr($appointment->time, 0, 5) : '') }}"></select></div>
    <div class="col-12"><label class="form-label">Reason</label><input name="reason" class="form-control" value="{{ old('reason', $appointment?->reason) }}"></div>
    <div class="col-12"><button class="btn btn-primary">{{ $appointment ? 'Save' : 'Book' }}</button></div>
</div></form>
</div></div>
@endsection
