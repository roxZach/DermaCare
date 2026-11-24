@extends('layouts.app')
@section('title', 'Front desk')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2"><h3 class="page-title mb-0">Front desk · {{ today()->format('D, d M Y') }}</h3>
    <div class="d-flex gap-2"><a href="{{ route('reception.patients.create') }}" class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Walk-in patient</a>
        <a href="{{ route('reception.appointments.create') }}" class="btn btn-primary"><i class="bi bi-calendar-plus"></i> Book appointment</a></div></div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Appointments today</div><div class="num">{{ $today->count() }}</div></div></div></div>
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Waiting now</div><div class="num">{{ $waiting->count() }}</div></div></div></div>
    <div class="col-md-3"><a href="{{ route('reception.invoices.index', ['status' => 'unpaid']) }}" class="card stat text-decoration-none"><div class="card-body"><div class="small text-muted">Unpaid invoices</div><div class="num">{{ $unpaid }}</div></div></a></div>
    <div class="col-md-3"><div class="card stat"><div class="card-body"><div class="small text-muted">Tomorrow</div><div class="num">{{ $tomorrow }}</div>
        <form method="POST" action="{{ route('reception.reminders') }}">@csrf<button class="btn btn-sm btn-link p-0">Send reminders</button></form></div></div></div>
</div>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-header">Waiting queue</div><ul class="list-group list-group-flush">
        @forelse($waiting as $a)<li class="list-group-item d-flex justify-content-between"><span><strong>#{{ $a->queue_no }}</strong> {{ $a->patient->name }}</span><span class="small text-muted">{{ $a->doctor->name }}</span></li>
        @empty <li class="list-group-item text-muted">Nobody is waiting.</li> @endforelse
    </ul></div></div>
    <div class="col-lg-8">@include('reception._appointments_table', ['appointments' => $today])</div>
</div>
@endsection
