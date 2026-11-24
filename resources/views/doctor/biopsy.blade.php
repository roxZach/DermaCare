@extends('layouts.app')
@section('title', 'Biopsy order')
@section('content')
<a href="{{ route('doctor.biopsy.index') }}" class="small">&larr; Biopsy orders</a>
<h3 class="page-title mt-2">{{ $order->test_type }} — {{ $order->patient->name }} @include('partials.status', ['s' => $order->status])</h3>
<div class="row g-4">
    <div class="col-lg-6"><div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-4">Ordered</dt><dd class="col-8">{{ $order->created_at->format('d M Y H:i') }}</dd>
            <dt class="col-4">Site</dt><dd class="col-8">{{ $order->site }}</dd>
            <dt class="col-4">Lesion</dt><dd class="col-8">@if($order->lesion)<a href="{{ route('doctor.lesions.show', $order->lesion) }}">{{ $order->lesion->label }}</a>@else - @endif</dd>
            <dt class="col-4">Clinical note</dt><dd class="col-8">{{ $order->clinical_note ?? '-' }}</dd>
            <dt class="col-4">Lab</dt><dd class="col-8">{{ $order->labUser?->name ?? '-' }}</dd>
        </dl>
    </div></div></div>
    <div class="col-lg-6"><div class="card"><div class="card-header">Pathology report</div><div class="card-body">
        @if(in_array($order->status, ['completed', 'reviewed']))
            <p style="white-space:pre-line">{{ $order->findings }}</p>
            <a href="{{ route('files.biopsy', [$order, 'report']) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> Report PDF</a>
            @if($order->histo_image_path)<a href="{{ route('files.biopsy', [$order, 'histo']) }}" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-image"></i> Histopathology image</a>@endif
            <hr>
            @if($order->status === 'completed')
                <form method="POST" action="{{ route('doctor.biopsy.review', $order) }}">@csrf
                    <label class="form-label">Your interpretation for the patient</label>
                    <textarea name="doctor_comment" rows="3" class="form-control mb-2" required></textarea>
                    <button class="btn btn-primary">Mark as reviewed &amp; share with patient</button></form>
            @else
                <p class="mb-0"><strong>Your comment:</strong> {{ $order->doctor_comment }}<br><span class="small text-muted">Reviewed {{ $order->reviewed_at?->format('d M Y') }}</span></p>
            @endif
        @else
            <span class="text-muted">The lab has not uploaded a report yet (status: {{ str_replace('_', ' ', $order->status) }}).</span>
        @endif
    </div></div></div>
</div>
@endsection
