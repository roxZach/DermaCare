@extends('layouts.app')
@section('title', 'Appointments')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><h3 class="page-title mb-0">Appointments</h3>
    <a href="{{ route('reception.appointments.create') }}" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Book appointment</a></div>
<form class="row g-2 mb-3">
    <div class="col-auto"><input type="date" name="date" value="{{ $date }}" class="form-control"></div>
    <div class="col-auto"><select name="doctor" class="form-select"><option value="">All doctors</option>@foreach($doctors as $d)<option value="{{ $d->id }}" @selected($doctorId == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-auto"><button class="btn btn-outline-primary">Show</button></div>
</form>
@include('reception._appointments_table')
@endsection
