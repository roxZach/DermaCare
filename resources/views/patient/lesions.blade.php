@extends('layouts.app')
@section('title', 'My lesions')
@section('content')
<h3 class="page-title">My lesions</h3>
<p class="text-muted">Skin lesions that you or your dermatologist have marked, with their photo history.</p>
<div class="row g-4">
    <div class="col-lg-4"><div class="card"><div class="card-body">@include('partials.bodymap', ['lesions' => $lesions])</div></div></div>
    <div class="col-lg-8">
        @forelse($lesions as $l)
            <div class="card mb-3"><div class="card-header d-flex justify-content-between">
                <span><span class="badge rounded-pill" style="background:var(--dc-coral)">{{ $loop->iteration }}</span> {{ $l->label }}</span>
                <span class="small text-muted">{{ $l->body_region }} ({{ $l->view }})</span></div>
                <div class="card-body"><div class="row g-2">
                    @forelse($l->images as $img)
                        <div class="col-4 col-md-3">
                            <img src="{{ route('files.lesion', $img) }}" class="lesion-thumb" alt="Lesion photo">
                            <div class="small text-muted">{{ $img->created_at->format('d M Y') }}</div>
                            @foreach($img->analyses as $a)
                                <a class="small" href="{{ route('print.report', $a) }}" target="_blank">{{ $a->final_diagnosis }}</a>
                            @endforeach
                        </div>
                    @empty <span class="text-muted small">No photos yet.</span> @endforelse
                </div></div></div>
        @empty
            <div class="card"><div class="card-body text-muted">No lesions recorded yet.
                <a href="{{ route('patient.tele.create') }}">Send a photo to a dermatologist</a>.</div></div>
        @endforelse
    </div>
</div>
@endsection
