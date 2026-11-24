@extends('layouts.app')
@section('title', 'Analysis reports')
@section('content')
<h3 class="page-title">Lesion analysis reports</h3>
<p class="text-muted">Only results that a dermatologist has reviewed are shown here.</p>
<div class="row g-3">
    @forelse($analyses as $a)
        <div class="col-md-6 col-lg-4"><div class="card h-100">
            <img src="{{ route('files.lesion', $a->image) }}" class="card-img-top" style="height:200px;object-fit:cover" alt="Lesion">
            <div class="card-body">
                <h6>{{ $a->image->lesion->label }} <span class="small text-muted">· {{ $a->image->lesion->body_region }}</span></h6>
                <div><strong>Diagnosis:</strong> {{ $a->final_diagnosis }}</div>
                <div class="small text-muted">Reviewed by {{ $a->reviewer?->name }} on {{ $a->reviewed_at?->format('d M Y') }}</div>
                @if($a->doctor_note)<div class="small mt-1">{{ $a->doctor_note }}</div>@endif
            </div>
            <div class="card-footer bg-white"><a href="{{ route('print.report', $a) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-file-earmark-pdf"></i> Download report (PDF)</a></div>
        </div></div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-muted">No verified reports yet.</div></div></div>
    @endforelse
</div>
@endsection
