@extends('layouts.app')
@section('title', 'New invoice')
@section('content')
<h3 class="page-title">New invoice</h3>
<form method="POST" action="{{ route('reception.invoices.store') }}" class="card"><div class="card-body">@csrf
    <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Patient</label>
            @if($patient)<input type="hidden" name="patient_id" value="{{ $patient->id }}"><div class="form-control bg-light">{{ $patient->name }} ({{ $patient->code }})</div>
            @else<input name="patient_id" class="form-control" placeholder="Patient number, e.g. 12" required>@endif</div>
        <input type="hidden" name="appointment_id" value="{{ $appointment?->id }}">
        @if($appointment)<div class="col-md-6"><label class="form-label">Appointment</label><div class="form-control bg-light">{{ $appointment->date->format('d M Y') }} with {{ $appointment->doctor->name }}</div></div>@endif
    </div>
    <template id="inv-tpl"><div class="row g-2 mb-2 form-row-item">
        <div class="col-md-3"><select class="form-select svc-pick" name="items[__I__][service_id]"><option value="">Custom item</option>
            @foreach($services as $s)<option value="{{ $s->id }}" data-name="{{ $s->name }}" data-price="{{ $s->price }}">{{ $s->name }}</option>@endforeach</select></div>
        <div class="col-md-4"><input name="items[__I__][description]" class="form-control inv-desc" placeholder="Description" required></div>
        <div class="col-md-1"><input name="items[__I__][quantity]" type="number" min="1" value="1" class="form-control inv-qty" required></div>
        <div class="col-md-2"><input name="items[__I__][unit_price]" type="number" step="0.01" min="0" class="form-control inv-price" placeholder="Price" required></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row w-100"><i class="bi bi-x"></i></button></div>
    </div></template>
    <div id="inv-rows">
        <div class="row g-2 mb-2 form-row-item">
            <div class="col-md-3"><select class="form-select svc-pick" name="items[0][service_id]"><option value="">Custom item</option>
                @foreach($services as $s)<option value="{{ $s->id }}" data-name="{{ $s->name }}" data-price="{{ $s->price }}" @selected($appointment && $s->name === 'Dermatology consultation')>{{ $s->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><input name="items[0][description]" class="form-control inv-desc" placeholder="Description" required value="{{ $appointment ? 'Consultation – ' . $appointment->doctor->name : '' }}"></div>
            <div class="col-md-1"><input name="items[0][quantity]" type="number" min="1" value="1" class="form-control inv-qty" required></div>
            <div class="col-md-2"><input name="items[0][unit_price]" type="number" step="0.01" min="0" class="form-control inv-price" placeholder="Price" required value="{{ $appointment?->doctor->consultation_fee }}"></div>
        </div>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" data-add-row="#inv-tpl" data-target="#inv-rows"><i class="bi bi-plus"></i> Add item</button>
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">Discount / package (optional)</label><input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control" value="0"></div>
        <div class="col-md-4"><label class="form-label">Reason</label><input name="discount_reason" class="form-control" placeholder="e.g. Acne package, senior citizen"></div>
        <div class="col-md-5 text-end"><div>Subtotal: <strong id="inv-subtotal">0.00</strong></div><div class="fs-5">Total: <strong id="inv-total">0.00</strong></div></div>
    </div>
    <button class="btn btn-primary mt-3">Create invoice</button>
</div></form>
@endsection
