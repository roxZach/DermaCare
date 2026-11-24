@extends('layouts.app')
@section('title', 'AI lesion analysis')
@section('content')
@php($lesion = $analysis->image->lesion)
<a href="{{ route('doctor.lesions.show', $lesion) }}" class="small">&larr; {{ $lesion->label }} · {{ $lesion->patient->name }}</a>
<h3 class="page-title mt-2">AI lesion analysis #{{ $analysis->id }} @include('partials.status', ['s' => $analysis->status])</h3>
@include('partials.disclaimer')
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <div class="row g-2">
                <div class="{{ $analysis->heatmap_path ? 'col-6' : 'col-12' }}"><div class="small text-muted mb-1">Image</div>
                    <img src="{{ route('files.lesion', $analysis->image) }}" class="lesion-thumb" alt="Lesion"></div>
                @if($analysis->heatmap_path)
                    <div class="col-6"><div class="small text-muted mb-1">Grad-CAM heatmap</div>
                        <img src="{{ route('files.heatmap', $analysis) }}?v={{ $analysis->updated_at->timestamp }}" class="lesion-thumb" alt="Heatmap"></div>
                @endif
            </div>
            @unless($analysis->heatmap_path)
                <form method="POST" action="{{ route('doctor.analyses.heatmap', $analysis) }}" class="mt-2">@csrf
                    <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-fire"></i> Explain: generate Grad-CAM heatmap</button></form>
            @else
                <div class="small text-muted mt-2">Red areas influenced the prediction most. Check that the model focused on the lesion, not on hair, rulers or ink marks.</div>
            @endunless
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card ai-card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between"><div>
                <div class="small text-muted">Top prediction</div>
                <h4 class="mb-0">{{ \App\Models\LesionAnalysis::className($analysis->predicted_class) }}</h4>
                <div class="small text-muted">Model {{ $analysis->model_version }} · {{ $analysis->created_at->format('d M Y H:i') }}</div></div>
                <div class="text-end"><div class="small text-muted">Risk level</div>
                    <div class="fs-4 fw-bold text-uppercase risk-{{ $analysis->risk_level }}">{{ $analysis->risk_level }}</div></div></div>
            <hr>
            @foreach($analysis->sortedProbabilities() as $code => $p)
                <div class="d-flex justify-content-between small"><span>{{ \App\Models\LesionAnalysis::className($code) }}</span><span>{{ number_format($p * 100, 1) }}%</span></div>
                <div class="prob-bar mb-2 {{ $loop->first ? 'top' : '' }}"><div style="width: {{ $p * 100 }}%"></div></div>
            @endforeach
        </div></div>

        <div class="card"><div class="card-header">Dermatologist review</div><div class="card-body">
            @if($analysis->isVerified())
                <p class="mb-1"><strong>Final diagnosis:</strong> {{ $analysis->final_diagnosis }}</p>
                <p class="mb-1"><strong>Decision:</strong> {{ $analysis->status === 'confirmed' ? 'AI result confirmed' : 'AI result overridden' }}</p>
                @if($analysis->doctor_note)<p class="mb-1"><strong>Note:</strong> {{ $analysis->doctor_note }}</p>@endif
                <p class="small text-muted mb-2">Reviewed by {{ $analysis->reviewer?->name }} on {{ $analysis->reviewed_at?->format('d M Y H:i') }}</p>
                <a href="{{ route('print.report', $analysis) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Report</a>
            @else
                <form method="POST" action="{{ route('doctor.analyses.review', $analysis) }}">@csrf
                    <div class="form-check"><input class="form-check-input" type="radio" name="decision" value="confirm" id="d1" checked>
                        <label class="form-check-label" for="d1">Confirm: I agree with <strong>{{ \App\Models\LesionAnalysis::className($analysis->predicted_class) }}</strong></label></div>
                    <div class="form-check mb-2"><input class="form-check-input" type="radio" name="decision" value="override" id="d2">
                        <label class="form-check-label" for="d2">Override: I disagree with the AI</label></div>
                    <div id="override-fields" class="d-none border rounded p-2 mb-2">
                        <label class="form-label small">Correct diagnosis</label>
                        <select name="final_class" id="final_class" class="form-select form-select-sm mb-2">
                            @foreach(\App\Models\LesionAnalysis::CLASSES as $k => $v)@if($k !== $analysis->predicted_class)<option value="{{ $k }}">{{ $v }}</option>@endif @endforeach
                            <option value="other">Other (type below)</option></select>
                        <input name="final_other" id="final-other" class="form-control form-control-sm d-none" placeholder="Diagnosis">
                        <div class="form-text">Your correction is logged as feedback for evaluating and retraining the model.</div>
                    </div>
                    <textarea name="doctor_note" rows="2" class="form-control mb-2" placeholder="Note for the patient record (optional)"></textarea>
                    <button class="btn btn-primary">Save to patient record</button>
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection
