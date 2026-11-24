@extends('layouts.app')
@section('title', $appointment ? 'Reschedule appointment' : 'Book appointment')
@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
<h3 class="page-title">{{ $appointment ? 'Reschedule appointment' : 'Book an appointment' }}</h3>
<form method="POST" action="{{ $appointment ? route('patient.appointments.update', $appointment) : route('patient.appointments.store') }}"
      class="card" data-slots-url="{{ url('/doctors/__ID__/slots') }}"><div class="card-body row g-3">@csrf
    @if($appointment) @method('PUT') @endif
    <div class="col-12"><label class="form-label">1. Select a dermatologist</label>
        <select name="doctor_id" id="doctor_id" class="form-select" required><option value="">Choose...</option>
            @foreach($doctors as $d)<option value="{{ $d->id }}" @selected(old('doctor_id', $selected) == $d->id)>{{ $d->name }} — {{ $d->specialty }} ({{ number_format($d->consultation_fee, 2) }})</option>@endforeach
        </select></div>
    <div class="col-md-6"><label class="form-label">2. Date</label>
        <input type="date" name="date" id="date" class="form-control" min="{{ today()->toDateString() }}" value="{{ old('date', $appointment?->date->toDateString()) }}" required></div>
    <div class="col-md-6"><label class="form-label">3. Time slot</label>
        <select name="time" id="time" class="form-select" required data-current="{{ old('time', $appointment ? substr($appointment->time, 0, 5) : '') }}"></select></div>
    <div class="col-12"><label class="form-label">Reason for visit</label>
        <input name="reason" class="form-control" maxlength="255" value="{{ old('reason', $appointment?->reason) }}" placeholder="e.g. itchy rash on arms, mole check"></div>
    <div class="col-12"><button class="btn btn-primary">{{ $appointment ? 'Save new time' : 'Book appointment' }}</button>
        <a href="{{ route('patient.appointments.index') }}" class="btn btn-link">Back</a></div>
</div></form>
</div></div>
@endsection
