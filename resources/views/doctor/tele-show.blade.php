@extends('layouts.app')
@section('title', $tele->subject)
@section('content')
<a href="{{ route('doctor.tele.index') }}" class="small">&larr; Teledermatology requests</a>
<h3 class="page-title mt-2">{{ $tele->subject }} @include('partials.status', ['s' => $tele->status])</h3>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card mb-3"><div class="card-body">
            <div class="mb-2"><strong>{{ $tele->patient->name }}</strong> · <a href="{{ route('doctor.patients.show', $tele->patient) }}">open EMR</a></div>
            <p style="white-space:pre-line">{{ $tele->description }}</p>
            <div class="small text-muted">Duration: {{ $tele->duration ?? '-' }} · Area: {{ $tele->lesion?->body_region }}</div>
        </div></div>
        <div class="card"><div class="card-header">Reply</div><div class="card-body">
            @if($tele->status === 'reviewed')
                <p style="white-space:pre-line">{{ $tele->doctor_reply }}</p>
                <span class="small text-muted">{{ $tele->visit_recommended ? 'In-person visit recommended' : '' }}</span>
            @else
                <form method="POST" action="{{ route('doctor.tele.reply', $tele) }}">@csrf
                    <textarea name="doctor_reply" rows="4" class="form-control mb-2" required placeholder="Your advice to the patient"></textarea>
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="visit_recommended" value="1" id="v"><label class="form-check-label" for="v">Recommend an in-person visit</label></div>
                    <button class="btn btn-primary">Send reply</button></form>
            @endif
        </div></div>
    </div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Photos &amp; AI analysis</div><div class="card-body">
        @foreach($tele->lesion?->images ?? [] as $img)
            <div class="row g-2 mb-3"><div class="col-5"><img src="{{ route('files.lesion', $img) }}" class="lesion-thumb" alt=""></div>
                <div class="col-7 small">{{ $img->quality_message }}
                    @forelse($img->analyses as $a)
                        <div class="mt-1">AI: <strong>{{ \App\Models\LesionAnalysis::className($a->predicted_class) }}</strong> ({{ round($a->confidence * 100) }}%) · risk {{ $a->risk_level }}
                            · @include('partials.status', ['s' => $a->status])<br><a href="{{ route('doctor.analyses.show', $a) }}" class="btn btn-sm btn-outline-primary mt-1">Review AI result</a></div>
                    @empty
                        <form method="POST" action="{{ route('doctor.images.analyze', $img) }}" class="mt-1">@csrf<button class="btn btn-sm btn-teal">Run AI analysis</button></form>
                    @endforelse
                </div></div>
        @endforeach
        @if($tele->lesion)<a href="{{ route('doctor.lesions.show', $tele->lesion) }}" class="small">Open lesion record</a>@endif
    </div></div></div>
</div>
@endsection
