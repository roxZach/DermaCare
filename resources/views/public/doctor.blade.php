@extends('layouts.app')
@section('title', $doctor->name)
@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <h3 class="page-title mb-0">{{ $doctor->name }}</h3>
            <div class="text-muted mb-2">{{ $doctor->specialty }} · {{ $doctor->qualifications }}</div>
            <p>{{ $doctor->bio }}</p>
            <dl class="row small mb-0">
                <dt class="col-sm-4">Experience</dt><dd class="col-sm-8">{{ $doctor->experience_years }} years</dd>
                <dt class="col-sm-4">Consultation fee</dt><dd class="col-sm-8">{{ number_format($doctor->consultation_fee, 2) }}</dd>
                <dt class="col-sm-4">Rating</dt><dd class="col-sm-8">{{ $doctor->average_rating ? $doctor->average_rating . ' / 5' : 'No ratings yet' }}</dd>
            </dl>
        </div></div>
        <div class="card mt-3"><div class="card-header">Patient reviews</div><div class="card-body">
            @forelse($doctor->reviews->sortByDesc('created_at')->take(5) as $r)
                <div class="mb-2"><span class="text-warning">{!! str_repeat('<i class="bi bi-star-fill"></i>', $r->rating) !!}</span>
                    <span class="small text-muted">{{ $r->created_at->format('d M Y') }}</span><div class="small">{{ $r->comment }}</div></div>
            @empty <span class="text-muted small">No reviews yet.</span> @endforelse
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-header">Weekly schedule</div><ul class="list-group list-group-flush">
            @forelse($doctor->schedules as $s)
                <li class="list-group-item d-flex justify-content-between"><span>{{ $s->day_name }}</span>
                    <span>{{ substr($s->start_time, 0, 5) }} – {{ substr($s->end_time, 0, 5) }}</span></li>
            @empty <li class="list-group-item text-muted">No schedule set.</li> @endforelse
        </ul></div>
        <div class="card mt-3"><div class="card-header">Check availability</div><div class="card-body">
            <input type="date" id="avail-date" class="form-control mb-2" min="{{ today()->toDateString() }}" data-url="{{ route('doctors.slots', $doctor) }}">
            <div id="avail-out" class="small text-muted">Pick a date to see free time slots.</div>
            <div class="mt-3">
                @auth @if(auth()->user()->hasRole('patient'))
                    <a class="btn btn-teal w-100" href="{{ route('patient.appointments.create', ['doctor' => $doctor->id]) }}">Book with {{ $doctor->name }}</a>
                @endif @else
                    <a class="btn btn-teal w-100" href="{{ route('register') }}">Register to book an appointment</a>
                @endauth
            </div>
        </div></div>
    </div>
</div>
@endsection
