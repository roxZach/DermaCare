@extends('layouts.app')
@section('title', 'Consultation')
@section('content')
<a href="{{ route('doctor.patients.show', $patient) }}" class="small">&larr; {{ $patient->name }}'s record</a>
<h3 class="page-title mt-2">Consultation — {{ $patient->name }}</h3>
<form method="POST" action="{{ route('doctor.consult.store', $patient) }}" class="card"><div class="card-body row g-3">@csrf
    <input type="hidden" name="appointment_id" value="{{ $appointment?->id }}">
    @if($appointment)<div class="col-12"><div class="alert alert-light border mb-0 small">Appointment {{ $appointment->date->format('d M Y') }} {{ substr($appointment->time, 0, 5) }} · Reason: {{ $appointment->reason ?? '-' }}</div></div>@endif
    <div class="col-12"><label class="form-label">Chief complaint</label><textarea name="complaint" rows="2" class="form-control" required>{{ old('complaint', $appointment?->reason) }}</textarea></div>
    <div class="col-12"><label class="form-label">Examination findings</label><textarea name="findings" rows="3" class="form-control" placeholder="Morphology, distribution, dermoscopy findings...">{{ old('findings') }}</textarea></div>
    <div class="col-md-6"><label class="form-label">Diagnosis</label><input name="diagnosis" value="{{ old('diagnosis') }}" class="form-control" required list="dx">
        <datalist id="dx">@foreach(['Acne vulgaris', 'Atopic dermatitis (eczema)', 'Psoriasis', 'Seborrheic dermatitis', 'Tinea (fungal infection)', 'Urticaria', 'Melasma', 'Vitiligo', 'Melanocytic nevus', 'Seborrheic keratosis', 'Actinic keratosis', 'Basal cell carcinoma', 'Suspected melanoma', 'Contact dermatitis', 'Rosacea', 'Scabies', 'Warts'] as $d)<option value="{{ $d }}">@endforeach</datalist></div>
    <div class="col-12"><hr class="my-1"><h6 class="mb-0">Skin type &amp; history <span class="badge text-bg-light border">required</span></h6></div>
    <div class="col-md-4"><label class="form-label">Fitzpatrick skin type</label><select name="fitzpatrick_type" class="form-select" required>
        <option value="">Choose</option>@for($i = 1; $i <= 6; $i++)<option value="{{ $i }}" @selected(old('fitzpatrick_type', $patient->fitzpatrick_type) == $i)>Type {{ $i }}</option>@endfor</select></div>
    <div class="col-md-4"><label class="form-label">Sun exposure</label><select name="sun_exposure" class="form-select">
        @foreach(['' => '-', 'low' => 'Low', 'moderate' => 'Moderate', 'high' => 'High'] as $k => $v)<option value="{{ $k }}" @selected(old('sun_exposure', $patient->sun_exposure) === $k)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-4 pt-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="family_history_skin_cancer" value="1" id="fh" @checked($patient->family_history_skin_cancer)><label class="form-check-label" for="fh">Family history of skin cancer</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="personal_history_skin_cancer" value="1" id="ph" @checked($patient->personal_history_skin_cancer)><label class="form-check-label" for="ph">Personal history of skin cancer</label></div></div>
    <div class="col-12"><label class="form-label">Notes / plan</label><textarea name="notes" rows="2" class="form-control">{{ old('notes') }}</textarea></div>
    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" name="next" value="emr">Save consultation</button>
        <button class="btn btn-teal" name="next" value="prescribe">Save and write prescription</button></div>
</div></form>
@endsection
