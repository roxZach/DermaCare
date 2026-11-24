@extends('layouts.app')
@section('title', 'Compare lesion images')
@section('content')
<a href="{{ route('doctor.lesions.show', $lesion) }}" class="small">&larr; {{ $lesion->label }}</a>
<h3 class="page-title mt-2">Compare images over time</h3>
<form class="row g-2 mb-3">
    @foreach(['a' => 'Earlier image', 'b' => 'Later image'] as $k => $label)
        <div class="col-md-5"><label class="form-label small">{{ $label }}</label><select name="{{ $k }}" class="form-select" onchange="this.form.submit()">
            @foreach($images as $img)<option value="{{ $img->id }}" @selected(${$k}?->id === $img->id)>{{ $img->created_at->format('d M Y H:i') }} ({{ $img->image_type }})</option>@endforeach
        </select></div>
    @endforeach
</form>
<div class="row g-4">
    @foreach([$a, $b] as $img)
        @if($img)
        <div class="col-md-6"><div class="card"><div class="card-body">
            <img src="{{ route('files.lesion', $img) }}" class="w-100 rounded" alt="Lesion">
            <div class="mt-2"><strong>{{ $img->created_at->format('d M Y') }}</strong> · {{ $img->image_type }}</div>
            @php($an = $img->analyses->first())
            @if($an)<div class="small">AI: {{ \App\Models\LesionAnalysis::className($an->predicted_class) }} ({{ round($an->confidence * 100) }}%), risk {{ $an->risk_level }}
                @if($an->final_diagnosis)<br>Final: {{ $an->final_diagnosis }}@endif</div>@endif
        </div></div></div>
        @endif
    @endforeach
</div>
@if($a && $b && $a->id !== $b->id)
    <p class="small text-muted mt-3">Time between images: {{ $a->created_at->diffForHumans($b->created_at, true) }}.
        Look for changes in size, shape, colour and border (ABCDE criteria). Consider a biopsy if the lesion is changing.</p>
@endif
@endsection
