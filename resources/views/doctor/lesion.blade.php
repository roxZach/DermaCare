@extends('layouts.app')
@section('title', $lesion->label)
@section('content')
<a href="{{ route('doctor.patients.show', $lesion->patient) }}" class="small">&larr; {{ $lesion->patient->name }}'s record</a>
<div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-3 gap-2">
    <h3 class="page-title mb-0">{{ $lesion->label }} <span class="fs-6 text-muted">{{ $lesion->body_region }} ({{ $lesion->view }})</span></h3>
    <div class="d-flex gap-2">
        @if($lesion->images->count() > 1)<a href="{{ route('doctor.lesions.compare', $lesion) }}" class="btn btn-outline-primary"><i class="bi bi-layout-split"></i> Compare over time</a>@endif
        <a href="{{ route('doctor.biopsy.create', [$lesion->patient, 'lesion' => $lesion->id]) }}" class="btn btn-outline-primary"><i class="bi bi-eyedropper"></i> Order biopsy</a>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card"><div class="card-header">Capture / upload image</div><div class="card-body">
            <form method="POST" action="{{ route('doctor.lesions.upload', $lesion) }}" enctype="multipart/form-data" class="row g-2">@csrf
                <div class="col-12"><input type="file" name="image" accept="image/jpeg,image/png" class="form-control" required capture="environment"></div>
                <div class="col-12"><select name="image_type" class="form-select"><option value="dermoscopic">Dermoscopic image</option><option value="clinical">Clinical photo</option></select></div>
                <div class="col-12 form-check ms-1"><input type="checkbox" class="form-check-input" name="analyze_now" value="1" id="an" checked>
                    <label for="an" class="form-check-label">Run AI analysis right away</label></div>
                <div class="col-12"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload</button></div>
            </form>
            <div class="small text-muted mt-2">The AI model was trained on dermoscopic images (HAM10000). Results on ordinary photos are less reliable.</div>
        </div></div>
        @if($lesion->notes)<div class="card mt-3"><div class="card-body small">{{ $lesion->notes }}</div></div>@endif
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Image timeline</div><div class="card-body">
            @forelse($lesion->images as $img)
                <div class="row g-3 border-bottom pb-3 mb-3">
                    <div class="col-sm-4"><img src="{{ route('files.lesion', $img) }}" class="lesion-thumb" alt="Lesion image"></div>
                    <div class="col-sm-8">
                        <div class="small text-muted">{{ $img->created_at->format('d M Y H:i') }} · {{ ucfirst($img->image_type) }} · {{ ucfirst($img->source) }} · by {{ $img->uploader?->name ?? '-' }}</div>
                        <div class="small mb-2"><i class="bi bi-check2-circle"></i> {{ $img->quality_message }}</div>
                        @forelse($img->analyses as $a)
                            <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">
                                <span><strong>{{ \App\Models\LesionAnalysis::className($a->predicted_class) }}</strong>
                                    ({{ round($a->confidence * 100) }}%) · risk <span class="risk-{{ $a->risk_level }}">{{ $a->risk_level }}</span>
                                    @if($a->final_diagnosis)<br><span class="small">Final: {{ $a->final_diagnosis }}</span>@endif</span>
                                <span>@include('partials.status', ['s' => $a->status]) <a href="{{ route('doctor.analyses.show', $a) }}" class="btn btn-sm btn-outline-primary ms-1">Open</a></span>
                            </div>
                        @empty
                            <form method="POST" action="{{ route('doctor.images.analyze', $img) }}">@csrf
                                <button class="btn btn-sm btn-teal"><i class="bi bi-cpu"></i> Request AI analysis</button></form>
                        @endforelse
                    </div>
                </div>
            @empty <span class="text-muted">No images yet. Upload the first one.</span> @endforelse
        </div></div>
    </div>
</div>
@endsection
