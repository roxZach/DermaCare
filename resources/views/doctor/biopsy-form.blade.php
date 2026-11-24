@extends('layouts.app')
@section('title', 'Order biopsy')
@section('content')
<a href="{{ route('doctor.patients.show', $patient) }}" class="small">&larr; {{ $patient->name }}'s record</a>
<h3 class="page-title mt-2">Order biopsy — {{ $patient->name }}</h3>
<form method="POST" action="{{ route('doctor.biopsy.store', $patient) }}" class="card"><div class="card-body row g-3">@csrf
    <div class="col-md-6"><label class="form-label">Lesion</label><select name="lesion_id" class="form-select"><option value="">Not linked to a marked lesion</option>
        @foreach($patient->lesions as $l)<option value="{{ $l->id }}" @selected($lesionId == $l->id)>{{ $l->label }} ({{ $l->body_region }})</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Test</label><select name="test_type" class="form-select">
        @foreach(['Skin punch biopsy', 'Shave biopsy', 'Excisional biopsy', 'Incisional biopsy', 'Skin scraping (KOH)', 'Fungal culture'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-label">Site</label><input name="site" class="form-control" required placeholder="e.g. Left upper back, 4 cm from spine"></div>
    <div class="col-12"><label class="form-label">Clinical note for the lab</label><textarea name="clinical_note" rows="3" class="form-control" placeholder="Clinical suspicion, relevant history"></textarea></div>
    <div class="col-12"><button class="btn btn-primary">Send order to lab</button></div>
</div></form>
@endsection
