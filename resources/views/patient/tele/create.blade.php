@extends('layouts.app')
@section('title', 'New teledermatology request')
@section('content')
<h3 class="page-title">Ask a dermatologist online</h3>
<form method="POST" action="{{ route('patient.tele.store') }}" enctype="multipart/form-data">@csrf
<div class="row g-4">
    <div class="col-lg-7"><div class="card"><div class="card-body row g-3">
        <div class="col-12"><label class="form-label">Subject</label><input name="subject" value="{{ old('subject') }}" class="form-control" required maxlength="150" placeholder="e.g. Mole on my back has changed colour"></div>
        <div class="col-12"><label class="form-label">Describe the problem</label>
            <textarea name="description" rows="4" class="form-control" required>{{ old('description') }}</textarea></div>
        <div class="col-md-6"><label class="form-label">How long have you had it?</label><input name="duration" value="{{ old('duration') }}" class="form-control" placeholder="e.g. 3 months"></div>
        <div class="col-md-6"><label class="form-label">Preferred dermatologist</label><select name="doctor_id" class="form-select">
            <option value="">Any available</option>@foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">Photos (1 to 3)</label>
            <input type="file" name="images[]" class="form-control" accept="image/jpeg,image/png" multiple required>
            <div class="form-text">Tips: use daylight, hold the camera 10–15 cm away, keep the lesion in the centre and in focus.
                Each photo is checked automatically by our AI for blur and lighting.</div></div>
    </div></div></div>
    <div class="col-lg-5"><div class="card"><div class="card-header">Where is it?</div><div class="card-body">
        @include('partials.bodymap', ['lesions' => collect(), 'clickable' => true])
        <input type="hidden" name="x" id="bm-x" value="{{ old('x') }}"><input type="hidden" name="y" id="bm-y" value="{{ old('y') }}">
        <input type="hidden" name="view" id="bm-view" value="{{ old('view', 'front') }}">
        <label class="form-label mt-2">Body area</label><input name="body_region" id="bm-region" value="{{ old('body_region') }}" class="form-control" required>
    </div></div></div>
    <div class="col-12"><button class="btn btn-primary btn-lg"><i class="bi bi-send"></i> Send to dermatologist</button></div>
</div>
</form>
@endsection
