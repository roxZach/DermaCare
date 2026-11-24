@extends('layouts.app')
@section('title', 'Prescription')
@section('content')
<a href="{{ route('doctor.patients.show', $patient) }}" class="small">&larr; {{ $patient->name }}'s record</a>
<h3 class="page-title mt-2">New prescription — {{ $patient->name }}</h3>
@if($patient->allergies)<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Allergies: {{ $patient->allergies }}</div>@endif
<form method="POST" action="{{ route('doctor.rx.store', $patient) }}" class="card"><div class="card-body">@csrf
    <input type="hidden" name="consultation_id" value="{{ $consultationId }}">
    <div id="rx-rows">
        <div class="row g-2 mb-2 form-row-item">
            <div class="col-md-3"><input name="items[0][medicine]" class="form-control" placeholder="Medicine" required list="meds"></div>
            <div class="col-md-2"><input name="items[0][dosage]" class="form-control" placeholder="Dosage / strength"></div>
            <div class="col-md-2"><input name="items[0][frequency]" class="form-control" placeholder="Frequency"></div>
            <div class="col-md-2"><input name="items[0][duration]" class="form-control" placeholder="Duration"></div>
            <div class="col-md-3"><input name="items[0][instructions]" class="form-control" placeholder="Instructions"></div>
        </div>
    </div>
    <datalist id="meds">@foreach(['Hydrocortisone 1% cream', 'Mometasone 0.1% cream', 'Clobetasol 0.05% ointment', 'Tretinoin 0.025% cream', 'Adapalene 0.1% gel', 'Benzoyl peroxide 5% gel', 'Clindamycin 1% gel', 'Doxycycline 100 mg', 'Isotretinoin 20 mg', 'Ketoconazole 2% cream', 'Terbinafine 250 mg', 'Cetirizine 10 mg', 'Fexofenadine 180 mg', 'Permethrin 5% cream', 'Tacrolimus 0.1% ointment', 'Sunscreen SPF 50', 'Emollient (moisturiser)'] as $m)<option value="{{ $m }}">@endforeach</datalist>
    <template id="rx-tpl"><div class="row g-2 mb-2 form-row-item">
        <div class="col-md-3"><input name="items[__I__][medicine]" class="form-control" placeholder="Medicine" required list="meds"></div>
        <div class="col-md-2"><input name="items[__I__][dosage]" class="form-control" placeholder="Dosage / strength"></div>
        <div class="col-md-2"><input name="items[__I__][frequency]" class="form-control" placeholder="Frequency"></div>
        <div class="col-md-2"><input name="items[__I__][duration]" class="form-control" placeholder="Duration"></div>
        <div class="col-md-2"><input name="items[__I__][instructions]" class="form-control" placeholder="Instructions"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-row w-100"><i class="bi bi-x"></i></button></div>
    </div></template>
    <button type="button" class="btn btn-sm btn-outline-secondary mb-3" data-add-row="#rx-tpl" data-target="#rx-rows"><i class="bi bi-plus"></i> Add medicine</button>
    <div class="row g-2">
        <div class="col-md-8"><label class="form-label">Advice</label><textarea name="advice" rows="2" class="form-control" placeholder="e.g. Avoid sun exposure, use sunscreen daily"></textarea></div>
        <div class="col-md-4"><label class="form-label">Follow-up date</label><input type="date" name="follow_up_date" class="form-control" min="{{ today()->addDay()->toDateString() }}"></div>
    </div>
    <button class="btn btn-primary mt-3">Save prescription</button>
</div></form>
@endsection
