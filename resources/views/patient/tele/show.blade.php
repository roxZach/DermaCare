@extends('layouts.app')
@section('title', $tele->subject)
@section('content')
<a href="{{ route('patient.tele.index') }}" class="small">&larr; All requests</a>
<h3 class="page-title mt-2">{{ $tele->subject }} @include('partials.status', ['s' => $tele->status])</h3>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <p style="white-space:pre-line">{{ $tele->description }}</p>
            <div class="small text-muted">Duration: {{ $tele->duration ?? '-' }} · Area: {{ $tele->lesion?->body_region }} · Sent {{ $tele->created_at->format('d M Y H:i') }}</div>
        </div></div>
        <div class="card"><div class="card-header">Dermatologist's reply</div><div class="card-body">
            @if($tele->status === 'reviewed')
                <p style="white-space:pre-line">{{ $tele->doctor_reply }}</p>
                <div class="small text-muted">{{ $tele->doctor?->name }}, {{ $tele->replied_at?->format('d M Y H:i') }}</div>
                @if($tele->visit_recommended)
                    <div class="alert alert-warning mt-3 mb-0">The dermatologist recommends an in-person visit.
                        <a href="{{ route('patient.appointments.create', ['doctor' => $tele->doctor_id]) }}" class="alert-link">Book an appointment</a></div>
                @endif
            @else
                <span class="text-muted">Waiting for a dermatologist to review your request. You will get a notification.</span>
            @endif
        </div></div>
    </div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Your photos</div><div class="card-body"><div class="row g-2">
        @foreach($tele->lesion?->images ?? [] as $img)
            <div class="col-6"><img src="{{ route('files.lesion', $img) }}" class="lesion-thumb" alt="Photo">
                <div class="small text-muted">{{ $img->quality_message }}</div></div>
        @endforeach
    </div></div></div></div>
</div>
@endsection
