@extends('layouts.app')
@section('title', 'Order #' . $order->id)
@section('content')
<a href="{{ route('lab.dashboard') }}" class="small">&larr; Pending orders</a>
<h3 class="page-title mt-2">Order #{{ $order->id }} — {{ $order->test_type }} @include('partials.status', ['s' => $order->status])</h3>
<div class="row g-4">
    <div class="col-lg-5"><div class="card"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-5">Patient</dt><dd class="col-7">{{ $order->patient->name }} ({{ $order->patient->code }})</dd>
            <dt class="col-5">Age / gender</dt><dd class="col-7">{{ $order->patient->age ?? '-' }} / {{ ucfirst($order->patient->gender ?? '-') }}</dd>
            <dt class="col-5">Ordered by</dt><dd class="col-7">{{ $order->doctor->name }}</dd>
            <dt class="col-5">Site</dt><dd class="col-7">{{ $order->site }}</dd>
            <dt class="col-5">Clinical note</dt><dd class="col-7">{{ $order->clinical_note ?? '-' }}</dd>
        </dl>
        @if(in_array($order->status, \App\Models\BiopsyOrder::LAB_STATUSES))
        <hr><form method="POST" action="{{ route('lab.orders.status', $order) }}" class="d-flex gap-2">@csrf
            <select name="status" class="form-select">@foreach(\App\Models\BiopsyOrder::LAB_STATUSES as $s)<option value="{{ $s }}" @selected($order->status === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>@endforeach</select>
            <button class="btn btn-outline-primary text-nowrap">Update status</button></form>
        @endif
    </div></div></div>
    <div class="col-lg-7"><div class="card"><div class="card-header">Upload pathology report</div><div class="card-body">
        @if(in_array($order->status, \App\Models\BiopsyOrder::LAB_STATUSES))
        <form method="POST" action="{{ route('lab.orders.report', $order) }}" enctype="multipart/form-data">@csrf
            <label class="form-label">Key findings</label><textarea name="findings" rows="5" class="form-control mb-2" required placeholder="Microscopic description and diagnosis"></textarea>
            <label class="form-label">Report (PDF)</label><input type="file" name="report" accept="application/pdf" class="form-control mb-2" required>
            <label class="form-label">Histopathology image (optional)</label><input type="file" name="histo_image" accept="image/*" class="form-control mb-3">
            <button class="btn btn-primary">Upload and notify dermatologist</button></form>
        @else
            <p style="white-space:pre-line">{{ $order->findings }}</p><a href="{{ route('files.biopsy', [$order, 'report']) }}" target="_blank">Report PDF</a>
        @endif
    </div></div></div>
</div>
@endsection
