@extends('layouts.app')
@section('title', $patient->name)
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><div><h3 class="page-title mb-0">{{ $patient->name }}</h3>
    <div class="text-muted">{{ $patient->code }} · {{ $patient->phone }} · {{ $patient->email }}</div></div>
    <div class="d-flex gap-2"><a href="{{ route('reception.appointments.create', ['patient' => $patient->id]) }}" class="btn btn-primary">Book appointment</a>
        <a href="{{ route('reception.invoices.create', ['patient' => $patient->id]) }}" class="btn btn-outline-primary">New invoice</a></div></div>
<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-header">Appointments</div><ul class="list-group list-group-flush">
        @forelse($patient->appointments->sortByDesc('date') as $a)<li class="list-group-item d-flex justify-content-between"><span>{{ $a->date->format('d M Y') }} {{ substr($a->time, 0, 5) }} · {{ $a->doctor->name }}</span>@include('partials.status', ['s' => $a->status])</li>
        @empty <li class="list-group-item text-muted">None.</li> @endforelse</ul></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Invoices</div><ul class="list-group list-group-flush">
        @forelse($patient->invoices->sortByDesc('created_at') as $i)<a href="{{ route('reception.invoices.show', $i) }}" class="list-group-item list-group-item-action d-flex justify-content-between"><span>{{ $i->number }} · {{ number_format($i->total, 2) }}</span>@include('partials.status', ['s' => $i->status])</a>
        @empty <li class="list-group-item text-muted">None.</li> @endforelse</ul></div></div>
</div>
@endsection
